<?php
require_once('../../global_functions.php');
require_once('../functions.php');
require_once('../../connections/parameters.php');

//Allow a user to leave a specific lobby

try {
    $userID = !empty($_GET['uid']) && is_numeric($_GET['uid'])
        ? $_GET['uid']
        : NULL;

    $lobbyID = !empty($_GET['lid']) && is_numeric($_GET['lid'])
        ? $_GET['lid']
        : NULL;

    $token = !empty($_GET['t'])
        ? $_GET['t']
        : NULL;

    $steamID = new SteamID($userID);
    $userID = $steamID->getSteamID64();

    if (!empty($userID) && !empty($lobbyID) && !empty($token)) {
        $lobbyStatus = array();

        $db = new dbWrapper_v2($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, false);
        $db->q('SET NAMES utf8;');

        if ($db) {
            $lobbyUserDetails = $db->q(
                'SELECT
                        ll.`lobby_id`,
                        ll.`mod_id`,
                        ll.`workshop_id`,
                        ll.`lobby_ttl`,
                        ll.`lobby_min_players`,
                        ll.`lobby_max_players`,
                        ll.`lobby_public`,
                        ll.`lobby_leader`,
                        ll.`lobby_active`,
                        ll.`lobby_hosted`,
                        ll.`lobby_pass`,
                        ll.`lobby_map`,
                        ll.`lobby_secure_token`,
                        llp.`user_id64`,
                        llp.`user_confirmed`
                    FROM `lobby_list_players` llp
                    LEFT JOIN `lobby_list` ll ON llp.`lobby_id` = ll.`lobby_id`
                    WHERE ll.`lobby_active` = 1 AND llp.`user_id64` = ? AND llp.`lobby_id` = ? AND ll.`lobby_secure_token` = ?
                    ORDER BY `lobby_id` DESC
                    LIMIT 0,1;',
                'sis',
                $userID, $lobbyID, $token
            );

            if (!empty($lobbyUserDetails)) {
                $sqlResult = $db->q(
                    'DELETE FROM `lobby_list_players` WHERE `lobby_id` = ? AND `user_id64` = ?;',
                    'is',
                    $lobbyID, $userID
                );

                if (!empty($sqlResult)) {
                    //RETURN LOBBY ID
                    $lobbyStatus['result'] = 'Removed ' . $userID . ' from ' . $lobbyID . '!';
                } else {
                    //SOMETHING FUNKY HAPPENED
                    $lobbyStatus['error'] = 'Unknown error!';
                }
            } else {
                $lobbyStatus['error'] = 'Not in active lobby or bad token!';
            }
        } else {
            $lobbyStatus['error'] = 'No DB connection!';
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