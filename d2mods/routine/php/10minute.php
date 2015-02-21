#!/usr/bin/php -q
<?php
require_once('../../functions.php');
require_once('../../../global_functions.php');
require_once('../../../connections/parameters.php');

try {
    $db = new dbWrapper_v3($hostname_gds_cron, $username_gds_cron, $password_gds_cron, $database_gds_cron, true);

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    if ($db) {
        $steamID = new SteamID();
        $steamWebAPI = new steam_webapi($api_key1);

        //Mini Game Leaderboard
        {
            try {
                $time_start1 = time();
                echo '<h2>Mini Game Leaderboard</h2>';

                $db->q("DROP TABLE IF EXISTS `cron_hs_temp`;");

                $leaderboards = $db->q(
                    'SELECT
                        DISTINCT sh.`minigameID`,
                        sh.`leaderboard`,
                        shm.`minigameName`,
                        shm.`minigameActive`,
                        shm.`minigameObjective`
                    FROM `stat_highscore` sh
                    JOIN `stat_highscore_minigames` shm ON sh.`minigameID` = shm.`minigameID`;'
                );

                if (!empty($leaderboards)) {
                    $sqlResult = $db->q(
                        "CREATE TABLE IF NOT EXISTS `cron_hs_temp` (
                          `minigameID` varchar(255) NOT NULL,
                          `leaderboard` varchar(255) NOT NULL,
                          `user_name` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
                          `user_id32` bigint(255) NOT NULL,
                          `highscore_value` bigint(255) NOT NULL,
                          `date_recorded` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
                          INDEX `id_hs_lb` (`minigameID`, `leaderboard`, `date_recorded`),
                          INDEX `highscore_value` (`highscore_value`),
                          INDEX `date_recorded` (`date_recorded`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=latin1;"
                    );

                    foreach ($leaderboards as $key => $value) {
                        $minigameID = $value['minigameID'];
                        $leaderboard = $value['leaderboard'];
                        $mgName = $value['minigameName'];

                        $mgObjective1 = !empty($value['minigameObjective']) && $value['minigameObjective'] == 'min'
                            ? 'MIN'
                            : 'MAX';

                        $mgObjective2 = !empty($value['minigameObjective']) && $value['minigameObjective'] == 'min'
                            ? 'ASC'
                            : 'DESC';

                        $sqlResult = $db->q(
                            "INSERT INTO `cron_hs_temp`
                            SELECT
                              `minigameID`,
                              `leaderboard`,
                              `user_name`,
                              `user_id32`,
                              $mgObjective1(`highscore_value`) as `highscore_value`,
                              `date_recorded`
                            FROM `stat_highscore`
                            WHERE `minigameID` = ? AND `leaderboard` = ?
                            GROUP BY `minigameID`, `leaderboard`, `user_id32`
                            ORDER BY `minigameID`, `leaderboard`, `highscore_value` $mgObjective2
                            LIMIT 0,20;",
                            'ss',
                            array($minigameID, $leaderboard)
                        );

                        echo $sqlResult
                            ? "[SUCCESS] Gathered High Scores for: $mgName [$leaderboard]!<br />"
                            : "[FAILURE] Gathered High Scores for: $mgName [$leaderboard]!<br />";
                    }

                    $sqlResult = $db->q(
                        'SELECT * FROM `cron_hs_temp`;'
                    );

                    if (!empty($sqlResult)) {
                        $db->q(
                            "CREATE TABLE IF NOT EXISTS `cron_hs` (
                              `minigameID` varchar(255) NOT NULL,
                              `leaderboard` varchar(255) NOT NULL,
                              `user_name` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
                              `user_id32` bigint(255) NOT NULL,
                              `highscore_value` bigint(255) NOT NULL,
                              `date_recorded` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
                              INDEX `id_hs_lb` (`minigameID`, `leaderboard`, `highscore_value`),
                              INDEX `highscore_value` (`highscore_value`),
                              INDEX `date_recorded` (`date_recorded`)
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

                $cron_hs = $db->q(
                    'SELECT * FROM cron_hs;'
                );

                if (!empty($cron_hs)) {
                    foreach ($cron_hs as $key => $value) {
                        if (!empty($value['user_id32'])) {
                            $steamID->setSteamID($value['user_id32']);

                            $mg_lb_user_details = $memcache->get('mg_lb_user_details_' . $steamID->getSteamID64());
                            if (!$mg_lb_user_details) {
                                sleep(0.5);
                                $mg_lb_user_details_temp = $steamWebAPI->GetPlayerSummariesV2($steamID->getSteamID64());

                                if (!empty($mg_lb_user_details_temp)) {
                                    $mg_lb_user_details[0]['user_id64'] = $steamID->getSteamID64();
                                    $mg_lb_user_details[0]['user_id32'] = $steamID->getSteamID32();
                                    $mg_lb_user_details[0]['user_name'] = htmlentities($mg_lb_user_details_temp['response']['players'][0]['personaname']);
                                    $mg_lb_user_details[0]['user_avatar'] = $mg_lb_user_details_temp['response']['players'][0]['avatar'];
                                    $mg_lb_user_details[0]['user_avatar_medium'] = $mg_lb_user_details_temp['response']['players'][0]['avatarmedium'];
                                    $mg_lb_user_details[0]['user_avatar_large'] = $mg_lb_user_details_temp['response']['players'][0]['avatarfull'];
                                    $memcache->set('mg_lb_user_details_' . $steamID->getSteamID64(), $mg_lb_user_details, 0, 10 * 60);
                                }
                            }

                            if (!empty($mg_lb_user_details)) {
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
                                        $mg_lb_user_details[0]['user_id64'],
                                        $mg_lb_user_details[0]['user_id32'],
                                        $mg_lb_user_details[0]['user_name'],
                                        $mg_lb_user_details[0]['user_avatar'],
                                        $mg_lb_user_details[0]['user_avatar_medium'],
                                        $mg_lb_user_details[0]['user_avatar_large']
                                    )
                                );
                            }
                        }
                    }
                } else {
                    echo 'No users in HoF to test for account<br />';
                }
                unset($cron_hs);

                unset($sqlResult);

                $time_end1 = time();
                echo 'Total Running: ' . ($time_end1 - $time_start1) . " seconds<br /><br />";
                echo '<hr />';
            } catch (Exception $e) {
                echo 'Caught Exception (MINI-GAMES) -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage() . '<br /><br />';
            }
        }
    }


    $memcache->close();
} catch (Exception $e) {
    echo 'Caught Exception (MAIN) -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage() . '<br /><br />';
}