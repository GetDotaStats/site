<?php
require_once('../global_functions.php');
require_once('../connections/parameters.php');

if (!isset($_SESSION)) {
    session_start();
}

try {
    if (empty($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception('Invalid userID! Bad type.');
    }

    $steamIDmanipulator = new SteamID($_GET['id']);
    $userID32 = $steamIDmanipulator->getsteamID32();
    $userID64 = $steamIDmanipulator->getSteamID64();

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcached = new Cache(NULL, NULL, $localDev);

    $userDetails = cached_query(
        's2_user_page_check' . $userID32,
        'SELECT
                `user_id64`,
                `user_id32`,
                `user_name`,
                `user_avatar`
            FROM `gds_users`
            WHERE `user_id32` = ?
            LIMIT 0,1;',
        's',
        array($userID32),
        15
    );

    if (empty($userDetails)) {
        $webAPI = new steam_webapi($api_key1);
        $userDetails = grabAndUpdateSteamUserDetails($userID32);
    }

    //TIDY VARIABLES
    {
        //User Avatar
        $userThumb = !empty($userDetails[0]['user_avatar'])
            ? $userDetails[0]['user_avatar']
            : $CDN_image . '/images/misc/steam/blank_avatar.jpg';
        $userThumb = '<img width="24" height="24" src="' . $userThumb . '" alt="User thumbnail" />';
        $userThumb = '<a target="_blank" href="https://steamcommunity.com/profiles/' . $userDetails[0]['user_id64'] . '">' . $userThumb . '</a>';

        //User Name
        $userName = !empty($userDetails) && !empty($userDetails[0]['user_name'])
            ? $userDetails[0]['user_name']
            : '????';
        $userName = '<a class="nav-clickable" href="#s2__user?id=' . $userDetails[0]['user_id32'] . '">' . $userName . '</a>';

        //User combo
        $userCombo = $userThumb . ' ' . $userName;

        //User external links
        $links['steam_profile'] = '<a href="https://steamcommunity.com/profiles/' . $userDetails[0]['user_id64'] . '" target="_new"><span class="glyphicon glyphicon-new-window"></span> Steam Profile</a>';
        $links['dotabuff_profile'] = '<a href="http://dotabuff.com/players/' . $userDetails[0]['user_id32'] . '" target="_new"><span class="glyphicon glyphicon-new-window"></span> Dotabuff</a>';
        $links = !empty($links)
            ? implode(' || ', $links)
            : 'None';

        //User flags
        {
            $userFlagsSQL = cached_query(
                's2_user_page_flags' . $userID64,
                'SELECT
                        gpu.`user_id64`,
                        gpu.`user_group`,
                        gpu.`date_recorded`
                    FROM `gds_power_users` gpu
                    WHERE gpu.`user_id64` = ?;',
                's',
                $userID64,
                15
            );

            $userFlags = array();
            if (!empty($userFlagsSQL)) {
                foreach ($userFlagsSQL as $key => $value) {
                    $userFlags[] = $value['user_group'];
                }
            } else {
                $userFlags[] = 'user';
            }
            $userFlags = implode(' || ', $userFlags);
            unset($userFlagsSQL);
        }
    }

    echo '<h2>' . $userCombo . '</h2>';

    //PLAYER INFO
    echo '<div class="container">';
    echo '<div class="col-sm-7">
                <div class="row mod_info_panel">
                    <div class="col-sm-12 text-center">
                        <button class="btn btn-sm" data-toggle="collapse" data-target="#mod_info">User Info</button>
                    </div>
                </div>
            </div>';

    echo '<div id="mod_info" class="collapse col-sm-7">
                <div class="row mod_info_panel">
                    <div class="col-sm-2"><strong>Links</strong></div>
                    <div class="col-sm-10">' . $links . '</div>
                </div>
                <div class="row mod_info_panel">
                    <div class="col-sm-2"><strong>steamID32</strong></div>
                    <div class="col-sm-4"><div>' . $userDetails[0]['user_id32'] . '</div></div>
                    <div class="col-sm-2"><strong>steamID64</strong></div>
                    <div class="col-sm-4"><div>' . $userDetails[0]['user_id64'] . '</div></div>
                </div>
                <div class="row mod_info_panel">
                    <div class="col-sm-2"><strong>Groups</strong></div>
                    <div class="col-sm-10"><div>' . $userFlags . '</div></div>
                </div>
           </div>';
    echo '</div>';

    echo '<span class="h4">&nbsp;</span>';

    echo '<hr />';

    ///////////////////////////////////
    // RECENT GAMES
    ///////////////////////////////////
    {
        try {
            echo '<h3>Overview of Games</h3>';
            echo '<p>The aggregate view of games played per mod.</p>';

            $userModAggregate = cached_query(
                's2_user_page_aggregate_games' . $userID64,
                'SELECT
                        s2mp.`modID`,
                        s2mp.`numGames`,
                        s2mp.`numWins`,
                        s2mp.`lastAbandon`,
                        s2mp.`lastFail`,
                        s2mp.`dateUpdated`,
                        ml.`mod_name`
                    FROM `s2_user_game_summary` s2mp
                    LEFT JOIN `mod_list` ml ON s2mp.`modID` = ml.`mod_id`
                    WHERE s2mp.`steamID64` = ? AND ml.`mod_active` = 1
                    GROUP BY s2mp.`modID`
                    ORDER BY s2mp.`numGames` DESC;',
                's',
                $userID64,
                15
            );

            if (empty($userModAggregate)) throw new Exception('User has no games recorded against mods we track!');


            echo '<div class="row searchRow">
                        <div class="col-md-3"><strong>Mod</strong></div>
                        <div class="col-md-1 text-center"><strong>Games</strong></div>
                        <div class="col-md-2 text-center"><strong>Wins</strong></div>
                        <div class="col-md-2 text-center"><strong>Last Abandon</strong></div>
                        <div class="col-md-2 text-center"><strong>Last Failed Load</strong></div>
                        <div class="col-md-2 text-center"><strong>Last Updated</strong></div>
                    </div>';

            foreach ($userModAggregate as $key => $value) {
                $winPercent = number_format($value['numWins'] / $value['numGames'] * 100, 1);

                $lastAbandon = !empty($value['lastAbandon'])
                    ? relative_time_v3($value['lastAbandon'])
                    : '&nbsp;';

                $lastFail = !empty($value['lastFail'])
                    ? relative_time_v3($value['lastFail'])
                    : '&nbsp;';

                $dateUpdated = !empty($value['dateUpdated'])
                    ? relative_time_v3($value['dateUpdated'])
                    : '&nbsp;';

                echo '<div class="row searchRow">
                            <div class="col-md-3"><a class="nav-clickable" href="#s2__mod?id=' . $value['modID'] . '"><span class="glyphicon glyphicon-eye-open"></span> ' . $value['mod_name'] . '</a></div>
                            <div class="col-md-1 text-center">' . number_format($value['numGames']) . '</div>
                            <div class="col-md-2 text-center">' . number_format($value['numWins']) . ' (' . $winPercent . '%)</div>
                            <div class="col-md-2 text-right">' . $lastAbandon . '</div>
                            <div class="col-md-2 text-right">' . $lastFail . '</div>
                            <div class="col-md-2 text-right">' . $dateUpdated . '</div>
                        </div>';
            }



            echo '<hr />';



            echo '<h3>Recent Games</h3>';
            echo '<p>The last 25 games this user has played for mods we track.</p>';
            $userRecentGames = cached_query(
                's2_user_page_recent_games' . $userID32,
                'SELECT
                        DISTINCT s2mp.`matchID`,
                        s2mp.`modID`,
                        s2mp.`steamID32`,
                        s2mp.`steamID64`,
                        s2mp.`connectionState`,

                        ml.`mod_name`,

                        s2m.`matchHostSteamID32`,
                        s2m.`matchPhaseID`,
                        s2m.`numRounds`,
                        s2m.`matchDuration`,
                        s2m.`dateRecorded`,

                        (SELECT `flagValue` FROM `s2_match_flags` WHERE `matchID` = s2m.`matchID` AND `flagName` = "numPlayers" LIMIT 0,1) AS `numPlayers`,
                        s2m.`numPlayers` AS `numPlayers2`,

                        (SELECT `isWinner` FROM `s2_match_players` WHERE `matchID` = s2m.`matchID` AND `steamID32` = s2mp.`steamID32` LIMIT 0,1) AS `isWinner`

                    FROM `s2_match_players` s2mp
                    JOIN `mod_list` ml ON s2mp.`modID` = ml.`mod_id`
                    LEFT JOIN `s2_match` s2m ON s2mp.`matchID` = s2m.`matchID`
                    WHERE `steamID32` = ?
                    ORDER BY s2mp.`matchID` DESC
                    LIMIT 0,25;',
                's',
                $userID32,
                15
            );

            if (empty($userRecentGames)) throw new Exception('User has no games recorded against mods we track!');

            echo '<div class="row">
                        <div class="col-md-4"><strong>Mod</strong></div>
                        <div class="col-md-6">
                            <div class="col-md-2 text-center"><strong>Players</strong></div>
                            <div class="col-md-2 text-center"><strong>Rounds</strong></div>
                            <div class="col-md-2 text-center"><strong>Phase</strong></div>
                            <div class="col-md-6">
                                <div class="row">
                                    <div class="col-md-3 text-center"><strong>Host</strong></div>
                                    <div class="col-md-3 text-center"><strong>Win</strong></div>
                                    <div class="col-md-3 text-center"><strong>State</strong></div>
                                    <div class="col-md-3 text-center"><strong>Duration</strong></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 text-center"><strong>Recorded</strong></div>
                    </div>';

            foreach ($userRecentGames as $key => $value) {
                $matchPhase = matchPhaseToGlyhpicon($value['matchPhaseID']);

                $isHost = $value['matchHostSteamID32'] == $userID32
                    ? '<span class="glyphicon glyphicon-ok boldGreenText"></span>'
                    : '<span class="glyphicon glyphicon-remove boldRedText"></span>';

                $isWinner = !empty($value['isWinner'])
                    ? '<span class="glyphicon glyphicon-ok boldGreenText"></span>'
                    : '<span class="glyphicon glyphicon-remove boldRedText"></span>';

                $numPlayers = !empty($value['numPlayers']) && is_numeric($value['numPlayers'])
                    ? $value['numPlayers']
                    : '?';

                if ($numPlayers == '?' && !empty($value['numPlayers2']) && is_numeric($value['numPlayers2'])) {
                    $numPlayers = $value['numPlayers2'];
                }

                $matchDuration = !empty($value['matchDuration']) && is_numeric($value['matchDuration'])
                    ? secs_to_clock($value['matchDuration'])
                    : '??:??';

                $connectionState = !empty(['connectionState'])
                    ? matchConnectionStatusToGlyhpicon($value['connectionState'])
                    : matchConnectionStatusToGlyhpicon(0);

                echo '<div class="row searchRow">
                        <a class="nav-clickable" href="#s2__match?id=' . $value['matchID'] . '">
                            <div class="col-md-4"><span class="glyphicon glyphicon-eye-open"></span> ' . $value['mod_name'] . '</div>
                            <div class="col-md-6">
                                <div class="col-md-2 text-center">' . $numPlayers . '</div>
                                <div class="col-md-2 text-center">' . $value['numRounds'] . '</div>
                                <div class="col-md-2 text-center">' . $matchPhase . '</div>
                                <div class="col-md-6">
                                    <div class="row">
                                        <div class="col-md-3 text-center">' . $isHost . '</div>
                                        <div class="col-md-3 text-center">' . $isWinner . '</div>
                                        <div class="col-md-3 text-center">' . $connectionState . '</div>
                                        <div class="col-md-3 text-center">' . $matchDuration . '</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2 text-right">' . relative_time_v3($value['dateRecorded']) . '</div>
                        </a>
                    </div>';
            }

            echo '<hr />';

        } catch (Exception $e) {
            echo formatExceptionHandling($e);
        }
    }

    echo '<span class="h4">&nbsp;</span>';

    echo '<div class="text-center">
                <a class="nav-clickable btn btn-default btn-lg" href="#s2__directory">Mod Directory</a>
                <a class="nav-clickable btn btn-default btn-lg" href="#s2__recent_games">Recent Games</a>
           </div>';

    echo '<span class="h4">&nbsp;</span>';
} catch (Exception $e) {
    echo formatExceptionHandling($e);
} finally {
    if (isset($memcached)) $memcached->close();
}