<?php
require_once('../../global_functions.php');
require_once('../functions.php');
require_once('../../connections/parameters.php');

//Get the lobby details of a specific user

try {
    $userID = !empty($_GET['uid']) && is_numeric($_GET['uid'])
        ? $_GET['uid']
        : NULL;

    $steamID = new SteamID($userID);
    $userID = $steamID->getSteamID64();

    if (!empty($userID)) {
        $memcache = new Memcache;
        $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"
        $lobbyStatus = $memcache->get('api_d2mods_lobby_user_status' . $userID);
        if (!$lobbyStatus) {
            $lobbyStatus = array();
            $db = new dbWrapper_v2($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, false);
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
                        WHERE ll.`lobby_active` = 1 AND llp.`user_id64` = ?
                        ORDER BY `lobby_id` DESC
                        LIMIT 0,1;',
                    's',
                    $userID
                );

                if (!empty($lobbyUserDetails)) {
                    $lobbyUserDetails = $lobbyUserDetails[0];

                    $lobbyStatus['lobby_id'] = $lobbyUserDetails['lobby_id'];
                    $lobbyStatus['mod_id'] = $lobbyUserDetails['mod_id'];
                    $lobbyStatus['workshop_id'] = $lobbyUserDetails['workshop_id'];
                    $lobbyStatus['lobby_max_players'] = $lobbyUserDetails['lobby_max_players'];
                    $lobbyStatus['lobby_leader'] = $lobbyUserDetails['lobby_leader'];
                    $lobbyStatus['lobby_hosted'] = $lobbyUserDetails['lobby_hosted'];
                    $lobbyStatus['lobby_pass'] = $lobbyUserDetails['lobby_pass'];
                    $lobbyStatus['lobby_map'] = $lobbyUserDetails['lobby_map'];
                } else {
                    $lobbyStatus['error'] = 'Not in active lobby!';
                }
            } else {
                $lobbyStatus['error'] = 'No DB connection!';
            }

            $memcache->set('api_d2mods_lobby_user_status' . $userID, $lobbyStatus, 0, 1);
        }
        $memcache->close();
    } else {
        $lobbyStatus['error'] = 'Invalid user id!';
    }

} catch (Exception $e) {
    unset($lobbyStatus);
    $lobbyStatus['error'] = 'Contact getdotastats.com - Caught Exception: ' . $e->getMessage();
}

echo utf8_encode(json_encode($lobbyStatus));