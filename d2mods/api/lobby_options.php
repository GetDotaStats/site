<?php
require_once('../../global_functions.php');
require_once('../functions.php');
require_once('../../connections/parameters.php');

//Get the lobby options

try {
    $userID = !empty($_GET['uid']) && is_numeric($_GET['uid'])
        ? $_GET['uid']
        : NULL;

    $modGUID = !empty($_GET['mid'])
        ? $_GET['mid']
        : NULL;

    $steamID = new SteamID($userID);
    $userID = $steamID->getSteamID64();

    if (!empty($userID) && !empty($modGUID)) {
        $lobbyStatus = array();

        $db = new dbWrapper_v2($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, false);
        $db->q('SET NAMES utf8;');

        if ($db) {
            $lobbyDetails = $db->q(
                'SELECT
                        ll.`lobby_id`,
                        ll.`mod_id`,
                        ll.`mod_guid`,
                        ll.`workshop_id`,
                        ll.`lobby_leader`,
                        ll.`lobby_options`
                    FROM `lobby_list` ll
                    WHERE ll.`lobby_leader` = ? AND ll.`mod_guid` = ?
                    ORDER BY `lobby_id` DESC
                    LIMIT 0,1;',
                'ss',
                $userID, $modGUID
            );

            if (!empty($lobbyDetails)) {
                $lobbyDetails = $lobbyDetails[0];

                $lobbyStatus['lobby_id'] = $lobbyDetails['lobby_id'];
                $lobbyStatus['mod_id'] = $lobbyDetails['mod_id'];
                $lobbyStatus['mod_guid'] = $lobbyDetails['mod_guid'];
                $lobbyStatus['workshop_id'] = $lobbyDetails['workshop_id'];

                $lobbyStatus['lobby_options'] = !empty($lobbyDetails['lobby_options'])
                    ? json_decode($lobbyDetails['lobby_options'], 1)
                    : NULL;
            } else {
                $lobbyStatus['error'] = 'No lobby with those fields!';
            }
        } else {
            $lobbyStatus['error'] = 'No DB connection!';
        }
    } else {
        $lobbyStatus['error'] = 'Invalid user id!';
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