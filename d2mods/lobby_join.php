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
                $sqlResult = $db->q(
                    'SELECT
                            llp.`lobby_id`
                        FROM `lobby_list_players` llp
                        WHERE llp.`user_id64` = ? AND llp.`lobby_id` IN (SELECT `lobby_id` FROM `lobby_list` ll WHERE `lobby_active` = 1)
                        LIMIT 0,1;',
                    's',
                    $_SESSION['user_id64']
                );

                //LEAVE OTHER ACTIVE LOBBIES
                if (!empty($sqlResult)) {
                    $db->q(
                        'DELETE FROM `lobby_list_players`
                            WHERE
                                `user_id64` = ? AND
                                `lobby_id` IN (SELECT `lobby_id` FROM `lobby_list` ll WHERE `lobby_active` = 1);',
                        's',
                        $_SESSION['user_id64']
                    );
                }

                //GET LOBBY DETAILS
                $sqlResult = $db->q(
                    'SELECT
                            `lobby_active`,
                            `lobby_max_players`,
                            (
                              SELECT
                                  COUNT(`user_id64`)
                                FROM `lobby_list_players`
                                WHERE `lobby_id` = ll.`lobby_id`
                                LIMIT 0,1
                            ) AS lobby_current_players
                        FROM `lobby_list` ll
                        WHERE ll.`lobby_id` = ?
                        LIMIT 0,1;',
                    'i',
                    $lobbyID
                );

                //JOIN LOBBY
                if (
                    !empty($sqlResult) &&
                    $sqlResult[0]['lobby_active'] == 1 &&
                    $sqlResult[0]['lobby_current_players'] < $sqlResult[0]['lobby_max_players']
                ) {
                    $userName = !empty($_SESSION['user_name'])
                        ? htmlentities($_SESSION['user_id64'])
                        : 'Unknown??';

                    $sqlResult = $db->q(
                        'INSERT INTO `lobby_list_players` (`lobby_id`, `user_id64`, `user_name`) VALUES (?, ?, ?);',
                        'iss',
                        $lobbyID, $_SESSION['user_id64'], $userName
                    );

                    if (!empty($sqlResult)) {
                        $json['result'] = 'Joined lobby!';
                    } else {
                        //SOMETHING FUNKY HAPPENED
                        $json['error'] = 'Unknown error!';
                    }
                } else {
                    $json['error'] = 'Lobby full or not active!';
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