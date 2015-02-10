#!/usr/bin/php -q
<?php
require_once('../../functions.php');
require_once('../../../global_functions.php');
require_once('../../../connections/parameters.php');

$db = new dbWrapper_v3($hostname_gds_cron, $username_gds_cron, $password_gds_cron, $database_gds_cron, true);

if ($db) {
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
                  COUNT(*) AS numPicks
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
                  PRIMARY KEY (`mod_id`,`player_hero_id`)
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

            //HOF1 - Connects
            {
                try {
                    $time_start2 = time();
                    echo '<h3>HoF1 - Connects</h3>';

                    $db->q("DROP TABLE IF EXISTS `cron_hof1_temp`;");

                    $sqlResult = $db->q(
                        "CREATE TABLE IF NOT EXISTS `cron_hof1_temp`
                            SELECT
                                mmp.`player_sid32`,
                                COUNT(*) as num_games
                            FROM `mod_match_players` mmp
                            WHERE mmp.`connection_status` = 2
                            GROUP BY mmp.`player_sid32`
                            ORDER BY num_games DESC
                            LIMIT 0,100;"
                    );

                    echo $sqlResult
                        ? "[SUCCESS] Table created!<br />"
                        : "[FAILURE] Table not created!<br />";

                    if ($sqlResult) {
                        $db->q(
                            "CREATE TABLE IF NOT EXISTS `cron_hof1` (
                              `player_sid32` bigint(255) NOT NULL,
                              `num_games` bigint(21) NOT NULL DEFAULT '0',
                              INDEX `player_sid32` (`player_sid32`),
                              INDEX `num_games` (`num_games`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=latin1;"
                        );

                        $db->q(
                            "TRUNCATE TABLE `cron_hof1`;"
                        );

                        $sqlResult = $db->q(
                            "INSERT INTO `cron_hof1`
                                SELECT * FROM `cron_hof1_temp`;"
                        );

                        echo $sqlResult
                            ? "[SUCCESS] Final table populated!<br />"
                            : "[FAILURE] Final table not populated!<br />";

                        $db->q("DROP TABLE IF EXISTS `cron_hof1_temp`;");
                    }

                    $hof_test = $db->q(
                        'SELECT * FROM cron_hof1;'
                    );

                    if (!empty($hof_test)) {
                        foreach ($hof_test as $key => $value) {
                            if ($value['player_sid32'] != 0) {
                                /*$hof1_user_details = $db->q(
                                    'SELECT
                                            `user_id64`,
                                            `user_id32`,
                                            `user_name`,
                                            `user_avatar`,
                                            `user_avatar_medium`,
                                            `user_avatar_large`
                                    FROM `gds_users`
                                    WHERE `user_id32` = ?
                                    LIMIT 0,1;',
                                    's',
                                    $value['player_sid32']
                                );*/

                                if (empty($hof1_user_details)) {
                                    sleep(0.5);
                                    $steamID->setSteamID($value['player_sid32']);
                                    $hof1_user_details_temp = $steamWebAPI->GetPlayerSummariesV2($steamID->getSteamID64());

                                    if (!empty($hof1_user_details_temp)) {
                                        $hof1_user_details[0]['user_id64'] = $steamID->getSteamID64();
                                        $hof1_user_details[0]['user_id32'] = $steamID->getSteamID32();
                                        $hof1_user_details[0]['user_name'] = htmlentities($hof1_user_details_temp['response']['players'][0]['personaname']);
                                        $hof1_user_details[0]['user_avatar'] = $hof1_user_details_temp['response']['players'][0]['avatar'];
                                        $hof1_user_details[0]['user_avatar_medium'] = $hof1_user_details_temp['response']['players'][0]['avatarmedium'];
                                        $hof1_user_details[0]['user_avatar_large'] = $hof1_user_details_temp['response']['players'][0]['avatarfull'];


                                        $db->q(
                                            'INSERT INTO `gds_users`
                                                (`user_id64`, `user_id32`, `user_name`, `user_avatar`, `user_avatar_medium`, `user_avatar_large`)
                                                VALUES (?, ?, ?, ?, ?, ?)
                                                ON DUPLICATE KEY UPDATE
                                                  `user_name` = VALUES(`user_name`),
                                                  `user_avatar` = VALUES(`user_avatar`),
                                                  `user_avatar_medium` = VALUES(`user_avatar_medium`),
                                                  `user_avatar_large` = VALUES(`user_avatar_large`);',
                                            'ssssss',
                                            array(
                                                $hof1_user_details[0]['user_id64'],
                                                $hof1_user_details[0]['user_id32'],
                                                $hof1_user_details[0]['user_name'],
                                                $hof1_user_details[0]['user_avatar'],
                                                $hof1_user_details[0]['user_avatar_medium'],
                                                $hof1_user_details[0]['user_avatar_large']
                                            )
                                        );

                                        unset($hof1_user_details_temp);
                                        unset($hof1_user_details);
                                    }
                                }
                            }
                        }
                    } else {
                        echo 'No users in HoF to test for account<br />';
                    }

                    unset($hof_test);

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

                    $db->q("DROP TABLE IF EXISTS `cron_hof2_temp`;");

                    $sqlResult = $db->q(
                        "CREATE TABLE IF NOT EXISTS `cron_hof2_temp`
                            SELECT
                                mmh.`player_sid32`,
                                SUM(mmh.`hero_kills`) as num_kills
                            FROM `mod_match_heroes` mmh
                            GROUP BY mmh.`player_sid32`
                            ORDER BY num_kills DESC
                            LIMIT 0,100;"
                    );

                    echo $sqlResult
                        ? "[SUCCESS] Table created!<br />"
                        : "[FAILURE] Table not created!<br />";

                    if ($sqlResult) {
                        $db->q(
                            "CREATE TABLE IF NOT EXISTS `cron_hof2` (
                              `player_sid32` bigint(255) NOT NULL,
                              `num_kills` decimal(65,0) NOT NULL DEFAULT '0',
                              INDEX `player_sid32` (`player_sid32`),
                              INDEX `num_kills` (`num_kills`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=latin1;"
                        );

                        $db->q(
                            "TRUNCATE TABLE `cron_hof2`;"
                        );

                        $sqlResult = $db->q(
                            "INSERT INTO `cron_hof2`
                                SELECT * FROM `cron_hof2_temp`;"
                        );

                        echo $sqlResult
                            ? "[SUCCESS] Final table populated!<br />"
                            : "[FAILURE] Final table not populated!<br />";

                        $db->q("DROP TABLE IF EXISTS `cron_hof2_temp`;");
                    }

                    $hof_test = $db->q(
                        'SELECT * FROM cron_hof2;'
                    );

                    if (!empty($hof_test)) {
                        foreach ($hof_test as $key => $value) {
                            if ($value['player_sid32'] != 0) {
                                /*$hof2_user_details = $db->q(
                                    'SELECT
                                            `user_id64`,
                                            `user_id32`,
                                            `user_name`,
                                            `user_avatar`,
                                            `user_avatar_medium`,
                                            `user_avatar_large`
                                    FROM `gds_users`
                                    WHERE `user_id32` = ?
                                    LIMIT 0,1;',
                                    's',
                                    $value['player_sid32']
                                );*/

                                if (empty($hof2_user_details)) {
                                    sleep(0.5);
                                    $steamID->setSteamID($value['player_sid32']);
                                    $hof2_user_details_temp = $steamWebAPI->GetPlayerSummariesV2($steamID->getSteamID64());

                                    if (!empty($hof2_user_details_temp)) {
                                        $hof2_user_details[0]['user_id64'] = $steamID->getSteamID64();
                                        $hof2_user_details[0]['user_id32'] = $steamID->getSteamID32();
                                        $hof2_user_details[0]['user_name'] = htmlentities($hof2_user_details_temp['response']['players'][0]['personaname']);
                                        $hof2_user_details[0]['user_avatar'] = $hof2_user_details_temp['response']['players'][0]['avatar'];
                                        $hof2_user_details[0]['user_avatar_medium'] = $hof2_user_details_temp['response']['players'][0]['avatarmedium'];
                                        $hof2_user_details[0]['user_avatar_large'] = $hof2_user_details_temp['response']['players'][0]['avatarfull'];


                                        $db->q(
                                            'INSERT INTO `gds_users`
                                                (`user_id64`, `user_id32`, `user_name`, `user_avatar`, `user_avatar_medium`, `user_avatar_large`)
                                                VALUES (?, ?, ?, ?, ?, ?)
                                                ON DUPLICATE KEY UPDATE
                                                  `user_name` = VALUES(`user_name`),
                                                  `user_avatar` = VALUES(`user_avatar`),
                                                  `user_avatar_medium` = VALUES(`user_avatar_medium`),
                                                  `user_avatar_large` = VALUES(`user_avatar_large`);',
                                            'ssssss',
                                            array(
                                                $hof2_user_details[0]['user_id64'],
                                                $hof2_user_details[0]['user_id32'],
                                                $hof2_user_details[0]['user_name'],
                                                $hof2_user_details[0]['user_avatar'],
                                                $hof2_user_details[0]['user_avatar_medium'],
                                                $hof2_user_details[0]['user_avatar_large']
                                            )
                                        );

                                        unset($hof2_user_details);
                                        unset($hof2_user_details_temp);
                                    }
                                }
                            }
                        }
                    } else {
                        echo 'No users in HoF to test for account<br />';
                    }

                    unset($hof_test);

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

                    $db->q("DROP TABLE IF EXISTS `cron_hof3_temp`;");

                    $sqlResult = $db->q(
                        "CREATE TABLE IF NOT EXISTS `cron_hof3_temp`
                            SELECT
                                `player_sid64`,
                                COUNT(*) as num_lobbies
                            FROM (
                                SELECT
                                    ll.`lobby_leader` as player_sid64,
                                    ll.`lobby_id`
                                FROM `lobby_list` ll
                                WHERE ll.`lobby_started` = 1
                            ) as t1
                            GROUP BY player_sid64
                            ORDER BY num_lobbies DESC
                            LIMIT 0,100;"
                    );

                    echo $sqlResult
                        ? "[SUCCESS] Table created!<br />"
                        : "[FAILURE] Table not created!<br />";

                    if ($sqlResult) {
                        $db->q(
                            "CREATE TABLE IF NOT EXISTS `cron_hof3` (
                              `player_sid64` bigint(255) NOT NULL,
                              `num_lobbies` bigint(21) NOT NULL DEFAULT '0',
                              INDEX `player_sid64` (`player_sid64`),
                              INDEX `num_lobbies` (`num_lobbies`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=latin1;"
                        );

                        $db->q(
                            "TRUNCATE TABLE `cron_hof3`;"
                        );

                        $sqlResult = $db->q(
                            "INSERT INTO `cron_hof3`
                                SELECT * FROM `cron_hof3_temp`;"
                        );

                        echo $sqlResult
                            ? "[SUCCESS] Final table populated!<br />"
                            : "[FAILURE] Final table not populated!<br />";

                        $db->q("DROP TABLE IF EXISTS `cron_hof3_temp`;");
                    }

                    $hof_test = $db->q(
                        'SELECT * FROM cron_hof3;'
                    );

                    if (!empty($hof_test)) {
                        foreach ($hof_test as $key => $value) {
                            if ($value['player_sid64'] != 0) {
                                /*$hof3_user_details = $db->q(
                                    'SELECT
                                            `user_id64`,
                                            `user_id32`,
                                            `user_name`,
                                            `user_avatar`,
                                            `user_avatar_medium`,
                                            `user_avatar_large`
                                    FROM `gds_users`
                                    WHERE `user_id64` = ?
                                    LIMIT 0,1;',
                                    's',
                                    $value['player_sid64']
                                );*/

                                if (empty($hof3_user_details)) {
                                    sleep(0.5);
                                    $steamID->setSteamID($value['player_sid64']);
                                    $hof3_user_details_temp = $steamWebAPI->GetPlayerSummariesV2($steamID->getSteamID64());

                                    if (!empty($hof3_user_details_temp)) {
                                        $hof3_user_details[0]['user_id64'] = $steamID->getSteamID64();
                                        $hof3_user_details[0]['user_id32'] = $steamID->getSteamID32();
                                        $hof3_user_details[0]['user_name'] = htmlentities($hof3_user_details_temp['response']['players'][0]['personaname']);
                                        $hof3_user_details[0]['user_avatar'] = $hof3_user_details_temp['response']['players'][0]['avatar'];
                                        $hof3_user_details[0]['user_avatar_medium'] = $hof3_user_details_temp['response']['players'][0]['avatarmedium'];
                                        $hof3_user_details[0]['user_avatar_large'] = $hof3_user_details_temp['response']['players'][0]['avatarfull'];


                                        $db->q(
                                            'INSERT INTO `gds_users`
                                                (`user_id64`, `user_id32`, `user_name`, `user_avatar`, `user_avatar_medium`, `user_avatar_large`)
                                                VALUES (?, ?, ?, ?, ?, ?)
                                                ON DUPLICATE KEY UPDATE
                                                  `user_name` = VALUES(`user_name`),
                                                  `user_avatar` = VALUES(`user_avatar`),
                                                  `user_avatar_medium` = VALUES(`user_avatar_medium`),
                                                  `user_avatar_large` = VALUES(`user_avatar_large`);',
                                            'ssssss',
                                            array(
                                                $hof3_user_details[0]['user_id64'],
                                                $hof3_user_details[0]['user_id32'],
                                                $hof3_user_details[0]['user_name'],
                                                $hof3_user_details[0]['user_avatar'],
                                                $hof3_user_details[0]['user_avatar_medium'],
                                                $hof3_user_details[0]['user_avatar_large']
                                            )
                                        );
                                    }
                                }
                            }
                        }
                    } else {
                        echo 'No users in HoF to test for account<br />';
                    }

                    unset($hof_test);

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

                    $db->q("DROP TABLE IF EXISTS `cron_hof4_temp`;");

                    $sqlResult = $db->q(
                        "CREATE TABLE IF NOT EXISTS `cron_hof4_temp`
                            SELECT
                                player_sid32,
                                SUM(hero_won) as num_wins,
                                COUNT(hero_won) as num_games
                            FROM `mod_match_heroes`
                            GROUP BY player_sid32
                            ORDER BY num_wins DESC
                            LIMIT 0,100;"
                    );

                    echo $sqlResult
                        ? "[SUCCESS] Table created!<br />"
                        : "[FAILURE] Table not created!<br />";

                    if ($sqlResult) {
                        $db->q(
                            "CREATE TABLE IF NOT EXISTS `cron_hof4` (
                              `player_sid32` bigint(255) NOT NULL,
                              `num_wins` decimal(25,0) DEFAULT NULL,
                              `num_games` bigint(21) NOT NULL DEFAULT '0',
                              INDEX `player_sid32` (`player_sid32`),
                              INDEX `num_wins` (`num_wins`),
                              INDEX `num_games` (`num_games`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=latin1;"
                        );

                        $db->q(
                            "TRUNCATE TABLE `cron_hof4`;"
                        );

                        $sqlResult = $db->q(
                            "INSERT INTO `cron_hof4`
                                SELECT * FROM `cron_hof4_temp`;"
                        );

                        echo $sqlResult
                            ? "[SUCCESS] Final table populated!<br />"
                            : "[FAILURE] Final table not populated!<br />";

                        $db->q("DROP TABLE IF EXISTS `cron_hof4_temp`;");
                    }

                    $hof_test = $db->q(
                        'SELECT * FROM cron_hof4;'
                    );

                    if (!empty($hof_test)) {
                        foreach ($hof_test as $key => $value) {
                            if ($value['player_sid32'] != 0) {
                                /*$hof4_user_details = $db->q(
                                    'SELECT
                                            `user_id64`,
                                            `user_id32`,
                                            `user_name`,
                                            `user_avatar`,
                                            `user_avatar_medium`,
                                            `user_avatar_large`
                                    FROM `gds_users`
                                    WHERE `user_id32` = ?
                                    LIMIT 0,1;',
                                    's',
                                    $value['player_sid32']
                                );*/

                                if (empty($hof4_user_details)) {
                                    sleep(0.5);
                                    $steamID->setSteamID($value['player_sid32']);
                                    $hof4_user_details_temp = $steamWebAPI->GetPlayerSummariesV2($steamID->getSteamID64());

                                    if (!empty($hof4_user_details_temp)) {
                                        $hof4_user_details[0]['user_id64'] = $steamID->getSteamID64();
                                        $hof4_user_details[0]['user_id32'] = $steamID->getSteamID32();
                                        $hof4_user_details[0]['user_name'] = htmlentities($hof4_user_details_temp['response']['players'][0]['personaname']);
                                        $hof4_user_details[0]['user_avatar'] = $hof4_user_details_temp['response']['players'][0]['avatar'];
                                        $hof4_user_details[0]['user_avatar_medium'] = $hof4_user_details_temp['response']['players'][0]['avatarmedium'];
                                        $hof4_user_details[0]['user_avatar_large'] = $hof4_user_details_temp['response']['players'][0]['avatarfull'];


                                        $db->q(
                                            'INSERT INTO `gds_users`
                                                (`user_id64`, `user_id32`, `user_name`, `user_avatar`, `user_avatar_medium`, `user_avatar_large`)
                                                VALUES (?, ?, ?, ?, ?, ?)
                                                ON DUPLICATE KEY UPDATE
                                                  `user_name` = VALUES(`user_name`),
                                                  `user_avatar` = VALUES(`user_avatar`),
                                                  `user_avatar_medium` = VALUES(`user_avatar_medium`),
                                                  `user_avatar_large` = VALUES(`user_avatar_large`);',
                                            'ssssss',
                                            array(
                                                $hof4_user_details[0]['user_id64'],
                                                $hof4_user_details[0]['user_id32'],
                                                $hof4_user_details[0]['user_name'],
                                                $hof4_user_details[0]['user_avatar'],
                                                $hof4_user_details[0]['user_avatar_medium'],
                                                $hof4_user_details[0]['user_avatar_large']
                                            )
                                        );
                                    }
                                }
                            }
                        }
                    } else {
                        echo 'No users in HoF to test for account<br />';
                    }
                    unset($hof_test);

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
}