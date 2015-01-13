<?php
require_once('../../global_functions.php');
require_once('../functions.php');
require_once('../../connections/parameters.php');

//Allow a user to join a specific lobby

try {
    $userID = !empty($_GET['uid']) && is_numeric($_GET['uid'])
        ? $_GET['uid']
        : NULL;

    $lobbyID = !empty($_GET['lid']) && is_numeric($_GET['lid'])
        ? $_GET['lid']
        : NULL;

    $steamID = new SteamID($userID);
    $userID = $steamID->getSteamID64();

    if (!empty($userID) && !empty($lobbyID)) {
        $memcache = new Memcache;
        $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"
        $lobbyStatus = $memcache->get('api_d2mods_lobby_joined' . $userID);
        if (!$lobbyStatus) {
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
                            llp.`user_id64`,
                            llp.`user_confirmed`
                        FROM `lobby_list_players` llp
                        LEFT JOIN `lobby_list` ll ON llp.`lobby_id` = ll.`lobby_id`
                        WHERE ll.`lobby_active` = 1 AND llp.`user_id64` = ? AND llp.`lobby_id` = ?
                        ORDER BY `lobby_id` DESC
                        LIMIT 0,1;',
                    'si',
                    $userID, $lobbyID
                );

                if (!empty($lobbyUserDetails)) {
                    $lobbyUserDetails = $lobbyUserDetails[0];

                    $steamIDLeader = new SteamID($lobbyUserDetails['lobby_leader']);
                    $lobbyLeader = $steamIDLeader->getSteamID32();

                    $lobbyStatus['lobby_id'] = $lobbyUserDetails['lobby_id'];
                    $lobbyStatus['mod_id'] = $lobbyUserDetails['mod_id'];
                    $lobbyStatus['workshop_id'] = $lobbyUserDetails['workshop_id'];
                    $lobbyStatus['lobby_max_players'] = $lobbyUserDetails['lobby_max_players'];
                    $lobbyStatus['lobby_leader'] = $lobbyLeader;
                    $lobbyStatus['lobby_hosted'] = $lobbyUserDetails['lobby_hosted'];
                    $lobbyStatus['lobby_pass'] = $lobbyUserDetails['lobby_pass'];
                    $lobbyStatus['lobby_map'] = $lobbyUserDetails['lobby_map'];

                    $sqlResult = $db->q(
                        'UPDATE `lobby_list_players` SET `user_confirmed` = 1 WHERE `lobby_id` = ? AND `user_id64` = ?;',
                        'is',
                        $lobbyID, $userID
                    );

                    if (!empty($sqlResult)) {
                        //RETURN LOBBY ID
                        $lobbyStatus['result'] = 'Lobby ' . $lobbyID . ' updated!';
                    } else {
                        //SOMETHING FUNKY HAPPENED
                        $lobbyStatus['error'] = 'Unknown error!';
                    }

                    if ($lobbyUserDetails['lobby_leader'] == $userID) {
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
                    $lobbyStatus['error'] = 'Not in active lobby!';
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