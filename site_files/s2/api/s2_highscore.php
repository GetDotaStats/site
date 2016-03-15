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

    if (!isset($preGameAuthPayloadJSON['schemaVersion']) || empty($preGameAuthPayloadJSON['schemaVersion']) || $preGameAuthPayloadJSON['schemaVersion'] < $requiredSchemaVersionHighscore) { //CHECK THAT SCHEMA VERSION IS CURRENT
        throw new Exception('Schema version out of date!');
    }

    $acceptableTypes = array(
        'SAVE',
        'LIST',
        'TOP'
    );

    if (
        !isset($preGameAuthPayloadJSON['type']) ||
        empty($preGameAuthPayloadJSON['type']) ||
        !in_array($preGameAuthPayloadJSON['type'], $acceptableTypes, 1)
    ) {
        throw new Exception('Missing or invalid type!');
    }

    $memcached = new Cache(NULL, NULL, $localDev);

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $steamIDConvertor = new SteamID();


    ///////////////
    // SAVE
    ///////////////
    {
        if ($preGameAuthPayloadJSON['type'] == 'SAVE') {
            $s2_response['type'] = 'save';

            if (
                !isset($preGameAuthPayloadJSON['modIdentifier']) || empty($preGameAuthPayloadJSON['modIdentifier']) ||
                !isset($preGameAuthPayloadJSON['highscoreID']) || empty($preGameAuthPayloadJSON['highscoreID']) ||
                !isset($preGameAuthPayloadJSON['steamID32']) || empty($preGameAuthPayloadJSON['steamID32']) ||
                !isset($preGameAuthPayloadJSON['userName']) || empty($preGameAuthPayloadJSON['userName']) ||
                !isset($preGameAuthPayloadJSON['highscoreValue']) || empty($preGameAuthPayloadJSON['highscoreValue']) || !is_numeric($preGameAuthPayloadJSON['highscoreValue'])
            ) {
                throw new Exception('Payload missing fields for given type!');
            }

            $highscoreID = $preGameAuthPayloadJSON['highscoreID'];
            $modIdentifier = $preGameAuthPayloadJSON['modIdentifier'];
            $highscoreType = $preGameAuthPayloadJSON['type'];
            $playerName = $preGameAuthPayloadJSON['userName'];
            $highscoreValue = $preGameAuthPayloadJSON['highscoreValue'];
            $userAuthKey = !empty($preGameAuthPayloadJSON['userAuthKey'])
                ? $preGameAuthPayloadJSON['userAuthKey']
                : NULL;

            $steamIDConvertor->setSteamID($preGameAuthPayloadJSON['steamID32']);
            $playerSteamID32 = $steamIDConvertor->getSteamID32();
            $playerSteamID64 = $steamIDConvertor->getSteamID64();

            $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $newAuthKey = '';
            for ($i = 0; $i < 10; $i++)
                $newAuthKey .= $characters[rand(0, 35)];

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
                's',
                $modIdentifier,
                15
            );

            if (empty($modIdentifierCheck)) throw new Exception('Invalid modID!');

            $modID = $modIdentifierCheck[0]['mod_id'];

            $hsidLookup = cached_query(
                's2_highscore_hsid_lookup_' . $highscoreID,
                'SELECT
                      `highscoreIdentifier`,
                      `highscoreID`,
                      `modID`,
                      `modIdentifier`,
                      `secureWithAuth`,
                      `highscoreName`,
                      `highscoreDescription`,
                      `highscoreActive`,
                      `highscoreObjective`,
                      `highscoreOperator`,
                      `highscoreFactor`,
                      `highscoreDecimals`,
                      `date_recorded`
                    FROM `stat_highscore_mods_schema`
                    WHERE `highscoreID` = ?
                    LIMIT 0,1;',
                's',
                array(
                    $highscoreID
                ),
                60
            );
            if (empty($hsidLookup)) throw new Exception('Invalid highscoreID!');

            $saveLookup = cached_query(
                's2_highscore_save_lookup_' . $modID . '_' . $highscoreID . '_' . $playerSteamID64,
                'SELECT
                            `modID`,
                            `highscoreID`,
                            `steamID32`,
                            `steamID64`,
                            `highscoreAuthKey`,
                            `userName`,
                            `highscoreValue`,
                            `date_recorded`
                        FROM `stat_highscore_mods`
                        WHERE `modID` = ? AND `highscoreID` = ? AND `steamID64` = ?
                        LIMIT 0,1;',
                'iss',
                array(
                    $modID,
                    $highscoreID,
                    $playerSteamID64
                ),
                5
            );

            if (empty($saveLookup) || $hsidLookup[0]['secureWithAuth'] == 0 || ($hsidLookup[0]['secureWithAuth'] == 1 && $saveLookup[0]['highscoreAuthKey'] == $userAuthKey)) {
                $sqlResult = $db->q(
                    'INSERT INTO `stat_highscore_mods` (`modID`, `highscoreID`, `steamID32`, `steamID64`, `highscoreAuthKey`, `userName`, `highscoreValue`, `date_recorded`)
                        VALUES (?, ?, ?, ?, ?, ?, ?, NULL)
                            ON DUPLICATE KEY UPDATE
                                `highscoreValue` = GREATEST(`highscoreValue`, VALUES(`highscoreValue`)),
                                `userName` = VALUES(`userName`),
                                `highscoreAuthKey` = VALUES(`highscoreAuthKey`),
                                `date_recorded` = NULL;',
                    'isssssi',
                    array(
                        $modID,
                        $highscoreID,
                        $playerSteamID32,
                        $playerSteamID64,
                        $newAuthKey,
                        $playerName,
                        $highscoreValue
                    )
                );

                $db->q(
                    'INSERT INTO `stat_highscore_mods_top` (`modID`, `highscoreID`, `steamID64`, `steamID32`, `userName`, `highscoreValue`)
                        VALUES (?, ?, ?, ?, ?, ?);',
                    'issssi',
                    array(
                        $modID,
                        $highscoreID,
                        $playerSteamID64,
                        $playerSteamID32,
                        $playerName,
                        $highscoreValue
                    )
                );

                $s2_response['authKey'] = $newAuthKey;
            } else {
                if (empty($userAuthKey)) {
                    throw new Exception('User provided authKey field is empty!');
                } else if ($hsidLookup[0]['secureWithAuth'] == 1 && $saveLookup[0]['highscoreAuthKey'] == $userAuthKey) {
                    throw new Exception('Invalid authKey for this save!');
                } else {
                    throw new Exception('Save already exists, is secure, and the authKey provided did not match!');
                }
            }
        }
    }

    ///////////////
    // LIST
    ///////////////
    {
        if ($preGameAuthPayloadJSON['type'] == 'LIST') {
            $s2_response['type'] = 'list';

            if (
                !isset($preGameAuthPayloadJSON['modIdentifier']) || empty($preGameAuthPayloadJSON['modIdentifier']) ||
                !isset($preGameAuthPayloadJSON['steamID32']) || empty($preGameAuthPayloadJSON['steamID32'])
            ) {
                throw new Exception('Payload missing fields for given type!');
            }

            $modIdentifier = $preGameAuthPayloadJSON['modIdentifier'];

            $steamIDConvertor->setSteamID($preGameAuthPayloadJSON['steamID32']);
            $playerSteamID32 = $steamIDConvertor->getSteamID32();
            $playerSteamID64 = $steamIDConvertor->getSteamID64();

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
                's',
                $modIdentifier,
                15
            );
            if (empty($modIdentifierCheck)) throw new Exception('Invalid modID!');

            $modID = $modIdentifierCheck[0]['mod_id'];


            $sqlResult = cached_query(
                's2_highscore_list_lookup_' . $modID . '_' . $playerSteamID64,
                'SELECT
                        `highscoreID`,
                        `highscoreValue`,
                        `highscoreAuthKey`,
                        `date_recorded`
                    FROM `stat_highscore_mods`
                    WHERE `modID` = ? AND `steamID64` = ?;',
                'is',
                array(
                    $modID,
                    $playerSteamID64
                ),
                5
            );

            $s2_response['jsonData'] = $sqlResult;
        }
    }

    ///////////////
    // TOP
    ///////////////
    {
        if ($preGameAuthPayloadJSON['type'] == 'TOP') {
            $s2_response['type'] = 'top';

            if (
                !isset($preGameAuthPayloadJSON['modIdentifier']) || empty($preGameAuthPayloadJSON['modIdentifier'])
            ) {
                throw new Exception('Payload missing fields for given type!');
            }

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
                        `mod_maps`,
                        `mod_max_players`,
                        `mod_options_enabled`,
                        `mod_options`,
                        `date_recorded`
                    FROM `mod_list`
                    WHERE `mod_identifier` = ?
                    LIMIT 0,1;',
                's',
                $modIdentifier,
                15
            );
            if (empty($modIdentifierCheck)) throw new Exception('Invalid modID!');

            $modID = $modIdentifierCheck[0]['mod_id'];

            $topLookup = cached_query(
                's2_highscore_top_schema_lookup_' . $modID,
                'SELECT
                        `modID`,
                        `modIdentifier`,
                        `highscoreID`,
                        `highscoreName`,
                        `highscoreDescription`,
                        `highscoreActive`,
                        `highscoreObjective`,
                        `highscoreOperator`,
                        `highscoreFactor`,
                        `highscoreDecimals`
                    FROM `stat_highscore_mods_schema`
                    WHERE `modID` = ?
                    ORDER BY
                      `date_recorded` ASC
                    LIMIT 0,1;',
                's',
                array(
                    $modID
                ),
                30
            );
            if (empty($topLookup)) throw new Exception('No schema for selected mod!');

            $topObjective = !empty($topLookup) && isset($topLookup[0]['highscoreObjective']) && $topLookup[0]['highscoreObjective'] == 'min'
                ? 'ASC'
                : 'DESC';

            $sqlResult = cached_query(
                's2_highscore_top_lookup_' . $modID,
                'SELECT
                            `highscoreID`,
                            `userName`,
                            `steamID32`,
                            `highscoreValue`,
                            `date_recorded`
                        FROM `stat_highscore_mods_top`
                        WHERE `modID` = ?
                        ORDER BY
                            `modID`,
                            `highscoreID`,
                            `highscoreValue` ' . $topObjective . ',
                            `date_recorded`;',
                's',
                array(
                    $modID
                ),
                30
            );

            $s2_response['jsonData'] = $sqlResult;
        }
    }


    if (!empty($sqlResult)) {
        $s2_response['result'] = 1;
        $s2_response['schemaVersion'] = $responseSchemaVersionHighscore;
    } else {
        //SOMETHING FUNKY HAPPENED
        $s2_response['result'] = 0;
        $s2_response['error'] = 'Unknown error!';
        $s2_response['schemaVersion'] = $responseSchemaVersionHighscore;
    }

} catch (Exception $e) {
    unset($s2_response);
    $s2_response['result'] = 0;
    $s2_response['error'] = 'Caught Exception: ' . $e->getMessage();
    $s2_response['schemaVersion'] = $responseSchemaVersionHighscore;
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