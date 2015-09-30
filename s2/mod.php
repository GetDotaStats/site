<?php
require_once('../global_functions.php');
require_once('../connections/parameters.php');

require_once("./highcharts/src/Highchart.php");
require_once("./highcharts/src/HighchartJsExpr.php");
require_once("./highcharts/src/HighchartOption.php");
require_once("./highcharts/src/HighchartOptionRenderer.php");

use Ghunti\HighchartsPHP\Highchart;
use Ghunti\HighchartsPHP\HighchartJsExpr;

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

    $modDetails = cached_query(
        's2_mod_page_details' . $modID,
        'SELECT
              ml.`mod_id`,
              ml.`steam_id64`,
              ml.`mod_identifier`,
              ml.`mod_name`,
              ml.`mod_description`,
              ml.`mod_workshop_link`,
              ml.`mod_steam_group`,
              ml.`mod_active`,
              ml.`mod_rejected`,
              ml.`mod_rejected_reason`,
              ml.`mod_size`,
              ml.`workshop_updated`,
              ml.`mod_maps`,
              ml.`date_recorded`,

              gu.`user_name`,

              guo.`user_email`,
              
              (SELECT
                    SUM(`gamesPlayed`)
                  FROM `cache_mod_matches` cmm
                  WHERE cmm.`modID` = ml.`mod_id` AND cmm.`gamePhase` = 3 AND cmm.`dateRecorded` >= now() - INTERVAL 7 DAY
              ) AS games_last_week,
              (SELECT
                    SUM(`gamesPlayed`)
                  FROM `cache_mod_matches` cmm
                  WHERE cmm.`modID` = ml.`mod_id` AND cmm.`gamePhase` = 3
              ) AS games_all_time

            FROM `mod_list` ml
            JOIN `gds_users` gu ON ml.`steam_id64` = gu.`user_id64`
            LEFT JOIN `gds_users_options` guo ON ml.`steam_id64` = guo.`user_id64`
            WHERE ml.`mod_id` = ?
            LIMIT 0,1;',
        'i',
        $modID,
        15
    );

    if (empty($modDetails)) {
        throw new Exception('Invalid modID! Not recorded in database.');
    }

    //Tidy variables
    {
        //Mod name and thumb
        {
            $modThumb = is_file('../images/mods/thumbs/' . $modDetails[0]['mod_id'] . '.png')
                ? $CDN_image . '/images/mods/thumbs/' . $modDetails[0]['mod_id'] . '.png'
                : $CDN_image . '/images/misc/steam/blank_avatar.jpg';
            $modThumb = '<img width="24" height="24" src="' . $modThumb . '" alt="Mod thumbnail" />';
            $modThumb = '<a target="_blank" href="http://steamcommunity.com/sharedfiles/filedetails/?id=' . $modDetails[0]['mod_workshop_link'] . '">' . $modThumb . '</a>';

            $modNameLink = '';
            if (!empty($_SESSION['user_id64'])) {
                //if admin, show modIdentifier too
                $adminCheck = adminCheck($_SESSION['user_id64'], 'admin');
                if (!empty($adminCheck)) {
                    $modNameLink = ' <small>' . $modDetails[0]['mod_identifier'] . '</small>';
                }
            }
            $modNameLink = $modThumb . ' <a class="nav-clickable" href="#s2__mod?id=' . $modDetails[0]['mod_id'] . '">' . $modDetails[0]['mod_name'] . $modNameLink . '</a>';
        }

        //Mod external links
        {
            !empty($modDetails[0]['mod_workshop_link'])
                ? $links['steam_workshop'] = '<a href="http://steamcommunity.com/sharedfiles/filedetails/?id=' . $modDetails[0]['mod_workshop_link'] . '" target="_new"><span class="glyphicon glyphicon-new-window"></span> Workshop</a>'
                : NULL;
            !empty($modDetails[0]['mod_steam_group'])
                ? $links['steam_group'] = '<a href="http://steamcommunity.com/groups/' . $modDetails[0]['mod_steam_group'] . '" target="_new"><span class="glyphicon glyphicon-new-window"></span> Steam Group</a>'
                : NULL;
            $links = !empty($links)
                ? implode(' || ', $links)
                : 'None';
        }

        //Developer name and avatar
        {
            $developerAvatar = !empty($value['user_avatar'])
                ? $value['user_avatar']
                : $CDN_image . '/images/misc/steam/blank_avatar.jpg';
            $developerAvatar = '<img width="20" height="20" src="' . $developerAvatar . '" alt="Developer avatar" />';
            $developerLink = '<a target="_blank" href="http://steamcommunity.com/profiles/' . $modDetails[0]['steam_id64'] . '">' . $developerAvatar . '</a> <a class="nav-clickable" href="#s2__user?id=' . $modDetails[0]['steam_id64'] . '">' . $modDetails[0]['user_name'] . '</a>';
        }

        //Developer email
        {
            $developerEmail = '';
            if (!empty($_SESSION['user_id64'])) {
                //if admin, show developer email too
                $adminCheck = adminCheck($_SESSION['user_id64'], 'admin');
                if (!empty($adminCheck)) {
                    $developerEmail = '<div class="row mod_info_panel">
                            <div class="col-sm-3"><strong>Developer Email</strong></div>
                            <div class="col-sm-9">';

                    if (!empty($modDetails[0]['user_email'])) {
                        $developerEmail .= $modDetails[0]['user_email'];
                    } else {
                        $developerEmail .= 'Developer has not given us it!';
                    }

                    $developerEmail .= '</div>
                        </div>';
                }
            }
        }

        //Mod maps
        $modMaps = !empty($modDetails[0]['mod_maps'])
            ? implode(", ", json_decode($modDetails[0]['mod_maps'], 1))
            : 'unknown';

        //Status
        if (!empty($modDetails[0]['mod_rejected']) && !empty($modDetails[0]['mod_rejected_reason'])) {
            $modStatus = '<span class="boldRedText">Rejected:</span> ' . $modDetails[0]['mod_rejected_reason'];
        } else if ($modDetails[0]['mod_active'] == 1) {
            $modStatus = '<span class="boldGreenText">Accepted</span>';
        } else {
            $modStatus = '<span class="boldOrangeText">Pending Approval</span>';
        }

        //Mod Size
        {
            $modSize = !empty($modDetails[0]['mod_size'])
                ? filesize_human_readable($modDetails[0]['mod_size'], 0, 'MB', true)
                : NULL;

            $modSize = !empty($modSize)
                ? $modSize['number'] . '<span class="db_link"> ' . $modSize['string'] . '</span>'
                : '??<span class="db_link"> MB</span>';
        }
    }

    echo '<h2>' . $modNameLink . '</h2>';

    //FEATURE REQUEST
    echo '<div class="alert alert-danger"><strong>Help Wanted!</strong> We are re-designing every page. If there are features you would like to
        see on this page, please let us know by making a post per feature on this page\'s
        <a target="_blank" href="https://github.com/GetDotaStats/site/issues/162">issue</a>.</div>';

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
                    <div class="col-sm-3"><strong>Status</strong></div>
                    <div class="col-sm-9">' . $modStatus . '</div>
                </div>
                <div class="row mod_info_panel">
                    <div class="col-sm-3"><strong>Links</strong></div>
                    <div class="col-sm-9">' . $links . '</div>
                </div>
                <div class="row mod_info_panel">
                    <div class="col-sm-3"><strong>Description</strong></div>
                    <div class="col-sm-9">' . $modDetails[0]['mod_description'] . '</div>
                </div>
                <div class="row mod_info_panel">
                    <div class="col-sm-3"><strong>Developer</strong></div>
                    <div class="col-sm-9">' . $developerLink . '</div>
                </div>' . $developerEmail . '
                <div class="row mod_info_panel">
                    <div class="col-sm-3"><strong>Maps</strong></div>
                    <div class="col-sm-9">' . $modMaps . '</div>
                </div>
                <div class="row mod_info_panel">
                    <div class="col-sm-3"><strong>Size</strong></div>
                    <div class="col-sm-9">' . $modSize . '</div>
                </div>
                <div class="row mod_info_panel">
                    <div class="col-sm-3"><strong>Total Games</strong></div>
                    <div class="col-sm-9">' . number_format($modDetails[0]['games_all_time']) . '</div>
                </div>
                <div class="row mod_info_panel">
                    <div class="col-sm-3"><strong>Games (Last Week)</strong></div>
                    <div class="col-sm-9">' . number_format($modDetails[0]['games_last_week']) . '</div>
                </div>
                <div class="row mod_info_panel">
                    <div class="col-sm-3"><strong>Updated</strong></div>
                    <div class="col-sm-9">' . relative_time_v3($modDetails[0]['workshop_updated']) . '</div>
                </div>
                <div class="row mod_info_panel">
                    <div class="col-sm-3"><strong>Added</strong></div>
                    <div class="col-sm-9">' . relative_time_v3($modDetails[0]['date_recorded']) . '</div>
                </div>
           </div>';
    echo '</div>';

    echo '<span class="h4">&nbsp;</span>';

    echo '<hr />';

    //////////////////
    //GAMES OVER TIME (ALL)
    //////////////////
    {
        try{
            $gamesOverTime = cached_query(
                's2_mod_page_games_over_time_all_' . $modID,
                'SELECT
                      cmm.`day`,
                      cmm.`month`,
                      cmm.`year`,
                      cmm.`gamePhase`,
                      SUM(cmm.`gamesPlayed`) AS gamesPlayed,
                      MIN(cmm.`dateRecorded`) AS dateRecorded
                    FROM `cache_mod_matches` cmm
                    WHERE cmm.`modID` = ?
                    GROUP BY 3,2,1,4;',
                'i',
                $modID,
                1
            );

            if (empty($gamesOverTime)) {
                throw new Exception('No games recorded!');
            }

            $bigArray = array();
            foreach ($gamesOverTime as $key => $value) {
                $year = $value['year'];
                $month = $value['month'];
                $day = $value['day'];

                $gamesPlayedRaw = !empty($value['gamesPlayed']) && is_numeric($value['gamesPlayed'])
                    ? intval($value['gamesPlayed'])
                    : 0;

                $bigArray[$value['gamePhase']][] = array(
                    new HighchartJsExpr("Date.UTC($year, $month, $day)"),
                    $gamesPlayedRaw,
                );
            }

            {
                $chart = new Highchart();

                $chart->chart->renderTo = "games_per_phase_all";
                $chart->chart->type = "spline";
                $chart->chart->zoomType = "x";
                $chart->title->text = "Number of Games per Phase over Time";
                $chart->subtitle->text = new HighchartJsExpr("document.ontouchstart === undefined ? 'Click and drag in the plot area to zoom in' : 'Pinch the chart to zoom in'");
                $chart->xAxis->type = "datetime";
                $chart->yAxis->title->text = "Games";
                $chart->yAxis->min = 0;
                /*$chart->tooltip->formatter = new HighchartJsExpr(
                    "function() {
                        return '<b>'+ this.series.name +'</b><br/>'+
                        this.y +' games';
                    }"
                );*/
                $chart->tooltip->crosshairs = true;
                $chart->tooltip->shared = true;
                $chart->credits->enabled = false;


                $i = 0;
                foreach ($bigArray as $key => $value) {
                    $chart->series[$i]->name = 'Phase ' . $key;
                    $chart->series[$i]->data = $value;

                    $i++;
                }
            }

            echo '<div id="games_per_phase_all"></div>';
            echo $chart->render("chart1",NULL,true);

        } catch (Exception $e) {
            echo formatExceptionHandling($e);
        }
    }

    echo '<hr />';

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
                    WHERE s2.`modID` = ? AND s2.`matchPhaseID` = 3
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