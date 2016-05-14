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

    if (!isset($preGameAuthPayloadJSON['schemaVersion']) || empty($preGameAuthPayloadJSON['schemaVersion']) || $preGameAuthPayloadJSON['schemaVersion'] < $requiredSchemaVersionPlayerSummary) { //CHECK THAT SCHEMA VERSION IS CURRENT
        throw new Exception('Schema version out of date!');
    }

    if (
        !isset($preGameAuthPayloadJSON['modIdentifier']) || empty($preGameAuthPayloadJSON['modIdentifier']) ||
        empty($preGameAuthPayloadJSON['players']) || !is_array($preGameAuthPayloadJSON['players'])
    ) {
        throw new Exception('Payload missing required field(s)!');
    }

    $memcached = new Cache(NULL, NULL, $localDev);

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $modIdentifier = $preGameAuthPayloadJSON['modIdentifier'];
    $fullData = !empty($preGameAuthPayloadJSON['full'])
        ? true
        : false;

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

    //PLAYER SUMMARY
    {
        $steamIDconvertor = new SteamID();

        $playerArraySQLstring = '';
        foreach ($preGameAuthPayloadJSON['players'] as $key => $value) {
            if (is_numeric($value)) {
                $steamIDconvertor->setSteamID($value);
                $steamID64 = $steamIDconvertor->getSteamID64();

                if (!empty($playerArraySQLstring)) {
                    $playerArraySQLstring .= ', ';
                }

                $playerArraySQLstring .= !empty($steamID64)
                    ? '"' . $steamID64 . '"'
                    : '';
            }
        }

        if (empty($playerArraySQLstring)) throw new Exception('No valid players selected!');

        $playersSQL = $db->q(
            "SELECT
                    `steamID32` as sid,
                    `numGames` AS ng,
                    `numWins` AS nw,
                    `numAbandons` AS na,
                    `numFails` AS nf,
                    `lastAbandon` AS la,
                    `lastFail` AS lf,
                    `lastRegular` AS lr,
                    `dateUpdated` AS lu
                FROM `s2_user_game_summary`
                WHERE `modID` = ? AND `steamID64` IN ({$playerArraySQLstring});",
            'i',
            $modID
        );
    }

    if (!empty($playersSQL)) {
        foreach ($playersSQL as $key => $value) {
            $lastAbandon = !empty($value['la'])
                ? relative_time_v3($value['la'], 1, 'hour', true)
                : array('number' => -1);
            $playersSQL[$key]['la'] = doubleval($lastAbandon['number']);

            $lastFail = !empty($value['lf'])
                ? relative_time_v3($value['lf'], 1, 'hour', true)
                : array('number' => -1);
            $playersSQL[$key]['lf'] = doubleval($lastFail['number']);

            $lastRegular = !empty($value['lr'])
                ? relative_time_v3($value['lr'], 1, 'hour', true)
                : array('number' => -1);
            $playersSQL[$key]['lr'] = doubleval($lastRegular['number']);

            $dateUpdated = !empty($value['lu'])
                ? relative_time_v3($value['lu'], 1, 'hour', true)
                : array('number' => -1);
            $playersSQL[$key]['lu'] = doubleval($dateUpdated['number']);
        }

        $s2_response['result'] = $playersSQL;
        $s2_response['schemaVersion'] = $responseSchemaVersionPlayerSummary;
    } else {
        //SOMETHING FUNKY HAPPENED
        $s2_response['result'] = 0;
        $s2_response['error'] = 'No details for selected player(s)!';
        $s2_response['schemaVersion'] = $responseSchemaVersionPlayerSummary;
    }

} catch (Exception $e) {
    unset($s2_response);
    $s2_response['result'] = 0;
    $s2_response['error'] = 'Caught Exception: ' . $e->getMessage();
    $s2_response['schemaVersion'] = $responseSchemaVersionPlayerSummary;
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