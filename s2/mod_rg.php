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

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    echo modPageHeader($modID, $CDN_image);

    //////////////////
    //RECENT GAMES
    //////////////////
    {
        try {
            $recentGames = cached_query(
                's2_mod_page_recent_games' . $modID,
                'SELECT
                      s2.`matchID`,
                      s2.`matchAuthKey`,
                      s2.`modID`,
                      s2.`matchHostSteamID32`,
                      s2.`matchPhaseID`,
                      s2.`isDedicated`,
                      s2.`matchMapName`,
                      s2.`numPlayers`,
                      s2.`numRounds`,
                      s2.`matchDuration`,
                      s2.`matchFinished`,
                      s2.`schemaVersion`,
                      s2.`dateUpdated`,
                      s2.`dateRecorded`,

                      ml.`mod_name`,
                      ml.`mod_workshop_link`
                    FROM `s2_match` s2
                    JOIN `mod_list` ml ON s2.`modID` = ml.`mod_id`
                    WHERE s2.`modID` = ?
                    ORDER BY s2.`dateRecorded` DESC
                    LIMIT 0,15;',
                'i',
                $modID,
                15
            );

            if (empty($recentGames)) {
                throw new Exception('No games recently played!');
            }

            echo '<h3>Last 15 Games <small><a class="nav-clickable" href="#s2__recent_games">MORE</a></small></h3>';

            echo '<div class="row">
                    <div class="col-md-1 h4">&nbsp;</div>
                    <div class="col-md-9">
                        <div class="col-md-3 h4 text-center">Players</div>
                        <div class="col-md-3 h4 text-center">Rounds</div>
                        <div class="col-md-3 h4 text-center">Duration</div>
                        <div class="col-md-3 h4 text-center">Phase</div>
                    </div>
                    <div class="col-md-2 h4 text-center">Recorded</div>
                </div>';

            foreach ($recentGames as $key => $value) {
                echo '<div class="row searchRow">
                    <a class="nav-clickable" href="#s2__match?id=' . $value['matchID'] . '">
                        <div class="col-md-1"><span class="glyphicon glyphicon-eye-open"></span></div>
                    </a>
                    <a class="nav-clickable" href="#s2__match?id=' . $value['matchID'] . '">
                        <div class="col-md-9">
                            <div class="col-md-3 text-center">' . $value['numPlayers'] . '</div>
                            <div class="col-md-3 text-center">' . $value['numRounds'] . '</div>
                            <div class="col-md-3 text-right">' . secs_to_clock($value['matchDuration']) . '</div>
                            <div class="col-md-3 text-center">' . $value['matchPhaseID'] . '</div>
                        </div>
                        <div class="col-md-2 text-right">' . relative_time_v3($value['dateRecorded']) . '</div>
                    </a>
                </div>';

                echo '<span class="h5">&nbsp;</span>';
            }
        } catch (Exception $e) {
            echo formatExceptionHandling($e);
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