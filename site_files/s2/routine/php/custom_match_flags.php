#!/usr/bin/php -q
<?php
require_once('../../../global_functions.php');
require_once('../../../connections/parameters.php');

try {
    /////////////////////////////
    // Parameters
    /////////////////////////////

    $daysToGather = 7;

    $db = new dbWrapper_v3($hostname_gds_cron, $username_gds_cron, $password_gds_cron, $database_gds_cron, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcached = new Cache(NULL, NULL, $localDev);

    $serviceReport = new serviceReporting($db);

    set_time_limit(0);

    $time_start1 = time();

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

    echo '<h2>Mod Flags</h2>';

    $maxSQL = cached_query(
        's2_cron_cmf_max',
        'SELECT `matchID`, `dateRecorded` FROM `s2_match` WHERE `matchID` = (SELECT MAX(`matchID`) FROM `s2_match`) LIMIT 0,1;'
    );
    if (empty($maxSQL)) throw new Exception('No matches with flags!');

    $maxMatchID = $maxSQL[0]['matchID'];
    $maxMatchDate = $maxSQL[0]['dateRecorded'];
    echo "<strong>Max:</strong> {$maxMatchID} [{$maxMatchDate}]<br />";

    $minSQL = cached_query(
        's2_cron_cmf_min',
        "SELECT `matchID`, `dateRecorded` FROM `s2_match` WHERE `dateRecorded` >= (? - INTERVAL ? DAY) LIMIT 0,1;",
        'ss',
        array($maxMatchDate, $daysToGather),
        15
    );
    if (empty($minSQL)) throw new Exception('No matches with flags!');

    $minMatchID = $minSQL[0]['matchID'];
    $minMatchDate = $minSQL[0]['dateRecorded'];
    echo "<strong>Min:</strong> {$minMatchID} [{$minMatchDate}]<br />";

    $db->q('DROP TABLE IF EXISTS `cache_custom_flags_temp0_games`;');
    $db->q('DROP TABLE IF EXISTS `cache_custom_flags_temp1_sort`;');

    $db->q("CREATE TABLE IF NOT EXISTS `cache_custom_flags` (
                `modID` bigint(255) NOT NULL,
                `flagName` varchar(100) NOT NULL,
                `flagValue` varchar(100) NOT NULL,
                `numGames` bigint(255) NOT NULL,
                PRIMARY KEY (`modID`, `flagName`, `flagValue`)
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

    $db->q("CREATE TEMPORARY TABLE IF NOT EXISTS `cache_custom_flags_temp0_games` (
                `modID` bigint(255) NOT NULL,
                `flagName` varchar(100) NOT NULL,
                `flagValue` varchar(100) NOT NULL,
                KEY (`modID`, `flagName`, `flagValue`)
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

    $db->q("CREATE TABLE IF NOT EXISTS `cache_custom_flags_temp1_sort` (
                    `modID` bigint(255) NOT NULL,
                    `flagName` varchar(100) NOT NULL,
                    `flagValue` varchar(100) NOT NULL,
                    `numGames` bigint(255) NOT NULL,
                    PRIMARY KEY (`modID`, `flagName`, `flagValue`)
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

    $db->q("INSERT INTO `cache_custom_flags_temp0_games`(`modID`, `flagName`, `flagValue`)
              SELECT `modID`, `flagName`, `flagValue`
                FROM `s2_match_flags`
                WHERE `matchID` >= ?;",
        's',
        array($minMatchID)
    );

    $totalFlagValues = $totalFlagValueCombos = 0;

    foreach ($activeMods as $key => $value) {
        try {
            $flagsUsed = $flagValueCombinations = 0;
            $modID = $value['mod_id'];
            $modName = $value['mod_name'];

            echo "<h4>{$modName}</h4>";

            $time_start2 = time();

            $flagsUsed = $db->q(
                'SELECT COUNT(*) as `numFlags` FROM `cache_custom_flags_temp0_games` WHERE `modID` = ? LIMIT 0,1;',
                's',
                array($modID)
            );
            $flagsUsed = $flagsUsed[0]['numFlags'];
            echo "Flags: {$flagsUsed[0]['numFlags']}<br />";

            $flagValueCombinations = $db->q(
                'INSERT INTO `cache_custom_flags_temp1_sort` (`modID`, `flagName`, `flagValue`, `numGames`)
                    SELECT
                            ccft0.`modID`,
                            ccft0.`flagName`,
                            ccft0.`flagValue`,
                            COUNT(*) AS numGames
                        FROM `cache_custom_flags_temp0_games` ccft0
                        WHERE ccft0.`modID` = ?
                        GROUP BY ccft0.`modID`, ccft0.`flagName`, ccft0.`flagValue`;',
                's',
                array($modID)
            );
            echo "Flag Combos: {$flagValueCombinations}<br />";

            $totalFlagValues += $flagsUsed = is_numeric($flagsUsed)
                ? $flagsUsed
                : 0;

            $totalFlagValueCombos += $flagValueCombinations = is_numeric($flagValueCombinations)
                ? $flagValueCombinations
                : 0;

            $time_end2 = time();
            $runTime = $time_end2 - $time_start2;
            echo '<strong>Run Time (query):</strong> ' . $runTime . " seconds<br />";

            try {
                $serviceReport->logAndCompareOld(
                    's2_cron_cmf_' . $modID,
                    array(
                        'value' => $runTime,
                        'min' => 5,
                        'growth' => 1,
                    ),
                    array(
                        'value' => $flagsUsed,
                        'min' => 10,
                        'growth' => 0.5,
                        'unit' => 'flag values',
                    ),
                    array(
                        'value' => $flagValueCombinations,
                        'min' => 10,
                        'growth' => 0.5,
                        'unit' => 'flag value combos',
                    ),
                    NULL,
                    TRUE,
                    $modName
                );
            } catch (Exception $e) {
                echo '<br />Caught Exception (SERVICE REPORT) -- ' . $e->getMessage() . '<br /><br />';

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
            echo '<br />Caught Exception (LOOP) -- ' . $e->getFile() . ':' . $e->getLine() . '<br />' . $e->getMessage() . '<br /><br />';
        }
    }

    $time_start3 = time();
    echo "<h3>Final Insert</h3>";

    $db->q('RENAME TABLE `cache_custom_flags` TO `cache_custom_flags_old`, `cache_custom_flags_temp1_sort` TO `cache_custom_flags`;');

    $db->q('DROP TABLE IF EXISTS `cache_custom_flags_old`;');
    $db->q('DROP TABLE IF EXISTS `cache_custom_flags_temp0_games`;');
    $db->q('DROP TABLE IF EXISTS `cache_custom_flags_temp1_sort`;');

    $time_end3 = time();
    $runTime = $time_end3 - $time_start3;
    echo '<strong>Run Time (final insert):</strong> ' . $runTime . " seconds<br />";

    $time_end1 = time();
    $totalRunTime = $time_end1 - $time_start1;

    echo '<br />';
    echo '<strong>Total Run Time:</strong> ' . $totalRunTime . " seconds<br /><br />";

    echo '<hr />';


    try {
        $serviceReport->logAndCompareOld(
            's2_cron_cmf',
            array(
                'value' => $totalRunTime,
                'min' => 20,
                'growth' => 1,
            ),
            array(
                'value' => $totalFlagValues,
                'min' => 10,
                'growth' => 0.2,
                'unit' => 'flag values',
            ),
            array(
                'value' => $totalFlagValueCombos,
                'min' => 10,
                'growth' => 0.1,
                'unit' => 'flag value combos',
            ),
            NULL,
            FALSE
        );
    } catch (Exception $e) {
        echo '<br />Caught Exception (SERVICE REPORT) -- ' . $e->getMessage() . '<br /><br />';
    }

} catch (Exception $e) {
    echo '<br />Caught Exception (MAIN) -- ' . $e->getFile() . ':' . $e->getLine() . '<br />' . $e->getMessage() . '<br /><br />';
} finally {
    if (isset($memcached)) $memcached->close();
}