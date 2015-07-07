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

    if (!isset($preGameAuthPayloadJSON['schemaVersion']) || empty($preGameAuthPayloadJSON['schemaVersion']) || $preGameAuthPayloadJSON['schemaVersion'] != $currentSchemaVersion) { //CHECK THAT SCHEMA VERSION IS CURRENT
        throw new Exception('Schema version out of date!');
    }

    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $authKey = '';
    for ($i = 0; $i < 10; $i++)
        $authKey .= $characters[rand(0, 35)];

    //$memcache = new Memcache;
    //$memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    if (
        !isset($preGameAuthPayloadJSON['modID']) || empty($preGameAuthPayloadJSON['modID']) ||
        !isset($preGameAuthPayloadJSON['hostSteamID32']) || empty($preGameAuthPayloadJSON['hostSteamID32']) ||
        !isset($preGameAuthPayloadJSON['numPlayers']) || empty($preGameAuthPayloadJSON['numPlayers']) || !is_numeric($preGameAuthPayloadJSON['numPlayers'])
    ) {
        throw new Exception('Payload missing fields!');
    }

    $preGameAuthPayloadJSON['isDedicated'] = !isset($preGameAuthPayloadJSON['isDedicated']) || empty($preGameAuthPayloadJSON['isDedicated'])
        ? 0
        : 1;

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    //MATCH DETAILS
    {
        $sqlResult = $db->q(
            'INSERT INTO `s2_match`(`matchAuthKey`, `modID`, `matchHostSteamID32`, `matchPhaseID`, `isDedicated`, `matchMapName`, `numPlayers`, `schemaVersion`, `dateUpdated`, `dateRecorded`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL, NULL);',
            'sssiisii',
            array(
                $authKey,
                $preGameAuthPayloadJSON['modID'],
                $preGameAuthPayloadJSON['hostSteamID32'],
                1,
                $preGameAuthPayloadJSON['isDedicated'],
                $preGameAuthPayloadJSON['mapName'],
                $preGameAuthPayloadJSON['numPlayers'],
                $preGameAuthPayloadJSON['schemaVersion']
            )
        );
    }

    $matchID = $db->last_index();

    //HOST DETAILS
    {
        if (!empty($preGameAuthPayloadJSON['hostSteamID32'])) {
            $remoteIP = isset($_SERVER['REMOTE_ADDR']) && !empty($_SERVER['REMOTE_ADDR'])
                ? $_SERVER['REMOTE_ADDR']
                : '??';

            $steamID_manipulator = new SteamID();

            $steamID_manipulator->setSteamID($preGameAuthPayloadJSON['hostSteamID32']);

            $steamID32 = $steamID_manipulator->getSteamID32();
            $steamID64 = $steamID_manipulator->getSteamID64();

            $db->q(
                'INSERT INTO `s2_match_client_details`(`matchID`, `modID`, `steamID32`, `steamID64`, `clientIP`, `isHost`)
                    VALUES (?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                      `modID` = VALUES(`modID`),
                      `clientIP` = VALUES(`clientIP`),
                      `isHost` = VALUES(`isHost`);',
                'sssssi',
                array(
                    $matchID,
                    $preGameAuthPayloadJSON['modID'],
                    $steamID32,
                    $steamID64,
                    $remoteIP,
                    1
                )
            );
        }
    }

    if (!empty($sqlResult)) {
        $s2_response['result'] = 1;
        $s2_response['authKey'] = $authKey;
        $s2_response['matchID'] = $matchID;
        $s2_response['modID'] = $preGameAuthPayloadJSON['modID'];
        $s2_response['schemaVersion'] = $currentSchemaVersion;
    } else {
        //SOMETHING FUNKY HAPPENED
        $s2_response['result'] = 0;
        $s2_response['error'] = 'Unknown error!';
        $s2_response['schemaVersion'] = $currentSchemaVersion;
    }

    //$memcache->close();

} catch (Exception $e) {
    unset($s2_response);
    $s2_response['result'] = 0;
    $s2_response['error'] = 'Caught Exception: ' . $e->getMessage();
    $s2_response['schemaVersion'] = $currentSchemaVersion;
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