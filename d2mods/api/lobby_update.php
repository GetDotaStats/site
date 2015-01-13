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
        : 0;

    $lobbyName = !empty($_GET['ln'])
        ? htmlentities($_GET['ln'])
        : 'Custom Lobby #' . $lobbyID;

    $token = !empty($_GET['t'])
        ? htmlentities($_GET['t'])
        : NULL;

    if (!empty($lobbyID) && !empty($map) && !empty($maxPlayers) && !empty($token)) {
        $lobbyStatus = array();

        $db = new dbWrapper_v2($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, false);
        $db->q('SET NAMES utf8;');

        if ($db) {
            $sqlResult = $db->q(
                'UPDATE `lobby_list` SET `lobby_max_players` = ?, `lobby_map` = ?, `lobby_region` = ?, `lobby_name` = ? WHERE `lobby_id` = ? AND `lobby_secure_token` = ?;',
                'isisis',
                $maxPlayers, $map, $region, $lobbyName, $lobbyID, $token
            );

            if (!empty($sqlResult)) {
                //RETURN LOBBY ID
                $lobbyStatus['result'] = 'Lobby ' . $lobbyID . ' updated!';
                $lobbyStatus['token'] = $token;
            } else {
                //SOMETHING FUNKY HAPPENED
                $lobbyStatus['error'] = 'Unknown error! Fields: {mp: ' . $maxPlayers . ', map: ' . $map . ', region: ' . $region . ', lobbyName: ' . $lobbyName . ', lid: ' . $lobbyID . ', token: ' . $token . '}';
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