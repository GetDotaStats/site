<?php
require_once('../global_functions.php');
require_once('../connections/parameters.php');

require_once('../bootstrap/highcharts/Highchart.php');
require_once('../bootstrap/highcharts/HighchartJsExpr.php');
require_once('../bootstrap/highcharts/HighchartOption.php');
require_once('../bootstrap/highcharts/HighchartOptionRenderer.php');

if (!isset($_SESSION)) {
    session_start();
}

try {
    $filterTimeSpan = !empty($_GET['t']) && is_numeric($_GET['t'])
        ? $_GET['t']
        : -1;

    switch ($filterTimeSpan) {
        case 1:
            $filterTimeSpanSQL = ' AND cmm.`dateRecorded` >= NOW() - INTERVAL 30 DAY ';
            break;
        case 2:
            $filterTimeSpanSQL = ' AND cmm.`dateRecorded` >= NOW() - INTERVAL 60 DAY ';
            break;
        case 3:
            $filterTimeSpanSQL = '';
            break;
        default:
            $filterTimeSpanSQL = ' AND cmm.`dateRecorded` >= NOW() - INTERVAL 30 DAY ';
            break;
    }

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    echo '<h2>Aggregate Mod Analysis</h2>';

    echo '<p>An overview of the total games played per mod per day over the selected timespan. By default only the last 30days are shown.';

    try {
        $serviceReporting = new serviceReporting($db);
        $lastCronUpdateDetails = $serviceReporting->getServiceLog('s2_cron_matches');
        $lastCronUpdateRunTime = $serviceReporting->getServiceLogRunTime();
        $lastCronUpdateExecutionTime = $serviceReporting->getServiceLogExecutionTime();

        echo " This data was last updated <strong>{$lastCronUpdateRunTime}</strong>, taking <strong>{$lastCronUpdateExecutionTime}</strong> to generate.</p>";
    } catch (Exception $e) {
        echo '</p>';
        echo formatExceptionHandling($e);
    }

    echo '<div class="text-center">
                <a class="nav-clickable btn btn-sm btn-info" href="#s2__mod_aggregate?t=1">Last 30 Days</a>
                <a class="nav-clickable btn btn-sm btn-info" href="#s2__mod_aggregate?t=2">Last 60 Days</a>
                <a class="nav-clickable btn btn-sm btn-info" href="#s2__mod_aggregate?t=3">All Time</a>
           </div>';

    //////////////////
    //GAMES OVER TIME (ALL)
    //////////////////
    {
        try {
            $gamesOverTime = cached_query(
                's2_mod_aggregate_page',
                'SELECT
                      cmm.`day`,
                      cmm.`month`,
                      cmm.`year`,
                      ml.`mod_name`,
                      SUM(cmm.`gamesPlayed`) AS gamesPlayed,
                      MIN(cmm.`dateRecorded`) AS dateRecorded
                    FROM `cache_mod_matches` cmm
                    JOIN `mod_list` ml ON cmm.`modID` = ml.`mod_id`
                    WHERE cmm.`gamePhase` = 3 ' . $filterTimeSpanSQL . '
                    GROUP BY 3,2,1,4;',
                NULL,
                NULL,
                1
            );

            if (empty($gamesOverTime)) {
                throw new Exception('No games recorded!');
            }

            $bigArray = array();
            foreach ($gamesOverTime as $key => $value) {
                $year = $value['year'];
                $month = $value['month'] >= 1
                    ? $value['month'] - 1
                    : $value['month'];
                $day = $value['day'];

                $gamesPlayedRaw = !empty($value['gamesPlayed']) && is_numeric($value['gamesPlayed'])
                    ? intval($value['gamesPlayed'])
                    : 0;

                $bigArray[$value['mod_name']][] = array(
                    new HighchartJsExpr("Date.UTC($year, $month, $day)"),
                    $gamesPlayedRaw,
                );
            }

            $lineGraph = makeLineChart(
                $bigArray,
                'games_per_phase_all',
                'Number of Games per Mod over Time',
                new HighchartJsExpr("document.ontouchstart === undefined ? 'Click and drag in the plot area to zoom in' : 'Pinch the chart to zoom in'"),
                array('title' => 'Games', 'min' => 0)
            );

            echo '<div id="games_per_phase_all"></div>';
            echo $lineGraph;

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