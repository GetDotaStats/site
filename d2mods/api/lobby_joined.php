<?php
require_once('../../global_functions.php');
require_once('../functions.php');
require_once('../../connections/parameters.php');

//Allow a user to join a specific lobby

try {
    $userID = !empty($_GET['uid']) && is_numeric($_GET['uid'])
        ? $_GET['uid']
        : NULL;

    $username = !empty($_GET['un'])
        ? htmlentities($_GET['un'])
        : 'Unknown??';

    $lobbyID = !empty($_GET['lid']) && is_numeric($_GET['lid'])
        ? $_GET['lid']
        : NULL;

    $token = !empty($_GET['t'])
        ? $_GET['t']
        : NULL;

    $steamID = new SteamID($userID);
    $userID = $steamID->getSteamID64();

    if (!empty($userID) && !empty($lobbyID) && !empty($token)) {
        $memcache = new Memcache;
        $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"
        $lobbyStatus = $memcache->get('api_d2mods_lobby_joined' . $userID);
        if (!$lobbyStatus) {
            $lobbyStatus = array();

            $db = new dbWrapper_v2($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, false);
            $db->q('SET NAMES utf8;');

            if ($db) {
                $lobbyDetails = $db->q(
                    'SELECT
                            ll.`lobby_id`,
                            ll.`mod_id`,
                            ll.`workshop_id`,
                            ll.`lobby_ttl`,
                            ll.`lobby_min_players`,
                            ll.`lobby_max_players`,
                            ll.`lobby_public`,
                            ll.`lobby_leader`,
                            ll.`lobby_leader_name`,
                            ll.`lobby_active`,
                            ll.`lobby_hosted`,
                            ll.`lobby_pass`,
                            ll.`lobby_map`
                        FROM `lobby_list` ll
                        WHERE ll.`lobby_active` = 1 AND ll.`lobby_id` = ? AND ll.`lobby_secure_token` = ?
                        ORDER BY `lobby_id` DESC
                        LIMIT 0,1;',
                    'is',
                    $lobbyID, $token
                );

                if (!empty($lobbyDetails)) {
                    $lobbyDetails = $lobbyDetails[0];

                    $steamIDLeader = new SteamID($lobbyDetails['lobby_leader']);
                    $lobbyLeader = $steamIDLeader->getSteamID32();

                    $lobbyStatus['lobby_id'] = $lobbyDetails['lobby_id'];
                    $lobbyStatus['mod_id'] = $lobbyDetails['mod_id'];
                    $lobbyStatus['workshop_id'] = $lobbyDetails['workshop_id'];
                    $lobbyStatus['lobby_max_players'] = $lobbyDetails['lobby_max_players'];
                    $lobbyStatus['lobby_leader'] = $lobbyLeader;

                    $lobbyStatus['lobby_leader_name'] = !empty($lobbyDetails['lobby_leader_name'])
                        ? $lobbyDetails['lobby_leader_name']
                        : 'Unknown??';

                    $lobbyStatus['lobby_hosted'] = $lobbyDetails['lobby_hosted'];
                    $lobbyStatus['lobby_pass'] = $lobbyDetails['lobby_pass'];
                    $lobbyStatus['lobby_map'] = $lobbyDetails['lobby_map'];

                    $sqlResult = $db->q(
                        'INSERT INTO `lobby_list_players` (`lobby_id`, `user_id64`, `user_confirmed`, `user_name`) VALUES (?, ?, 1, ?) ON DUPLICATE KEY UPDATE `user_confirmed` = 1;',
                        'iss',
                        $lobbyID, $userID, $username
                    );

                    if (!empty($sqlResult)) {
                        //RETURN LOBBY ID
                        $lobbyStatus['result'] = 'User ' . $userID . ' joined lobby #' . $lobbyID . '!';
                    } else {
                        //SOMETHING FUNKY HAPPENED
                        $lobbyStatus['error'] = 'Unknown error!';
                    }

                    if ($lobbyDetails['lobby_leader'] == $userID) {
                        $sqlResult = $db->q(
                            'UPDATE `lobby_list` SET `lobby_hosted` = 1 WHERE `lobby_id` = ?;',
                            'i',
                            $lobbyID
                        );

                        if (!empty($sqlResult)) {
                            //RETURN LOBBY ID
                            $lobbyStatus['result2'] = 'Lobby ' . $lobbyID . ' hosted!';
                        } else {
                            //SOMETHING FUNKY HAPPENED
                            $lobbyStatus['error2'] = 'Unknown error!';
                        }
                    }
                } else {
                    $lobbyStatus['error'] = 'Lobby not active or bad token!';
                }
            } else {
                $lobbyStatus['error'] = 'No DB connection!';
            }

            $memcache->set('api_d2mods_lobby_joined' . $userID, $lobbyStatus, 0, 1);
        }
        $memcache->close();
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