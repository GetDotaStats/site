#!/usr/bin/php -q
<?php
require_once('../../../global_functions.php');
require_once('../../../connections/parameters.php');

try {
    $db = new dbWrapper_v3($hostname_gds_cron, $username_gds_cron, $password_gds_cron, $database_gds_cron, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    set_time_limit(0);

    $daysToGather = 7;

    $activeMods = cached_query(
        's2_cron_active_mods',
        'SELECT
              ml.`mod_id`,
              ml.`mod_identifier`,
              ml.`mod_name`,
              ml.`mod_steam_group`,
              ml.`mod_workshop_link`,
              ml.`mod_size`,
              ml.`workshop_updated`,
              ml.`date_recorded`
            FROM `mod_list` ml
            WHERE ml.`mod_active` = 1;',
        NULL,
        NULL,
        5
    );

    if (empty($activeMods)) throw new Exception('No active mods!');

    $time_start2 = time();
    echo '<h2>Gather Matches</h2>';

    $db->q('DROP TABLE IF EXISTS `cache_cmg`;');

    $db->q("CREATE TABLE IF NOT EXISTS `cache_cmg` (
            `modID` bigint(255) NOT NULL,
            `matchID` bigint(255) NOT NULL,
            PRIMARY KEY (`modID`, `matchID`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1;");


    $totalRecentMatchesConsidered = $totalMatchesUsed = 0;
    foreach ($activeMods as $key => $value) {
        try {
            $matchesConsidered = $matchesUsed = 0;
            $modID = $value['mod_id'];
            $modName = $value['mod_name'];

            echo "<h4>{$modName}</h4>";

            $time_start1 = time();

            $db->q('DROP TABLE IF EXISTS `cache_cmg2`;');
            $db->q("CREATE TABLE IF NOT EXISTS `cache_cmg2` (
                `modID` bigint(255) NOT NULL,
                `matchID` bigint(255) NOT NULL,
                PRIMARY KEY (`modID`, `matchID`)
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

            $matchesConsidered = $db->q(
                'INSERT INTO `cache_cmg2`
                    SELECT
                      `modID`,
                      `matchID`
                    FROM `s2_match`
                    WHERE
                      `modID` = ? AND
                      `dateRecorded` >= NOW() - INTERVAL ' . $daysToGather . ' DAY;',
                's',
                array($modID)
            );

            echo "Matches Considered: {$matchesConsidered}<br />";

            $schemaIDtoUse = $db->q(
                'SELECT
                      MAX(`schemaID`) AS schemaID
                    FROM `s2_mod_custom_schema`
                    WHERE
                        `modID` = ? AND
                        `schemaApproved` = 1;',
                'i',
                array($modID)
            );

            if (!empty($schemaIDtoUse) && !empty($schemaIDtoUse[0]['schemaID'])) {
                $schemaIDtoUse = $schemaIDtoUse[0]['schemaID'];

                echo "Schema: v{$schemaIDtoUse}<br />";

                $matchesUsed = $db->q(
                    'INSERT IGNORE INTO `cache_cmg`
                        SELECT
                          `modID`,
                          `matchID`
                        FROM `s2_match_players_custom`
                        WHERE
                          `matchID` IN (
                            SELECT
                                `matchID`
                              FROM `cache_cmg2`
                              WHERE `modID` = ?
                          ) AND
                          `schemaID` = ?;',
                    'ii',
                    array($modID, $schemaIDtoUse)
                );
                echo "Matches Used: {$matchesUsed}<br />";
            } else {
                $matchesUsed = $db->q(
                    'INSERT INTO `cache_cmg`
                        SELECT
                          `modID`,
                          `matchID`
                        FROM `cache_cmg2`
                        WHERE
                          `modID` = ?;',
                    'i',
                    array($modID)
                );
                echo "Matches Used: {$matchesUsed}<br />";
            }

            $db->q('DROP TABLE IF EXISTS `cache_cmg2`;');

            $totalRecentMatchesConsidered += $matchesConsidered;
            $totalMatchesUsed += $matchesUsed;

            $time_end1 = time();
            $runTime = $time_end1 - $time_start1;
            echo '<strong>Run Time:</strong> ' . $runTime . " seconds<br />";

            try {
                $serviceName = 's2_cron_cmg_' . $modID;

                $oldServiceReport = cached_query(
                    $serviceName . '_old_service_report',
                    'SELECT
                            `instance_id`,
                            `service_name`,
                            `execution_time`,
                            `performance_index1`,
                            `performance_index2`,
                            `performance_index3`,
                            `date_recorded`
                        FROM `cron_services`
                        WHERE `service_name` = ?
                        ORDER BY `date_recorded` DESC
                        LIMIT 0,1;',
                    's',
                    array($serviceName),
                    1
                );

                service_report($serviceName, $runTime, $matchesConsidered, $matchesUsed, NULL, TRUE);

                if (!empty($oldServiceReport)) {
                    $oldServiceReport = $oldServiceReport[0];

                    //Check if first time it's had data
                    if (
                        (empty($oldServiceReport['performance_index1']) && !empty($matchesConsidered)) ||
                        (empty($oldServiceReport['performance_index2']) && !empty($matchesUsed))
                    ) {
                        throw new Exception("Mod `{$modName}` has games to use since the last report!");
                    }

                    //Check if it had data, but now does not
                    if (
                        (!empty($oldServiceReport['performance_index1']) && empty($matchesConsidered)) ||
                        (!empty($oldServiceReport['performance_index2']) && empty($matchesUsed))
                    ) {
                        throw new Exception("Mod `{$modName}` had games in the last report, but now does not!");
                    }

                    //Check if the run-time increased majorly
                    if ($runTime > 5 && ($runTime > ($oldServiceReport['execution_time'] * 5))) {
                        throw new Exception("Major increase (>400%) in execution time for `{$modName}`! {$oldServiceReport['execution_time']}secs to {$runTime}secs");
                    }

                    //Check if the performance_index1 increased majorly
                    if ($matchesConsidered > ($oldServiceReport['performance_index1'] * 2)) {
                        throw new Exception("Major increase (>100%) in performance index #1 for `{$modName}`! {$oldServiceReport['performance_index1']} matches considered to {$matchesConsidered} matches considered");
                    }

                    //Check if the performance_index2 increased majorly
                    if ($matchesUsed > ($oldServiceReport['performance_index2'] * 2)) {
                        throw new Exception("Major increase (>100%) in performance index #2 for `{$modName}`! {$oldServiceReport['performance_index2']} matches used to {$matchesUsed} matches used");
                    }
                }

            } catch (Exception $e) {
                echo 'Caught Exception (MAIN) -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage() . '<br /><br />';

                //WEBHOOK
                {
                    $irc_message = new irc_message($webhook_gds_site_admin);

                    $message = array(
                        array(
                            $irc_message->colour_generator('red'),
                            '[CRON]',
                            $irc_message->colour_generator(NULL),
                        ),
                        array(
                            $irc_message->colour_generator('green'),
                            '[CMG]',
                            $irc_message->colour_generator(NULL),
                        ),
                        array(
                            $irc_message->colour_generator('bold'),
                            $irc_message->colour_generator('blue'),
                            'Warning:',
                            $irc_message->colour_generator(NULL),
                            $irc_message->colour_generator('bold'),
                        ),
                        array($e->getMessage() . ' ||'),
                        array('http://getdotastats.com/s2/routine/log_hourly.html?' . time())
                    );

                    $message = $irc_message->combine_message($message);
                    $irc_message->post_message($message, array('localDev' => $localDev));
                }
            }
        } catch (Exception $e) {
            echo 'Caught Exception (LOOP) -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage() . '<br /><br />';
        }
    }

    $time_end2 = time();
    $totalRunTime = $time_end2 - $time_start2;
    echo '<br />';
    echo '<strong>Total Run Time:</strong> ' . $totalRunTime . " seconds<br /><br />";

    echo '<hr />';

    try {
        $serviceName = 's2_cron_cmg';

        $oldServiceReport = cached_query(
            $serviceName . '_old_service_report',
            'SELECT
                    `instance_id`,
                    `service_name`,
                    `execution_time`,
                    `performance_index1`,
                    `performance_index2`,
                    `performance_index3`,
                    `date_recorded`
                FROM `cron_services`
                WHERE `service_name` = ?
                ORDER BY `date_recorded` DESC
                LIMIT 0,1;',
            's',
            array($serviceName),
            1
        );

        service_report($serviceName, $totalRunTime, $totalRecentMatchesConsidered, $totalMatchesUsed, NULL, FALSE);

        if (empty($oldServiceReport)) throw new Exception('No old service report data!');

        $oldServiceReport = $oldServiceReport[0];

        //Check if the run-time increased majorly
        if ($totalRunTime > 20 && ($totalRunTime > ($oldServiceReport['execution_time'] * 1.5))) {
            throw new Exception("Major increase (>50%) in execution time! {$oldServiceReport['execution_time']}secs to {$totalRunTime}secs");
        }

        //Check if the performance_index1 increased majorly
        if ($totalRecentMatchesConsidered > ($oldServiceReport['performance_index1'] * 1.2)) {
            throw new Exception("Major increase (>20%) in performance index #1! {$oldServiceReport['performance_index1']} matches to {$totalRecentMatchesConsidered} matches");
        }

        //Check if the performance_index2 increased majorly
        if ($totalMatchesUsed > ($oldServiceReport['performance_index2'] * 1.2)) {
            throw new Exception("Major increase (>20%) in performance index #2! {$oldServiceReport['performance_index2']} matches to {$totalMatchesUsed} matches");
        }

    } catch (Exception $e) {
        echo 'Caught Exception (MAIN) -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage() . '<br /><br />';
    }

} catch (Exception $e) {
    echo 'Caught Exception (MAIN) -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage() . '<br /><br />';
} finally {
    if (isset($memcache)) $memcache->close();
}