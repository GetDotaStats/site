<?php
require_once('../../global_functions.php');
require_once('../functions.php');
require_once('../../connections/parameters.php');

try {
    $lobbyID = !empty($_GET['lid']) && is_numeric($_GET['lid'])
        ? $_GET['lid']
        : NULL;

    $token = !empty($_GET['t'])
        ? $_GET['t']
        : NULL;

    if (!empty($lobbyID) && !empty($token)) {
        $lobbyStatus = array();

        $db = new dbWrapper_v2($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, false);
        $db->q('SET NAMES utf8;');

        $lobbyDetails = $db->q(
            'SELECT
                    ll.`lobby_id`,
                    ll.`mod_id`,
                    ll.`workshop_id`,
                    ll.`lobby_ttl`,
                    ll.`lobby_active`,
                    ll.`date_keep_alive`,
                    ll.`date_recorded`
                FROM `lobby_list` ll
                WHERE ll.`lobby_id` = ? AND ll.`lobby_active` = 1
                ORDER BY `lobby_id` DESC
                LIMIT 0,1;',
            'i',
            $lobbyID
        );

        if (!empty($lobbyDetails)) {
            $lobbyDetails = $lobbyDetails[0];

            $dateKeepAlive = date('Y-m-d G:i:s');

            $sqlResult = $db->q(
                'UPDATE `lobby_list` SET `date_keep_alive` = ? WHERE `lobby_id` = ? AND `lobby_active` = 1 AND `lobby_secure_token` = ?;',
                'sis',
                $dateKeepAlive, $lobbyID, $token
            );

            if (!empty($sqlResult)) {
                //RETURN LOBBY ID
                $lobbyStatus['result'] = 'Lobby ' . $lobbyID . ' kept alive!';
                $lobbyStatus['token'] = $token;
                $lobbyStatus['lobby_ttl'] = $lobbyDetails['lobby_ttl'];
                $lobbyStatus['lobby_active'] = $lobbyDetails['lobby_active'];
                $lobbyStatus['date_keep_alive'] = relative_time($dateKeepAlive);
                $lobbyStatus['date_recorded'] = relative_time($lobbyDetails['date_recorded']);
            } else {
                //SOMETHING FUNKY HAPPENED
                $lobbyStatus['error'] = 'Lobby not updated! Either already updated or invalid fields.';
            }
        }
        else{
            $lobbyStatus['lobby_active'] = 0;
            $lobbyStatus['error'] = 'Lobby does not exist or is in-active.';
        }
    } else {
        $lobbyStatus['error'] = 'Missing field!';
    }
} catch (Exception $e) {
    unset($lobbyStatus);
    $lobbyStatus['error'] = 'Contact getdotastats.com - Caught Exception: ' . $e->getMessage();
}

try {
    echo utf8_encode(json_encode($lobbyStatus));
} catch (Exception $e) {
    unset($lobbyStatus);
    $lobbyStatus['error'] = 'Contact getdotastats.com - Caught Exception: ' . $e->getMessage();
    echo utf8_encode(json_encode($lobbyStatus));
}