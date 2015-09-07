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

    if (!isset($preGameAuthPayloadJSON['schemaVersion']) || empty($preGameAuthPayloadJSON['schemaVersion']) || $preGameAuthPayloadJSON['schemaVersion'] != $currentSchemaVersionPhase2) { //CHECK THAT SCHEMA VERSION IS CURRENT
        throw new Exception('Schema version out of date!');
    }

    if (
        !isset($preGameAuthPayloadJSON['authKey']) || empty($preGameAuthPayloadJSON['authKey']) ||
        !isset($preGameAuthPayloadJSON['matchID']) || empty($preGameAuthPayloadJSON['matchID']) || !is_numeric($preGameAuthPayloadJSON['matchID']) ||
        !isset($preGameAuthPayloadJSON['modID']) || empty($preGameAuthPayloadJSON['modID']) ||
        !isset($preGameAuthPayloadJSON['players']) || empty($preGameAuthPayloadJSON['players'])
    ) {
        throw new Exception('Payload missing fields!');
    }

    $numPlayers = count($preGameAuthPayloadJSON['players']);
    $matchID = $preGameAuthPayloadJSON['matchID'];
    $modID = $preGameAuthPayloadJSON['modID'];
    $authKey = $preGameAuthPayloadJSON['authKey'];

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    //MATCH CHECK
    {
        $matchDetails = cached_query(
            's2_match_query_' . $matchID,
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
            WHERE `matchID` = ? AND `modID` = ? AND `matchAuthKey` = ?;',
            'sss',
            array(
                $matchID,
                $modID,
                $authKey
            ),
            5
        );
    }

    if (!isset($matchDetails) || empty($matchDetails)) {
        throw new Exception('No match found matching parameters!');
    }

    //MATCH DETAILS
    {
        $sqlResult = $db->q(
            'INSERT INTO `s2_match`(`matchID`, `matchPhaseID`)
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE
                  `matchPhaseID` = VALUES(`matchPhaseID`);',
            'si',
            array(
                $matchID,
                2
            )
        );
    }

    //PLAYERS DETAILS
    {
        if (!empty($preGameAuthPayloadJSON['players'])) {
            $steamID_manipulator = new SteamID();

            foreach ($preGameAuthPayloadJSON['players'] as $key => $value) {
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
        if (!empty($preGameAuthPayloadJSON['flags'])) {
            foreach ($preGameAuthPayloadJSON['flags'] as $key => $value) {
                $db->q(
                    'INSERT INTO `s2_match_flags`(`matchID`, `modID`, `flagName`, `flagValue`)
                        VALUES (?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                          `flagName` = VALUES(`flagName`),
                          `flagValue` = VALUES(`flagValue`);',
                    'ssss',
                    array(
                        $matchID,
                        $preGameAuthPayloadJSON['modID'],
                        $key,
                        $value
                    )
                );
            }
        }
    }

    if (!empty($sqlResult)) {
        $s2_response['result'] = 1;
        $s2_response['schemaVersion'] = $currentSchemaVersionPhase2;
    } else {
        //SOMETHING FUNKY HAPPENED
        $s2_response['result'] = 0;
        $s2_response['error'] = 'Unknown error!';
        $s2_response['schemaVersion'] = $currentSchemaVersionPhase2;
    }

} catch (Exception $e) {
    unset($s2_response);
    $s2_response['result'] = 0;
    $s2_response['error'] = 'Caught Exception: ' . $e->getMessage();
    $s2_response['schemaVersion'] = $currentSchemaVersionPhase2;
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