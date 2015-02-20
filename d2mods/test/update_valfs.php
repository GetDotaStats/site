<?php
require_once('../../global_functions.php');
require_once('../functions.php');
require_once('../../connections/parameters.php');

try {
    if (!isset($_SESSION)) {
        session_start();
    }

    checkLogin_v2();

    if (!empty($_SESSION['user_id64']) && !empty($_SESSION['isAdmin'])) {
        $db = new dbWrapper_v2($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site);

        if ($db) {
            $valfs = $db->q("SELECT * FROM `steam_valfs`;");

            $webAPI = new steam_webapi($api_key1);

            if (!empty($valfs)) {
                foreach ($valfs as $key => $value) {
                    $userID64 = $value['user_id64'];

                    $valfUser = new SteamID($userID64);
                    $userID32 = $valfUser->getSteamID32();

                    $profileRequest = $webAPI->GetPlayerSummariesV2($userID64);

                    $userName = !empty($profileRequest)
                        ? $profileRequest['response']['players'][0]['personaname']
                        : '??';

                    echo $userID64 . ' | ' . $userName . '<br />';

                    $db->q(
                        'INSERT INTO `steam_valfs` (`user_id64`, `user_id32`, `user_name`)
                            VALUES (?, ?, ?)
                            ON DUPLICATE KEY UPDATE `user_id32` = VALUES(`user_id32`), `user_name` = VALUES(`user_name`);',
                        'sss',
                        $userID64, $userID32, $userName
                    );
                }
            } else {
                echo bootstrapMessage('Oh Snap', 'No known Valfs!', 'danger');
            }
        } else {
            echo bootstrapMessage('Oh Snap', 'No DB!', 'danger');
        }
    } else {
        echo bootstrapMessage('Oh Snap', 'Not logged in!', 'danger');
    }
} catch (Exception $e) {
    $message = 'Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage();
    echo bootstrapMessage('Oh Snap', $message, 'danger');
}