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

    if (!isset($preGameAuthPayloadJSON['schemaVersion']) || empty($preGameAuthPayloadJSON['schemaVersion']) || $preGameAuthPayloadJSON['schemaVersion'] < $currentSchemaVersionCustom) { //CHECK THAT SCHEMA VERSION IS CURRENT
        throw new Exception('Schema version out of date!');
    }

    if (
        !isset($preGameAuthPayloadJSON['authKey']) || empty($preGameAuthPayloadJSON['authKey']) ||
        !isset($preGameAuthPayloadJSON['matchID']) || !is_numeric($preGameAuthPayloadJSON['matchID']) ||
        !isset($preGameAuthPayloadJSON['modIdentifier']) || empty($preGameAuthPayloadJSON['modIdentifier']) ||
        !isset($preGameAuthPayloadJSON['schemaAuthKey']) || empty($preGameAuthPayloadJSON['schemaAuthKey']) ||
        !isset($preGameAuthPayloadJSON['rounds']) || empty($preGameAuthPayloadJSON['rounds'])
    ) {
        throw new Exception('Payload missing fields!');
    }

    $matchID = $preGameAuthPayloadJSON['matchID'];
    $modIdentifier = htmlentities($preGameAuthPayloadJSON['modIdentifier']);
    $authKey = htmlentities($preGameAuthPayloadJSON['authKey']);
    $schemaAuthKey = htmlentities($preGameAuthPayloadJSON['schemaAuthKey']);

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
                `mod_maps`,
                `mod_max_players`,
                `mod_options_enabled`,
                `mod_options`,
                `date_recorded`
            FROM `mod_list`
            WHERE `mod_identifier` = ?
            LIMIT 0,1;',
        'i',
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
                `isDedicated`,
                `matchMapName`,
                `numPlayers`,
                `numRounds`,
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

    if (empty($matchDetails)) {
        throw new Exception('No match found matching parameters!');
    }

    //SCHEMA CHECK
    {
        $schemaDetails = cached_query(
            's2_schema_query_' . $schemaAuthKey,
            'SELECT
                  `schemaID`,
                  `modID`,
                  `schemaAuth`,
                  `schemaVersion`,
                  `schemaApproved`,
                  `schemaRejected`,
                  `schemaRejectedReason`,
                  `schemaSubmitterUserID64`,
                  `schemaApproverUserID64`,
                  `dateRecorded`
            FROM `s2_mod_custom_schema`
            WHERE `schemaAuth` = ? AND `schemaApproved` = 1
            LIMIT 0,1;',
            's',
            array(
                $schemaAuthKey
            ),
            15
        );
    }

    if (empty($schemaDetails)) {
        throw new Exception('No schema found matching parameters!');
    }

    $schemaID = $schemaDetails[0]['schemaID'];

    //GRAB SCHEMA FIELDS
    {
        $schemaDetailsFields = cached_query(
            's2_schema_fields_query_' . $schemaID,
            'SELECT
                  `schemaID`,
                  `fieldType`,
                  `fieldOrder`,
                  `customValueObjective`,
                  `customValueDisplay`,
                  `customValueName`
            FROM `s2_mod_custom_schema_fields`
            WHERE `schemaID` = ?
            ORDER BY `fieldType` ASC, `fieldOrder` ASC;',
            'i',
            array(
                $schemaID
            ),
            15
        );
    }

    if (empty($schemaDetailsFields)) {
        throw new Exception('No schema fields found for schema!');
    }

    //CONSTRUCT ARRAYS FOR FIELDS TO CHECK AGAINST
    {
        $schemaFieldsGameArray = array();
        $schemaFieldsPlayerArray = array();

        foreach ($schemaDetailsFields as $key => $value) {
            if ($value['fieldType'] == 1) {
                $schemaFieldsGameArray[$value['fieldOrder']] = $value['customValueName'];
            } else if ($value['fieldType'] == 2) {
                $schemaFieldsPlayerArray[$value['fieldOrder']] = $value['customValueName'];
            }
        }

        $schemaFieldsGameArrayResult = array();
        $schemaFieldsPlayerArrayResult = array();

        //Iterate through the rounds
        foreach ($preGameAuthPayloadJSON['rounds'] as $key => $value) {
            //Check that all of the fields defined as Game variables in the schema are present in at least Round 0
            foreach ($schemaFieldsGameArray as $key2 => $value2) {
                if (isset($value['game'][$value2])) {
                    $schemaFieldsGameArrayResult[$key][$key2] = $value['game'][$value2];
                } else if ($key == 0) {
                    throw new Exception("Missing `$value2` from Round `$key` in Game array!");
                }
            }

            //Check that all of the fields defined as Player variables in the schema are present in all rounds
            //Iterate through player object
            foreach ($value['players'] as $key2 => $value2) {
                foreach ($schemaFieldsPlayerArray as $key3 => $value3) {
                    if (isset($value2[$value3]) && isset($value2['steamID32'])) {
                        $schemaFieldsPlayerArrayResult[$key][$value2['steamID32']][$key3] = $value2[$value3];
                    } else {
                        throw new Exception("Missing `$value3` from Round `$key` in Player array!");
                    }
                }
            }
        }

        /////////////////////////////////////////
        //INSERT custom Game values
        /////////////////////////////////////////
        if (!empty($schemaFieldsGameArrayResult)) {
            //Iterate over rounds
            foreach ($schemaFieldsGameArrayResult as $key => $value) {
                //Iterate over fields in round
                foreach ($value as $key2 => $value2) {
                    $sqlResultGame = $db->q(
                        'INSERT INTO `s2_match_custom`
                                (`matchID`, `modID`, `schemaID`, `round`, `fieldOrder`, `fieldValue`)
                            VALUES (?, ?, ?, ?, ?, ?)
                            ON DUPLICATE KEY UPDATE
                                `fieldValue` = VALUES(`fieldValue`);',
                        'iiiiis',
                        array(
                            $matchID,
                            $modID,
                            $schemaID,
                            $key,
                            $key2,
                            $value2
                        )
                    );
                }
            }

        }

        /////////////////////////////////////////
        //INSERT custom Player values
        /////////////////////////////////////////
        if (!empty($schemaFieldsPlayerArrayResult)) {
            //Iterate over rounds
            foreach ($schemaFieldsPlayerArrayResult as $key => $value) {
                //Iterate over players in round
                foreach ($value as $key2 => $value2) {
                    //Iterate over fields in player
                    foreach ($value2 as $key3 => $value3) {
                        $sqlResultPlayer = $db->q(
                            'INSERT INTO `s2_match_players_custom`
                                    (`matchID`, `modID`, `schemaID`, `round`, `userID32`, `fieldOrder`, `fieldValue`)
                                VALUES (?, ?, ?, ?, ?, ?, ?)
                                ON DUPLICATE KEY UPDATE
                                    `fieldValue` = VALUES(`fieldValue`);',
                            'iiiisis',
                            array(
                                $matchID,
                                $modID,
                                $schemaID,
                                $key,
                                $key2,
                                $key3,
                                $value3
                            )
                        );
                    }
                }
            }

        }
    }


    $s2_response['result'] = 1;
    $s2_response['schemaVersion'] = $currentSchemaVersionCustom;

    $irc_message = new irc_message($webhook_gds_site_announce);

    $message = array(
        array(
            '[',
            $irc_message->colour_generator('bold'),
            $irc_message->colour_generator('blue'),
            'CUSTOM',
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
    $irc_message->post_message($message, array('localDev' => $localDev));

} catch (Exception $e) {
    unset($s2_response);
    $s2_response['result'] = 0;
    $s2_response['error'] = 'Caught Exception: ' . $e->getMessage();
    $s2_response['schemaVersion'] = $currentSchemaVersionCustom;
} finally {
    if (isset($memcache)) $memcache->close();
}

try {
    //header('Content-Type: application/json');
    echo utf8_encode(json_encode($s2_response));
} catch (Exception $e) {
    unset($s2_response);
    $s2_response['result'] = 0;
    $s2_response['error'] = 'Caught Exception: ' . $e->getMessage();
    echo utf8_encode(json_encode($s2_response));
}