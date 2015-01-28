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
        $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);

        if ($db) {
            $lobbyID = !empty($_POST['lobby_id']) && is_numeric($_POST['lobby_id'])
                ? $_POST['lobby_id']
                : NULL;

            if (!empty($lobbyID)) {
                $sqlResult = $db->q(
                    'SELECT `lobby_leader` FROM `lobby_list` WHERE `lobby_id` = ? AND `lobby_active` = 1 LIMIT 0,1;',
                    'i',
                    $lobbyID
                );

                //CHECK IF LOBBY EXISTS AND USER IS THE LEADER
                if (!empty($sqlResult) && $sqlResult[0]['lobby_leader'] == $_SESSION['user_id64']) {
                    //UPDATE LOBBY LISTING
                    $sqlResult = $db->q(
                        'UPDATE `lobby_list` SET `lobby_active` = 0 WHERE `lobby_id` = ?;',
                        'i',
                        $lobbyID
                    );

                    if (!empty($sqlResult)) {
                        //RETURN LOBBY ID
                        $json['result'] = 'Lobby ' . $db->last_index() . ' closed!';
                    } else {
                        //SOMETHING FUNKY HAPPENED
                        $json['error'] = 'Unknown error!';
                    }
                } else {
                    $json['error'] = 'Lobby does not exist, or you are not lobby leader!';
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