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
            $lobbyID = !empty($_POST['lobby_id']) && is_numeric($_POST['lobby_id'])
                ? $_POST['lobby_id']
                : NULL;

            if (!empty($lobbyID)) {
                //LEAVE LOBBY
                $sqlResult = $db->q(
                    'DELETE FROM `lobby_list_players`
                        WHERE
                            `lobby_id` = ? AND
                            `user_id64` = ?;',
                    'is',
                    $lobbyID, $_SESSION['user_id64']
                );

                if (!empty($sqlResult)) {
                    $json['result'] = 'Left lobby!';
                } else {
                    //SOMETHING FUNKY HAPPENED
                    $json['error'] = 'Unknown error!';
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