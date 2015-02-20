<?php
try {
    require_once('../../global_functions.php');
    require_once('../../connections/parameters.php');

    if (!isset($_SESSION)) {
        session_start();
    }

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    checkLogin_v2();
    if (empty($_SESSION['user_id64'])) throw new Exception('Not logged in!');

    $adminCheck = adminCheck($_SESSION['user_id64'], 'admin');
    if (empty($adminCheck)) throw new Exception('Not an admin!');


    $badHighScorers = $db->q("SELECT * FROM `cron_hs`;"); //" WHERE `user_name` = 'Unknown';");
    $webAPI = new steam_webapi($api_key1);
    $steamID = new SteamID();

    if (!empty($badHighScorers)) {
        foreach ($badHighScorers as $key => $value) {
            if (!empty($value['user_id32'])) {
                $steamID->setSteamID($value['user_id32']);

                $mg_lb_user_details = $memcache->get('mg_lb_user_details_' . $steamID->getSteamID64());
                if (!$mg_lb_user_details) {
                    sleep(0.5);
                    $mg_lb_user_details_temp = $webAPI->GetPlayerSummariesV2($steamID->getSteamID64());

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

                $sqlResult = $db->q(
                    'UPDATE `stat_highscore` SET `user_name` = ? WHERE `user_id32` = ?;',
                    'ss',
                    array($mg_lb_user_details[0]['user_name'], $value['user_id32'])
                );

                if ($sqlResult) {
                    echo "[SUCCESS] Updated name for [" . $steamID->getSteamID64() . "]!<br />";
                }

                flush();
            }
        }
    } else {
        echo bootstrapMessage('Oh Snap', 'No bad leaderboard entries!', 'danger');
    }

    $memcache->close();
} catch (Exception $e) {
    $message = 'Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage();
    echo bootstrapMessage('Oh Snap', $message, 'danger');
}