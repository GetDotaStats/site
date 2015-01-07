<?php
require_once('../global_functions.php');
require_once('../connections/parameters.php');

$json = array();

try {
    if (!isset($_SESSION)) {
        session_start();
    }

    header('Content-Type: application/json');

    checkLogin_v2();

    if (!empty($_SESSION['user_id64'])) {
        $db = new dbWrapper_v2($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, false);
        $db->q('SET NAMES utf8;');
        if ($db) {
            $modID = !empty($_POST['mod_id']) && is_numeric($_POST['mod_id'])
                ? $_POST['mod_id']
                : NULL;

            $lobbyTTL = !empty($_POST['lobby_ttl']) && is_numeric($_POST['lobby_ttl'])
                ? $_POST['lobby_ttl']
                : NULL;

            $lobbyMinPlayers = !empty($_POST['lobby_min_players']) && is_numeric($_POST['lobby_min_players'])
                ? $_POST['lobby_min_players']
                : NULL;

            $lobbyMaxPlayers = !empty($_POST['lobby_max_players']) && is_numeric($_POST['lobby_max_players'])
                ? $_POST['lobby_max_players']
                : NULL;

            $lobbyIsPublic = !empty($_POST['lobby_public']) && is_numeric($_POST['lobby_public'])
                ? $_POST['lobby_public']
                : NULL;

            if (!empty($modID) && !empty($lobbyTTL) && !empty($lobbyMinPlayers) && !empty($lobbyMaxPlayers) && !empty($lobbyIsPublic)) {
                //CHECK IF USER HAS AN ACTIVE LOBBY
                $sqlResult = $db->q(
                    'SELECT `lobby_id` FROM `lobby_list` WHERE `lobby_active` = 1 AND `lobby_leader` = ? LIMIT 0,1;',
                    's',
                    $_SESSION['user_id64']
                );

                if (empty($sqlResult)) {
                    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                    $lobbyPass = '';
                    for ($i = 0; $i < 5; $i++)
                        $lobbyPass .= $characters[rand(0, 35)];

                    //INSERT NEW LOBBY LISTING
                    $sqlResult = $db->q(
                        'INSERT INTO `lobby_list`(`lobby_leader`, `mod_id`, `lobby_ttl`, `lobby_min_players`, `lobby_max_players`, `lobby_public`, `lobby_pass`) VALUES (?, ?, ?, ?, ?, ?, ?)',
                        'siiiiis',
                        $_SESSION['user_id64'], $modID, $lobbyTTL, $lobbyMinPlayers, $lobbyMaxPlayers, $lobbyIsPublic, $lobbyPass
                    );

                    if (!empty($sqlResult)) {
                        //RETURN LOBBY ID
                        $json['lobby_id'] = $db->last_index();
                    } else {
                        //SOMETHING FUNKY HAPPENED
                        $json['error'] = 'Unknown error!';
                    }
                } else {
                    //RETURN LOBBY ID OF EXISTING ACTIVE LOBBY
                    //TODO MAKE A CHECK FOR ANY GAMES THAT HAVE RECENTLY FINISHED WITH THIS LOBBY LEADER IN THEM. IF SO, MARK LOBBY AS IN-ACTIVE
                    $json['lobby_id'] = $sqlResult[0]['lobby_id'];
                }
            } else {
                $json['error'] = 'Missing fields!';
            }
        } else {
            $json['error'] = 'No DB!';
        }
    } else {
        $json['error'] = 'Not logged in!';
    }
} catch (Exception $e) {
    $json['error'] = 'Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . ' -- ' . $e->getMessage();
}

//RETURN THE JSON ENCODED RESULT
try {
    echo json_encode($json);
} catch (Exception $e) {
    $json['error'] = 'Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . ' -- ' . $e->getMessage();
    echo json_encode($json);
}