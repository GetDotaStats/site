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
        ? handlingUnicodeFromFlashWithURLencoding($_GET['un'])
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
        $lobbyStatus['reported_fields'] = array(
            '$userID' => $userID,
            '$username' => $username,
            '$totalGames' => $totalGames,
            '$soloMMR' => $soloMMR,
            '$teamMMR' => $teamMMR,
            '$statsDisabled' => $statsDisabled
        );

        throw new Exception("Missing parameter(s).");
    }

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    $steamID = new SteamID($userID);

    $lobbyStatus['userID32'] = $steamID->getsteamID32();
    $lobbyStatus['userID64'] = $steamID->getsteamID64();
    $lobbyStatus['username'] = $username;
    $lobbyStatus['totalGames'] = $totalGames;
    $lobbyStatus['soloMMR'] = $soloMMR;
    $lobbyStatus['teamMMR'] = $teamMMR;
    $lobbyStatus['statsDisabled'] = $statsDisabled;

    $previousMMR = cached_query(
        'api_user_mmr_previous' . $userID,
        'SELECT
                `user_id32`,
                `user_id64`,
                `user_name`,
                `user_games`,
                `user_mmr_solo`,
                `user_mmr_party`,
                `user_stats_disabled`,
                `date_recorded`
            FROM `gds_users_mmr`
            WHERE `user_id64` = ?
            ORDER BY `date_recorded` DESC
            LIMIT 0,1;',
        's',
        array($steamID->getSteamID64()),
        5
    );

    $MMRcheck = true;
    if (!empty($previousMMR)) {
        //$lastRecord = relative_time_v3($previousMMR[0]['date_recorded'], 1, 'day', true);
        //$lastRecord = $lastRecord['number'];

        $previousRecord = new DateTime($previousMMR[0]['date_recorded']);
        $todayRecord = date('Y-m-d');

        if ($soloMMR > ($previousMMR[0]['user_mmr_solo'] + 50) && $totalGames <= ($previousMMR[0]['user_games'] + 1)) throw new Exception('Anti-Cheat 1 triggered!');

        if ($teamMMR > ($previousMMR[0]['user_mmr_party'] + 50) && $totalGames <= ($previousMMR[0]['user_games'] + 1)) throw new Exception('Anti-Cheat 2 triggered!');

        if ($totalGames <= ($previousMMR[0]['user_games'] - 5)) throw new Exception('Anti-Cheat 3 triggered!');

        if ($teamMMR == $previousMMR[0]['user_mmr_party'] && $soloMMR == $previousMMR[0]['user_mmr_solo'] && ($previousRecord->format('Y-m-d') == $todayRecord)) $MMRcheck = false;
    }

    if ($MMRcheck) {
        $sqlResult = $db->q(
            'INSERT INTO `gds_users_mmr`(`user_id32`, `user_id64`, `user_name`, `user_games`, `user_mmr_solo`, `user_mmr_party`, `user_stats_disabled`, `date_recorded`)
                VALUES (?, ?, ?, ?, ?, ?, ?, NULL);',
            'sssiiii',
            $steamID->getSteamID32(), $steamID->getSteamID64(), $username, $totalGames, $soloMMR, $teamMMR, $statsDisabled
        );

        if (!empty($sqlResult)) {
            $lobbyStatus['result'] = 'MMR updated!';
        } else {
            $lobbyStatus['error'] = 'Unknown error!';
        }
    } else {
        $lobbyStatus['result'] = 'MMR not recorded due to lack of change!';
    }

    $memcache->close();
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