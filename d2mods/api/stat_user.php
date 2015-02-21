<?php
require_once('../../global_functions.php');
require_once('../functions.php');
require_once('./functions.php');
require_once('../../connections/parameters.php');

try {
    $userID = !empty($_GET['uid']) && is_numeric($_GET['uid'])
        ? $_GET['uid']
        : NULL;

    $username = !empty($_GET['un'])
        ? htmlentities($_GET['un'])
        : NULL;

    $totalGames = isset($_GET['tg']) && is_numeric($_GET['tg'])
        ? $_GET['tg']
        : NULL;

    $soloMMR = isset($_GET['sm']) && is_numeric($_GET['sm'])
        ? $_GET['sm']
        : NULL;

    $teamMMR = isset($_GET['tm']) && is_numeric($_GET['tm'])
        ? $_GET['tm']
        : NULL;

    $statsDisabled = isset($_GET['sd']) && $_GET['sd'] == 1
        ? 1
        : 0;

    $steamID = new SteamID($userID);
    $userID = $steamID->getSteamID64();

    if ($userID == NULL || $username == NULL || $totalGames == NULL || $soloMMR == NULL || $teamMMR == NULL) {
        throw new Exception("Missing parameter(s)." . json_encode(
                array(
                    '$userID' => $userID,
                    '$username' => $username,
                    '$totalGames' => $totalGames,
                    '$soloMMR' => $soloMMR,
                    '$teamMMR' => $teamMMR,
                    '$statsDisabled' => $statsDisabled
                )
            )
        );
    }

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $userID = new SteamID($userID);

    $lobbyStatus['userID'] = $userID;
    $lobbyStatus['username'] = $username;
    $lobbyStatus['totalGames'] = $totalGames;
    $lobbyStatus['soloMMR'] = $soloMMR;
    $lobbyStatus['teamMMR'] = $teamMMR;
    $lobbyStatus['statsDisabled'] = $statsDisabled;

    $sqlResult = $db->q(
        'INSERT INTO `gds_users_mmr`(`user_id32`, `user_id64`, `user_name`, `user_games`, `user_mmr_solo`, `user_mmr_party`, `user_stats_disabled`, `date_recorded`)
            VALUES (?, ?, ?, ?, ?, ?, ?, NULL);',
        'sssiiii',
        $userID->getSteamID32(), $userID->getSteamID64(), $username, $totalGames, $soloMMR, $teamMMR, $statsDisabled
    );

    if (!empty($sqlResult)) {
        $lobbyStatus['result'] = 'Results recorded for ' . $db->last_index() . ' created!';
    } else {
        $lobbyStatus['error'] = 'Unknown error!';
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