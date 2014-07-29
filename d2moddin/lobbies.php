<?php
require_once('./functions.php');
require_once('../global_functions.php');
require_once('../connections/parameters.php');

$start = time();

if (!isset($_SESSION)) {
    session_start();
}

try {
    $db = new dbWrapper($hostname_d2moddin, $username_d2moddin, $password_d2moddin, $database_d2moddin, true);
    if ($db) {
        $memcache = new Memcache;
        $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

        echo '<h2>Breakdown of Lobby Statuses</h2>';

        {
            ////////////////////////////////////////////////////////
            // Last 2DAYS
            ////////////////////////////////////////////////////////

            $chart = new chart2('ComboChart');

            echo '<h3>Last 4Days</h3>';

            //$stats = json_decode(curl('http://ddp2.d2modd.in/stats/general', NULL, NULL, NULL, NULL, 20), 1);
            $production_stats = simple_cached_query('d2moddin_production_stats_4days',
                'SELECT MINUTE(`date_recorded`) as minute, HOUR(`date_recorded`) as hour, DAY(`date_recorded`) as day, MONTH(`date_recorded`) as month, YEAR(`date_recorded`) as year, `lobby_total`, `lobby_wait`, `lobby_play`, `lobby_queue` FROM `stats_production` WHERE `date_recorded` >= now() - INTERVAL 4 DAY GROUP BY 5,4,3,2,1 ORDER BY 5 DESC,4 DESC,3 DESC,2 DESC,1 DESC;',
                10);

            $super_array = array();
            foreach ($production_stats as $key => $value) {
                $date = $value['year'] . '-' . $value['month'] . '-' . $value['day'] . ' ' . str_pad($value['hour'], 2, '0', STR_PAD_LEFT) . ':' . ' ' . str_pad($value['minute'], 2, '0', STR_PAD_LEFT);
                $super_array[] = array('c' => array(array('v' => $date), array('v' => $value['lobby_wait']), array('v' => $value['lobby_play']), array('v' => $value['lobby_queue'])));
            }

            $data = array(
                'cols' => array(
                    array('id' => '', 'label' => 'Date', 'type' => 'string'),
                    array('id' => '', 'label' => 'Waiting', 'type' => 'number'),
                    array('id' => '', 'label' => 'Playing', 'type' => 'number'),
                    array('id' => '', 'label' => 'Queueing', 'type' => 'number'),
                ),
                'rows' => $super_array
            );

            $chart_width = max(count($production_stats) * 2, 800);
            $options = array(
                //'title' => 'Average spins in ' . $hits . ' attacks',
                //'theme' => 'maximized',
                'axisTitlesPosition' => 'in',
                'width' => $chart_width,
                'bar' => array(
                    'groupWidth' => 1,
                ),
                'height' => 300,
                'chartArea' => array(
                    'width' => '100%',
                    'height' => '90%',
                    'left' => 60,
                    'top' => 10,
                ),
                'hAxis' => array(
                    'title' => 'Date',
                    'maxAlternation' => 1,
                    'textPosition' => 'none',
                    //'textPosition' => 'in',
                    //'viewWindowMode' => 'maximized'
                ),
                'vAxis' => array(
                    'title' => 'Lobbies',
                    //'textPosition' => 'in',
                ),
                'legend' => array(
                    'position' => 'bottom',
                    'alignment' => 'start',
                    'textStyle' => array(
                        'fontSize' => 10
                    )
                ),
                'seriesType' => "bars",
                /*'series' => array(
                    3 => array(
                        'type' => "line"
                    ),
                ),*/
                'isStacked' => 'true',
            );

            $optionsDataTable = array(
                'width' => 800,
                'sortColumn' => 0,
                'sortAscending' => true,
                'alternatingRowStyle' => true,
                'page' => 'enable',
                'pageSize' => 6);

            echo '<div id="lobby_count" style="width: 800px;"></div>';
            echo '<div style="width: 800px;"><h4 class="text-center">Newest -> Oldest</h4></div>';

            $chart->load(json_encode($data));
            echo $chart->draw('lobby_count', $options);
        }

        {
            ////////////////////////////////////////////////////////
            // All Time
            ////////////////////////////////////////////////////////

            $chart = new chart2('ComboChart');

            echo '<h3>All Time</h3>';

            //$stats = json_decode(curl('http://ddp2.d2modd.in/stats/general', NULL, NULL, NULL, NULL, 20), 1);
            $production_stats = simple_cached_query('d2moddin_production_stats_alltime',
                'SELECT HOUR(`date_recorded`) as hour, DAY(`date_recorded`) as day, MONTH(`date_recorded`) as month, YEAR(`date_recorded`) as year, `lobby_total`, `lobby_wait`, `lobby_play`, `lobby_queue` FROM `stats_production` GROUP BY 4,3,2,1 ORDER BY 4 DESC,3 DESC,2 DESC,1 DESC;',
                10);

            $super_array = array();
            foreach ($production_stats as $key => $value) {
                $date = $value['year'] . '-' . $value['month'] . '-' . $value['day'] . ' ' . str_pad($value['hour'], 2, '0', STR_PAD_LEFT) . ':00';
                $super_array[] = array('c' => array(array('v' => $date), array('v' => $value['lobby_wait']), array('v' => $value['lobby_play']), array('v' => $value['lobby_queue'])));
            }

            $data = array(
                'cols' => array(
                    array('id' => '', 'label' => 'Date', 'type' => 'string'),
                    array('id' => '', 'label' => 'Waiting', 'type' => 'number'),
                    array('id' => '', 'label' => 'Playing', 'type' => 'number'),
                    array('id' => '', 'label' => 'Queueing', 'type' => 'number'),
                ),
                'rows' => $super_array
            );

            $chart_width = max(count($production_stats) * 2, 800);
            $options = array(
                //'title' => 'Average spins in ' . $hits . ' attacks',
                //'theme' => 'maximized',
                'axisTitlesPosition' => 'in',
                'width' => $chart_width,
                'bar' => array(
                    'groupWidth' => 1,
                ),
                'height' => 300,
                'chartArea' => array(
                    'width' => '100%',
                    'height' => '90%',
                    'left' => 60,
                    'top' => 10,
                ),
                'hAxis' => array(
                    'title' => 'Date',
                    'maxAlternation' => 1,
                    'textPosition' => 'none',
                    //'textPosition' => 'in',
                    //'viewWindowMode' => 'maximized'
                ),
                'vAxis' => array(
                    'title' => 'Lobbies',
                    //'textPosition' => 'in',
                ),
                'legend' => array(
                    'position' => 'bottom',
                    'alignment' => 'start',
                    'textStyle' => array(
                        'fontSize' => 10
                    )
                ),
                'seriesType' => "bars",
                /*'series' => array(
                    3 => array(
                        'type' => "line"
                    ),
                ),*/
                'isStacked' => 'true',
            );

            $optionsDataTable = array(
                'width' => 800,
                'sortColumn' => 0,
                'sortAscending' => true,
                'alternatingRowStyle' => true,
                'page' => 'enable',
                'pageSize' => 6);

            echo '<div id="lobby_count_alltime" style="overflow-x: scroll; width: 800px;"></div>';
            echo '<div style="width: 800px;"><h4 class="text-center">Newest -> Oldest</h4></div>';

            $chart->load(json_encode($data));
            echo $chart->draw('lobby_count_alltime', $options);
        }

        echo '<div id="pagerendertime" style="font-size: 12px;">';
        echo '<hr />Page generated in ' . (time() - $start) . 'secs';
        echo '</div>';


        $memcache->close();
    } else {
        echo 'No DB';
    }
} catch (Exception $e) {
    echo $e->getMessage();
}