<?php
try {
    require_once('../../global_functions.php');
    require_once('../../connections/parameters.php');

    set_time_limit(0);

    if (!isset($_SESSION)) {
        session_start();
    }

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcached = new Cache(NULL, NULL, $localDev);

    checkLogin_v2();
    if (empty($_SESSION['user_id64'])) throw new Exception('Not logged in!');

    $adminCheck = adminCheck($_SESSION['user_id64'], 'admin');
    if (empty($adminCheck)) throw new Exception('Not an admin!');

    $steamIDconvertor = new SteamID();

    $numMatches = $db->q(
        'SELECT COUNT(*) as total_reps FROM `node_listener` nl LIMIT 0,1;'
    );

    if (empty($numMatches)) {
        throw new Exception('No more matches to count!');
    }

    $repSize = 400;

    $numReps = round($numMatches[0]['total_reps'] / $repSize);

    if (empty($numReps)) {
        throw new Exception('No more reps to rep!');
    }

    echo $numReps . ' total reps of ' . $repSize . '<hr />';


    for ($i = 0; $i <= $numReps; $i++) {
        flush();
        ob_flush();

        $numMatches = $db->q(
            'SELECT COUNT(*) as total_reps FROM `node_listener` nl LIMIT 0,1;'
        );

        if (empty($numMatches)) {
            throw new Exception('No more matches to count!');
        }

        echo $numMatches[0]['total_reps'] . ' left<br />';


        $oldMatches = $db->q(
            'SELECT
                    nl.`test_id`,
                    nl.`message`,
                    nl.`date_recorded`
                FROM `node_listener` nl
                ORDER BY nl.`date_recorded` ASC
                LIMIT 0,' . $repSize . ';'
        );

        if (empty($oldMatches)) {
            throw new Exception('No more matches!');
        }

        foreach ($oldMatches as $key => $value) {
            try {
                $oldMessage = json_decode($value['message'], true);

                if(empty($oldMessage)){
                    $db->q(
                        'DELETE FROM `node_listener` WHERE `test_id` = ?;',
                        'i',
                        $value['test_id']
                    );

                    continue;
                }

                $matchID = $oldMessage['matchID'];

                //Check if match already recorded
                /*
                $matchIDLookup = cached_query(
                    'tool_maid' . $matchID,
                    'SELECT
                          `matchID`
                        FROM `s2_match`
                        WHERE `oldMatchID` = ?
                        LIMIT 0,1;',
                    's',
                    $matchID,
                    5
                );

                if (!empty($matchIDLookup)) {
                    $db->q(
                        'DELETE FROM `node_listener` WHERE `test_id` = ?;',
                        'i',
                        $value['test_id']
                    );

                    //throw new Exception("<strong>$matchID</strong> already parsed!");
                }*/


                $modIdentifier = $oldMessage['modID'];
                $duration = $oldMessage['duration'];
                $winningTeam = !empty($oldMessage['winner'])
                    ? $oldMessage['winner']
                    : 0;
                $isDedicated = !empty($oldMessage['flags']['dedicated'])
                    ? 1
                    : 0;
                $map = !empty($oldMessage['map'])
                    ? $oldMessage['map']
                    : '';
                $version = !empty($oldMessage['version'])
                    ? $oldMessage['version']
                    : 0;

                $rounds = $oldMessage['rounds'];
                $numRounds = count($oldMessage['rounds']);

                //Players array
                if (!empty($rounds['players'])) {
                    $players = $rounds['players'];
                    $numPlayers = count($players);
                } else if (!empty($rounds[0]['players'])) {
                    $players = $rounds[0]['players'];
                    $numPlayers = count($players);
                } else {
                    $numPlayers = 0;
                }

                //Flags array
                $flags = array();
                if (!empty($oldMessage['flags'])) {
                    foreach ($oldMessage['flags'] as $key2 => $value2) {
                        if ($value2 === true) {
                            $flags[$key2] = 1;
                        } else if ($value2 === false) {
                            $flags[$key2] = 0;
                        }
                    }
                } else if (!empty($oldMessage['lod_settings'])) {
                    foreach ($oldMessage['lod_settings'] as $key2 => $value2) {
                        if ($value2 === true) {
                            $flags[$key2] = 1;
                        } else if ($value2 === false) {
                            $flags[$key2] = 0;
                        }
                    }
                }

                //Modes
                if (!empty($oldMessage['modes'])) {
                    foreach ($oldMessage['modes'] as $key2 => $value2) {
                        if ($value2 === true) {
                            $flags[$key2] = 1;
                        } else if ($value2 === false) {
                            $flags[$key2] = 0;
                        }
                    }
                }

                //Check if the modID is valid
                $modIdentifierCheck = cached_query(
                    'tool_mic' . $modIdentifier,
                    'SELECT
                            `mod_id`,
                            `mod_identifier`
                        FROM `mod_list`
                        WHERE `mod_identifier` = ?
                        LIMIT 0,1;',
                    's',
                    $modIdentifier,
                    60
                );

                if(empty($modIdentifierCheck)){
                    $db->q(
                        'DELETE FROM `node_listener` WHERE `test_id` = ?;',
                        'i',
                        $value['test_id']
                    );

                    continue;
                }

                $modID = $modIdentifierCheck[0]['mod_id'];


                //////////////////////
                //RECORD MATCHES
                //////////////////////
                $newMatchDetails = array(
                    'matchAuthKey' => 'XXX',
                    'modID' => $modID,
                    'matchHostSteamID32' => '0',
                    'matchPhaseID' => '3',
                    'isDedicated' => $isDedicated,
                    'matchMapName' => $map,
                    'numPlayers' => $numPlayers,
                    'numRounds' => $numRounds,
                    'matchDuration' => $duration,
                    'schemaVersion' => $version,
                    'oldMatchID' => $matchID,
                    'dateUpdated' => $value['date_recorded'],
                    'dateRecorded' => $value['date_recorded'],
                );

                $db->q(
                    'INSERT INTO `s2_match`
                            (
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
                                `oldMatchID`,
                                `dateUpdated`,
                                `dateRecorded`
                            )
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);',
                    'sisiisiiiisss',
                    $newMatchDetails
                );

                $newMatchID = $db->last_index();

                //////////////////////
                //RECORD FLAGS
                //////////////////////
                if (!empty($flags)) {
                    foreach ($flags as $key2 => $value2) {
                        $db->q(
                            'INSERT INTO `s2_match_flags`
                                    (
                                        `matchID`,
                                        `modID`,
                                        `flagName`,
                                        `flagValue`
                                    )
                                VALUES (?, ?, ?, ?);',
                            'iiss',
                            array(
                                $newMatchID,
                                $modID,
                                $key2,
                                $value2,
                            )
                        );
                    }
                }

                //////////////////////
                //RECORD PLAYERS
                //////////////////////
                if (!empty($players)) {
                    $botCount = 1;
                    foreach ($players as $key2 => $value2) {
                        if (!empty($value2['steamID32'])) {
                            $steamIDconvertor->setSteamID($value2['steamID32']);

                            $steamID32 = $steamIDconvertor->getsteamID32();
                            $steamID64 = $steamIDconvertor->getsteamID64();

                            //SET PLAYER NAME
                            if (!empty($value2['playerName'])) {
                                $db->q(
                                    'INSERT INTO `s2_match_players_name`
                                            (
                                                `steamID32`,
                                                `steamID64`,
                                                `playerName`,
                                                `dateUpdated`
                                            )
                                        VALUES (?, ?, ?, ?)
                                        ON DUPLICATE KEY UPDATE
                                            `playerName` = VALUES(`playerName`),
                                            `dateUpdated` = VALUES(`dateUpdated`);',
                                    'ssss',
                                    array(
                                        $steamID32,
                                        $steamID64,
                                        $value2['playerName'],
                                        $value['date_recorded'],
                                    )
                                );
                            }
                        } else {
                            $steamID32 = -1 * $botCount;
                            $steamID64 = -1 * $botCount;
                            $botCount++;
                        }


                        $connectionState = !empty($value2['connectionStatus'])
                            ? $value2['connectionStatus']
                            : 0;

                        $isWinner = !empty($value2['teamID']) && $value2['teamID'] == $winningTeam
                            ? 1
                            : 0;

                        $db->q(
                            'INSERT INTO `s2_match_players`
                                    (
                                        `matchID`,
                                        `roundID`,
                                        `modID`,
                                        `steamID32`,
                                        `steamID64`,
                                        `connectionState`,
                                        `isWinner`
                                    )
                                VALUES (?, ?, ?, ?, ?, ?, ?);',
                            'iiissii',
                            array(
                                $newMatchID,
                                0,
                                $modID,
                                $steamID32,
                                $steamID64,
                                $connectionState,
                                $isWinner,
                            )
                        );
                    }
                }

                $db->q(
                    'DELETE FROM `node_listener` WHERE `test_id` = ?;',
                    'i',
                    $value['test_id']
                );

                //echo "<strong>$matchID</strong> recorded as <strong>$newMatchID</strong>! <br />";


            } catch (Exception $e) {
                echo "[FAIL] {$e->getMessage()}<br />";
            }
        }
    }
} catch (Exception $e) {
    echo "[CRITICAL] {$e->getMessage()}";
}
