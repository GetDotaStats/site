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

        echo '<h2>Breakdown of Lobbies per Server</h2>';

        ////////////////////////////////////////////////////////
        // Last 4 Days
        ////////////////////////////////////////////////////////

        {
            $chart = new chart2('ComboChart');

            echo '<h3>Last 4Days</h3>';

            //$stats = json_decode(curl('http://ddp2.d2modd.in/stats/general', NULL, NULL, NULL, NULL, 20), 1);
            $region_stats = simple_cached_query('d2moddin_production_servers',
                'SELECT MINUTE(sps.`date_recorded`) as minute, HOUR(sps.`date_recorded`) as hour, DAY(sps.`date_recorded`) as day, MONTH(sps.`date_recorded`) as month, YEAR(sps.`date_recorded`) as year, SUM(sps.`server_activeinstances`) as server_activeinstances, SUM(sps.`server_maxinstances`) as server_maxinstances, sps.`server_name` FROM `stats_production_servers` sps WHERE `date_recorded` >= now() - INTERVAL 4 DAY GROUP BY 5,4,3,2,1, `server_name` ORDER BY 5 DESC,4 DESC,3 DESC,2 DESC,1 DESC, sps.`server_name` DESC;',
                60);
            $region_list = simple_cached_query('d2moddin_production_server_list',
                'SELECT DISTINCT `server_name` FROM `stats_production_servers` WHERE `date_recorded` >= now() - INTERVAL 4 DAY ORDER BY server_name DESC;',
                60);
            $mod_range = simple_cached_query('d2moddin_production_server_range',
                'SELECT MIN(`date_recorded`) as min_date, MAX(`date_recorded`) as max_date FROM `stats_production_servers` WHERE `date_recorded` >= now() - INTERVAL 4 DAY;',
                60);

            if (!empty($region_stats)) {
                $test_array = array();
                foreach ($region_stats as $key => $value) {
                    $date = $value['year'] . '-' . $value['month'] . '-' . $value['day'] . ' ' . str_pad($value['hour'], 2, '0', STR_PAD_LEFT) . ':' . ' ' . str_pad($value['minute'], 2, '0', STR_PAD_LEFT);

                    if (!isset($test_array[$date])) {
                        foreach ($region_list as $mod_list_key => $mod_list_value) {
                            $test_array[$date][$mod_list_value['server_name']] = 0;
                        }
                    }

                    $test_array[$date][$value['server_name']] = $value['server_activeinstances'];
                    //ksort($test_array[$date]);
                }

                $super_array = array();
                $i = 0;
                foreach ($test_array as $key => $value) {
                    $super_array[$i] = array('c' => array(array('v' => $key)));

                    foreach ($value as $key2 => $value2) {
                        $super_array[$i]['c'][] = array('v' => $value2);
                    }
                    $i++;
                }


                $data = array(
                    'cols' => array(
                        array('id' => '', 'label' => 'Date', 'type' => 'string'),
                    ),
                    'rows' => $super_array
                );

                foreach ($region_list as $key => $value) {
                    $data['cols'][] = array('id' => '', 'label' => $value['server_name'], 'type' => 'number');
                }


                $chart_width = max(count($test_array) * 2, 800);

                $options = array(
                    //'title' => 'Average spins in ' . $hits . ' attacks',
                    //'theme' => 'maximized',
                    'width' => $chart_width,
                    'bar' => array(
                        'groupWidth' => 1,
                    ),
                    'height' => 300,
                    'chartArea' => array(
                        'width' => '100%',
                        'height' => '85%',
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

                echo '<div id="lobby_count" style="width: 800px;"></div>';
                // echo '<div style="width: 800px;"><h4 class="text-center">'.date('Y-m-d', strtotime($mod_range[0]['max_date'])).' --> '.date('Y-m-d', strtotime($mod_range[0]['min_date'])).'</h4></div>';
                echo '<div style="width: 800px;"><h4 class="text-center">' . relative_time($mod_range[0]['max_date']) . ' --> ' . relative_time($mod_range[0]['min_date']) . '</h4></div>';

                $chart->load(json_encode($data));
                echo $chart->draw('lobby_count', $options);
            }
            else{
                echo 'No data for the last week!';
            }
        }

        ////////////////////////////////////////////////////////
        // All Time
        ////////////////////////////////////////////////////////

        {
            $chart = new chart2('ComboChart');

            echo '<h3>All Time</h3>';

            //$stats = json_decode(curl('http://ddp2.d2modd.in/stats/general', NULL, NULL, NULL, NULL, 20), 1);
            $region_stats = simple_cached_query('d2moddin_production_servers_alltime',
                'SELECT HOUR(sps.`date_recorded`) as hour, DAY(sps.`date_recorded`) as day, MONTH(sps.`date_recorded`) as month, YEAR(sps.`date_recorded`) as year, SUM(sps.`server_activeinstances`) as server_activeinstances, SUM(sps.`server_maxinstances`) as server_maxinstances, sps.`server_name` FROM `stats_production_servers` sps GROUP BY 4,3,2,1, `server_name` ORDER BY 4 DESC,3 DESC,2 DESC,1 DESC, sps.`server_name` DESC;',
                60);
            $region_list = simple_cached_query('d2moddin_production_server_list_alltime',
                'SELECT DISTINCT `server_name` FROM `stats_production_servers` ORDER BY server_name DESC;',
                60);
            $mod_range = simple_cached_query('d2moddin_production_server_range_alltime',
                'SELECT MIN(`date_recorded`) as min_date, MAX(`date_recorded`) as max_date FROM `stats_production_servers`;',
                60);

            $test_array = array();
            foreach ($region_stats as $key => $value) {
                $date = $value['year'] . '-' . $value['month'] . '-' . $value['day'] . ' ' . str_pad($value['hour'], 2, '0', STR_PAD_LEFT) . ':00';

                if (!isset($test_array[$date])) {
                    foreach ($region_list as $mod_list_key => $mod_list_value) {
                        $test_array[$date][$mod_list_value['server_name']] = 0;
                    }
                }

                $test_array[$date][$value['server_name']] = $value['server_activeinstances'];
                //ksort($test_array[$date]);
            }

            $super_array = array();
            $i = 0;
            foreach ($test_array as $key => $value) {
                $super_array[$i] = array('c' => array(array('v' => $key)));

                foreach ($value as $key2 => $value2) {
                    $super_array[$i]['c'][] = array('v' => $value2);
                }
                $i++;
            }


            $data = array(
                'cols' => array(
                    array('id' => '', 'label' => 'Date', 'type' => 'string'),
                ),
                'rows' => $super_array
            );

            foreach ($region_list as $key => $value) {
                $data['cols'][] = array('id' => '', 'label' => $value['server_name'], 'type' => 'number');
            }


            $chart_width = max(count($test_array) * 2, 800);

            $options = array(
                //'title' => 'Average spins in ' . $hits . ' attacks',
                //'theme' => 'maximized',
                'width' => $chart_width,
                'bar' => array(
                    'groupWidth' => 1,
                ),
                'height' => 300,
                'chartArea' => array(
                    'width' => '100%',
                    'height' => '85%',
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

            echo '<div id="lobby_count_alltime" style="overflow-x: scroll; width: 800px;"></div>';
            //echo '<div style="width: 800px;"><h4 class="text-center">'.date('Y-m-d', strtotime($mod_range[0]['max_date'])).' --> '.date('Y-m-d', strtotime($mod_range[0]['min_date'])).'</h4></div>';
            echo '<div style="width: 800px;"><h4 class="text-center">' . relative_time($mod_range[0]['max_date']) . ' --> ' . relative_time($mod_range[0]['min_date']) . '</h4></div>';

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