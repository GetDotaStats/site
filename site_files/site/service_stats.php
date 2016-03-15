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

    $memcached = new Cache(NULL, NULL, $localDev);

    ////////////////////////////////////
    //Execution time of Services
    ////////////////////////////////////
    try {
        echo '<h2>Service Stats</h2>';
        echo '<p>A lot of the data displayed on this site can take a long time to compute. This
            page shows how long many of the cron-jobs/services this site relies on, took to
            execute. If data starts getting stale, you may see the cause in the below graphs.</p>';

        //////////////////////////////
        //MAIN SERVICES
        //////////////////////////////
        echo '<h3>Execution time of Main Services</h3>';
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
                WHERE
                  `is_sub` = 0 AND
                  `date_recorded` >= NOW() - INTERVAL 1 MONTH
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
            'service_stats_main',
            'Execution Time for Main Services',
            new HighchartJsExpr("document.ontouchstart === undefined ? 'Click and drag in the plot area to zoom in' : 'Pinch the chart to zoom in'"),
            array('title' => 'Execution Time (mins)', 'min' => 0)
        );

        echo '<div id="service_stats_main"></div>';
        echo $lineChart;
    } catch (Exception $e) {
        echo formatExceptionHandling($e);
    }

    try {
        //////////////////////////////
        //SUB SERVICES
        //////////////////////////////
        echo '<h3>Execution time of Sub Services</h3>';
        echo '<p>How long services have taken to execute over the last month.</p>';

        $subServices = array(
            's2_cron_cmf',
            's2_cron_cmgv',
            's2_cron_cmpv',
            's2_cron_highscore_clean'
        );

        foreach ($subServices as $key => $value) {
            $serviceStats = cached_query(
                'admin_service_stats_sub' . $value,
                'SELECT
                        DAY(`date_recorded`) AS `day`,
                        MONTH(`date_recorded`) AS `month`,
                        YEAR(`date_recorded`) AS `year`,
                        `service_name`,
                        AVG(`execution_time`) AS `execution_time`
                    FROM `cron_services`
                    WHERE
                      `service_name` LIKE ? AND
                      `is_sub` = 1 AND
                      `date_recorded` >= NOW() - INTERVAL 1 MONTH
                    GROUP BY 3,2,1,4
                    ORDER BY 3,2,1,4;',
                's',
                array($value . '\_%'),
                5
            );

            if (empty($serviceStats)) {
                throw new Exception('No service stats!');
            }

            $bigArray = array();
            foreach ($serviceStats as $key2 => $value2) {
                $year = $value2['year'];
                $month = $value2['month'] >= 1
                    ? $value2['month'] - 1
                    : $value2['month'];
                $day = $value2['day'];

                $executionTime = !empty($value2['execution_time']) && is_numeric($value2['execution_time'])
                    ? intval($value2['execution_time'])
                    : 0;

                $modName = $serviceName = $value2['service_name'];
                $removeServiceName = $value . '_';

                if (!empty($serviceName) && stripos($serviceName, $removeServiceName) !== false) {
                    $modID = str_replace($removeServiceName, '', $serviceName);

                    if (!empty($modID)) {
                        $modNameSQL = cached_query(
                            'admin_service_mod_name' . $modID,
                            'SELECT `mod_name` FROM `mod_list` WHERE `mod_id` = ? LIMIT 0,1;',
                            'i',
                            $modID,
                            15
                        );

                        $modName = !empty($modNameSQL)
                            ? $modNameSQL[0]['mod_name']
                            : $serviceName;
                    }
                }

                $bigArray[$modName][] = array(
                    new HighchartJsExpr("Date.UTC($year, $month, $day)"),
                    $executionTime,
                );
            }

            $lineChart = makeLineChart(
                $bigArray,
                'service_stats_sub_' . $value,
                'Execution Time for Sub Services of ' . $value,
                new HighchartJsExpr("document.ontouchstart === undefined ? 'Click and drag in the plot area to zoom in' : 'Pinch the chart to zoom in'"),
                array('title' => 'Execution Time (secs)', 'min' => 0)
            );

            echo '<div id="service_stats_sub_' . $value . '"></div>';
            echo $lineChart;
        }
    } catch (Exception $e) {
        echo formatExceptionHandling($e);
    }

    try {
        //////////////////////////////
        //Recent MAIN Service Records
        //////////////////////////////
        echo '<h3>Recent Main Service Records</h3>';
        echo '<p>The last 30 records recorded against various main services that we run.</p>';

        $lastServiceRuns = cached_query(
            'admin_last_30_service_runs_main',
            'SELECT
                    `instance_id`,
                    `service_name`,
                    `execution_time`,
                    `performance_index1`,
                    `performance_index2`,
                    `performance_index3`,
                    `date_recorded`
                FROM `cron_services`
                WHERE `is_sub` = 0
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

    try {
        //////////////////////////////
        //Recent SUB Service Records
        //////////////////////////////
        echo '<h3>Recent Sub Service Records</h3>';
        echo '<p>The last 30 records recorded against various main services that we run.</p>';

        $lastServiceRuns = cached_query(
            'admin_last_30_service_runs_sub',
            'SELECT
                    `instance_id`,
                    `service_name`,
                    `execution_time`,
                    `performance_index1`,
                    `performance_index2`,
                    `performance_index3`,
                    `date_recorded`
                FROM `cron_services`
                WHERE `is_sub` = 1
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
    if (isset($memcached)) $memcached->close();
}