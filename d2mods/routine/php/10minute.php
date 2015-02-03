#!/usr/bin/php -q
<?php
require_once('../../functions.php');
require_once('../../../global_functions.php');
require_once('../../../connections/parameters.php');

$db = new dbWrapper_v3($hostname_gds_cron, $username_gds_cron, $password_gds_cron, $database_gds_cron, true);

if ($db) {
    //Mini Game Leaderboard
    {
        try {
            $time_start1 = time();
            echo '<h2>Mini Game Leaderboard</h2>';

            $db->q("DROP TABLE IF EXISTS `cron_hs_temp`;");

            $leaderboards = $db->q(
                'SELECT DISTINCT `minigameID`, `leaderboard` FROM `stat_highscore`;'
            );

            if (!empty($leaderboards)) {
                $sqlResult = $db->q(
                    "CREATE TABLE IF NOT EXISTS `cron_hs_temp` (
                      `minigameID` varchar(255) NOT NULL,
                      `leaderboard` varchar(255) NOT NULL,
                      `user_id32` bigint(255) NOT NULL,
                      `highscore_value` bigint(255) NOT NULL,
                      `date_recorded` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
                      PRIMARY KEY (`minigameID`, `leaderboard`),
                      INDEX `highscore_value` (`highscore_value`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;"
                );

                foreach ($leaderboards as $key => $value) {
                    $minigameID = $value['minigameID'];
                    $leaderboard = $value['leaderboard'];

                    $sqlResult = $db->q(
                        "INSERT INTO `cron_hs_temp`
                            SELECT
                              `minigameID`,
                              `leaderboard`,
                              `user_id32`,
                              `highscore_value`,
                              `date_recorded`
                            FROM `stat_highscore`
                            WHERE `minigameID` = ? AND `leaderboard` = ?
                            ORDER BY `highscore_value` DESC
                            LIMIT 0,10;",
                        'ss',
                        array($minigameID, $leaderboard)
                    );

                    echo $sqlResult
                        ? "[SUCCESS] Gathered High Scores for: $minigameID [$leaderboard]!<br />"
                        : "[FAILURE] Gathered High Scores for:  $minigameID [$leaderboard]!<br />";
                }

                $sqlResult = $db->q(
                    'SELECT * FROM `cron_hs_temp`;'
                );

                if (!empty($sqlResult)) {
                    $db->q(
                        "CREATE TABLE IF NOT EXISTS `cron_hs` (
                          `minigameID` varchar(255) NOT NULL,
                          `leaderboard` varchar(255) NOT NULL,
                          `user_id32` bigint(255) NOT NULL,
                          `highscore_value` bigint(255) NOT NULL,
                          `date_recorded` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
                          PRIMARY KEY (`minigameID`, `leaderboard`),
                          INDEX `highscore_value` (`highscore_value`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=latin1;"
                    );

                    $db->q(
                        "TRUNCATE TABLE `cron_hs`;"
                    );

                    $sqlResult = $db->q(
                        "INSERT INTO `cron_hs`
                            SELECT * FROM `cron_hs_temp`;"
                    );

                    echo $sqlResult
                        ? "[SUCCESS] Final table populated!<br />"
                        : "[FAILURE] Final table not populated!<br />";

                    $db->q("DROP TABLE IF EXISTS `cron_hs_temp`;");
                } else {
                    echo "[FAILURE] Final table not populated!<br />";
                }
            } else {
                echo "[FAILURE] Final table not populated!<br />";;
            }

            unset($sqlResult);

            $time_end1 = time();
            echo 'Total Running: ' . ($time_end1 - $time_start1) . " seconds<br /><br />";
            echo '<hr />';
        } catch (Exception $e) {
            echo 'Caught Exception (MINI-GAMES) -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage() . '<br /><br />';
        }
    }
}