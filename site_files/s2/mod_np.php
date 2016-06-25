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

    $memcached = new Cache(NULL, NULL, $localDev);

    echo modPageHeader($modID, $CDN_image);

    //////////////////
    //PLAYERS OVER TIME (ALL)
    //////////////////
    {
        try {
            echo '<h3>Unique Players</h3>';

            echo '<p>Breakdown of unique players per day over the last month. Calculated every 10minutes.</p>';

            try {
                $serviceReporting = new serviceReporting($db);
                $lastCronUpdateDetails = $serviceReporting->getServiceLog('cron_match_player_count');
                $lastCronUpdateRunTime = $serviceReporting->getServiceLogRunTime();
                $lastCronUpdateExecutionTime = $serviceReporting->getServiceLogExecutionTime();

                echo "<p>This data was last updated <strong>{$lastCronUpdateRunTime}</strong>, taking <strong>{$lastCronUpdateExecutionTime}</strong> to generate.</p>";
            } catch (Exception $e) {
                echo formatExceptionHandling($e);
            }

            echo '<div class="text-center">
                    <a class="nav-clickable btn btn-sm btn-success" href="#s2__mod_np?id=' . $modID . '&t=1">Last Week</a>
                    <a class="nav-clickable btn btn-sm btn-success" href="#s2__mod_np?id=' . $modID . '&t=2">Last 2 Weeks</a>
                    <a class="nav-clickable btn btn-sm btn-success" href="#s2__mod_np?id=' . $modID . '&t=3">Last Month</a>
                    <a class="nav-clickable btn btn-sm btn-success" href="#s2__mod_np?id=' . $modID . '&t=4">All Time</a>
               </div>';

            $gamesOverTime = cached_query(
                's2_mod_page_players_over_time_all_' . $modID . '_' . $sqlFilter,
                'SELECT
                      cmm.`day`,
                      cmm.`month`,
                      cmm.`year`,
                      cmm.`connectionState`,
                      SUM(cmm.`uniquePlayers`) AS uniquePlayers,
                      MIN(cmm.`dateRecorded`) AS dateRecorded
                    FROM `cache_mod_match_player_count` cmm
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

                $uniquePlayersRaw = !empty($value['uniquePlayers']) && is_numeric($value['uniquePlayers'])
                    ? intval($value['uniquePlayers'])
                    : 0;

                switch ($value['connectionState']) {
                    case 0:
                        $phase = '0 - Unknown';
                        break;
                    case 1:
                        $phase = '1 - Has not Connected';
                        break;
                    case 2:
                        $phase = '2 - Connected';
                        break;
                    case 3:
                        $phase = '3 - Disconnected';
                        break;
                    case 4:
                        $phase = '4 - Abandoned';
                        break;
                    case 5:
                        $phase = '5 - Loading';
                        break;
                    case 6:
                        $phase = '6 - Failed';
                        break;
                }

                $bigArray[$phase][] = array(
                    'x' => new HighchartJsExpr("Date.UTC($year, $month, $day)"),
                    'y' => $uniquePlayersRaw,
                );
            }

            ksort($bigArray);

            foreach ($bigArray as $key => $value) {
                foreach ($value as $key2 => $value2) {
                    $connectionStatus0 = 0;
                    if (!empty($bigArray['0 - Unknown'])) {
                        foreach ($bigArray['0 - Unknown'] as $key3 => $value3) {
                            if ($value3['x'] == $value2['x']) {
                                $connectionStatus0 = !empty($value3['y']) && is_numeric($value3['y'])
                                    ? $value3['y']
                                    : 0;
                                break;
                            }
                        }
                    }

                    $connectionStatus1 = 0;
                    if (!empty($bigArray['1 - Has not Connected'])) {
                        foreach ($bigArray['1 - Has not Connected'] as $key3 => $value3) {
                            if ($value3['x'] == $value2['x']) {
                                $connectionStatus1 = !empty($value3['y']) && is_numeric($value3['y'])
                                    ? $value3['y']
                                    : 0;
                                break;
                            }
                        }
                    }

                    $connectionStatus2 = 0;
                    if (!empty($bigArray['2 - Connected'])) {
                        foreach ($bigArray['2 - Connected'] as $key3 => $value3) {
                            if ($value3['x'] == $value2['x']) {
                                $connectionStatus2 = !empty($value3['y']) && is_numeric($value3['y'])
                                    ? $value3['y']
                                    : 0;
                                break;
                            }
                        }
                    }

                    $connectionStatus3 = 0;
                    if (!empty($bigArray['3 - Disconnected'])) {
                        foreach ($bigArray['3 - Disconnected'] as $key3 => $value3) {
                            if ($value3['x'] == $value2['x']) {
                                $connectionStatus3 = !empty($value3['y']) && is_numeric($value3['y'])
                                    ? $value3['y']
                                    : 0;
                                break;
                            }
                        }
                    }

                    $connectionStatus4 = 0;
                    if (!empty($bigArray['4 - Abandoned'])) {
                        foreach ($bigArray['4 - Abandoned'] as $key3 => $value3) {
                            if ($value3['x'] == $value2['x']) {
                                $connectionStatus4 = !empty($value3['y']) && is_numeric($value3['y'])
                                    ? $value3['y']
                                    : 0;
                                break;
                            }
                        }
                    }

                    $connectionStatus5 = 0;
                    if (!empty($bigArray['5 - Loading'])) {
                        foreach ($bigArray['5 - Loading'] as $key3 => $value3) {
                            if ($value3['x'] == $value2['x']) {
                                $connectionStatus5 = !empty($value3['y']) && is_numeric($value3['y'])
                                    ? $value3['y']
                                    : 0;
                                break;
                            }
                        }
                    }

                    $connectionStatus6 = 0;
                    if (!empty($bigArray['6 - Failed'])) {
                        foreach ($bigArray['6 - Failed'] as $key3 => $value3) {
                            if ($value3['x'] == $value2['x']) {
                                $connectionStatus6 = !empty($value3['y']) && is_numeric($value3['y'])
                                    ? $value3['y']
                                    : 0;
                                break;
                            }
                        }
                    }

                    if (
                        ($connectionStatus0 > 0) ||
                        ($connectionStatus1 > 0) ||
                        ($connectionStatus2 > 0) ||
                        ($connectionStatus3 > 0) ||
                        ($connectionStatus4 > 0) ||
                        ($connectionStatus5 > 0) ||
                        ($connectionStatus6 > 0)
                    ) {
                        $bigArray[$key][$key2]['percentage'] = number_format($value2['y'] / ($connectionStatus0 + $connectionStatus1 + $connectionStatus2 + $connectionStatus3 + $connectionStatus4 + $connectionStatus5 + $connectionStatus6) * 100, 1);
                    }
                }
            }

            $lineChart = makeLineChart(
                $bigArray,
                'players_per_connection_status_all',
                'Number of Unique Players per Connection Status over Time',
                new HighchartJsExpr("document.ontouchstart === undefined ? 'Click and drag in the plot area to zoom in' : 'Pinch the chart to zoom in'"),
                array('title' => 'Players', 'min' => 0),
                NULL,
                array("pointFormat" => "<b>{series.name}</b>: {point.percentage}% ({point.y})<br />", "shared" => true)
            );

            echo '<div id="players_per_connection_status_all"></div>';
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
    if (isset($memcached)) $memcached->close();
}