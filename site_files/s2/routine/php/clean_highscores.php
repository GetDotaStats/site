#!/usr/bin/php -q
<?php
try {
    require_once('../../../global_functions.php');
    require_once('../../../connections/parameters.php');

    $db = new dbWrapper_v3($hostname_gds_cron, $username_gds_cron, $password_gds_cron, $database_gds_cron, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcached = new Cache(NULL, NULL, $localDev);

    $serviceReport = new serviceReporting($db);

    $numPlayersPerLeaderboard = 51;

    //UPDATE WORKSHOP DETAILS
    {
        set_time_limit(0);

        $schemaList = cached_query(
            'cron_highscore_schema_list',
            'SELECT
                    shms.`highscoreID`,
                    shms.`highscoreIdentifier`,
                    shms.`modID`,
                    shms.`modIdentifier`,
                    shms.`secureWithAuth`,
                    shms.`highscoreName`,
                    shms.`highscoreDescription`,
                    shms.`highscoreActive`,
                    shms.`highscoreObjective`,
                    shms.`highscoreOperator`,
                    shms.`highscoreFactor`,
                    shms.`highscoreDecimals`,
                    shms.`date_recorded`,

                    ml.`mod_name`
                FROM `stat_highscore_mods_schema` shms
                JOIN `mod_list` ml ON shms.`modID` = ml.`mod_id`;'
        );

        $time_start1 = time();
        echo '<h2>Cleaning the Highscores</h2>';

        $totalDeletes = 0;

        foreach ($schemaList as $key => $value) {
            try {
                $time_start2 = time();
                $SQLdelete = 0;

                $modID = $value['modID'];
                $modName = $value['mod_name'];
                $highscoreName = $value['highscoreName'];
                $highscoreID = $value['highscoreID'];

                echo "<h4>{$modName} <small>{$highscoreName}</small></h4>";

                if ($value['highscoreObjective'] == 'max') {
                    $findPositionOfLast = cached_query(
                        'cron_highscores_last_place' . $value['modID'],
                        "SELECT
                                `modID`,
                                `highscoreID`,
                                `steamID32`,
                                `steamID64`,
                                `highscoreAuthKey`,
                                `userName`,
                                `highscoreValue`,
                                `date_recorded`
                            FROM `stat_highscore_mods`
                            WHERE `modID` = ? AND `highscoreID` = ?
                            ORDER BY `highscoreValue` DESC
                            LIMIT {$numPlayersPerLeaderboard},1;",
                        'ii',
                        array($modID, $highscoreID)
                    );
                    if (empty($findPositionOfLast)) throw new Exception('Not enough entries in leaderboard to cull!');

                    $SQLdelete = $db->q(
                        'DELETE FROM `stat_highscore_mods_top` WHERE `highscoreValue` <= ?;',
                        'i',
                        array($findPositionOfLast[0]['highscoreValue'])
                    );

                    $SQLdelete = is_numeric($SQLdelete)
                        ? $SQLdelete
                        : 0;

                    $totalDeletes += $SQLdelete;
                } else if ($value['highscoreObjective'] == 'min') {
                    $findPositionOfLast = cached_query(
                        'cron_highscores_last_place' . $value['modID'],
                        "SELECT
                                `modID`,
                                `highscoreID`,
                                `steamID32`,
                                `steamID64`,
                                `highscoreAuthKey`,
                                `userName`,
                                `highscoreValue`,
                                `date_recorded`
                            FROM `stat_highscore_mods`
                            WHERE `modID` = ? AND `highscoreID` = ?
                            ORDER BY `highscoreValue` ASC
                            LIMIT {$numPlayersPerLeaderboard},1;",
                        'ii',
                        array($modID, $highscoreID)
                    );
                    if (empty($findPositionOfLast)) throw new Exception('Not enough entries in leaderboard to cull!');

                    $SQLdelete = $db->q(
                        'DELETE FROM `stat_highscore_mods_top` WHERE `highscoreValue` >= ?;',
                        'i',
                        array($findPositionOfLast[0]['highscoreValue'])
                    );

                    $SQLdelete = is_numeric($SQLdelete)
                        ? $SQLdelete
                        : 0;

                    $totalDeletes += $SQLdelete;
                }

                $time_end2 = time();
                $totalRunTime2 = $time_end2 - $time_start2;

                try {
                    $serviceReport->logAndCompareOld(
                        's2_cron_highscore_clean_' . $modID,
                        array(
                            'value' => $totalRunTime2,
                            'min' => 10,
                            'growth' => 0.5,
                        ),
                        array(
                            'value' => $SQLdelete,
                            'min' => 100,
                            'growth' => 0.5,
                            'unit' => 'successful scrapes',
                        ),
                        NULL,
                        NULL,
                        TRUE,
                        $modName . ' - ' . $highscoreName
                    );
                } catch (Exception $e) {
                    echo '<br />Caught Exception (SERVICE REPORTING LOOP) -- ' . $e->getMessage() . '<br />';
                }

            } catch (Exception $e) {
                echo '<br />Caught Exception (LOOP) -- ' . $e->getMessage() . '<br />';
            }
        }

        $time_end1 = time();
        $totalRunTime = $time_end1 - $time_start1;
        echo 'Total Running: ' . ($time_end1 - $time_start1) . " seconds<br /><br />";
        echo '<hr />';

        try {
            $serviceReport->logAndCompareOld(
                's2_cron_highscore_clean',
                array(
                    'value' => $totalRunTime,
                    'min' => 10,
                    'growth' => 0.5,
                ),
                array(
                    'value' => $totalDeletes,
                    'min' => 100,
                    'growth' => 0.5,
                    'unit' => 'successful scrapes',
                ),
                NULL,
                NULL,
                FALSE
            );
        } catch (Exception $e) {
            echo '<br />Caught Exception (SERVICE REPORTING MAIN) -- ' . $e->getMessage() . '<br />';

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
                        '[HIGHSCORES]',
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
                    array('http://getdotastats.com/s2/routine/log_highscores.html?' . time())
                );

                $message = $irc_message->combine_message($message);
                $irc_message->post_message($message, array('localDev' => $localDev));
            }
        }
    }

} catch (Exception $e) {
    echo '<br />Caught Exception (MAIN) -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage() . '<br />';
} finally {
    if (isset($memcached)) $memcached->close();
}
