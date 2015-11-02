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

    if (!isset($preGameAuthPayloadJSON['schemaVersion']) || empty($preGameAuthPayloadJSON['schemaVersion']) || $preGameAuthPayloadJSON['schemaVersion'] < $requiredSchemaVersionPhase3) { //CHECK THAT SCHEMA VERSION IS CURRENT
        throw new Exception('Schema version out of date!');
    }

    if (
        !isset($preGameAuthPayloadJSON['authKey']) || empty($preGameAuthPayloadJSON['authKey']) ||
        !isset($preGameAuthPayloadJSON['matchID']) || empty($preGameAuthPayloadJSON['matchID']) || !is_numeric($preGameAuthPayloadJSON['matchID']) ||
        !isset($preGameAuthPayloadJSON['modIdentifier']) || empty($preGameAuthPayloadJSON['modIdentifier']) ||
        !isset($preGameAuthPayloadJSON['rounds']) || empty($preGameAuthPayloadJSON['rounds'])
    ) {
        throw new Exception('Payload missing fields!');
    }

    $matchID = $preGameAuthPayloadJSON['matchID'];
    $modIdentifier = $preGameAuthPayloadJSON['modIdentifier'];
    $authKey = $preGameAuthPayloadJSON['authKey'];

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    //Check if the modIdentifier is valid
    $modIdentifierCheck = cached_query(
        's2_mod_identifier_check' . $modIdentifier,
        'SELECT
                `mod_id`,
                `steam_id64`,
                `mod_name`,
                `mod_description`,
                `mod_workshop_link`,
                `mod_steam_group`,
                `mod_active`,
                `mod_rejected`,
                `mod_rejected_reason`,
                `date_recorded`
            FROM `mod_list`
            WHERE `mod_identifier` = ?
            LIMIT 0,1;',
        's',
        $modIdentifier,
        15
    );

    if (empty($modIdentifierCheck)) {
        throw new Exception('Invalid modID!');
    }

    $modID = $modIdentifierCheck[0]['mod_id'];

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
                `numPlayers`,
                `numRounds`,
                `matchDuration`,
                `schemaVersion`,
                `dateUpdated`,
                `dateRecorded`
            FROM `s2_match`
            WHERE `matchID` = ? AND `modID` = ? AND `matchAuthKey` = ?
            LIMIT 0,1;',
            'sss',
            array(
                $matchID,
                $modID,
                $authKey
            ),
            5
        );
    }

    if (empty($matchDetails)) {
        throw new Exception('No match found matching parameters!');
    }

    //MATCH DETAILS
    {
        $gameFinished = isset($preGameAuthPayloadJSON['gameFinished']) && $preGameAuthPayloadJSON['gameFinished'] == 0
            ? 0
            : 1;

        $sqlResult = $db->q(
            'INSERT INTO `s2_match`(`matchID`, `matchPhaseID`, `matchDuration`, `matchFinished`)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                  `matchPhaseID` = VALUES(`matchPhaseID`),
                  `matchDuration` = VALUES(`matchDuration`),
                  `matchFinished` = VALUES(`matchFinished`);',
            'siii',
            array(
                $matchID,
                3,
                $preGameAuthPayloadJSON['gameDuration'],
                $gameFinished
            )
        );
    }

    //PLAYERS DETAILS
    {
        if (!empty($preGameAuthPayloadJSON['rounds'])) {
            $steamID_manipulator = new SteamID();

            foreach ($preGameAuthPayloadJSON['rounds'] as $key => $value) {
                if (!empty($value['players'])) {
                    $i = -1;
                    foreach ($value['players'] as $key2 => $value2) {
                        //Do steamID bot work around
                        if(!empty($value2['steamID32']) && is_numeric($value2['steamID32'])){
                            $steamID_manipulator->setSteamID($value2['steamID32']);

                            $steamID32 = $steamID_manipulator->getSteamID32();
                            $steamID64 = $steamID_manipulator->getSteamID64();
                        } else{
                            $steamID32 = $i;
                            $steamID64 = $i;
                            $i--;
                        }

                        $db->q(
                            'INSERT INTO `s2_match_players`(`matchID`, `roundID`, `modID`, `steamID32`, `steamID64`, `connectionState`, `isWinner`)
                                VALUES (?, ?, ?, ?, ?, ?, ?)
                                ON DUPLICATE KEY UPDATE
                                  `connectionState` = VALUES(`connectionState`),
                                  `isWinner` = VALUES(`isWinner`);',
                            'sisssii',
                            array(
                                $matchID,
                                ($key + 1),
                                $modID,
                                $steamID32,
                                $steamID64,
                                $value2['connectionState'],
                                $value2['isWinner']
                            )
                        );
                    }
                } else{
                    throw new Exception("No player data for round #$key!");
                }
            }
        }
    }

    //UPDATE NUM OF ROUNDS
    {
        $db->q(
            'UPDATE `s2_match` SET `numRounds` = (SELECT COUNT(*) FROM (SELECT `roundID` FROM `s2_match_players` WHERE `matchID` = ? GROUP BY `roundID`) t1) WHERE `matchID` = ?;',
            'ss',
            array(
                $matchID,
                $matchID,
            )
        );
    }

    if (!empty($sqlResult)) {
        $s2_response['result'] = 1;
        $s2_response['schemaVersion'] = $responseSchemaVersionPhase3;

        /*$irc_message = new irc_message($webhook_gds_site_announce);

        $message = array(
            array(
                '[',
                $irc_message->colour_generator('bold'),
                $irc_message->colour_generator('blue'),
                'PHASE3',
                $irc_message->colour_generator(NULL),
                $irc_message->colour_generator('bold'),
                ']',
            ),
            array(
                $irc_message->colour_generator('red'),
                '[' . $modIdentifierCheck[0]['mod_name'] . ']',
                $irc_message->colour_generator(NULL),
            ),
            array(' || http://getdotastats.com/#s2__match?id=' . $matchID),
        );

        $message = $irc_message->combine_message($message);
        $irc_message->post_message($message, array('localDev' => $localDev));*/
    } else {
        //SOMETHING FUNKY HAPPENED
        $s2_response['result'] = 0;
        $s2_response['error'] = 'Unknown error!';
        $s2_response['schemaVersion'] = $responseSchemaVersionPhase3;
    }

} catch (Exception $e) {
    unset($s2_response);
    $s2_response['result'] = 0;
    $s2_response['error'] = 'Caught Exception: ' . $e->getMessage();
    $s2_response['schemaVersion'] = $responseSchemaVersionPhase3;
} finally {
    if (isset($memcache)) $memcache->close();
    if (!isset($s2_response)) $s2_response = array('error' => 'Unknown exception');
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