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
    echo '<h2>Mod Player Values</h2>';

    $db->q('DROP TABLE IF EXISTS `cache_custom_player_values_temp0`;');

    $db->q("CREATE TABLE IF NOT EXISTS `cache_custom_player_values` (
        `modID` bigint(255) NOT NULL,
        `fieldOrder` tinyint(1) NOT NULL,
        `fieldValue` varchar(100) NOT NULL,
        `numGames` bigint(255) NOT NULL,
        `numWins` bigint(255) NOT NULL,
        PRIMARY KEY (`modID`, `fieldOrder`, `fieldValue`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

    $db->q("CREATE TABLE IF NOT EXISTS `cache_custom_player_values_temp0` (
        `modID` bigint(255) NOT NULL,
        `fieldOrder` tinyint(1) NOT NULL,
        `fieldValue` varchar(100) NOT NULL,
        `numGames` bigint(255) NOT NULL,
        `numWins` bigint(255) NOT NULL,
        PRIMARY KEY (`modID`, `fieldOrder`, `fieldValue`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

    $totalCustomPlayerValues = $totalCustomPlayerValueCombos = 0;

    foreach ($activeMods as $key => $value) {
        try {
            $customPlayerValues = $customPlayerValueCombos = 0;
            $modID = $value['mod_id'];
            $modName = $value['mod_name'];

            echo "<h4>{$modName}</h4>";

            $time_start1 = time();

            $db->q('DROP TABLE IF EXISTS `cache_custom_player_values_temp3`;');

            $db->q("CREATE TABLE IF NOT EXISTS `cache_custom_player_values_temp3` (
                `modID` int(255) NOT NULL,
                `fieldOrder` tinyint(1) NOT NULL,
                `fieldValue` varchar(100) NOT NULL,
                `isWinner` tinyint(1) NOT NULL,
                KEY (`modID`, `fieldOrder`, `fieldValue`)
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

            $customPlayerValues = $db->q(
                'INSERT INTO `cache_custom_player_values_temp3`
                    SELECT
                            s2mpc.`modID`,
                            s2mpc.`fieldOrder`,
                            s2mpc.`fieldValue`,
                            (
                              SELECT
                                  s2mp.`isWinner`
                                FROM `s2_match_players` s2mp
                                WHERE
                                  s2mp.`matchID` = s2mpc.`matchID` AND
                                  s2mp.`roundID` = s2mpc.`round` AND
                                  s2mp.`steamID32` = s2mpc.`userID32`
                            ) AS isWinner
                        FROM `s2_match_players_custom` s2mpc
                        WHERE s2mpc.`matchID` IN (
                          SELECT
                            `matchID`
                          FROM `cache_cmg`
                          WHERE `modID` = ?
                        );',
                's',
                $modID
            );

            echo "Matches: {$customPlayerValues}<br />";

            //IF NUMBER OF UNIQUE VALUES IS GREATER THAN 20
            //SELECT THE DATA SET FOR THE FIELD
            //FIND: 3rd QUARTILE, RANGE
            //IF 3rd QUARTILE IS LARGER THAN 10
            //MAKE 10 GROUPINGS STARTING FROM 0 TO 3rd QUARTILE
            //THROW REST OF DATA INTO 5 EQUAL GROUPS

            //FIND OUT WHICH FIELDS ARE GROUPABLE
            $schemaFields = $db->q(
                'SELECT
                      s2mcsf.`schemaID`,
                      s2mcsf.`fieldOrder`,
                      s2mcsf.`customValueDisplay`
                    FROM `s2_mod_custom_schema` s2mcs
                    JOIN `s2_mod_custom_schema_fields` s2mcsf
                      ON s2mcs.`schemaID` = s2mcsf.`schemaID`
                    WHERE
                      s2mcs.`modID` = ? AND
                      s2mcs.`schemaApproved` = 1 AND
                      s2mcsf.`isGroupable` = 1 AND
                      s2mcsf.`fieldType` = 2;',
                'i',
                $modID
            );

            if (!empty($schemaFields)) {
                //ITERATE THROUGH EACH FIELD
                foreach ($schemaFields as $key2 => $value2) {
                    try {
                        $schemaID = $value2['schemaID'];
                        $fieldID = $value2['fieldOrder'];
                        $fieldName = $value2['customValueDisplay'];

                        $db->q('DROP TABLE IF EXISTS `cache_custom_player_values_temp1`;');
                        $db->q('DROP TABLE IF EXISTS `cache_custom_player_values_temp2`;');

                        $db->q("CREATE TABLE IF NOT EXISTS `cache_custom_player_values_temp2` (
                            `modID` int(255) NOT NULL,
                            `fieldOrder` tinyint(1) NOT NULL,
                            `fieldValue` varchar(100) NOT NULL,
                            `isWinner` tinyint(1) NOT NULL,
                            KEY (`modID`, `fieldOrder`, `fieldValue`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

                        $db->q(
                            'INSERT INTO `cache_custom_player_values_temp2`
                                SELECT
                                        `modID`,
                                        `fieldOrder`,
                                        `fieldValue`,
                                        `isWinner`
                                    FROM `cache_custom_player_values_temp3`
                                    WHERE
                                      `modID` = ? AND
                                      `fieldOrder` = ?;',
                            'ii',
                            array($modID, $value2['fieldOrder'])
                        );

                        //Find if there is data for field
                        $playData = $db->q(
                            'SELECT
                                    `modID`,
                                    `fieldOrder`,
                                    `fieldValue`
                                FROM `cache_custom_player_values_temp2`'
                        );

                        //If not data for this groupable field, skip it and do it normally
                        if (empty($playData)) {
                            echo "<h4>{$fieldName}</h4>";
                            echo "No data!<br />";
                            continue;
                        }

                        $bigArray = array();
                        foreach ($playData as $key3 => $value3) {
                            $bigArray[] = $value3['fieldValue'];
                        }

                        $statsLibrary = new basicStatsForArrays($bigArray);

                        $quart75 = $statsLibrary->Quartile_75();
                        $max = $statsLibrary->Max();
                        $min = $statsLibrary->Min();
                        $count = $statsLibrary->Count();
                        $lpad_length = strlen(floor($max));

                        $firstGroupMaxCategories = 30;
                        $secondGroupMaxCategories = 20;
                        $firstGroupMaxValue = $firstGroupMaxCategories + 10;

                        //If the amount of values does not warrant splitting, skip it and do it normally
                        if (($max <= $firstGroupMaxValue) || ($quart75 < $firstGroupMaxCategories)) {
                            echo '<ul>';
                            echo "<li><strong>{$fieldName}</strong></li>";
                            echo "<ul><li>Third quartile not above {$firstGroupMaxCategories} or maximum value not greater than {$firstGroupMaxValue}!</li></ul>";
                            echo '</ul>';
                            continue;
                        }

                        echo '<ul>';
                        echo "<li><strong>{$fieldName}</strong></li>";
                        echo '<ul>';
                        echo "<li>Count: {$count}</li>";
                        echo "<li>Range: {$min} - {$max}</li>";
                        echo "<li>LPAD: {$lpad_length}</li>";
                        echo "<li>Quartile_75: {$quart75}</li>";

                        $firstGroupBy = floor($quart75 / $firstGroupMaxCategories);
                        $firstGroupLimit = ($firstGroupBy * $firstGroupMaxCategories);

                        $secondGroupBy = floor(($max - $firstGroupLimit) / $secondGroupMaxCategories);

                        echo "<li>Values [0 - {$firstGroupLimit}] in {$firstGroupMaxCategories} groups with value of {$firstGroupBy}</li>";
                        echo "<li>Values [{$firstGroupLimit}+] in {$secondGroupMaxCategories} groups with value of {$secondGroupBy}</li>";

                        echo '</ul>';
                        echo '</ul>';

                        $db->q(
                            'DELETE FROM `cache_custom_player_values_temp3` WHERE `modID` = ? AND `fieldOrder` = ?;',
                            'ii',
                            array($modID, $value2['fieldOrder'])
                        );

                        $db->q("CREATE TABLE IF NOT EXISTS `cache_custom_player_values_temp1` (
                            `valueGroupingLower` int(100) NOT NULL,
                            `valueGroupingUpper` int(100) NOT NULL,
                            `numGames` int(100) NOT NULL,
                            `numWins` bigint(100) NOT NULL,
                            PRIMARY KEY (`valueGroupingLower`, `numGames`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

                        $db->q(
                            "INSERT INTO `cache_custom_player_values_temp1`
                                SELECT
                                  (FLOOR(`fieldValue` / {$firstGroupBy}) * {$firstGroupBy}) AS valueGroupingLower,
                                  ((FLOOR(`fieldValue` / {$firstGroupBy}) + 1) * {$firstGroupBy}) AS valueGroupingUpper,
                                  COUNT(*) AS numGames,
                                  SUM(`isWinner`) AS numWins
                                FROM `cache_custom_player_values_temp2`
                                WHERE `fieldValue` < ?
                                GROUP BY valueGroupingLower;",
                            'i',
                            array($firstGroupLimit)
                        );

                        $db->q(
                            'DELETE FROM `cache_custom_player_values_temp2` WHERE `fieldValue` < ?;',
                            'i',
                            array($firstGroupLimit)
                        );

                        $db->q(
                            "INSERT INTO `cache_custom_player_values_temp1`
                                SELECT
                                  ((FLOOR((`fieldValue` - {$firstGroupLimit}) / {$secondGroupBy}) * {$secondGroupBy}) + {$firstGroupLimit}) AS valueGroupingLower,
                                  (((FLOOR((`fieldValue` - {$firstGroupLimit}) / {$secondGroupBy}) + 1) * {$secondGroupBy}) + {$firstGroupLimit}) AS valueGroupingUpper,
                                  COUNT(*) AS numGames,
                                  SUM(`isWinner`) AS numWins
                                FROM `cache_custom_player_values_temp2`
                                GROUP BY valueGroupingLower;"
                        );

                        $customPlayerValueCombos = $db->q(
                            "INSERT INTO `cache_custom_player_values_temp0`
                                SELECT
                                  {$modID} AS modID2,
                                  {$fieldID} AS fieldOrder2,
                                  CONCAT(LPAD(`valueGroupingLower`,{$lpad_length},'0'), ' - ', LPAD(`valueGroupingUpper`,{$lpad_length},'0')) AS fieldValue2,
                                  SUM(`numGames`) AS numGames,
                                  SUM(`numWins`) AS numWins
                                FROM `cache_custom_player_values_temp1`
                                GROUP BY modID2, fieldOrder2, fieldValue2;"
                        );

                        $db->q('DROP TABLE IF EXISTS `cache_custom_player_values_temp1`;');
                        $db->q('DROP TABLE IF EXISTS `cache_custom_player_values_temp2`;');

                        $totalCustomPlayerValueCombos += $customPlayerValueCombos = is_numeric($customPlayerValueCombos)
                            ? $customPlayerValueCombos
                            : 0;


                    } catch (Exception $e) {
                        echo 'Caught Exception (FIELD LOOP) -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage() . '<br /><br />';
                    }
                }

                $customPlayerValueCombos = $db->q(
                    'INSERT INTO `cache_custom_player_values_temp0`
                        SELECT
                            s2mc.`modID`,
                            s2mc.`fieldOrder`,
                            s2mc.`fieldValue`,
                            COUNT(*) AS numGames,
                            SUM(s2mc.`isWinner`) AS numWins
                        FROM `cache_custom_player_values_temp3` s2mc
                        GROUP BY s2mc.`modID`, s2mc.`fieldOrder`, s2mc.`fieldValue`;'
                );

                if (!empty($customPlayerValueCombos)) {
                    $totalCustomPlayerValueCombos += $customPlayerValueCombos = is_numeric($customPlayerValueCombos)
                        ? $customPlayerValueCombos
                        : 0;
                }
            } else {
                $customPlayerValueCombos = $db->q(
                    'INSERT INTO `cache_custom_player_values_temp0`
                        SELECT
                            s2mc.`modID`,
                            s2mc.`fieldOrder`,
                            s2mc.`fieldValue`,
                            COUNT(*) AS numGames,
                            SUM(s2mc.`isWinner`) AS numWins
                        FROM `cache_custom_player_values_temp3` s2mc
                        GROUP BY s2mc.`modID`, s2mc.`fieldOrder`, s2mc.`fieldValue`;'
                );
            }

            $totalCustomPlayerValues += $customPlayerValues = is_numeric($customPlayerValues)
                ? $customPlayerValues
                : 0;

            $totalCustomPlayerValueCombos += $customPlayerValueCombos = is_numeric($customPlayerValueCombos)
                ? $customPlayerValueCombos
                : 0;

            echo "<strong>Results:</strong> Game Values: $customPlayerValues || Game Value Combos: $customPlayerValueCombos<br />";

            $time_end1 = time();
            $runTime = $time_end1 - $time_start1;
            $totalRunTime += $runTime;
            echo '<strong>Run Time:</strong> ' . $runTime . " seconds<br />";

            try {
                $serviceReport->logAndCompareOld(
                    's2_cron_cmpv_' . $modID,
                    array(
                        'value' => $runTime,
                        'min' => 5,
                        'growth' => 1,
                    ),
                    array(
                        'value' => $customPlayerValues,
                        'min' => 10,
                        'growth' => 0.5,
                        'unit' => 'player values',
                    ),
                    array(
                        'value' => $customPlayerValueCombos,
                        'min' => 10,
                        'growth' => 0.5,
                        'unit' => 'player value combos',
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
                            '[CMPV]',
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

            echo '<hr />';
        } catch (Exception $e) {
            echo 'Caught Exception (MOD LOOP) -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage() . '<br /><br />';
        }
    }

    $time_start1 = time();
    echo "<h3>Final Insert</h3>";

    $db->q('RENAME TABLE `cache_custom_player_values` TO `cache_custom_player_values_old`, `cache_custom_player_values_temp0` TO `cache_custom_player_values`;');

    $db->q('DROP TABLE IF EXISTS `cache_custom_player_values_old`;');
    $db->q('DROP TABLE IF EXISTS `cache_custom_player_values_temp0`;');
    $db->q('DROP TABLE IF EXISTS `cache_custom_player_values_temp1`;');
    $db->q('DROP TABLE IF EXISTS `cache_custom_player_values_temp2`;');
    $db->q('DROP TABLE IF EXISTS `cache_custom_player_values_temp3`;');

    $time_end1 = time();
    echo '<strong>Run Time:</strong> ' . ($time_end1 - $time_start1) . " seconds<br />";

    echo '<br />';
    echo '<strong>Total Run Time:</strong> ' . $totalRunTime . " seconds<br /><br />";

    echo '<hr />';


    try {
        $serviceReport->logAndCompareOld(
            's2_cron_cmpv',
            array(
                'value' => $totalRunTime,
                'min' => 20,
                'growth' => 0.5,
            ),
            array(
                'value' => $totalCustomPlayerValues,
                'min' => 10,
                'growth' => 0.1,
                'unit' => 'player values',
            ),
            array(
                'value' => $totalCustomPlayerValueCombos,
                'min' => 10,
                'growth' => 0.1,
                'unit' => 'player value combos',
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