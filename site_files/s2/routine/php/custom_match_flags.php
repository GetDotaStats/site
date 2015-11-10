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

    $time_start1 = $time_start2 = time();
    echo '<h2>Mod Flags</h2>';

    $db->q('DROP TABLE IF EXISTS `cache_custom_flags_temp0`;');

    $db->q("CREATE TABLE IF NOT EXISTS `cache_custom_flags` (
                `modID` bigint(255) NOT NULL,
                `flagName` varchar(100) NOT NULL,
                `flagValue` varchar(100) NOT NULL,
                `numGames` bigint(255) NOT NULL,
                PRIMARY KEY (`modID`, `flagName`, `flagValue`)
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

    $totalFlagValues = $totalFlagValueCombos = 0;

    foreach ($activeMods as $key => $value) {
        try {
            $flagsUsed = $flagValueCombinations = 0;
            $modID = $value['mod_id'];
            $modName = $value['mod_name'];

            echo "<h4>{$modName}</h4>";

            $time_start1 = time();

            $db->q('DROP TABLE IF EXISTS `cache_custom_flags_temp1`;');

            $db->q("CREATE TABLE IF NOT EXISTS `cache_custom_flags_temp0` (
                `modID` bigint(255) NOT NULL,
                `flagName` varchar(100) NOT NULL,
                `flagValue` varchar(100) NOT NULL,
                `numGames` bigint(255) NOT NULL,
                PRIMARY KEY (`modID`, `flagName`, `flagValue`)
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

            $db->q("CREATE TABLE IF NOT EXISTS `cache_custom_flags_temp1` (
                `modID` int(255) NOT NULL,
                `flagName` varchar(100) NOT NULL,
                `flagValue` varchar(100) NOT NULL,
                KEY (`modID`, `flagName`, `flagValue`)
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

            $flagsUsed = $db->q(
                'INSERT INTO `cache_custom_flags_temp1`
                    SELECT
                            s2mf.`modID`,
                            s2mf.`flagName`,
                            s2mf.`flagValue`
                        FROM `s2_match_flags` s2mf
                        WHERE s2mf.`matchID` IN (
                          SELECT
                            `matchID`
                          FROM `cache_cmg`
                          WHERE `modID` = ?
                        );',
                's',
                $modID
            );

            echo "Flags: {$flagsUsed}<br />";

            $flagValueCombinations = $db->q(
                'INSERT INTO `cache_custom_flags_temp0`
                    SELECT
                            s2mf.`modID`,
                            s2mf.`flagName`,
                            s2mf.`flagValue`,
                            COUNT(*) AS numGames
                        FROM `cache_custom_flags_temp1` s2mf
                        GROUP BY s2mf.`modID`, s2mf.`flagName`, s2mf.`flagValue`;'
            );

            echo "Flag Combos: {$flagValueCombinations}<br />";

            $db->q('DROP TABLE IF EXISTS `cache_custom_flags_temp1`;');

            $totalFlagValues += $flagsUsed = is_numeric($flagsUsed)
                ? $flagsUsed
                : 0;

            $totalFlagValueCombos += $flagValueCombinations = is_numeric($flagValueCombinations)
                ? $flagValueCombinations
                : 0;

            $time_end1 = time();
            $runTime = $time_end1 - $time_start1;
            echo '<strong>Run Time:</strong> ' . $runTime . " seconds<br />";

            try {
                $serviceName = 's2_cron_cmf_' . $modID;

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

                service_report($serviceName, $runTime, $flagsUsed, $flagValueCombinations, NULL, TRUE);

                if (!empty($oldServiceReport)) {
                    $oldServiceReport = $oldServiceReport[0];

                    //Check if first time it's had data
                    if (
                        (empty($oldServiceReport['performance_index1']) && !empty($flagsUsed)) ||
                        (empty($oldServiceReport['performance_index2']) && !empty($flagValueCombinations))
                    ) {
                        throw new Exception("Mod `{$modName}` has flags to use since the last report!");
                    }

                    //Check if it had data, but now does not
                    if (
                        (!empty($oldServiceReport['performance_index1']) && empty($flagsUsed)) ||
                        (!empty($oldServiceReport['performance_index2']) && empty($flagValueCombinations))
                    ) {
                        throw new Exception("Mod `{$modName}` had flags in the last report, but now does not!");
                    }

                    //Check if the run-time increased majorly
                    if ($runTime > 5 && ($runTime > ($oldServiceReport['execution_time'] * 5))) {
                        throw new Exception("Major increase (>400%) in execution time for `{$modName}`! {$oldServiceReport['execution_time']}secs to {$runTime}secs");
                    }

                    //Check if the performance_index1 increased majorly
                    if ($flagsUsed > ($oldServiceReport['performance_index1'] * 1.5)) {
                        throw new Exception("Major increase (>50%) in performance index #1 for `{$modName}`! {$oldServiceReport['performance_index1']} flags used to {$flagsUsed} flags used");
                    }

                    //Check if the performance_index2 increased majorly
                    if ($flagValueCombinations > ($oldServiceReport['performance_index2'] * 1.5)) {
                        throw new Exception("Major increase (>50%) in performance index #2 for `{$modName}`! {$oldServiceReport['performance_index2']} flag combos to {$flagValueCombinations} flag combos");
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
                            '[CMF]',
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

    $time_start1 = time();
    echo "<h3>Final Insert</h3>";

    $db->q('RENAME TABLE `cache_custom_flags` TO `cache_custom_flags_old`, `cache_custom_flags_temp0` TO `cache_custom_flags`;');

    $db->q('DROP TABLE IF EXISTS `cache_custom_flags_old`;');
    $db->q('DROP TABLE IF EXISTS `cache_custom_flags_temp1`;');
    $db->q('DROP TABLE IF EXISTS `cache_custom_flags_temp0`;');

    $time_end1 = time();
    echo '<strong>Run Time:</strong> ' . ($time_end1 - $time_start1) . " seconds<br />";

    $time_end2 = time();
    $totalRunTime = $time_end2 - $time_start2;

    echo '<br />';
    echo '<strong>Total Run Time:</strong> ' . $totalRunTime . " seconds<br /><br />";

    echo '<hr />';


    try {
        $serviceName = 's2_cron_cmf';

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

        service_report($serviceName, $totalRunTime, $totalFlagValues, $totalFlagValueCombos, NULL, FALSE);

        if (empty($oldServiceReport)) throw new Exception('No old service report data!');

        $oldServiceReport = $oldServiceReport[0];

        //Check if the run-time increased majorly
        if ($totalRunTime > 20 && ($totalRunTime > ($oldServiceReport['execution_time'] * 2))) {
            throw new Exception("Major increase (>100%) in execution time! {$oldServiceReport['execution_time']}secs to {$totalRunTime}secs");
        }

        //Check if the performance_index1 increased majorly
        if ($totalFlagValues > ($oldServiceReport['performance_index1'] * 1.2)) {
            throw new Exception("Major increase (>20%) in performance index #1! {$oldServiceReport['performance_index1']} flags to {$totalFlagValues} flags");
        }

        //Check if the performance_index2 increased majorly
        if ($totalFlagValueCombos > ($oldServiceReport['performance_index2'] * 1.1)) {
            throw new Exception("Major increase (>10%) in performance index #2! {$oldServiceReport['performance_index2']} flag combos to {$totalFlagValueCombos} flag combos");
        }

    } catch (Exception $e) {
        echo 'Caught Exception (MAIN) -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage() . '<br /><br />';
    }
} catch (Exception $e) {
    echo 'Caught Exception (MAIN) -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage() . '<br /><br />';
} finally {
    if (isset($memcache)) $memcache->close();
}