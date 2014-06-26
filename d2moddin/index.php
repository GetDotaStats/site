<?php
require_once('./functions.php');
require_once('../global_functions.php');
require_once('../connections/parameters.php');

$start = time();
include('./chart.php');

if (!isset($_SESSION)) {
    session_start();
}

try {
    $db = new dbWrapper($hostname_d2moddin, $username_d2moddin, $password_d2moddin, $database_d2moddin, true);
    if ($db) {
        $memcache = new Memcache;
        $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

        echo '<script type="text/javascript" src="//www.google.com/jsapi"></script>';

        $chart = new chart('ColumnChart');

        $options = array(
            //'title' => 'Average spins in ' . $hits . ' attacks',
            //'theme' => 'maximized',
            'axisTitlesPosition' => 'in',
            'width' => 800,
            'height' => 400,
            'chartArea' => array(
                'width' => '100%',
                'left' => 60
            ),
            'hAxis' => array(
                'title' => 'Date',
                'maxAlternation' => 1,
                //'textPosition' => 'in',
                //'viewWindowMode' => 'maximized'
            ),
            'vAxis' => array(
                'title' => 'Users',
                //'textPosition' => 'in',
            ),
            'legend' => array(
                'position' => 'bottom',
                'textStyle' => array(
                    'fontSize' => 10
                )
            ));

        $optionsDataTable = array(
            'width' => 600,
            'sortColumn' => 0,
            'sortAscending' => true,
            'alternatingRowStyle' => true,
            'page' => 'enable',
            'pageSize' => 6);

        echo '<div id="about" style="width: 600px;">';
        echo '<h2>Plot of people joining the queue over time</h2>';
        echo '<p>No longer kept up-to-date as the queue system was later merged into the existing systems and out of my control.</p>';
        echo '</div>';

        $signup_stats = simple_cached_query('d2moddin_stats_queue_joins',
            'SELECT HOUR(date_invited) as hour, DAY(date_invited) as day, MONTH(date_invited) as month, YEAR(date_invited) as year, COUNT(*) as count FROM invite_key GROUP BY HOUR(date_invited), DAY(date_invited), MONTH(date_invited) ORDER BY 4,3,2,1;',
            60);

        $super_array = array();
        foreach ($signup_stats as $key => $value) {
            $date = $value['year'].'-'.$value['month'].'-'.$value['day'].' '.str_pad($value['hour'], 2, '0', STR_PAD_LEFT).':00';
            $super_array[] = array('c' => array(array('v' => $date), array('v' => $value['count'])));
        }

        $data = array(
            'cols' => array(
                array('id' => '', 'label' => 'Date', 'type' => 'string'),
                array('id' => '', 'label' => 'Joins', 'type' => 'number'),
            ),
            'rows' => $super_array
        );
        $chart->load(json_encode($data));
        echo $chart->draw('queue_count', $options, true, $optionsDataTable);


        echo '<div id="queue_count"></div>';
        echo '<div id="queue_count_dataTable"></div>';

        echo '<div id="pagerendertime" style="font-size: 12px;">';
        echo '<hr />Page generated in ' . (time() - $start) . 'secs';
        echo '</div>';


        $memcache->close();
    } else {
        echo 'No DB';
    }

    $stats = json_decode(curl('http://ddp2.d2modd.in/stats/general', NULL, NULL, NULL, NULL, 20), 1);

    echo '<pre>';
    print_r($stats);
    echo '</pre>';

} catch (Exception $e) {
    echo $e->getMessage();
}