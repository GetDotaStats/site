<?php
require_once('../global_functions.php');
require_once('../connections/parameters.php');

$start = time();

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
                s2m.`oldMatchID`,
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
                <div class="col-md-6">&nbsp;</div>
                <div class="col-md-6 mod_info_panel">
                    <div class="row">
                        <div class="col-md-2"><strong>Phase</strong></div>
                        <div class="col-md-3"><strong>Players</strong></div>
                        <div class="col-md-3"><strong>Duration</strong></div>
                        <div class="col-md-4"><strong>Recorded</strong></div>
                    </div>

                    <div class="row">
                        <div class="col-md-2">' . $matchDetails[0]['matchPhaseID'] . '</div>
                        <div class="col-md-3">' . $matchDetails[0]['numPlayers'] . '</div>
                        <div class="col-md-3">' . round($matchDetails[0]['matchDuration'] / 60) . ' mins</div>
                        <div class="col-md-4">' . relative_time_v3($matchDetails[0]['dateRecorded'], 1, NULL, false, true, false) . '</div>
                    </div>
                </div>
            </div>';

    //PLAYERS
    {
        $playerDetails = cached_query(
            's2_player_match_details' . $matchID,
            'SELECT
                    s2mp.`matchID`,
                    s2mp.`roundID`,
                    s2mp.`modID`,
                    s2mp.`steamID32`,
                    s2mp.`steamID64`,
                    s2mp.`connectionState`,
                    s2mp.`isWinner`,

                    s2mpn.`playerName`,

                    gu.`user_avatar`
                FROM `s2_match_players` s2mp
                JOIN `s2_match_players_name` s2mpn ON s2mp.`steamID32` = s2mpn.`steamID32`
                LEFT JOIN `gds_users` gu ON s2mp.`steamID32` = gu.`user_id32`
                WHERE s2mp.`matchID` = ?
                ORDER BY s2mp.`roundID` ASC, s2mp.`steamID32` ASC;',
            's',
            $matchID,
            5
        );

        if (empty($playerDetails)) {
            throw new Exception('No player data recorded!');
        }

        $lastRound = -1;
        foreach ($playerDetails as $key => $value) {
            if ($value['roundID'] != $lastRound) {
                echo '<span class="h4">&nbsp;</span>';

                echo '<div class="row"><div class="col-md-4 h4">Round #' . $value['roundID'] . '</div></div>';
                echo '<div class="row">
                        <div class="col-md-4"><strong>Player</strong></div>
                        <div class="col-md-2"><strong>Connection</strong></div>
                        <div class="col-md-1"><strong>Winner</strong></div>
                    </div>';


                $lastRound = $value['roundID'];
            }

            $userAvatar = !empty($value['user_avatar'])
                ? $value['user_avatar']
                : $CDN_image . '/images/misc/steam/blank_avatar.jpg';
            $userAvatarLink = '<a target="_blank" href="https://steamcommunity.com/profiles/' . $value['steamID64'] . '"><img src="' . $userAvatar . '" width="14" /></a>';

            $usernameLink = '<a class="nav-clickable" href="#s2__user?id=' . $value['steamID64'] . '">' . $value['playerName'] . '</a>';

            echo '<div class="row">
                        <div class="col-md-4">' . $userAvatarLink . ' ' . $usernameLink . '</div>
                        <div class="col-md-2">' . $value['connectionState'] . '</div>
                        <div class="col-md-1">' . $value['isWinner'] . '</div>
                    </div>';
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
            echo '<h4>Flags Dump</h4>';
            echo '<p>I\'m sorry</p>';

            echo '<pre>';
            print_r($flagsDetails);
            echo '</pre>';
        }
    }

    echo '<span class="h4">&nbsp;</span>';

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
            echo '<h4>Custom Game Values Dump</h4>';
            echo '<p>I\'m sorry</p>';

            $tempArray = array();

            foreach ($customGameDetails as $key => $value) {
                $tempArray[$value['round']][$value['customValueDisplay']] = $value['fieldValue'];
            }

            echo '<pre>';
            print_r($tempArray);
            echo '</pre>';
            unset($tempArray);
        }
    }

    echo '<span class="h4">&nbsp;</span>';

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
            echo '<h4>Custom Player Values Dump</h4>';
            echo '<p>I\'m sorry</p>';

            $tempArray = array();

            foreach ($customPlayerDetails as $key => $value) {
                $tempArray[$value['round']][$value['userID32']][$value['customValueDisplay']] = $value['fieldValue'];
            }

            echo '<pre>';
            print_r($tempArray);
            echo '</pre>';
            unset($tempArray);
        }
    }

    echo '<hr />';


    echo '<span class="h4">&nbsp;</span>';

    echo '<div class="text-center">
                <a class="nav-clickable btn btn-default btn-lg" href="#s2__directory">Mod Directory</a>
                <a class="nav-clickable btn btn-default btn-lg" href="#s2__recent_games">Recent Games</a>
           </div>';

    echo '<span class="h4">&nbsp;</span>';
} catch (Exception $e) {
    echo formatExceptionHandling($e);
} finally {
    if (isset($memcache)) $memcache->close();
}