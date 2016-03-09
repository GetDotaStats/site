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

    echo '<h2>Mod Game Values</h2>';

    $maxSQL = cached_query(
        's2_cron_cmf_max',
        'SELECT `matchID`, `dateRecorded` FROM `s2_match` WHERE `matchID` = (SELECT MAX(`matchID`) FROM `s2_match`) LIMIT 0,1;'
    );
    if (empty($maxSQL)) throw new Exception('No matches with game values!');

    $maxMatchID = $maxSQL[0]['matchID'];
    $maxMatchDate = $maxSQL[0]['dateRecorded'];
    echo "<strong>Max:</strong> {$maxMatchID} [{$maxMatchDate}]<br />";

    $minSQL = cached_query(
        's2_cron_cmf_min',
        "SELECT `matchID`, `dateRecorded` FROM `s2_match` WHERE `dateRecorded` >= (? - INTERVAL ? DAY) LIMIT 0,1;",
        'si',
        array($maxMatchDate, $daysToGather),
        15
    );
    if (empty($minSQL)) throw new Exception('No matches with game values!');

    $minMatchID = $minSQL[0]['matchID'];
    $minMatchDate = $minSQL[0]['dateRecorded'];
    echo "<strong>Min:</strong> {$minMatchID} [{$minMatchDate}]<br />";

    $db->q('DROP TABLE IF EXISTS `cache_custom_game_values_temp0_games`;');
    $db->q('DROP TABLE IF EXISTS `cache_custom_game_values_temp1_grouping`;');
    $db->q('DROP TABLE IF EXISTS `cache_custom_game_values_temp2_sort`;');

    $db->q("CREATE TABLE IF NOT EXISTS `cache_custom_game_values` (
                `modID` bigint(255) NOT NULL,
                `fieldOrder` tinyint(1) NOT NULL,
                `fieldValue` varchar(100) NOT NULL,
                `numGames` bigint(255) NOT NULL,
                PRIMARY KEY (`modID`, `fieldOrder`, `fieldValue`)
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

    $db->q("CREATE TEMPORARY TABLE IF NOT EXISTS `cache_custom_game_values_temp0_games` (
                `modID` int(255) NOT NULL,
                `schemaID` int(255) NOT NULL,
                `fieldOrder` tinyint(1) NOT NULL,
                `fieldValue` varchar(100) NOT NULL,
                KEY `modID_fO_fV` (`modID`, `fieldOrder`, `fieldValue`),
                KEY (`schemaID`)
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

    $db->q("CREATE TABLE IF NOT EXISTS `cache_custom_game_values_temp2_sort` (
                `modID` bigint(255) NOT NULL,
                `fieldOrder` tinyint(1) NOT NULL,
                `fieldValue` varchar(100) NOT NULL,
                `numGames` bigint(255) NOT NULL,
                PRIMARY KEY (`modID`, `fieldOrder`, `fieldValue`)
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

    $db->q("INSERT INTO `cache_custom_game_values_temp0_games`(`modID`, `schemaID`, `fieldOrder`, `fieldValue`)
              SELECT `modID`, `schemaID`, `fieldOrder`, `fieldValue`
                FROM `s2_match_custom`
                WHERE `matchID` BETWEEN ? AND ?;",
        'ii',
        array($minMatchID, $maxMatchID)
    );

    //Kill old schemas
    $db->q(
        'DELETE FROM `cache_custom_game_values_temp0_games` WHERE `schemaID` NOT IN
            (SELECT MAX(`schemaID`) AS `schemaID` FROM `s2_mod_custom_schema` WHERE `schemaApproved` = 1 GROUP BY `modID`);'
    );

    $totalCustomGameValues = $totalCustomGameValueCombos = 0;

    foreach ($activeMods as $key => $value) {
        try {
            $customGameValues = $customGameValueCombos = 0;
            $modID = $value['mod_id'];
            $modName = $value['mod_name'];

            echo "<h4>{$modName}</h4>";

            $time_start2 = time();

            ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

            $customGameValues = cached_query(
                's2_cron_cmg_match_count_' . $modID,
                'SELECT COUNT(*) AS `totalGameValues` FROM `cache_custom_game_values_temp0_games` WHERE `modID` = ?;',
                'i',
                array($modID)
            );
            $customGameValues = !empty($customGameValues) ? $customGameValues[0]['totalGameValues'] : 0;

            echo "Game Values: {$customGameValues}<br />";

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
                      s2mcsf.`isGroupable`,
                      s2mcsf.`customValueDisplay`
                    FROM `s2_mod_custom_schema_fields` s2mcsf
                    WHERE
                      s2mcsf.`schemaID` = (
                        SELECT MAX(`schemaID`) FROM `s2_mod_custom_schema` WHERE `modID` = ? AND `schemaApproved` = 1
                      ) AND
                      s2mcsf.`fieldType` = 1;',
                'i',
                $modID
            );

            $echoedSchemaID = false;

            if (!empty($schemaFields)) {
                //ITERATE THROUGH EACH FIELD
                foreach ($schemaFields as $key2 => $value2) {
                    try {
                        $schemaID = $value2['schemaID'];
                        $isGroupable = $value2['isGroupable'];
                        $fieldID = $value2['fieldOrder'];
                        $fieldName = $value2['customValueDisplay'];

                        if(!$echoedSchemaID){
                            echo "SchemaID: {$schemaID}<br />";
                            $echoedSchemaID = true;
                        }

                        if ($isGroupable == '1') {
                            //Find if there is data for field
                            $playData = $db->q(
                                'SELECT
                                    `modID`,
                                    `fieldOrder`,
                                    `fieldValue`
                                FROM `cache_custom_game_values_temp0_games`
                                WHERE `modID` = ? AND
                                  `fieldOrder` = ?;',
                                'ii',
                                array($modID, $fieldID)
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
                            echo "<li><strong>{$fieldName}</strong> [{$fieldID}]</li>";
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

                            $db->q('DROP TABLE IF EXISTS `cache_custom_game_values_temp1_grouping`;');

                            $db->q(
                                "CREATE TEMPORARY TABLE IF NOT EXISTS `cache_custom_game_values_temp1_grouping` (
                                    `valueGroupingLower` int(100) NOT NULL,
                                    `valueGroupingUpper` int(100) NOT NULL,
                                    `numGames` int(100) NOT NULL,
                                    PRIMARY KEY (`valueGroupingLower`, `numGames`)
                                ) ENGINE=InnoDB DEFAULT CHARSET=latin1;"
                            );

                            $db->q(
                                "INSERT INTO `cache_custom_game_values_temp1_grouping`
                                    SELECT
                                      (FLOOR(`fieldValue` / {$firstGroupBy}) * {$firstGroupBy}) AS valueGroupingLower,
                                      ((FLOOR(`fieldValue` / {$firstGroupBy}) + 1) * {$firstGroupBy}) AS valueGroupingUpper,
                                      COUNT(*) AS numGames
                                    FROM `cache_custom_game_values_temp0_games`
                                    WHERE `modID` = ? AND `fieldOrder` = ? AND `fieldValue` < ?
                                    GROUP BY valueGroupingLower;",
                                'iii',
                                array($modID, $fieldID, $firstGroupLimit)
                            );

                            ////////////////////////////////////////////////////////////
                            //DEBUG

                            /*$dump = $db->q('SELECT * FROM `cache_custom_game_values_temp1_grouping`
                                    WHERE `modID` = ? AND `fieldOrder` = ? AND `fieldValue` < ?;',
                                'sss',
                                array($modID, $fieldID, $firstGroupLimit)
                            );
                            echo '<pre>';
                            print_r($dump);
                            echo '</pre>';
                            exit();*/

                            ////////////////////////////////////////////////////////////

                            $db->q(
                                'DELETE FROM `cache_custom_game_values_temp0_games`
                                    WHERE `modID` = ? AND `fieldOrder` = ? AND `fieldValue` < ?;',
                                'iii',
                                array($modID, $fieldID, $firstGroupLimit)
                            );

                            $db->q(
                                "INSERT INTO `cache_custom_game_values_temp1_grouping`
                                SELECT
                                  ((FLOOR((`fieldValue` - {$firstGroupLimit}) / {$secondGroupBy}) * {$secondGroupBy}) + {$firstGroupLimit}) AS valueGroupingLower,
                                  (((FLOOR((`fieldValue` - {$firstGroupLimit}) / {$secondGroupBy}) + 1) * {$secondGroupBy}) + {$firstGroupLimit}) AS valueGroupingUpper,
                                  COUNT(*) AS numGames
                                FROM `cache_custom_game_values_temp0_games`
                                WHERE `modID` = ? AND `fieldOrder` = ?
                                GROUP BY valueGroupingLower;",
                                'ii',
                                array($modID, $fieldID)
                            );

                            $db->q(
                                'DELETE FROM `cache_custom_game_values_temp0_games`
                                    WHERE `modID` = ? AND `fieldOrder` = ?;',
                                'ii',
                                array($modID, $fieldID)
                            );

                            $customGameValueCombos = $db->q(
                                "INSERT INTO `cache_custom_game_values_temp2_sort`
                                SELECT
                                  {$modID} AS modID2,
                                  {$fieldID} AS fieldOrder2,
                                  CONCAT(LPAD(`valueGroupingLower`,{$lpad_length},'0'), ' - ', LPAD(`valueGroupingUpper`,{$lpad_length},'0')) AS fieldValue2,
                                  SUM(`numGames`) AS numGames
                                FROM `cache_custom_game_values_temp1_grouping`
                                GROUP BY modID2, fieldOrder2, fieldValue2;"
                            );

                            $db->q('DROP TABLE IF EXISTS `cache_custom_game_values_temp1_grouping`;');

                            $totalCustomGameValueCombos += $customGameValueCombos = is_numeric($customGameValueCombos)
                                ? $customGameValueCombos
                                : 0;
                        } else {
                            //DO NORMAL GROUPING FOR REMAINING FIELDS IN THE TABLE
                            $customGameValueCombos = $db->q(
                                'INSERT INTO `cache_custom_game_values_temp2_sort`
                                    SELECT
                                        s2mc.`modID`,
                                        s2mc.`fieldOrder`,
                                        s2mc.`fieldValue`,
                                        COUNT(*) AS numGames
                                    FROM `cache_custom_game_values_temp0_games` s2mc
                                    WHERE `modID` = ? AND `fieldOrder` = ?
                                    GROUP BY s2mc.`modID`, s2mc.`fieldOrder`, s2mc.`fieldValue`;',
                                'ii',
                                array($modID, $fieldID)
                            );

                            if (!empty($customGameValueCombos)) {
                                $totalCustomGameValueCombos += $customGameValueCombos = is_numeric($customGameValueCombos)
                                    ? $customGameValueCombos
                                    : 0;
                            }
                        }
                    } catch (Exception $e) {
                        echo '<br />Caught Exception (FIELD LOOP) -- ' . $e->getFile() . ':' . $e->getLine() . '<br />' . $e->getMessage() . '<br /><br />';
                    }
                }
            }

            $totalCustomGameValues += $customGameValues = is_numeric($customGameValues)
                ? $customGameValues
                : 0;

            $totalCustomGameValueCombos += $customGameValueCombos = is_numeric($customGameValueCombos)
                ? $customGameValueCombos
                : 0;

            echo "<strong>Results:</strong> Game Values: $customGameValues || Game Value Combos: $customGameValueCombos<br />";

            $time_end2 = time();
            $runTime = $time_end2 - $time_start2;
            echo '<strong>Run Time:</strong> ' . $runTime . " seconds<br />";

            try {
                $serviceReport->logAndCompareOld(
                    's2_cron_cmgv_' . $modID,
                    array(
                        'value' => $runTime,
                        'min' => 5,
                        'growth' => 1,
                    ),
                    array(
                        'value' => $customGameValues,
                        'min' => 10,
                        'growth' => 0.5,
                        'unit' => 'game values',
                    ),
                    array(
                        'value' => $customGameValueCombos,
                        'min' => 10,
                        'growth' => 0.5,
                        'unit' => 'game value combos',
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
                            '[CMGV]',
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
                        array('http://getdotastats.com/s2/routine/log_daily.html?' . time())
                    );

                    $message = $irc_message->combine_message($message);
                    $irc_message->post_message($message, array('localDev' => $localDev));
                }
            }

            echo '<hr />';
        } catch (Exception $e) {
            echo '<br />Caught Exception (MOD LOOP) -- ' . $e->getFile() . ':' . $e->getLine() . '<br />' . $e->getMessage() . '<br /><br />';
        }
    }

    $time_start3 = time();
    echo "<h3>Final Insert</h3>";

    $db->q('RENAME TABLE `cache_custom_game_values` TO `cache_custom_game_values_old`, `cache_custom_game_values_temp2_sort` TO `cache_custom_game_values`;');

    $db->q('DROP TABLE IF EXISTS `cache_custom_game_values_old`;');
    $db->q('DROP TABLE IF EXISTS `cache_custom_game_values_temp0_games`;');
    $db->q('DROP TABLE IF EXISTS `cache_custom_game_values_temp1_grouping`;');
    $db->q('DROP TABLE IF EXISTS `cache_custom_game_values_temp2_sort`;');

    $time_end3 = time();
    $runTime = $time_end3 - $time_start3;
    echo '<strong>Run Time:</strong> ' . $runTime . " seconds<br />";

    $time_end1 = time();
    $totalRunTime = $time_end1 - $time_start1;
    echo '<br />';
    echo '<strong>Total Run Time:</strong> ' . $totalRunTime . " seconds<br /><br />";

    echo '<hr />';


    try {
        $serviceReport->logAndCompareOld(
            's2_cron_cmgv',
            array(
                'value' => $totalRunTime,
                'min' => 20,
                'growth' => 1,
            ),
            array(
                'value' => $totalCustomGameValues,
                'min' => 10,
                'growth' => 0.1,
                'unit' => 'game values',
            ),
            array(
                'value' => $totalCustomGameValueCombos,
                'min' => 10,
                'growth' => 0.1,
                'unit' => 'game value combos',
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