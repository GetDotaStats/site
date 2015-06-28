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

    if (!isset($preGameAuthPayloadJSON['gamePhase']) || empty($preGameAuthPayloadJSON['gamePhase']) || $preGameAuthPayloadJSON['gamePhase'] != 1) { //CHECK THAT gamePhase IS CORRECT
        throw new Exception('Wrong endpoint for this phase!');
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
        !isset($preGameAuthPayloadJSON['players']) || empty($preGameAuthPayloadJSON['players'])
    ) {
        throw new Exception('Payload missing fields!');
    }

    $numPlayers = count($preGameAuthPayloadJSON['players']);
    $preGameAuthPayloadJSON['isDedicated'] = !isset($preGameAuthPayloadJSON['isDedicated']) || empty($preGameAuthPayloadJSON['isDedicated'])
        ? 0
        : 1;

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    //MATCH DETAILS
    {
        $sqlResult = $db->q(
            'INSERT INTO `s2_match`(`matchAuthKey`, `modID`, `matchHostSteamID32`, `matchPhaseID`, `isDedicated`, `numPlayers`, `schemaVersion`, `dateUpdated`, `dateRecorded`)
                VALUES (?, ?, ?, ?, ?, ?, ?, NULL, NULL);',
            'sssiiii',
            array(
                $authKey,
                $preGameAuthPayloadJSON['modID'],
                $preGameAuthPayloadJSON['hostSteamID32'],
                $preGameAuthPayloadJSON['gamePhase'],
                $preGameAuthPayloadJSON['isDedicated'],
                $numPlayers,
                $preGameAuthPayloadJSON['schemaVersion']
            )
        );
    }

    $matchID = $db->last_index();

    //PLAYERS DETAILS
    {
        if(!empty($preGameAuthPayloadJSON['players'])){
            $steamID_manipulator = new SteamID();

            foreach($preGameAuthPayloadJSON['players'] as $key => $value){
                $steamID_manipulator->setSteamID($value['steamID32']);

                $steamID32 = $steamID_manipulator->getSteamID32();
                $steamID64 = $steamID_manipulator->getSteamID64();

                $db->q(
                    'INSERT INTO `s2_match_players`(`matchID`, `roundID`, `modID`, `steamID32`, `steamID64`, `playerName`, `teamID`, `slotID`, `heroID`, `connectionState`)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                          `playerName` = VALUES(`playerName`),
                          `teamID` = VALUES(`teamID`),
                          `slotID` = VALUES(`slotID`),
                          `heroID` = VALUES(`heroID`),
                          `connectionState` = VALUES(`connectionState`);',
                    'sissssiiii',
                    array(
                        $matchID,
                        1,
                        $preGameAuthPayloadJSON['modID'],
                        $steamID32,
                        $steamID64,
                        $value['playerName'],
                        $value['teamID'],
                        $value['slotID'],
                        $value['heroID'],
                        $value['connectionState']
                    )
                );
            }
        }
    }

    //FLAGS
    {
        if(!empty($preGameAuthPayloadJSON['flags'])){
            foreach($preGameAuthPayloadJSON['flags'] as $key => $value){
                $db->q(
                    'INSERT INTO `s2_match_flags`(`matchID`, `modID`, `flag`)
                        VALUES (?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                          `flag` = VALUES(`flag`);',
                    'sss',
                    array(
                        $matchID,
                        $preGameAuthPayloadJSON['modID'],
                        $value
                    )
                );
            }
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
    }

    //$memcache->close();

} catch (Exception $e) {
    unset($s2_response);
    $s2_response['result'] = 0;
    $s2_response['error'] = 'Caught Exception: ' . $e->getMessage();
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