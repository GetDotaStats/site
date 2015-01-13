<?php
require_once('../../global_functions.php');
require_once('../functions.php');
require_once('../../connections/parameters.php');

//List of all the active lobbies on the site

try {
    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"
    $lobbyList = $memcache->get('api_d2mods_lobby_list');
    if (!$lobbyList) {
        $lobbyList = array();

        $db = new dbWrapper_v2($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, false);
        $db->q('SET NAMES utf8;');

        if ($db) {
            $lobbyListSQL = $db->q(
                'SELECT
                        ll.`lobby_id`,
                        ll.`mod_id`,
                        ll.`workshop_id`,
                        ll.`lobby_max_players`,
                        ll.`lobby_leader`,
                        ll.`lobby_hosted`,
                        ll.`lobby_pass`,
                        ll.`lobby_map`,
                        (
                          SELECT
                              COUNT(`user_id64`)
                            FROM `lobby_list_players`
                            WHERE `lobby_id` = ll.`lobby_id`
                            LIMIT 0,1
                        ) AS lobby_current_players
                    FROM `lobby_list` ll
                    WHERE ll.`lobby_active` = 1
                    ORDER BY `lobby_id` DESC
                    LIMIT 0,50;'
            );

            if (!empty($lobbyListSQL)) {
                foreach ($lobbyListSQL as $key => $value) {
                    $steamIDLeader = new SteamID($value['lobby_leader']);
                    $lobbyLeader = $steamIDLeader->getSteamID32();

                    $lobbyList[] = array(
                        'lobby_id' => $value['lobby_id'],
                        'mod_id' => $value['mod_id'],
                        'workshop_id' => $value['workshop_id'],
                        'lobby_max_players' => $value['lobby_max_players'],
                        'lobby_leader' => $lobbyLeader,
                        'lobby_hosted' => $value['lobby_hosted'],
                        'lobby_pass' => $value['lobby_pass'],
                        'lobby_map' => $value['lobby_map'],
                        'lobby_current_players' => $value['lobby_current_players']
                    );
                }
            } else {
                $lobbyList['error'] = 'No active lobbies!';
            }
        } else {
            $lobbyList['error'] = 'No DB connection!';
        }

        $memcache->set('api_d2mods_lobby_list', $lobbyList, 0, 5);
    }
    $memcache->close();
} catch (Exception $e) {
    unset($lobbyList);
    $lobbyList['error'] = 'Contact getdotastats.com - Caught Exception: ' . $e->getMessage();
}

try {
    echo utf8_encode(json_encode($lobbyStatus));
} catch (Exception $e) {
    unset($lobbyStatus);
    $lobbyStatus['error'] = 'Contact getdotastats.com - Caught Exception: ' . $e->getMessage();
    echo utf8_encode(json_encode($lobbyStatus));
}