#!/usr/bin/php -q
<?php
try {
    require_once('../../functions.php');
    require_once('../../../global_functions.php');
    require_once('../../../connections/parameters.php');

    $db = new dbWrapper_v3($hostname_gds_cron, $username_gds_cron, $password_gds_cron, $database_gds_cron, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    //MOD-HERO BREAKDOWN
    {
        $time_start1 = time();
        echo '<h2>Mod-Hero Breakdown</h2>';

        $db->q("DROP TABLE IF EXISTS `cron_mod_heroes_temp`;");

        $sqlResult = $db->q(
            "CREATE TABLE IF NOT EXISTS `cron_mod_heroes_temp`
                SELECT
                  `mod_id`,
                  `hero_id`,
                  COUNT(*) AS numPicks,
                  SUM(`hero_won`) AS numWins
                FROM `mod_match_heroes`
                GROUP BY `mod_id`, `hero_id`
                ORDER BY `mod_id`, `hero_id`;"
        );

        echo $sqlResult
            ? "[SUCCESS] Table created!<br />"
            : "[FAILURE] Table not created!<br />";

        if ($sqlResult) {
            $db->q(
                "CREATE TABLE IF NOT EXISTS `cron_mod_heroes` (
                  `mod_id` varchar(255) NOT NULL,
                  `player_hero_id` int(255) NOT NULL,
                  `numPicks` bigint(21) NOT NULL DEFAULT '0',
                  `numWins` bigint(21) NOT NULL DEFAULT '0',
                  PRIMARY KEY (`mod_id`,`player_hero_id`),
                  INDEX `mod_numPicks` (`mod_id`, `numPicks`),
                  INDEX `mod_numWins` (`mod_id`, `numWins`)
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1;"
            );

            $db->q(
                "TRUNCATE TABLE `cron_mod_heroes`;"
            );

            $sqlResult = $db->q(
                "INSERT INTO `cron_mod_heroes`
                    SELECT * FROM `cron_mod_heroes_temp`;"
            );

            echo $sqlResult
                ? "[SUCCESS] Final table populated!<br />"
                : "[FAILURE] Final table not populated!<br />";

            $db->q("DROP TABLE IF EXISTS `cron_mod_heroes_temp`;");
        }

        $time_end1 = time();
        echo '<br />Total Running: ' . ($time_end1 - $time_start1) . " seconds<br /><br />";
        echo '<hr />';
    }

    //Halls of Fame
    {
        try {
            $time_start1 = time();
            echo '<h2>Halls of Fame</h2>';

            $steamID = new SteamID();
            $steamWebAPI = new steam_webapi($api_key1);

            //CREATE TABLE
            {
                try {
                    $time_start2 = time();
                    echo '<h3>HoF - Table Setup</h3>';

                    $db->q(
                        "CREATE TABLE IF NOT EXISTS `cron_hof` (
                                  `hof_id` int(11) NOT NULL,
                                  `player_sid32` bigint(20) NOT NULL,
                                  `player_sid64` bigint(30) NOT NULL,
                                  `hof_rank` int(100) NOT NULL,
                                  `hof_score1` bigint(20) DEFAULT NULL,
                                  `hof_score2` bigint(20) DEFAULT NULL,
                                  `hof_score3` bigint(20) DEFAULT NULL,
                                  `date_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                  `date_recorded` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
                                  PRIMARY KEY (`hof_id`,`hof_rank`),
                                  INDEX `player_sid32` (`player_sid32`),
                                  INDEX `player_sid64` (`player_sid64`),
                                  INDEX `date_updated` (`date_updated`),
                                  INDEX `date_recorded` (`date_recorded`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=latin1;"
                    );

                    $time_end2 = time();
                    echo 'Running: ' . ($time_end2 - $time_start2) . " seconds<br /><br />";
                } catch (Exception $e) {
                    echo 'Caught Exception (HOF) -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage() . '<br /><br />';
                }
            }

            //HOF1 - Connects
            {
                try {
                    $time_start2 = time();
                    echo '<h3>HoF1 - Connects</h3>';

                    $sqlResult = $db->q(
                        "SELECT
                                mmp.`player_sid32` as player_sid,
                                COUNT(*) as num_games
                            FROM `mod_match_players` mmp
                            WHERE mmp.`connection_status` = 2
                            GROUP BY mmp.`player_sid32`
                            ORDER BY num_games DESC
                            LIMIT 0,100;"
                    );

                    if (!empty($sqlResult)) {
                        foreach ($sqlResult as $key => $value) {
                            $tempArray = array();

                            if (!empty($value['player_sid'])) {
                                $playerID = new SteamID($value['player_sid']);

                                $playerDBStatus = updateUserDetails($playerID->getSteamID64(), $api_key1);

                                $tempArray['player_sid32'] = $playerID->getSteamID32();
                                $tempArray['player_sid64'] = $playerID->getSteamID64();
                            } else {
                                $playerDBStatus = false;
                                $tempArray['player_sid32'] = 0;
                                $tempArray['player_sid64'] = 0;
                            }

                            $tempArray['hof_rank'] = ($key + 1);
                            $tempArray['hof_score1'] = $value['num_games'];
                            $tempArray['hof_score2'] = NULL;
                            $tempArray['hof_score3'] = NULL;

                            $sqlResult = $db->q(
                                'INSERT INTO `cron_hof`(
                                        `hof_id`,
                                        `player_sid32`,
                                        `player_sid64`,
                                        `hof_rank`,
                                        `hof_score1`,
                                        `hof_score2`,
                                        `hof_score3`,
                                        `date_updated`,
                                        `date_recorded`
                                    )
                                VALUES (?, ?, ?, ?, ?, ?, ?, NULL, NULL)
                                ON DUPLICATE KEY UPDATE
                                    `player_sid32` = VALUES(`player_sid32`),
                                    `player_sid64` = VALUES(`player_sid64`),
                                    `hof_score1` = VALUES(`hof_score1`),
                                    `hof_score2` = VALUES(`hof_score2`),
                                    `hof_score3` = VALUES(`hof_score3`),
                                    `date_updated` = NULL;',
                                'ississs',
                                array(
                                    1,
                                    $tempArray['player_sid32'],
                                    $tempArray['player_sid64'],
                                    ($key + 1),
                                    $tempArray['hof_score1'],
                                    $tempArray['hof_score2'],
                                    $tempArray['hof_score3'],
                                )
                            );

                            echo $sqlResult
                                ? "[SUCCESS] (" . $tempArray['player_sid64'] . ") Player HoF updated!"
                                : "[FAILURE] (" . $tempArray['player_sid64'] . ") Player HoF not updated!";

                            echo $playerDBStatus
                                ? " <strong>UPDATED</strong>"
                                : "";

                            echo '<br />';

                            unset($tempArray);
                            unset($playerDBStatus);
                            unset($sqlResult);
                        }
                    } else {
                        echo '[FAILURE] No players found to fill HoF<br />';
                    }

                    $time_end2 = time();
                    echo 'Running: ' . ($time_end2 - $time_start2) . " seconds<br /><br />";
                } catch (Exception $e) {
                    echo 'Caught Exception (HOF1) -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage() . '<br /><br />';
                }
            }

            //HOF2 - Kills
            {
                try {
                    $time_start2 = time();
                    echo '<h3>HoF2 - Kills</h3>';

                    $sqlResult = $db->q(
                        "SELECT
                                mmh.`player_sid32` as player_sid,
                                SUM(mmh.`hero_kills`) as num_kills
                            FROM `mod_match_heroes` mmh
                            GROUP BY mmh.`player_sid32`
                            ORDER BY num_kills DESC
                            LIMIT 0,100;"
                    );

                    if (!empty($sqlResult)) {
                        foreach ($sqlResult as $key => $value) {
                            $tempArray = array();

                            if (!empty($value['player_sid'])) {
                                $playerID = new SteamID($value['player_sid']);

                                $playerDBStatus = updateUserDetails($playerID->getSteamID64(), $api_key1);

                                $tempArray['player_sid32'] = $playerID->getSteamID32();
                                $tempArray['player_sid64'] = $playerID->getSteamID64();
                            } else {
                                $playerDBStatus = false;
                                $tempArray['player_sid32'] = 0;
                                $tempArray['player_sid64'] = 0;
                            }

                            $tempArray['hof_rank'] = ($key + 1);
                            $tempArray['hof_score1'] = $value['num_kills'];
                            $tempArray['hof_score2'] = NULL;
                            $tempArray['hof_score3'] = NULL;

                            $sqlResult = $db->q(
                                'INSERT INTO `cron_hof`(
                                        `hof_id`,
                                        `player_sid32`,
                                        `player_sid64`,
                                        `hof_rank`,
                                        `hof_score1`,
                                        `hof_score2`,
                                        `hof_score3`,
                                        `date_updated`,
                                        `date_recorded`
                                    )
                                VALUES (?, ?, ?, ?, ?, ?, ?, NULL, NULL)
                                ON DUPLICATE KEY UPDATE
                                    `player_sid32` = VALUES(`player_sid32`),
                                    `player_sid64` = VALUES(`player_sid64`),
                                    `hof_score1` = VALUES(`hof_score1`),
                                    `hof_score2` = VALUES(`hof_score2`),
                                    `hof_score3` = VALUES(`hof_score3`),
                                    `date_updated` = NULL;',
                                'ississs',
                                array(
                                    2,
                                    $tempArray['player_sid32'],
                                    $tempArray['player_sid64'],
                                    ($key + 1),
                                    $tempArray['hof_score1'],
                                    $tempArray['hof_score2'],
                                    $tempArray['hof_score3'],
                                )
                            );

                            echo $sqlResult
                                ? "[SUCCESS] (" . $tempArray['player_sid64'] . ") Player HoF updated!"
                                : "[FAILURE] (" . $tempArray['player_sid64'] . ") Player HoF not updated!";

                            echo $playerDBStatus
                                ? " <strong>UPDATED</strong>"
                                : "";

                            echo '<br />';

                            unset($tempArray);
                            unset($playerDBStatus);
                            unset($sqlResult);
                        }
                    } else {
                        echo '[FAILURE] No players found to fill HoF<br />';
                    }

                    $time_end2 = time();
                    echo 'Running: ' . ($time_end2 - $time_start2) . " seconds<br /><br />";
                } catch (Exception $e) {
                    echo 'Caught Exception (HOF2) -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage() . '<br /><br />';
                }
            }

            //HOF3 - Lobbies
            {
                try {
                    $time_start2 = time();
                    echo '<h3>HoF3 - Lobbies</h3>';

                    $sqlResult = $db->q(
                        "SELECT
                                `lobby_leader` as player_sid,
                                COUNT(*) as num_lobbies
                            FROM (
                                SELECT
                                    ll.`lobby_leader`,
                                    ll.`lobby_id`
                                FROM `lobby_list` ll
                                WHERE ll.`lobby_started` = 1
                            ) as t1
                            GROUP BY lobby_leader
                            ORDER BY num_lobbies DESC
                            LIMIT 0,100;"
                    );

                    if (!empty($sqlResult)) {
                        foreach ($sqlResult as $key => $value) {
                            $tempArray = array();

                            if (!empty($value['player_sid'])) {
                                $playerID = new SteamID($value['player_sid']);

                                $playerDBStatus = updateUserDetails($playerID->getSteamID64(), $api_key1);

                                $tempArray['player_sid32'] = $playerID->getSteamID32();
                                $tempArray['player_sid64'] = $playerID->getSteamID64();
                            } else {
                                $playerDBStatus = false;
                                $tempArray['player_sid32'] = 0;
                                $tempArray['player_sid64'] = 0;
                            }

                            $tempArray['hof_rank'] = ($key + 1);
                            $tempArray['hof_score1'] = $value['num_lobbies'];
                            $tempArray['hof_score2'] = NULL;
                            $tempArray['hof_score3'] = NULL;

                            $sqlResult = $db->q(
                                'INSERT INTO `cron_hof`(
                                        `hof_id`,
                                        `player_sid32`,
                                        `player_sid64`,
                                        `hof_rank`,
                                        `hof_score1`,
                                        `hof_score2`,
                                        `hof_score3`,
                                        `date_updated`,
                                        `date_recorded`
                                    )
                                VALUES (?, ?, ?, ?, ?, ?, ?, NULL, NULL)
                                ON DUPLICATE KEY UPDATE
                                    `player_sid32` = VALUES(`player_sid32`),
                                    `player_sid64` = VALUES(`player_sid64`),
                                    `hof_score1` = VALUES(`hof_score1`),
                                    `hof_score2` = VALUES(`hof_score2`),
                                    `hof_score3` = VALUES(`hof_score3`),
                                    `date_updated` = NULL;',
                                'ississs',
                                array(
                                    3,
                                    $tempArray['player_sid32'],
                                    $tempArray['player_sid64'],
                                    ($key + 1),
                                    $tempArray['hof_score1'],
                                    $tempArray['hof_score2'],
                                    $tempArray['hof_score3'],
                                )
                            );

                            echo $sqlResult
                                ? "[SUCCESS] (" . $tempArray['player_sid64'] . ") Player HoF updated!"
                                : "[FAILURE] (" . $tempArray['player_sid64'] . ") Player HoF not updated!";

                            echo $playerDBStatus
                                ? " <strong>UPDATED</strong>"
                                : "";

                            echo '<br />';

                            unset($tempArray);
                            unset($playerDBStatus);
                            unset($sqlResult);
                        }
                    } else {
                        echo '[FAILURE] No players found to fill HoF<br />';
                    }

                    $time_end2 = time();
                    echo 'Running: ' . ($time_end2 - $time_start2) . " seconds<br /><br />";
                } catch (Exception $e) {
                    echo 'Caught Exception (HOF3) -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage() . '<br /><br />';
                }
            }

            //HOF4 - Wins
            {
                try {
                    $time_start2 = time();
                    echo '<h3>HoF4 - Wins</h3>';

                    $sqlResult = $db->q(
                        "SELECT
                                player_sid32 as player_sid,
                                SUM(hero_won) as num_wins,
                                COUNT(hero_won) as num_games
                            FROM `mod_match_heroes`
                            GROUP BY player_sid32
                            ORDER BY num_wins DESC
                            LIMIT 0,100;"
                    );

                    if (!empty($sqlResult)) {
                        foreach ($sqlResult as $key => $value) {
                            $tempArray = array();

                            if (!empty($value['player_sid'])) {
                                $playerID = new SteamID($value['player_sid']);

                                $playerDBStatus = updateUserDetails($playerID->getSteamID64(), $api_key1);

                                $tempArray['player_sid32'] = $playerID->getSteamID32();
                                $tempArray['player_sid64'] = $playerID->getSteamID64();
                            } else {
                                $playerDBStatus = false;
                                $tempArray['player_sid32'] = 0;
                                $tempArray['player_sid64'] = 0;
                            }

                            $tempArray['hof_rank'] = ($key + 1);
                            $tempArray['hof_score1'] = $value['num_wins'];
                            $tempArray['hof_score2'] = $value['num_games'];
                            $tempArray['hof_score3'] = NULL;

                            $sqlResult = $db->q(
                                'INSERT INTO `cron_hof`(
                                        `hof_id`,
                                        `player_sid32`,
                                        `player_sid64`,
                                        `hof_rank`,
                                        `hof_score1`,
                                        `hof_score2`,
                                        `hof_score3`,
                                        `date_updated`,
                                        `date_recorded`
                                    )
                                VALUES (?, ?, ?, ?, ?, ?, ?, NULL, NULL)
                                ON DUPLICATE KEY UPDATE
                                    `player_sid32` = VALUES(`player_sid32`),
                                    `player_sid64` = VALUES(`player_sid64`),
                                    `hof_score1` = VALUES(`hof_score1`),
                                    `hof_score2` = VALUES(`hof_score2`),
                                    `hof_score3` = VALUES(`hof_score3`),
                                    `date_updated` = NULL;',
                                'ississs',
                                array(
                                    4,
                                    $tempArray['player_sid32'],
                                    $tempArray['player_sid64'],
                                    ($key + 1),
                                    $tempArray['hof_score1'],
                                    $tempArray['hof_score2'],
                                    $tempArray['hof_score3'],
                                )
                            );

                            echo $sqlResult
                                ? "[SUCCESS] (" . $tempArray['player_sid64'] . ") Player HoF updated!"
                                : "[FAILURE] (" . $tempArray['player_sid64'] . ") Player HoF not updated!";

                            echo $playerDBStatus
                                ? " <strong>UPDATED</strong>"
                                : "";

                            echo '<br />';

                            unset($tempArray);
                            unset($playerDBStatus);
                            unset($sqlResult);
                        }
                    } else {
                        echo '[FAILURE] No players found to fill HoF<br />';
                    }

                    $time_end2 = time();
                    echo 'Running: ' . ($time_end2 - $time_start2) . " seconds<br /><br />";
                } catch (Exception $e) {
                    echo 'Caught Exception (HOF4) -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage() . '<br /><br />';
                }
            }

            $time_end1 = time();
            echo 'Total Running: ' . ($time_end1 - $time_start1) . " seconds<br /><br />";
            echo '<hr />';
        } catch (Exception $e) {
            echo 'Caught Exception (HOF) -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage() . '<br /><br />';
        }
    }

} catch (Exception $e) {
    echo 'Caught Exception (MAIN) -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage() . '<br /><br />';
} finally {
    if (isset($memcache)) $memcache->close();
}