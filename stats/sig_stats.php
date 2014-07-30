<?php
require_once('./functions.php');
require_once('../global_functions.php');
require_once('../connections/parameters.php');

$start = time();

if (!isset($_SESSION)) {
    session_start();
}

try {
    $db = new dbWrapper($hostname_sig, $username_sig, $password_sig, $database_sig, true);
    if ($db) {
        $memcache = new Memcache;
        $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

        echo '<h2>Breakdown of Signature Adoption</h2>';

        {
            ////////////////////////////////////////////////////////
            // Hourly (All Time)
            ////////////////////////////////////////////////////////

            $chart = new chart2('ComboChart');

            echo '<h3>All Time (Hourly)</h3>';

            $production_stats = simple_cached_query('stats_signature_adoption_alltime',
                'SELECT `hour`, `day`, `month`, `year`, `sig_views` FROM `stats_1_count` GROUP BY 4,3,2,1 ORDER BY 4 DESC,3 DESC,2 DESC,1 DESC;',
                10);

            $super_array = array();
            foreach ($production_stats as $key => $value) {
                $date = $value['year'] . '-' . $value['month'] . '-' . $value['day'] . ' ' . str_pad($value['hour'], 2, '0', STR_PAD_LEFT) . ':00';
                $super_array[] = array('c' => array(array('v' => $date), array('v' => $value['sig_views'])));
            }

            $data = array(
                'cols' => array(
                    array('id' => '', 'label' => 'Date', 'type' => 'string'),
                    array('id' => '', 'label' => 'Views', 'type' => 'number'),
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
                    'left' => 110,
                    'top' => 15,
                ),
                'hAxis' => array(
                    'title' => 'Date',
                    'maxAlternation' => 1,
                    'textPosition' => 'none',
                    //'textPosition' => 'in',
                    //'viewWindowMode' => 'maximized'
                ),
                'vAxis' => array(
                    'title' => 'Views',
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

            echo '<div id="sig_views_alltime" style="overflow-x: scroll; width: 800px;"></div>';
            echo '<div style="width: 800px;"><h4 class="text-center">Newest -> Oldest</h4></div>';

            $chart->load(json_encode($data));
            echo $chart->draw('sig_views_alltime', $options);
        }

        {
            ////////////////////////////////////////////////////////
            // Daily (All Time)
            ////////////////////////////////////////////////////////

            $chart = new chart2('ComboChart');

            echo '<h3>All Time (Daily)</h3>';

            $production_stats = simple_cached_query('stats_signature_adoption_alltime_daily',
                'SELECT `day`, `month`, `year`, SUM(`sig_views`) as sig_views FROM `stats_1_count` GROUP BY 3,2,1 ORDER BY 3 DESC,2 DESC,1 DESC;',
                10);

            $super_array = array();
            foreach ($production_stats as $key => $value) {
                $date = $value['year'] . '-' . $value['month'] . '-' . $value['day'];
                $super_array[] = array('c' => array(array('v' => $date), array('v' => $value['sig_views'])));
            }

            $data = array(
                'cols' => array(
                    array('id' => '', 'label' => 'Date', 'type' => 'string'),
                    array('id' => '', 'label' => 'Views', 'type' => 'number'),
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
                    'left' => 70,
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
                    'title' => 'Views',
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

            echo '<div id="sig_views_alltime_daily" style="overflow-x: scroll; width: 800px;"></div>';
            echo '<div style="width: 800px;"><h4 class="text-center">Newest -> Oldest</h4></div>';

            $chart->load(json_encode($data));
            echo $chart->draw('sig_views_alltime_daily', $options);
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