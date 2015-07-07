<?php
require_once('./functions.php');
require_once('../../global_functions.php');
require_once('../../connections/parameters.php');

try {
    $s2_response = array();

    if (!isset($_POST['payload']) || empty($_POST['payload'])) {
        throw new Exception('Missing payload!');
    }

    $preGameAuthPayload = $_POST['payload'];
    $preGameAuthPayloadJSON = json_decode($preGameAuthPayload, 1);

    if (!isset($preGameAuthPayloadJSON) || empty($preGameAuthPayloadJSON)) {
        throw new Exception('Payload not JSON!');
    }

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    if (
        !isset($preGameAuthPayloadJSON['modID']) || empty($preGameAuthPayloadJSON['modID']) ||
        !isset($preGameAuthPayloadJSON['steamID32']) || empty($preGameAuthPayloadJSON['steamID32']) ||
        !isset($preGameAuthPayloadJSON['matchID']) || empty($preGameAuthPayloadJSON['matchID']) || !is_numeric($preGameAuthPayloadJSON['matchID'])
    ) {
        throw new Exception('Payload missing fields!');
    }

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    //MATCH CHECK
    {
        $matchDetails = cached_query(
            's2_match_query_' . $preGameAuthPayloadJSON['matchID'],
            'SELECT
                `matchID`,
                `matchAuthKey`,
                `modID`,
                `matchHostSteamID32`,
                `matchPhaseID`,
                `isDedicated`,
                `numPlayers`,
                `matchWinningTeamID`,
                `matchDuration`,
                `schemaVersion`,
                `dateUpdated`,
                `dateRecorded`
            FROM `s2_match`
            WHERE `matchID` = ? AND `modID` = ?;',
            'ss',
            array(
                $preGameAuthPayloadJSON['matchID'],
                $preGameAuthPayloadJSON['modID']
            ),
            5
        );
    }

    if (!isset($matchDetails) || empty($matchDetails)) {
        throw new Exception('No match found matching parameters!');
    }

    //CLIENT DETAILS
    {
        if (!empty($preGameAuthPayloadJSON['steamID32'])) {
            $remoteIP = isset($_SERVER['REMOTE_ADDR']) && !empty($_SERVER['REMOTE_ADDR'])
                ? $_SERVER['REMOTE_ADDR']
                : '??';

            $steamID_manipulator = new SteamID();

            $steamID_manipulator->setSteamID($preGameAuthPayloadJSON['steamID32']);

            $steamID32 = $steamID_manipulator->getSteamID32();
            $steamID64 = $steamID_manipulator->getSteamID64();

            $sqlResult = $db->q(
                'INSERT INTO `s2_match_client_details`(`matchID`, `modID`, `steamID32`, `steamID64`, `clientIP`, `isHost`)
                    VALUES (?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                      `clientIP` = VALUES(`clientIP`);',
                'sssssi',
                array(
                    $preGameAuthPayloadJSON['matchID'],
                    $preGameAuthPayloadJSON['modID'],
                    $steamID32,
                    $steamID64,
                    $remoteIP,
                    0
                )
            );
        }
    }

    if (!empty($sqlResult)) {
        $s2_response['result'] = 1;
    } else {
        //SOMETHING FUNKY HAPPENED
        $s2_response['result'] = 0;
        $s2_response['error'] = 'Unknown error!';
    }

} catch (Exception $e) {
    unset($s2_response);
    $s2_response['result'] = 0;
    $s2_response['error'] = 'Caught Exception: ' . $e->getMessage();
} finally {
    if (isset($memcache)) $memcache->close();
}

try {
    header('Content-Type: application/json');
    echo utf8_encode(json_encode($s2_response));
} catch (Exception $e) {
    unset($s2_response);
    $s2_response['result'] = 0;
    $s2_response['error'] = 'Caught Exception: ' . $e->getMessage();
    echo utf8_encode(json_encode($s2_response));
}