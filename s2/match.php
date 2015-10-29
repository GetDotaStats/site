<?php
require_once('../global_functions.php');
require_once('../connections/parameters.php');

if (!isset($_SESSION)) {
    session_start();
}

try {
    if (empty($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception('Invalid matchID! Bad type.');
    }

    $matchID = $_GET['id'];

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    $matchDetails = cached_query(
        's2_match_check' . $matchID,
        'SELECT
                s2m.`matchID`,
                s2m.`modID`,
                s2m.`matchHostSteamID32`,
                s2m.`matchPhaseID`,
                s2m.`isDedicated`,
                s2m.`matchMapName`,
                s2m.`numPlayers`,
                s2m.`numRounds`,
                s2m.`matchDuration`,
                s2m.`matchFinished`,
                s2m.`schemaVersion`,
                s2m.`dateUpdated`,
                s2m.`dateRecorded`,

                ml.`mod_name`,
                ml.`steam_id64` AS mod_developer,
                ml.`mod_identifier`,
                ml.`mod_name`,
                ml.`mod_description`,
                ml.`mod_workshop_link`,
                ml.`mod_steam_group`,
                ml.`mod_active`,
                ml.`mod_rejected`,
                ml.`mod_rejected_reason`
            FROM `s2_match` s2m
            JOIN `mod_list` ml ON s2m.`modID` = ml.`mod_id`
            WHERE s2m.`matchID` = ?
            LIMIT 0,1;',
        's',
        $matchID,
        5
    );

    if (empty($matchDetails)) {
        throw new Exception('Invalid matchID! Not recorded in database.');
    }

    $hostUserID32 = $matchDetails[0]['matchHostSteamID32'];
    $numRounds = $matchDetails[0]['numRounds'];
    $numPlayers = $matchDetails[0]['numPlayers'];

    echo '<h2><a class="nav-clickable" href="#s2__mod?id=' . $matchDetails[0]['modID'] . '">' . $matchDetails[0]['mod_name'] . '</a> <small>' . $matchID . '</small></h2>';

    !empty($matchDetails[0]['mod_workshop_link'])
        ? $links['steam_workshop'] = '<a href="http://steamcommunity.com/sharedfiles/filedetails/?id=' . $matchDetails[0]['mod_workshop_link'] . '" target="_new"><span class="glyphicon glyphicon-new-window"></span> Workshop</a>'
        : NULL;
    !empty($matchDetails[0]['mod_steam_group'])
        ? $links['steam_group'] = '<a href="http://steamcommunity.com/groups/' . $matchDetails[0]['mod_steam_group'] . '" target="_new"><span class="glyphicon glyphicon-new-window"></span> Steam Group</a>'
        : NULL;
    $links = !empty($links)
        ? implode(' || ', $links)
        : 'None';


    //MOD INFO
    echo '<div class="container">';
    echo '<div class="col-sm-7">
                <div class="row mod_info_panel">
                    <div class="col-sm-12 text-center">
                        <button class="btn btn-sm" data-toggle="collapse" data-target="#mod_info">Mod Info</button>
                    </div>
                </div>
            </div>';
    echo '<div id="mod_info" class="collapse col-sm-7">
                <div class="row mod_info_panel">
                    <div class="col-sm-3"><strong>Links</strong></div>
                    <div class="col-sm-9">' . $links . '</div>
                </div>
                <div class="row mod_info_panel">
                    <div class="col-sm-3"><strong>Description</strong></div>
                    <div class="col-sm-9">' . $matchDetails[0]['mod_description'] . '</div>
                </div>
           </div>';
    echo '</div>';

    echo '<span class="h4">&nbsp;</span>';

    //GAME SUMMARY
    echo '<div class="row">
                <div class="col-md-5">&nbsp;</div>
                <div class="col-md-7 mod_info_panel">
                    <div class="row">
                        <div class="col-md-5">
                            <div class="row">
                                <div class="col-md-4"><strong>Phase</strong></div>
                                <div class="col-md-4"><strong>Players</strong></div>
                                <div class="col-md-4"><strong>Rounds</strong></div>
                            </div>
                        </div>
                        <div class="col-md-3"><strong>Duration</strong></div>
                        <div class="col-md-4"><strong>Recorded</strong></div>
                    </div>

                    <div class="row">
                        <div class="col-md-5">
                            <div class="row">
                                <div class="col-md-4 text-center">' . $matchDetails[0]['matchPhaseID'] . '</div>
                                <div class="col-md-4 text-center">' . $matchDetails[0]['numPlayers'] . '</div>
                                <div class="col-md-4 text-center">' . $matchDetails[0]['numRounds'] . '</div>
                            </div>
                        </div>
                        <div class="col-md-3">' . round($matchDetails[0]['matchDuration'] / 60) . ' mins</div>
                        <div class="col-md-4">' . relative_time_v3($matchDetails[0]['dateRecorded'], 1, NULL, false, true, false) . '</div>
                    </div>
                </div>
            </div>';

    $matchSummary = array();

    //PLAYER DETAILS
    {
        $playerDetails = cached_query(
            's2_player_match_details' . $matchID,
            'SELECT
                    s2mp.`roundID`,
                    s2mp.`steamID32`,
                    s2mp.`steamID64`,
                    s2mp.`connectionState`,
                    s2mp.`isWinner`,

                    s2mpn.`playerName`,

                    gu.`user_avatar`,

                    gcs.`cs_name` AS `connectionStateName`
                FROM `s2_match_players` s2mp
                LEFT JOIN `s2_match_players_name` s2mpn ON s2mp.`steamID32` = s2mpn.`steamID32`
                LEFT JOIN `gds_users` gu ON s2mp.`steamID32` = gu.`user_id32`
                LEFT JOIN `game_connection_status` gcs ON s2mp.`connectionState` = gcs.`cs_id`
                WHERE s2mp.`matchID` = ?
                ORDER BY s2mp.`roundID` ASC, s2mp.`steamID32` ASC;',
            's',
            $matchID,
            5
        );

        if (!empty($playerDetails)) {
            foreach ($playerDetails as $key => $value) {
                $matchSummary[$value['roundID']]['players'][$value['steamID32']]['steamID32'] = $value['steamID32'];
                $matchSummary[$value['roundID']]['players'][$value['steamID32']]['steamID64'] = $value['steamID64'];
                $matchSummary[$value['roundID']]['players'][$value['steamID32']]['isWinner'] = $value['isWinner'];
                $matchSummary[$value['roundID']]['players'][$value['steamID32']]['playerName'] = $value['playerName'];
                $matchSummary[$value['roundID']]['players'][$value['steamID32']]['userAvatar'] = $value['user_avatar'];
                $matchSummary[$value['roundID']]['players'][$value['steamID32']]['connectionState'] = $value['connectionState'];
                $matchSummary[$value['roundID']]['players'][$value['steamID32']]['connectionStateName'] = $value['connectionStateName'];
            }
        }
    }

    //CUSTOM FIELDS GAME
    {
        $customGameDetails = cached_query(
            's2_custom_game_match_details' . $matchID,
            'SELECT
                    s2mc.`matchID`,
                    s2mc.`modID`,
                    s2mc.`schemaID`,
                    s2mc.`round`,
                    s2mc.`fieldOrder`,
                    s2mc.`fieldValue`,

                    s2mcsf.`customValueDisplay`
                FROM `s2_match_custom` s2mc
                JOIN `s2_mod_custom_schema_fields` s2mcsf ON s2mc.`schemaID` = s2mcsf.`schemaID` AND s2mc.`fieldOrder` = s2mcsf.`fieldOrder` AND s2mcsf.`fieldType` = 1
                WHERE s2mc.`matchID` = ?
                ORDER BY s2mc.`round` ASC, s2mc.`fieldOrder` ASC;',
            's',
            $matchID,
            5
        );

        if (!empty($customGameDetails)) {
            foreach ($customGameDetails as $key => $value) {
                $matchSummary[$value['round']]['cgv'][$value['customValueDisplay']] = $value['fieldValue'];
            }
        }
    }

    //CUSTOM FIELDS PLAYER
    {
        $customPlayerDetails = cached_query(
            's2_custom_player_match_details' . $matchID,
            'SELECT
                    s2mpc.`matchID`,
                    s2mpc.`modID`,
                    s2mpc.`schemaID`,
                    s2mpc.`round`,
                    s2mpc.`userID32`,
                    s2mpc.`fieldOrder`,
                    s2mpc.`fieldValue`,

                    s2mcsf.`customValueDisplay`
                FROM `s2_match_players_custom` s2mpc
                JOIN `s2_mod_custom_schema_fields` s2mcsf ON s2mpc.`schemaID` = s2mcsf.`schemaID` AND s2mpc.`fieldOrder` = s2mcsf.`fieldOrder` AND s2mcsf.`fieldType` = 2
                WHERE s2mpc.`matchID` = ?
                ORDER BY s2mpc.`round` ASC, s2mpc.`fieldOrder` ASC;',
            's',
            $matchID,
            5
        );

        if (!empty($customPlayerDetails)) {
            foreach ($customPlayerDetails as $key => $value) {
                if (!isset($matchSummary[$value['round']]['players'][$value['userID32']])) throw new Exception('No basic stats for user ' . $value['userID32'] . ' in round #' . $value['round'] . '!');
                $matchSummary[$value['round']]['players'][$value['userID32']]['cpv'][$value['customValueDisplay']] = $value['fieldValue'];
            }
        }
    }

    //Client IPs
    {
        if (!empty($_SESSION['user_id64'])) {
            //if admin, show clientIPs too
            $adminCheck = adminCheck($_SESSION['user_id64'], 'admin');
            if (!empty($adminCheck)) {
                $clientDetails = cached_query(
                    's2_client_details' . $matchID,
                    'SELECT
                          `matchID`,
                          `modID`,
                          `steamID32`,
                          `steamID64`,
                          `clientIP`,
                          `isHost`,
                          `dateRecorded`
                        FROM `s2_match_client_details`
                        WHERE `matchID` = ?;',
                    's',
                    $matchID,
                    5
                );

                if (!empty($clientDetails)) {
                    foreach ($clientDetails as $key => $value) {
                        if (!isset($matchSummary[1]['players'][$value['steamID32']])) throw new Exception('No basic stats for user ' . $value['steamID32'] . ' in round #1!');
                        $matchSummary[1]['players'][$value['steamID32']]['cpv']['IP'] = $value['clientIP'];
                    }
                }
            }
        }
    }

    if (empty($matchSummary)) throw new Exception('No player details recorded!');

    //Iterate over all of the rounds in the match summary array
    foreach ($matchSummary as $key => $value) {
        try {
            //Only show round heading if there is more than 1 round
            echo $numRounds > 1
                ? '<h4>Round #' . $key . '</h4>'
                : '<br />';

            //Only show the custom GAME stats button if there is data to show
            if (!empty($value['cgv'])) {
                echo '<strong>Custom Game stats</strong> <button type="button" class="btn btn-default btn-xs" data-toggle="collapse" data-target="#custom_game_info_' . $key . '">show</button>';

                echo '<div class="container">';
                echo '<div id="custom_game_info_' . $key . '" class="collapse col-sm-5">';
                echo '<br />';

                //Iterate over all the custom stats for the round we are currently iterating over
                foreach ($value['cgv'] as $key2 => $value2) {
                    echo '<div class="row mod_info_panel">
                                <div class="col-sm-4"><strong>' . $key2 . '</strong></div>
                                <div class="col-sm-8">' . $value2 . '</div>
                            </div>';
                }

                echo '<br />';
                echo '</div>';
                echo '</div>';
            }


            echo '<div class="row">
                        <div class="col-md-4"><strong>Player</strong></div>
                        <div class="col-md-1 text-center"><strong>Winner</strong></div>
                        <div class="col-md-2 text-center"><strong>Connection</strong></div>
                    </div>';

            if (empty($value['players'])) throw new Exception("No basic stats for this round #{$key}!");

            //Iterate over all of the players for this round in the match summary array
            foreach ($value['players'] as $key2 => $value2) {
                $userAvatar = !empty($value2['user_avatar'])
                    ? $value2['user_avatar']
                    : $CDN_image . '/images/misc/steam/blank_avatar.jpg';
                $userAvatarLink = '<a target="_blank" href="https://steamcommunity.com/profiles/' . $value2['steamID64'] . '"><img src="' . $userAvatar . '" width="14" /></a>';

                $usernameLink = !empty($value2['playerName'])
                    ? $value2['playerName']
                    : '????';
                $usernameLink = '<a class="nav-clickable" href="#s2__user?id=' . $value2['steamID64'] . '">' . $usernameLink . '</a>';

                $isWinner = $value2['isWinner'] == 1
                    ? '<span class="glyphicon glyphicon-ok boldGreenText"></span>'
                    : '<span class="glyphicon glyphicon-remove boldRedText"></span>';

                $isHost = $value2['steamID32'] == $hostUserID32
                    ? ' <span class="glyphicon glyphicon-asterisk" title="This player was the host"></span>'
                    : '';

                $goodConnectionStates = array(1, 2, 3);
                $connectionState = in_array($value2['connectionState'], $goodConnectionStates)
                    ? '<span class="glyphicon glyphicon-ok boldGreenText" title="' . $value2['connectionStateName'] . '"></span>'
                    : '<span class="glyphicon glyphicon-remove boldRedText" title="' . $value2['connectionStateName'] . '"></span>';

                //Only show the custom stats button if there is data to show
                $moreInfoButton = !empty($value2['cpv'])
                    ? '<div class="col-md-2 text-center"><button type="button" class="btn btn-default btn-xs" data-toggle="collapse" data-target="#player_info_' . $key . '_' . $value2['steamID32'] . '">Info</button></div>'
                    : '';

                echo '<div class="row">
                            <div class="col-md-4">' . $userAvatarLink . ' ' . $usernameLink . $isHost . '</div>
                            <div class="col-md-1 text-center">' . $isWinner . '</div>
                            <div class="col-md-2 text-center">' . $connectionState . '</div>' . $moreInfoButton . '
                        </div>';

                if (!empty($value2['cpv'])) {
                    echo '<div class="container">';
                    echo '<div id="player_info_' . $key . '_' . $value2['steamID32'] . '" class="collapse col-sm-9">';
                    echo '<br />';

                    $customStats = '<div class="row mod_info_panel">';
                    //Iterate over all the custom stats for the player we are currently iterating over
                    $i = 1;
                    $numStats = count($value2['cpv']);
                    foreach ($value2['cpv'] as $key3 => $value3) {
                        $customStats .= '<div class="col-sm-2"><strong>' . $key3 . '</strong></div>
                                    <div class="col-sm-2">' . $value3 . '</div>';

                        if ($i % 3 == 0 && $i > 1 && $i != $numStats) {
                            $customStats .= '</div><div class="row mod_info_panel">';
                        }

                        $i++;
                    }

                    $customStats .= '</div>';

                    echo $customStats;

                    echo '<br />';
                    echo '</div>';
                    echo '</div>';
                }
            }

            echo '<span class="h4">&nbsp;</span>';
        } catch (Exception $e) {
            echo formatExceptionHandling($e);
        }
    }


    echo '<span class="h4">&nbsp;</span>';

    //FLAGS
    {
        $flagsDetails = cached_query(
            's2_flags_match_details' . $matchID,
            'SELECT
                    s2mf.`flagName`,
                    s2mf.`flagValue`
                FROM `s2_match_flags` s2mf
                WHERE s2mf.`matchID` = ?
                ORDER BY s2mf.`flagName` ASC;',
            's',
            $matchID,
            5
        );

        if (!empty($flagsDetails)) {
            echo '<div class="row">
                    <div class="col-sm-2"><strong>Flag</strong></div>
                    <div class="col-sm-3"><strong>Value</strong></div>
                </div>';

            foreach ($flagsDetails as $key => $value) {
                echo '<div class="row">
                    <div class="col-sm-2">' . $value['flagName'] . '</div>
                    <div class="col-sm-3">' . $value['flagValue'] . '</div>
                </div>';
            }
        }
    }

    echo '<hr />';
} catch (Exception $e) {
    echo formatExceptionHandling($e);
} finally {
    if (isset($memcache)) $memcache->close();

    echo '<span class="h4">&nbsp;</span>';

    echo '<div class="text-center">
                <a class="nav-clickable btn btn-default btn-lg" href="#s2__directory">Mod Directory</a>
                <a class="nav-clickable btn btn-default btn-lg" href="#s2__recent_games">Recent Games</a>
           </div>';

    echo '<span class="h4">&nbsp;</span>';
}