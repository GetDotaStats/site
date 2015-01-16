<?php
require_once('../../global_functions.php');
require_once('../functions.php');
require_once('../../connections/parameters.php');

//Get the lobby details of a specific lobby

try {
    $lobbyID = !empty($_GET['lid']) && is_numeric($_GET['lid'])
        ? $_GET['lid']
        : NULL;

    if (!empty($lobbyID)) {
        $memcache = new Memcache;
        $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"
        $lobbyStatus = $memcache->get('api_d2mods_lobby_status' . $lobbyID);
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
                            ll.`lobby_name`,
                            ll.`lobby_region`,
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
                        WHERE ll.`lobby_id` = ?
                        ORDER BY `lobby_id` DESC
                        LIMIT 0,1;',
                    'i',
                    $lobbyID
                );

                $lobbyPlayers = $db->q(
                    'SELECT
                            llp.`lobby_id`,
                            llp.`user_id64`,
                            llp.`user_name`,
                            llp.`user_confirmed`
                        FROM `lobby_list_players` llp
                        WHERE lobby_id = ?;',
                    'i',
                    $lobbyID
                );

                if (!empty($lobbyDetails)) {
                    $lobbyDetails = $lobbyDetails[0];

                    $steamIDLeader = new SteamID($lobbyDetails['lobby_leader']);
                    $lobbyLeader = $steamIDLeader->getSteamID32();

                    $lobbyPlayersArray = array();
                    if (!empty($lobbyPlayers)) {
                        foreach ($lobbyPlayers as $key => $value) {
                            $userName = !empty($value['user_name'])
                                ? urldecode($value['user_name'])
                                : 'Unknown??';

                            $lobbyPlayersArray[] = array(
                                'user_id64' => $value['user_id64'],
                                'user_name' => $userName,
                                'user_confirmed' => $value['user_confirmed']
                            );
                        }
                    }

                    $lobbyStatus['lobby_id'] = $lobbyDetails['lobby_id'];
                    $lobbyStatus['mod_id'] = $lobbyDetails['mod_id'];
                    $lobbyStatus['workshop_id'] = $lobbyDetails['workshop_id'];

                    $lobbyStatus['lobby_name'] = !empty($lobbyDetails['lobby_name'])
                        ? urldecode($lobbyDetails['lobby_name'])
                        : 'Custom Lobby #' . $lobbyDetails['lobby_id'];

                    $lobbyStatus['lobby_region'] = !empty($lobbyDetails['lobby_region'])
                        ? $lobbyDetails['lobby_region']
                        : 0;

                    $lobbyStatus['lobby_max_players'] = $lobbyDetails['lobby_max_players'];
                    $lobbyStatus['lobby_leader'] = $lobbyLeader;

                    $lobbyStatus['lobby_leader_name'] = !empty($lobbyDetails['lobby_leader_name'])
                        ? urldecode($lobbyDetails['lobby_leader_name'])
                        : 'Unknown??';

                    $lobbyStatus['lobby_active'] = $lobbyDetails['lobby_active'];
                    $lobbyStatus['lobby_hosted'] = $lobbyDetails['lobby_hosted'];
                    $lobbyStatus['lobby_pass'] = urldecode($lobbyDetails['lobby_pass']);
                    $lobbyStatus['lobby_map'] = urldecode($lobbyDetails['lobby_map']);
                    $lobbyStatus['lobby_players'] = $lobbyPlayersArray;
                } else {
                    $lobbyStatus['error'] = 'No lobby with that ID!';
                }
            } else {
                $lobbyStatus['error'] = 'No DB connection!';
            }

            $memcache->set('api_d2mods_lobby_status' . $lobbyID, $lobbyStatus, 0, 1);
        }
        $memcache->close();
    } else {
        $lobbyStatus['error'] = 'Invalid lobby id!';
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