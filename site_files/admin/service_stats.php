<?php
try {
    require_once('../connections/parameters.php');
    require_once('../global_functions.php');

    require_once('../bootstrap/highcharts/Highchart.php');
    require_once('../bootstrap/highcharts/HighchartJsExpr.php');
    require_once('../bootstrap/highcharts/HighchartOption.php');
    require_once('../bootstrap/highcharts/HighchartOptionRenderer.php');

    if (!isset($_SESSION)) {
        session_start();
    }

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    checkLogin_v2();
    if (empty($_SESSION['user_id64'])) throw new Exception('Not logged in!');

    $adminCheck = adminCheck($_SESSION['user_id64'], 'admin');
    if (empty($adminCheck)) throw new Exception('Not an admin!');

    ////////////////////////////////////
    //Execution time of Services
    ////////////////////////////////////
    try {
        echo '<h3>Execution time of Services</h3>';
        echo '<p>How long services have taken to execute over the last month.</p>';

        $serviceStats = cached_query(
            'admin_service_stats',
            'SELECT
                    DAY(`date_recorded`) AS `day`,
                    MONTH(`date_recorded`) AS `month`,
                    YEAR(`date_recorded`) AS `year`,
                    `service_name`,
                    AVG(`execution_time`) AS `execution_time`
                FROM `cron_services`
                WHERE `date_recorded` >= NOW() - INTERVAL 1 MONTH
                GROUP BY 3,2,1,4
                ORDER BY 3,2,1,4;',
            NULL,
            NULL,
            5
        );

        if (empty($serviceStats)) {
            throw new Exception('No service stats!');
        }

        $bigArray = array();
        foreach ($serviceStats as $key => $value) {
            $year = $value['year'];
            $month = $value['month'] >= 1
                ? $value['month'] - 1
                : $value['month'];
            $day = $value['day'];

            $executionTime = !empty($value['execution_time']) && is_numeric($value['execution_time'])
                ? intval($value['execution_time'] / 60)
                : 0;

            $bigArray[$value['service_name']][] = array(
                new HighchartJsExpr("Date.UTC($year, $month, $day)"),
                $executionTime,
            );
        }

        $lineChart = makeLineChart(
            $bigArray,
            'service_stats_all',
            'Execution Time for Services',
            new HighchartJsExpr("document.ontouchstart === undefined ? 'Click and drag in the plot area to zoom in' : 'Pinch the chart to zoom in'"),
            array('title' => 'Execution Time (mins)', 'min' => 0)
        );

        echo '<div id="service_stats_all"></div>';
        echo $lineChart;
    } catch (Exception $e) {
        echo formatExceptionHandling($e);
    }

    ////////////////////////////////////
    //Recent Service Records
    ////////////////////////////////////
    try {
        echo '<h3>Recent Service Records</h3>';
        echo '<p>The last 30 records recorded against various services that we run.</p>';

        $lastServiceRuns = cached_query(
            'admin_last_30_service_runs',
            'SELECT
                    `instance_id`,
                    `service_name`,
                    `execution_time`,
                    `performance_index1`,
                    `performance_index2`,
                    `performance_index3`,
                    `date_recorded`
                FROM `cron_services`
                ORDER BY `date_recorded` DESC
                LIMIT 0,30;',
            NULL,
            NULL,
            5
        );

        if (empty($lastServiceRuns)) throw new Exception('No recorded service runs!');

        echo "<div class='row'>
                    <div class='col-md-3'><strong>Service</strong></div>
                    <div class='col-md-7'>
                        <div class='row'>
                            <div class='col-md-3 text-center'><strong>Execution</strong></div>
                            <div class='col-md-3 text-center'><strong>P. Index1</strong></div>
                            <div class='col-md-3 text-center'><strong>P. Index2</strong></div>
                            <div class='col-md-3 text-center'><strong>P. Index3</strong></div>
                        </div>
                    </div>
                    <div class='col-md-2'><strong>Recorded</strong></div>
                </div>";

        echo '<span class="h4">&nbsp;</span>';

        foreach ($lastServiceRuns as $key => $value) {
            $dateRecorded = relative_time_v3($value['date_recorded']);
            $executionTime = $value['execution_time'];
            $performanceIndex1 = number_format($value['performance_index1']);
            $performanceIndex2 = number_format($value['performance_index2']);
            $performanceIndex3 = number_format($value['performance_index3']);

            echo "<div class='row'>
                    <div class='col-md-3'>{$value['service_name']}</div>
                    <div class='col-md-7'>
                        <div class='row'>
                            <div class='col-md-3 text-right'>{$executionTime} sec</div>
                            <div class='col-md-3 text-right'>{$performanceIndex1}</div>
                            <div class='col-md-3 text-right'>{$performanceIndex2}</div>
                            <div class='col-md-3 text-right'>{$performanceIndex3}</div>
                        </div>
                    </div>
                    <div class='col-md-2 text-right'>{$dateRecorded}</div>
                </div>";
        }
    } catch (Exception $e) {
        echo formatExceptionHandling($e);
    }

    echo '<span class="h4">&nbsp;</span>';

} catch (Exception $e) {
    echo formatExceptionHandling($e, true);
} finally {
    if (isset($memcache)) $memcache->close();
}