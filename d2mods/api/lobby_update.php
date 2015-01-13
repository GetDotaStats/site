<?php
require_once('../../global_functions.php');
require_once('../functions.php');
require_once('../../connections/parameters.php');

//Allow client plugin to communicate new lobbies to site

try {
    $lobbyID = !empty($_GET['lid']) && is_numeric($_GET['lid'])
        ? $_GET['lid']
        : NULL;

    $map = !empty($_GET['map'])
        ? htmlentities($_GET['map'])
        : NULL;

    $maxPlayers = !empty($_GET['mp']) && is_numeric($_GET['mp'])
        ? $_GET['mp']
        : NULL;

    $region = !empty($_GET['r']) && is_numeric($_GET['r'])
        ? $_GET['r']
        : NULL;

    $lobbyName = !empty($_GET['ln'])
        ? htmlentities($_GET['ln'])
        : NULL;

    $token = !empty($_GET['t'])
        ? htmlentities($_GET['t'])
        : NULL;

    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $lobbySecureToken = '';
    for ($i = 0; $i < 10; $i++)
        $lobbySecureToken .= $characters[rand(0, 35)];

    $steamID = new SteamID($userID);
    $userID = $steamID->getSteamID64();

    if (!empty($userID) && !empty($modID) && !empty($workshopID) && !empty($map) && !empty($pass) && !empty($maxPlayers) && !empty($token)) {
        $memcache = new Memcache;
        $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

        $lobbyStatus = array();

        $db = new dbWrapper_v2($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, false);
        $db->q('SET NAMES utf8;');

        if ($db) {
            $sqlResult = $db->q(
                'UPDATE `lobby_list` SET `lobby_max_players`, `lobby_map`) VALUES (?, ?) WHERE `lobby_id` = ? AND `lobby_secure_token` = ?;',
                'isis',
                $maxPlayers, $map, $lobbyID, $lobbySecureToken
            );

            if (!empty($sqlResult)) {
                //RETURN LOBBY ID
                $json['result'] = 'Lobby ' . $lobbyID . ' updated!';
                $json['token'] = $lobbySecureToken;
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

try {
    echo utf8_encode(json_encode($lobbyStatus));
} catch (Exception $e) {
    unset($lobbyStatus);
    $lobbyStatus['error'] = 'Contact getdotastats.com - Caught Exception: ' . $e->getMessage();
    echo utf8_encode(json_encode($lobbyStatus));
}