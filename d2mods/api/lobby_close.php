<?php
require_once('../../global_functions.php');
require_once('../functions.php');
require_once('../../connections/parameters.php');

//Allow a host to close lobby

try {
    $lobbyID = !empty($_GET['lid']) && is_numeric($_GET['lid'])
        ? $_GET['lid']
        : NULL;

    $token = !empty($_GET['t'])
        ? $_GET['t']
        : NULL;

    $lobbyStarted = isset($_GET['s']) && $_GET['s'] == 1
        ? 1
        : 0;

    if (!empty($lobbyID) && !empty($token)) {
        $lobbyStatus = array();

        $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
        if (empty($db)) throw new Exception('No DB!');

        $sqlResult = $db->q(
            'UPDATE `lobby_list` SET `lobby_active` = 0, `lobby_hosted` = 0, `lobby_started` = ? WHERE `lobby_id` = ? AND `lobby_secure_token` = ?;',
            'iis',
            $lobbyStarted, $lobbyID, $token
        );

        if (!empty($sqlResult)) {
            //RETURN LOBBY ID
            $lobbyStatus['result'] = 'Lobby ' . $lobbyID . ' closed!';
        } else {
            //SOMETHING FUNKY HAPPENED
            $lobbyStatus['error'] = 'Unknown error!';
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