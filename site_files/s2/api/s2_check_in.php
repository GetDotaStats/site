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

    if (!isset($preGameAuthPayloadJSON['schemaVersion']) || empty($preGameAuthPayloadJSON['schemaVersion']) || $preGameAuthPayloadJSON['schemaVersion'] < $requiredSchemaVersionClientCheckIn) { //CHECK THAT SCHEMA VERSION IS CURRENT
        throw new Exception('Schema version out of date!');
    }

    if (
        !isset($preGameAuthPayloadJSON['modIdentifier']) || empty($preGameAuthPayloadJSON['modIdentifier']) ||
        !isset($preGameAuthPayloadJSON['steamID32']) || empty($preGameAuthPayloadJSON['steamID32']) ||
        !isset($preGameAuthPayloadJSON['matchID']) || empty($preGameAuthPayloadJSON['matchID']) || !is_numeric($preGameAuthPayloadJSON['matchID'])
    ) {
        throw new Exception('Payload missing fields!');
    }

    if (isset($preGameAuthPayloadJSON['isHost']) && !is_numeric($preGameAuthPayloadJSON['isHost'])) {
        throw new Exception('Field `isHost` has invalid value!');
    }

    $modIdentifier = $preGameAuthPayloadJSON['modIdentifier'];
    $matchID = $preGameAuthPayloadJSON['matchID'];
    $isHost = isset($preGameAuthPayloadJSON['isHost']) && $preGameAuthPayloadJSON['isHost'] == '1'
        ? 1
        : 0;

    $memcached = new Cache(NULL, NULL, $localDev);

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    //Check if the modIdentifier is valid
    {
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
        if (empty($modIdentifierCheck)) throw new Exception('Invalid modID!');
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
                `isDedicated`,
                `matchMapName`,
                `numRounds`,
                `schemaVersion`,
                `dateUpdated`,
                `dateRecorded`
            FROM `s2_match`
            WHERE `matchID` = ? AND `modID` = ?
            LIMIT 0,1;',
            'ss',
            array(
                $matchID,
                $modID
            ),
            5
        );
        if (empty($matchDetails)) throw new Exception('No match found matching parameters!');
    }

    //CLIENT DETAILS
    {
        if (!empty($preGameAuthPayloadJSON['steamID32'])) {
            $remoteIP = isset($_SERVER['REMOTE_ADDR']) && !empty($_SERVER['REMOTE_ADDR'])
                ? $_SERVER['REMOTE_ADDR']
                : '??';

            if ($preGameAuthPayloadJSON['steamID32'] > 0) {
                $steamID_manipulator = new SteamID();
                $steamID_manipulator->setSteamID($preGameAuthPayloadJSON['steamID32']);
                $steamID32 = $steamID_manipulator->getSteamID32();
                $steamID64 = $steamID_manipulator->getSteamID64();
            } else {
                $steamID32 = $steamID64 = NULL;
            }


            $sqlResult = $db->q(
                'INSERT INTO `s2_match_client_details`(`matchID`, `modID`, `steamID32`, `steamID64`, `clientIP`, `isHost`)
                    VALUES (?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                      `clientIP` = VALUES(`clientIP`);',
                'sssssi',
                array(
                    $matchID,
                    $modID,
                    $steamID32,
                    $steamID64,
                    $remoteIP,
                    $isHost
                )
            );

            if (!empty($sqlResult)) {
                /*$irc_message = new irc_message($webhook_gds_site_announce);

                $message = array(
                    array(
                        '[',
                        $irc_message->colour_generator('bold'),
                        $irc_message->colour_generator('blue'),
                        'CLIENT',
                        $irc_message->colour_generator(NULL),
                        $irc_message->colour_generator('bold'),
                        ']',
                    ),
                    array(
                        $irc_message->colour_generator('red'),
                        '[' . $modIdentifierCheck[0]['mod_name'] . ']',
                        $irc_message->colour_generator(NULL),
                    ),
                    array(
                        $irc_message->colour_generator('pink'),
                        'Client:',
                        $irc_message->colour_generator(NULL),
                    ),
                    array($steamID64),
                    array(' || http://getdotastats.com/#s2__match?id=' . $matchID),
                );

                $message = $irc_message->combine_message($message);
                $irc_message->post_message($message, array('localDev' => $localDev));*/
            }
        }
    }

    $s2_response['result'] = 1;
    $s2_response['schemaVersion'] = $responseSchemaVersionClientCheckIn;

} catch (Exception $e) {
    unset($s2_response);
    $s2_response['result'] = 0;
    $s2_response['error'] = 'Caught Exception: ' . $e->getMessage();
    $s2_response['schemaVersion'] = $responseSchemaVersionClientCheckIn;
} finally {
    if (isset($memcached)) $memcached->close();
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