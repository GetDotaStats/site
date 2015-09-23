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

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    $userDetails = cached_query(
        's2_user_page_check' . $userID32,
        'SELECT
                s2mpn.`steamID32`,
                s2mpn.`steamID64`,
                s2mpn.`playerName`,
                s2mpn.`playerVanity`,
                s2mpn.`dateUpdated`,

                gu.`user_avatar`
            FROM `s2_match_players_name` s2mpn
            LEFT JOIN `gds_users` gu ON s2mpn.`steamID32` = gu.`user_id32`
            WHERE s2mpn.`steamID32` = ?
            LIMIT 0,1;',
        's',
        array($userID32),
        15
    );

    if (empty($userDetails)) {
        throw new Exception('Invalid userID! Not recorded in database.');
    }

    //TIDY VARIABLES
    {
        //User Avatar
        $userThumb = !empty($userDetails[0]['user_avatar'])
            ? $userDetails[0]['user_avatar']
            : $CDN_image . '/images/misc/steam/blank_avatar.jpg';
        $userThumb = '<img width="24" height="24" src="' . $userThumb . '" alt="User thumbnail" />';
        $userThumb = '<a target="_blank" href="https://steamcommunity.com/profiles/' . $userDetails[0]['steamID64'] . '">' . $userThumb . '</a>';

        //User Name
        $userName = !empty($userDetails)
            ? $userDetails[0]['playerName']
            : '????';
        $userName = '<a class="nav-clickable" href="#s2__user?id=' . $userDetails[0]['steamID32'] . '">' . $userName . '</a>';

        //User combo
        $userCombo = $userThumb . ' ' . $userName;

        //User external links
        $links['steam_profile'] = '<a href="https://steamcommunity.com/profiles/' . $userDetails[0]['steamID64'] . '" target="_new"><span class="glyphicon glyphicon-new-window"></span> Steam Profile</a>';
        $links['dotabuff_profile'] = '<a href="http://dotabuff.com/players/' . $userDetails[0]['steamID32'] . '" target="_new"><span class="glyphicon glyphicon-new-window"></span> Dotabuff</a>';
        $links = !empty($links)
            ? implode(' || ', $links)
            : 'None';

        //User last game
        $lastGame = relative_time_v3($userDetails[0]['dateUpdated']);

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

    echo '<h2>' . $userCombo . ' <small>' . $userDetails[0]['steamID32'] . '</small></h2>';

    //FEATURE REQUEST
    echo '<div class="alert alert-danger"><strong>Help Wanted!</strong> We are re-designing every page. If there are features you would like to
        see on this page, please let us know by making a post per feature on this page\'s
        <a target="_blank" href="https://github.com/GetDotaStats/site/issues/166">issue</a>.</div>';

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
                    <div class="col-sm-4"><div>' . $userDetails[0]['steamID32'] . '</div></div>
                    <div class="col-sm-2"><strong>steamID64</strong></div>
                    <div class="col-sm-4"><div>' . $userDetails[0]['steamID64'] . '</div></div>
                </div>
                <div class="row mod_info_panel">
                    <div class="col-sm-2"><strong>Groups</strong></div>
                    <div class="col-sm-10"><div>' . $userFlags . '</div></div>
                </div>
                <div class="row mod_info_panel">
                    <div class="col-sm-2"><strong>Last Game</strong></div>
                    <div class="col-sm-10"><div>' . $lastGame . '</div></div>
                </div>
           </div>';
    echo '</div>';

    echo '<span class="h4">&nbsp;</span>';

    echo '<hr />';

    try {
        echo '<h3>Recent Games</h3>';

        $userRecentGames = cached_query(
            's2_user_page_recent_games' . $userID32,
            'SELECT
                    DISTINCT s2mp.`matchID`,
                    s2mp.`modID`,
                    s2mp.`steamID32`,
                    s2mp.`steamID64`,

                    ml.`mod_name`,

                    s2m.`matchHostSteamID32`,
                    s2m.`matchPhaseID`,
                    s2m.`matchMapName`,
                    s2m.`numPlayers`,
                    s2m.`numRounds`,
                    s2m.`matchDuration`,
                    s2m.`dateRecorded`
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

        if (!empty($userRecentGames)) {
            echo '<div class="row">
                        <div class="col-md-3 h4"><strong>Mod</strong></div>
                        <div class="col-md-7">
                            <div class="col-md-9">
                                <div class="col-md-3 h4 text-center"><strong>Players</strong></div>
                                <div class="col-md-3 h4 text-center"><strong>Rounds</strong></div>
                                <div class="col-md-3 h4 text-center"><strong>Phase</strong></div>
                                <div class="col-md-3 h4 text-center"><strong>Host</strong></div>
                            </div>
                            <div class="col-md-3">
                                <div class="col-md-12 h4 text-center"><strong>Duration</strong></div>
                            </div>
                        </div>
                        <div class="col-md-2 h4 text-center"><strong>Recorded</strong></div>
                    </div>';

            foreach ($userRecentGames as $key => $value) {
                $isHost = $value['matchHostSteamID32'] == $userID32
                    ? '<span class="glyphicon glyphicon-ok boldGreenText"></span>'
                    : '<span class="glyphicon glyphicon-remove boldRedText"></span>';

                echo '<div class="row searchRow">
                        <a class="nav-clickable" href="#s2__match?id=' . $value['matchID'] . '">
                            <div class="col-md-3"><span class="glyphicon glyphicon-eye-open"></span> ' . $value['mod_name'] . '</div>
                            <div class="col-md-7">
                                <div class="col-md-9">
                                    <div class="col-md-3 text-center">' . $value['numPlayers'] . '</div>
                                    <div class="col-md-3 text-center">' . $value['numRounds'] . '</div>
                                    <div class="col-md-3 text-center">' . $value['matchPhaseID'] . '</div>
                                    <div class="col-md-3 text-center">' . $isHost . '</div>
                                </div>
                                <div class="col-md-3">
                                    <div class="col-md-12 text-center">' . secs_to_clock($value['matchDuration']) . '</div>
                                </div>
                            </div>
                            <div class="col-md-2 text-right">' . relative_time_v3($value['dateRecorded']) . '</div>
                        </a>
                    </div>';
            }
        }


    } catch (Exception $e) {
        echo formatExceptionHandling($e);
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