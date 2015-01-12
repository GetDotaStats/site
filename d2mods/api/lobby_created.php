<?php
require_once('../../global_functions.php');
require_once('../functions.php');
require_once('../../connections/parameters.php');

//Allow client plugin to communicate new lobbies to site

try {
    $userID = !empty($_GET['uid']) && is_numeric($_GET['uid'])
        ? $_GET['uid']
        : NULL;

    $modID = !empty($_GET['mid']) && is_numeric($_GET['mid'])
        ? $_GET['mid']
        : NULL;

    $workshopID = !empty($_GET['wid']) && is_numeric($_GET['wid'])
        ? $_GET['wid']
        : NULL;

    $map = !empty($_GET['map'])
        ? htmlentities($_GET['map'])
        : NULL;

    $pass = !empty($_GET['p'])
        ? htmlentities($_GET['p'])
        : NULL;

    $maxPlayers = !empty($_GET['mp']) && is_numeric($_GET['mp'])
        ? $_GET['mp']
        : NULL;

    $steamID = new SteamID($userID);
    $userID = $steamID->getSteamID64();

    if (!empty($userID) && !empty($modID) && !empty($workshopID) && !empty($map) && !empty($pass) && !empty($maxPlayers)) {
        $memcache = new Memcache;
        $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

        $lobbyStatus = array();
        $db = new dbWrapper_v2($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, false);
        if ($db) {
            $sqlResult = $db->q(
                'INSERT INTO `lobby_list`(`mod_id`, `workshop_id`, `lobby_max_players`, `lobby_leader`, `lobby_active`, `lobby_hosted`, `lobby_pass`, `lobby_map`) VALUES (?, ?, ?, ?, 1, 1, ?, ?);',
                'isisss',
                $modID, $workshopID, $maxPlayers, $userID, $pass, $map
            );

            if (!empty($sqlResult)) {
                //RETURN LOBBY ID
                $json['result'] = 'Lobby ' . $db->last_index() . ' created!';
            } else {
                //SOMETHING FUNKY HAPPENED
                $json['error'] = 'Unknown error!';
            }
        } else {
            $lobbyStatus['error'] = 'No DB connection!';
        }

        $memcache->close();
    } else {
        $lobbyStatus['error'] = 'Missing field!';
    }

} catch (Exception $e) {
    unset($lobbyStatus);
    $lobbyStatus['error'] = 'Contact getdotastats.com - Caught Exception: ' . $e->getMessage();
}

echo utf8_encode(json_encode($lobbyStatus));