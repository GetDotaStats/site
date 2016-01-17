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

    $filterTimeSpan = !empty($_GET['t']) && is_numeric($_GET['t'])
        ? $_GET['t']
        : -1;

    switch ($filterTimeSpan) {
        case 1:
            $filterTimeSpanSQL = ' AND cmm.`dateRecorded` >= NOW() - INTERVAL 7 DAY ';
            $sqlFilter = 1;
            break;
        case 2:
            $filterTimeSpanSQL = ' AND cmm.`dateRecorded` >= NOW() - INTERVAL 14 DAY ';
            $sqlFilter = 2;
            break;
        case 3:
            $filterTimeSpanSQL = ' AND cmm.`dateRecorded` >= NOW() - INTERVAL 30 DAY ';
            $sqlFilter = 3;
            break;
        case 4:
            $filterTimeSpanSQL = '';
            $sqlFilter = 4;
            break;
        default:
            $filterTimeSpanSQL = ' AND cmm.`dateRecorded` >= NOW() - INTERVAL 14 DAY ';
            $sqlFilter = 2;
            break;
    }

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    echo modPageHeader($modID, $CDN_image);

    //////////////////
    //GAMES OVER TIME (ALL)
    //////////////////
    {
        try {
            echo '<h3>Games</h3>';

            echo '<p>Breakdown of games per day over the last month. Calculated every 10minutes.';

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
                    <a class="nav-clickable btn btn-sm btn-info" href="#s2__mod?id=' . $modID . '&t=1">Last Week</a>
                    <a class="nav-clickable btn btn-sm btn-info" href="#s2__mod?id=' . $modID . '&t=2">Last 2 Weeks</a>
                    <a class="nav-clickable btn btn-sm btn-info" href="#s2__mod?id=' . $modID . '&t=3">Last Month</a>
                    <a class="nav-clickable btn btn-sm btn-info" href="#s2__mod?id=' . $modID . '&t=4">All Time</a>
               </div>';

            $gamesOverTime = cached_query(
                's2_mod_page_games_over_time_all_' . $modID . '_' . $sqlFilter,
                'SELECT
                      cmm.`day`,
                      cmm.`month`,
                      cmm.`year`,
                      cmm.`gamePhase`,
                      SUM(cmm.`gamesPlayed`) AS gamesPlayed,
                      MIN(cmm.`dateRecorded`) AS dateRecorded
                    FROM `cache_mod_matches` cmm
                    WHERE cmm.`modID` = ? ' . $filterTimeSpanSQL . '
                    GROUP BY 3,2,1,4;',
                'i',
                $modID,
                10
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

                switch ($value['gamePhase']) {
                    case 1:
                        $phase = '1 - Players loaded';
                        break;
                    case 2:
                        $phase = '2 - Game started';
                        break;
                    case 3:
                        $phase = '3 - Game ended';
                        break;
                    default:
                        $phase = '1 - Players loaded';
                        break;
                }

                $bigArray[$phase][] = array(
                    new HighchartJsExpr("Date.UTC($year, $month, $day)"),
                    $gamesPlayedRaw,
                );
            }

            ksort($bigArray);

            $lineChart = makeLineChart(
                $bigArray,
                'games_per_phase_all',
                'Number of Games per Phase over Time',
                new HighchartJsExpr("document.ontouchstart === undefined ? 'Click and drag in the plot area to zoom in' : 'Pinch the chart to zoom in'"),
                array('title' => 'Games', 'min' => 0)
            );

            echo '<div id="games_per_phase_all"></div>';
            echo $lineChart;

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