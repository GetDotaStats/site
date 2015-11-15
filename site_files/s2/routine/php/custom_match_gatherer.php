#!/usr/bin/php -q
<?php
require_once('../../../global_functions.php');
require_once('../../../connections/parameters.php');

try {
    $db = new dbWrapper_v3($hostname_gds_cron, $username_gds_cron, $password_gds_cron, $database_gds_cron, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    $serviceReport = new serviceReporting($db);

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

    $totalRunTime = 0;
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
            $totalRunTime += $runTime;
            echo '<strong>Run Time:</strong> ' . $runTime . " seconds<br />";

            try {
                $serviceReport->logAndCompareOld(
                    's2_cron_cmg_' . $modID,
                    array(
                        'value' => $runTime,
                        'min' => 5,
                        'growth' => 4,
                    ),
                    array(
                        'value' => $matchesConsidered,
                        'min' => 10,
                        'growth' => 1,
                        'unit' => 'matches considered',
                    ),
                    array(
                        'value' => $matchesUsed,
                        'min' => 10,
                        'growth' => 1,
                        'unit' => 'matches used',
                    ),
                    NULL,
                    TRUE,
                    $modName
                );
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

    echo '<br />';
    echo '<strong>Total Run Time:</strong> ' . $totalRunTime . " seconds<br /><br />";

    echo '<hr />';

    try {
        $serviceReport->logAndCompareOld(
            's2_cron_cmg',
            array(
                'value' => $totalRunTime,
                'min' => 20,
                'growth' => 0.5,
            ),
            array(
                'value' => $totalRecentMatchesConsidered,
                'min' => 10,
                'growth' => 0.2,
                'unit' => 'matches',
            ),
            array(
                'value' => $totalMatchesUsed,
                'min' => 10,
                'growth' => 0.2,
                'unit' => 'matches',
            ),
            NULL,
            FALSE
        );
    } catch (Exception $e) {
        echo 'Caught Exception (MAIN) -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage() . '<br /><br />';
    }

} catch (Exception $e) {
    echo 'Caught Exception (MAIN) -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage() . '<br /><br />';
} finally {
    if (isset($memcache)) $memcache->close();
}