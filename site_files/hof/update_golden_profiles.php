<?php
require_once('./functions.php');
require_once('../global_functions.php');
require_once('../connections/parameters.php');

if (!isset($_SESSION)) {
    session_start();
}

try {
    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    $db->q('SET NAMES utf8;');

    if ($db) {
        $memcache = new Memcache;
        $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"
        $steamWebAPI = new steam_webapi($api_key1);

        $hofDetails = $db->q(
            'SELECT
                  hof_gp.`auction_rank`,
                  hof_gp.`user_id64`,
                  hof_gp.`user_id32`
                FROM `hof_golden_profiles` hof_gp
                WHERE `isParsed` = 0
                ORDER BY auction_rank ASC;'
        );

        if (!empty($hofDetails)) {
            foreach ($hofDetails as $key => $value) {
                if (!empty($value['user_id64']) && $value['user_id64'] != '-1') {
                    echo 'Updating: ' . $value['user_id64'];

                    $playerSummary = $steamWebAPI->GetPlayerSummaries($value['user_id64']);

                    $steamID64 = $value['user_id64'];
                    $steamID32 = convert_steamid($steamID64);
                    $userName = $playerSummary['personaname'];
                    $userAvatar = $playerSummary['avatar'];
                    $userAvatarMedium = $playerSummary['avatarmedium'];
                    $userAvatarLarge = $playerSummary['avatarfull'];

                    echo ' (' . $userName . ')';

                    $sqlResult = $db->q("INSERT INTO `gds_users`(`user_id32`, `user_id64`, `user_name`, `user_avatar`, `user_avatar_medium`, `user_avatar_large`)
                            VALUES (?, ?, ?, ?, ?, ?)
                            ON DUPLICATE KEY UPDATE
                              `user_name` = VALUES(`user_name`),
                              `user_avatar` = VALUES(`user_avatar`),
                              `user_avatar_medium` = VALUES(`user_avatar_medium`),
                              `user_avatar_large` = VALUES(`user_avatar_large`);",
                        'ssssss',
                        $steamID32, $steamID64, $userName, $userAvatar, $userAvatarMedium, $userAvatarLarge
                    );

                    if($sqlResult){
                        echo ' - Success!';
                    }
                    else{
                        echo ' - <strong>Failure!</strong>';
                    }

                    $db->q(
                        'UPDATE `hof_golden_profiles` SET `isParsed` = 1 WHERE `user_id64` = ?;',
                        's',
                        $steamID64
                    );

                    echo '<br />';
                }
            }
        }
        $memcache->close();
    } else {
        echo bootstrapMessage('Oh Snap', 'No DB!', 'danger');
    }
} catch (Exception $e) {
    $message = 'Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage();
    echo bootstrapMessage('Oh Snap', $message, 'danger');
}