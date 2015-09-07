#!/usr/bin/php -q
<?php
require_once('../../../global_functions.php');
require_once('../../../connections/parameters.php');

try {
    $db = new dbWrapper_v3($hostname_gds_cron, $username_gds_cron, $password_gds_cron, $database_gds_cron, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    $steamID = new SteamID();
    $steamWebAPI = new steam_webapi($api_key1);

    $updateUsers = true; //CHANGE THIS WHEN NOT TESTING

    //Mod Leaderboards
    {
        try {
            $time_start1 = time();
            echo '<h2>Mod Leaderboards</h2>';

            $db->q("DROP TABLE IF EXISTS `cron_hs_mod_temp`;");

            $leaderboards = $db->q(
                'SELECT
                    DISTINCT sh.`modID`,
                    sh.`highscoreID`,
                    shm.`highscoreName`,
                    shm.`highscoreActive`,
                    shm.`highscoreObjective`
                FROM `stat_highscore_mods` sh
                JOIN `stat_highscore_mods_schema` shm ON sh.`highscoreID` = shm.`highscoreID`;'
            );

            if (!empty($leaderboards)) {
                $sqlResult = $db->q(
                    "CREATE TABLE IF NOT EXISTS `cron_hs_mod_temp` (
                      `modID` varchar(255) NOT NULL,
                      `highscoreID` varchar(255) NOT NULL,
                      `userName` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
                      `steamID32` bigint(255) NOT NULL,
                      `highscoreValue` bigint(255) NOT NULL,
                      `date_recorded` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
                      INDEX `id_hs_lb` (`modID`, `highscoreID`, `date_recorded`),
                      INDEX `highscoreValue` (`highscoreValue`),
                      INDEX `date_recorded` (`date_recorded`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;"
                );

                foreach ($leaderboards as $key => $value) {
                    $modID = $value['modID'];
                    $highscoreID = $value['highscoreID'];
                    $highscoreName = $value['highscoreName'];

                    $mgObjective1 = !empty($value['highscoreObjective']) && $value['highscoreObjective'] == 'min'
                        ? 'MIN'
                        : 'MAX';

                    $mgObjective2 = !empty($value['highscoreObjective']) && $value['highscoreObjective'] == 'min'
                        ? 'ASC'
                        : 'DESC';

                    $sqlResult = $db->q(
                        "INSERT INTO `cron_hs_mod_temp`
                            SELECT
                                    sh1.`modID`,
                                    sh1.`highscoreID`,
                                    sh1.`userName`,
                                    sh1.`steamID32`,
                                    sh1.`highscoreValue`,
                                    sh1.`date_recorded`
                                FROM `stat_highscore_mods` sh1
                                JOIN (
                                    SELECT
                                        `steamID32`,
                                        $mgObjective1(`highscoreValue`) as `adjusted_value`
                                    FROM `stat_highscore_mods`
                                    WHERE `modID` = ? AND `highscoreID` = ?
                                    GROUP BY `modID`, `highscoreID`, `steamID32`
                                    ORDER BY `modID`, `highscoreID`, `adjusted_value` $mgObjective2
                                    LIMIT 0,20
                                ) sh2 ON sh2.`steamID32` = sh1.`steamID32` AND sh2.`adjusted_value` = sh1.`highscoreValue` 
                                WHERE `modID` = ? AND `highscoreID` = ?
                                GROUP BY sh1.`steamID32`
                                ORDER BY sh1.`modID`, sh1.`highscoreID`, sh1.`highscoreValue` $mgObjective2, sh1.`date_recorded`;",
                        'ssss',
                        array($modID, $highscoreID, $modID, $highscoreID)
                    );

                    echo $sqlResult
                        ? "[SUCCESS] Gathered High Scores for: $modID [$highscoreID - $highscoreName]!<br />"
                        : "[FAILURE] Gathered High Scores for: $modID [$highscoreID - $highscoreName]!<br />";
                }

                $sqlResult = $db->q(
                    'SELECT * FROM `cron_hs_mod_temp`;'
                );

                if (!empty($sqlResult)) {
                    $db->q(
                        "CREATE TABLE IF NOT EXISTS `cron_hs_mod` (
                          `modID` varchar(255) NOT NULL,
                          `highscoreID` varchar(255) NOT NULL,
                          `userName` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
                          `steamID32` bigint(255) NOT NULL,
                          `highscoreValue` bigint(255) NOT NULL,
                          `date_recorded` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
                          INDEX `id_hs_lb` (`modID`, `highscoreID`, `date_recorded`),
                          INDEX `highscoreValue` (`highscoreValue`),
                          INDEX `date_recorded` (`date_recorded`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=latin1;"
                    );

                    $db->q(
                        "TRUNCATE TABLE `cron_hs_mod`;"
                    );

                    $sqlResult = $db->q(
                        "INSERT INTO `cron_hs_mod`
                            SELECT * FROM `cron_hs_mod_temp`;"
                    );

                    echo $sqlResult
                        ? "[SUCCESS] Final table populated!<br />"
                        : "[FAILURE] Final table not populated! 3<br />";

                    $db->q("DROP TABLE IF EXISTS `cron_hs_mod_temp`;");
                } else {
                    echo "[FAILURE] Final table not populated! 2<br />";
                }
            } else {
                echo "[FAILURE] Final table not populated! 1<br />";;
            }

            if ($updateUsers) {
                unset($cron_hs);
                $cron_hs = $db->q(
                    'SELECT * FROM cron_hs_mod;'
                );

                if (!empty($cron_hs)) {
                    foreach ($cron_hs as $key => $value) {
                        if (!empty($value['steamID32'])) {
                            $steamID->setSteamID($value['steamID32']);

                            $mg_lb_user_details = $memcache->get('mg_lb_user_details_' . $steamID->getSteamID64());
                            if (!$mg_lb_user_details) {
                                $mg_lb_user_details_temp = $steamWebAPI->GetPlayerSummariesV2($steamID->getSteamID64());

                                if (!empty($mg_lb_user_details_temp) && !empty($mg_lb_user_details_temp['response']['players'])) {
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
            }

            unset($sqlResult);

            $time_end1 = time();
            echo 'Total Running: ' . ($time_end1 - $time_start1) . " seconds<br /><br />";
            echo '<hr />';
        } catch (Exception $e) {
            echo 'Caught Exception (mods) -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage() . '<br /><br />';
        }
    }


    $memcache->close();
} catch (Exception $e) {
    echo 'Caught Exception (MAIN) -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage() . '<br /><br />';
}