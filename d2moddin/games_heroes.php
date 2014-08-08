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

        echo '<h2>Heroes Picked per Mod</h2>';

        ////////////////////////////////////////////////////////
        // LAST WEEK PIE
        ////////////////////////////////////////////////////////
        /*
                {
                    $chart = new chart2('PieChart');

                    $mod_stats_pie = simple_cached_query('d2moddin_games_mods_pie',
                        'SELECT `mod` as mod_name, COUNT(*) as num_games FROM `match_stats` WHERE `match_ended` >= (SELECT MAX(`match_ended`) FROM `match_stats`) - INTERVAL 7 DAY GROUP BY `mod_name` ORDER BY mod_name DESC;',
                        60);

                    $super_array = array();
                    foreach ($mod_stats_pie as $key => $value) {
                        $super_array[] = array('c' => array(array('v' => $value['mod_name']), array('v' => $value['num_games'])));
                    }

                    $data = array(
                        'cols' => array(
                            array('id' => '', 'label' => 'Mod', 'type' => 'string'),
                            array('id' => '', 'label' => 'Games', 'type' => 'number'),
                        ),
                        'rows' => $super_array
                    );

                    $chart_width = max(count($test_array) * 2, 800);

                    $options = array(
                        'width' => $chart_width,
                        'height' => 300,
                        'chartArea' => array(
                            'width' => '100%',
                            'height' => '85%',
                        ),
                        'legend' => array(
                            'position' => 'top',
                            'alignment' => 'center',
                            'textStyle' => array(
                                'fontSize' => 10
                            )
                        ),
                        'is3D' => 'true'
                    );

                    echo '<div id="lobby_count_pie" style="width: 800px;"></div>';

                    $chart->load(json_encode($data));
                    echo $chart->draw('lobby_count_pie', $options);
                }
        */
        ////////////////////////////////////////////////////////
        // ALL TIME
        ////////////////////////////////////////////////////////

        {
            $chart = new chart2('ComboChart');

            echo '<h3>All Available Data</h3>';

            /*
                CREATE TABLE IF NOT EXISTS `states_mods_heroes`
                SELECT mp.`hero_id` , COUNT( mp.`hero_id` ) AS picked, ms.`mod` AS mod_name
                FROM  `match_players` mp
                LEFT JOIN  `match_stats` ms ON mp.`match_id` = ms.`match_id`
                GROUP BY ms.`mod` , mp.`hero_id`
                ORDER BY 1 , 2;
             */

            //$stats = json_decode(curl('http://ddp2.d2modd.in/stats/general', NULL, NULL, NULL, NULL, 20), 1);
            $mod_stats = simple_cached_query('d2moddin_games_mods_alltime',
                'SELECT mp.`hero_id`, COUNT(mp.`hero_id`) as picked, ms.`mod` as mod_name FROM `match_players` mp LEFT JOIN `match_stats` ms ON mp.`match_id` = ms.`match_id` GROUP BY ms.`mod`, mp.`hero_id` ORDER BY 1,2;',
                60);
            $mod_list = simple_cached_query('d2moddin_games_mods_list_alltime',
                'SELECT DISTINCT  `mod` as mod_name FROM `match_stats` ORDER BY `mod_name`;',
                60);
            $mod_range = simple_cached_query('d2moddin_games_mods_range_alltime',
                'SELECT MIN(`match_ended`) as min_date, MAX(`match_ended`) as max_date FROM `match_stats`;',
                60);

            $test_array = array();
            foreach ($mod_stats as $key => $value) {
                $date = $value['year'] . '-' . $value['month'] . '-' . $value['day'] . ' ' . str_pad($value['hour'], 2, '0', STR_PAD_LEFT) . ':00';

                if (!isset($test_array[$date])) {
                    foreach ($mod_list as $mod_list_key => $mod_list_value) {
                        $test_array[$date][$mod_list_value['mod_name']] = 0;
                    }
                }

                $test_array[$date][$value['mod_name']] = $value['num_lobbies'];
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

            foreach ($mod_list as $key => $value) {
                $data['cols'][] = array('id' => '', 'label' => $value['mod_name'], 'type' => 'number');
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
                    'left' => 50,
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
                    'title' => 'Games',
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
            //echo '<div style="width: 800px;"><h4 class="text-center">' . date('Y-m-d', strtotime($mod_range[0]['max_date'])) . ' --> ' . date('Y-m-d', strtotime($mod_range[0]['min_date'])) . '</h4></div>';
            echo '<div style="width: 800px;"><h4 class="text-center">' . relative_time($mod_range[0]['max_date']) . ' --> ' . relative_time($mod_range[0]['min_date']) . '</h4></div>';

            echo '<div class="panel-heading" style="width: 800px;">
                    <h4 class="text-center">
                        <a class="btn btn-success collapsed" type="button" onclick="downloadCSV(\'mods' . time() . '.csv\')">Download to CSV</a>
                    </h4>
                </div>';

            $chart->load(json_encode($data));
            echo $chart->draw('lobby_count_alltime', $options, false, array(), true);
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