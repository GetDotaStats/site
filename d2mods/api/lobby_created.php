<?php
require_once('../../global_functions.php');
require_once('../functions.php');
require_once('../../connections/parameters.php');

//Allow client plugin to communicate new lobbies to site

try {
    $userID = !empty($_GET['uid']) && is_numeric($_GET['uid'])
        ? $_GET['uid']
        : NULL;

    $username = !empty($_GET['un'])
        ? urlencode(unicodeToUTF_8($_GET['un']))
        : NULL;

    $modID = !empty($_GET['mid']) && is_numeric($_GET['mid'])
        ? $_GET['mid']
        : NULL;

    $workshopID = !empty($_GET['wid']) && is_numeric($_GET['wid'])
        ? $_GET['wid']
        : NULL;

    $map = !empty($_GET['map'])
        ? urlencode(unicodeToUTF_8($_GET['map']))
        : NULL;

    $pass = !empty($_GET['p'])
        ? urlencode(unicodeToUTF_8($_GET['p']))
        : NULL;

    $maxPlayers = !empty($_GET['mp']) && is_numeric($_GET['mp']) && $_GET['mp'] > 1 && $_GET['mp'] <= 20
        ? $_GET['mp']
        : NULL;

    $region = !empty($_GET['r']) && is_numeric($_GET['r']) && $_GET['r'] <= 100
        ? $_GET['r']
        : NULL;

    $lobbyName = !empty($_GET['ln'])
        ? urlencode(unicodeToUTF_8($_GET['ln']))
        : NULL;

    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $lobbySecureToken = '';
    for ($i = 0; $i < 10; $i++)
        $lobbySecureToken .= $characters[rand(0, 35)];

    $steamID = new SteamID($userID);
    $userID = $steamID->getSteamID64();

    if (!empty($userID) && !empty($modID) && !empty($workshopID) && !empty($map) && !empty($pass) && !empty($maxPlayers) && !empty($username)) {
        $memcache = new Memcache;
        $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

        $lobbyStatus = array();

        $db = new dbWrapper_v2($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, false);
        $db->q('SET NAMES utf8;');

        if ($db) {
            $lobbyUserDetails = $db->q(
                'SELECT
                        ll.`lobby_id`,
                        ll.`lobby_leader`,
                        ll.`lobby_active`,
                        ll.`lobby_hosted`
                    FROM `lobby_list` ll
                    WHERE ll.`lobby_active` = 1 AND ll.`lobby_leader` = ?
                    LIMIT 0,1;',
                's',
                $userID
            );

            if (empty($lobbyUserDetails)) {
                $sqlResult = $db->q(
                    'INSERT INTO `lobby_list`(`mod_id`, `workshop_id`, `lobby_name`, `lobby_region`, `lobby_max_players`, `lobby_leader`, `lobby_leader_name`, `lobby_active`, `lobby_hosted`, `lobby_pass`, `lobby_map`, `lobby_secure_token`, `date_keep_alive`, `date_recorded`)
                        VALUES (?, ?, ?, ?, ?, ?, ?, 1, 0, ?, ?, ?, NULL, NULL);',
                    'issiisssss',
                    $modID, $workshopID, $lobbyName, $region, $maxPlayers, $userID, $username, $pass, $map, $lobbySecureToken
                );

                if (!empty($sqlResult)) {
                    //RETURN LOBBY ID
                    $lobbyStatus['result'] = 'Lobby ' . $db->last_index() . ' created!';
                    $lobbyStatus['lobby_id'] = $db->last_index();
                    $lobbyStatus['token'] = $lobbySecureToken;
                } else {
                    //SOMETHING FUNKY HAPPENED
                    $lobbyStatus['error'] = 'Unknown error!';
                }
            } else {
                $lobbyStatus['error'] = 'Already created an active lobby!';
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