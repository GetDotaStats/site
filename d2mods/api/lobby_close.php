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

            if ($lobbyStarted == 1) {
                $lobbyDetails = $db->q(
                    'SELECT
                            ll.`lobby_id`,
                            ll.`mod_id`,
                            ll.`workshop_id`,
                            ll.`lobby_ttl`,
                            ll.`lobby_min_players`,
                            ll.`lobby_max_players`,
                            ll.`lobby_public`,
                            ll.`lobby_leader`,
                            ll.`lobby_leader_name`,
                            ll.`lobby_active`,
                            ll.`lobby_hosted`,
                            ll.`lobby_pass`,
                            ll.`lobby_map`
                        FROM `lobby_list` ll
                        WHERE ll.`lobby_id` = ? AND ll.`lobby_secure_token` = ?
                        ORDER BY `lobby_id` DESC
                        LIMIT 0,1;',
                    'is',
                    array($lobbyID, $token)
                );

                if (!empty($lobbyDetails)) {
                    $userID = $lobbyDetails[0]['lobby_leader'];
                    $username = $lobbyDetails[0]['lobby_leader_name'];

                    $sqlResult = $db->q(
                        'INSERT INTO `lobby_list_players` (`lobby_id`, `user_id64`, `user_confirmed`, `user_name`)
                            VALUES (?, ?, 1, ?)
                            ON DUPLICATE KEY UPDATE `user_confirmed` = 1;',
                        'iss',
                        $lobbyID, $userID, $username
                    );

                    if (!empty($sqlResult)) {
                        $lobbyStatus['result2'] = 'Player inserted into Lobby ' . $lobbyID . '!';
                    }
                }
            }
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