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

    if (!isset($preGameAuthPayloadJSON['schemaVersion']) || empty($preGameAuthPayloadJSON['schemaVersion']) || $preGameAuthPayloadJSON['schemaVersion'] < $requiredSchemaVersionPhase1) { //CHECK THAT SCHEMA VERSION IS CURRENT
        throw new Exception('Schema version out of date!');
    }

    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $authKey = '';
    for ($i = 0; $i < 10; $i++)
        $authKey .= $characters[rand(0, 35)];

    if ($preGameAuthPayloadJSON['schemaVersion'] < 3) {
        //V1 & V2 required numPlayers
        if (
            !isset($preGameAuthPayloadJSON['modIdentifier']) || empty($preGameAuthPayloadJSON['modIdentifier']) ||
            !isset($preGameAuthPayloadJSON['hostSteamID32']) || empty($preGameAuthPayloadJSON['hostSteamID32']) ||
            !isset($preGameAuthPayloadJSON['numPlayers']) || empty($preGameAuthPayloadJSON['numPlayers']) || !is_numeric($preGameAuthPayloadJSON['numPlayers'])
        ) {
            throw new Exception('Payload missing fields!');
        }
    } else {
        //V3 removed numPlayers
        if (
            !isset($preGameAuthPayloadJSON['modIdentifier']) || empty($preGameAuthPayloadJSON['modIdentifier']) ||
            !isset($preGameAuthPayloadJSON['hostSteamID32']) || empty($preGameAuthPayloadJSON['hostSteamID32'])
        ) {
            throw new Exception('Payload missing fields!');
        }
    }

    $memcached = new Cache(NULL, NULL, $localDev);

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $modIdentifier = $preGameAuthPayloadJSON['modIdentifier'];

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

    //MATCH DETAILS
    {
        if ($preGameAuthPayloadJSON['schemaVersion'] < 3) {
            //V1 & V2 required numPlayers
            $sqlResult = $db->q(
                'INSERT INTO `s2_match`(`matchAuthKey`, `modID`, `matchHostSteamID32`, `matchPhaseID`, `numPlayers`, `schemaVersion`, `dateUpdated`, `dateRecorded`)
                    VALUES (?, ?, ?, ?, ?, ?, NULL, NULL);',
                'sisiii',
                array(
                    $authKey,
                    $modID,
                    $preGameAuthPayloadJSON['hostSteamID32'],
                    1,
                    $preGameAuthPayloadJSON['numPlayers'],
                    $preGameAuthPayloadJSON['schemaVersion']
                )
            );
        } else {
            //V3 removed numPlayers
            $sqlResult = $db->q(
                'INSERT INTO `s2_match`(`matchAuthKey`, `modID`, `matchHostSteamID32`, `matchPhaseID`, `schemaVersion`, `dateUpdated`, `dateRecorded`)
                    VALUES (?, ?, ?, ?, ?, NULL, NULL);',
                'sisii',
                array(
                    $authKey,
                    $modID,
                    $preGameAuthPayloadJSON['hostSteamID32'],
                    1,
                    $preGameAuthPayloadJSON['schemaVersion']
                )
            );
        }
    }

    $matchID = $db->last_index();

    if (!empty($sqlResult)) {
        $s2_response['result'] = 1;
        $s2_response['authKey'] = $authKey;
        $s2_response['matchID'] = $matchID;
        $s2_response['modID'] = $modID;
        $s2_response['modIdentifier'] = $modIdentifier;
        $s2_response['schemaVersion'] = $responseSchemaVersionPhase1;

        /*
        $irc_message = new irc_message($webhook_gds_site_announce);

        $message = array(
            array(
                '[',
                $irc_message->colour_generator('bold'),
                $irc_message->colour_generator('blue'),
                'PHASE1',
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
                'Players:',
                $irc_message->colour_generator(NULL),
            ),
            array($preGameAuthPayloadJSON['numPlayers']),
            array(' || http://getdotastats.com/#s2__match?id=' . $matchID),
        );

        $message = $irc_message->combine_message($message);
        $irc_message->post_message($message, array('localDev' => $localDev));*/
    } else {
        //SOMETHING FUNKY HAPPENED
        $s2_response['result'] = 0;
        $s2_response['error'] = 'Unknown error!';
        $s2_response['schemaVersion'] = $responseSchemaVersionPhase1;
    }

} catch (Exception $e) {
    unset($s2_response);
    $s2_response['result'] = 0;
    $s2_response['error'] = 'Caught Exception: ' . $e->getMessage();
    $s2_response['schemaVersion'] = $responseSchemaVersionPhase1;
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