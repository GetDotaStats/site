<?php
try {
    require_once('../global_functions.php');
    require_once('../connections/parameters.php');

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

    echo '<h2>Service Stats</h2>';
    echo '<p>This is the admin section dedicated to the overview of critical cron jobs.</p>';


    try {
        echo '<h3>Last 30 Records</h3>';

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
                LIMIT 0,30;'
        );

        if(empty($lastServiceRuns)) throw new Exception('No recorded service runs!');

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

        foreach($lastServiceRuns as $key => $value){
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
    echo formatExceptionHandling($e);
} finally {
    if (isset($memcache)) $memcache->close();
}