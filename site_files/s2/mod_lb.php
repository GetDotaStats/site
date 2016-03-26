<?php
require_once('../global_functions.php');
require_once('../connections/parameters.php');
require_once('./functions.php');

require_once('../bootstrap/highcharts/Highchart.php');
require_once('../bootstrap/highcharts/HighchartJsExpr.php');
require_once('../bootstrap/highcharts/HighchartOption.php');
require_once('../bootstrap/highcharts/HighchartOptionRenderer.php');

if (!isset($_SESSION)) {
    session_start();
}

try {
    if (empty($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception('Invalid modID! Bad type.');
    }

    $modID = $_GET['id'];

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcached = new Cache(NULL, NULL, $localDev);

    echo modPageHeader($modID, $CDN_image);

    //////////////////
    //Leaderboard
    //////////////////
    {
        try {
            echo '<h3>Leaderboard <small>Top 101 Players</small></h3>';

            echo '<p>Rough leaderboard that was put together in a few minutes.</p>';

            echo '<hr />';

            $userLeaderboardSQL = cached_query(
                's2_mod_page_lb' . $modID,
                'SELECT
                      sugs.`steamID64`,
                      sugs.`numGames`,
                      sugs.`numWins`,
                      sugs.`numAbandons`,
                      sugs.`numFails`,
                      sugs.`lastAbandon`,
                      sugs.`lastFail`,
                      sugs.`dateUpdated`,

                      smpn.`playerName`,

                      gdsu.`user_name`,
                      gdsu.`user_avatar`
                    FROM `s2_user_game_summary` sugs
                    LEFT JOIN `s2_match_players_name` smpn ON sugs.`steamID64` = smpn.`steamID64`
                    LEFT JOIN `gds_users` gdsu ON sugs.`steamID64` = gdsu.`user_id64`
                    WHERE sugs.`modID` = ?
                    ORDER BY sugs.`numGames` DESC
                    LIMIT 0, 101;',
                'i',
                $modID,
                10
            );

            if (empty($userLeaderboardSQL)) throw new Exception('No players have games recorded for this mod!');

            echo '<div class="row">
                    <div class="col-md-1">&nbsp;</div>
                    <div class="col-md-3"><strong>Player</strong></div>
                    <div class="col-md-6">
                        <div class="row">
                            <div class="col-md-3 text-center"><strong>Games</strong></div>
                            <div class="col-md-3 text-center"><strong>Wins</strong></div>
                            <div class="col-md-3 text-center"><strong>Abandons</strong></div>
                            <div class="col-md-3 text-center"><strong>Fails</strong></div>
                        </div>
                    </div>
                    <div class="col-md-2 text-center"><strong>Last Updated</strong></div>
                </div>';
            echo '<span class="h4">&nbsp;</span>';

            foreach ($userLeaderboardSQL as $key => $value) {
                if (!empty($value['user_name'])) {
                    $userName = $value['user_name'];
                } else if (!empty($value['playerName'])) {
                    $userName = $value['playerName'];
                } else {
                    $userName = $value['steamID64'];
                }

                $userName = '<a class="nav-clickable" href="#s2__user?id=' . $value['steamID64'] . '">' . $userName . '</a>';

                $userAvatar = !empty($value['user_avatar'])
                    ? '<img width="24" height="24" src="' . $value['user_avatar'] . '" alt="User thumbnail" />'
                    : '<img width="24" height="24" src="' . $CDN_image . '/images/misc/steam/blank_avatar.jpg' . '" alt="User thumbnail" />';

                $userAvatar = '<a target="_blank" href="//steamcommunity.com/profiles/' . $value['steamID64'] . '">' . $userAvatar . '</a>';

                $lastAbandon = !empty($value['lastAbandon'])
                    ? relative_time_v3($value['lastAbandon'])
                    : '&nbsp;';

                $lastFail = !empty($value['lastFail'])
                    ? relative_time_v3($value['lastFail'])
                    : '&nbsp;';

                $dateUpdated = !empty($value['dateUpdated'])
                    ? relative_time_v3($value['dateUpdated'])
                    : '&nbsp;';

                $rank = '#' . ($key + 1);

                echo "<div class='row'>
                    <div class='col-md-1'>{$rank}</div>
                    <div class='col-md-3'>{$userAvatar} {$userName}</div>
                    <div class='col-md-6'>
                        <div class='row'>
                            <div class='col-md-3 text-center'>{$value['numGames']}</div>
                            <div class='col-md-3 text-center'>{$value['numWins']}</div>
                            <div class='col-md-3 text-center'>{$value['numAbandons']}</div>
                            <div class='col-md-3 text-center'>{$value['numFails']}</div>
                        </div>
                    </div>
                    <div class='col-md-2 text-right'>{$dateUpdated}</div>
                </div>";
                echo '<span class="h5">&nbsp;</span>';
            }

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