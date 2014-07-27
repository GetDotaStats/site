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

        ////////////////////////////////////////////////////////
        // PRODUCTION STATS
        ////////////////////////////////////////////////////////

        $chart = new chart2('ComboChart');

        echo '<h2>Plot of lobbies per mod created over time</h2>';

        //$stats = json_decode(curl('http://ddp2.d2modd.in/stats/general', NULL, NULL, NULL, NULL, 20), 1);
        $mod_stats = simple_cached_query('d2moddin_production_mods',
            'SELECT MINUTE(`date_recorded`) as minute, HOUR(`date_recorded`) as hour, DAY(`date_recorded`) as day, MONTH(`date_recorded`) as month, YEAR(`date_recorded`) as year, `mod_lobbies`, `mod_version`, `mod_name` FROM `stats_production_mods` ORDER BY 5 DESC,4 DESC,3 DESC,2 DESC,1 DESC;',
            60);
        $mod_list = simple_cached_query('d2moddin_production_mod_list',
            'SELECT DISTINCT  `mod_name` as mod_name FROM `stats_production_mods` ORDER BY `mod_name`;',
            60);

        $test_array = array();
        foreach($mod_stats as $key => $value){
            $date = $value['year'].'-'.$value['month'].'-'.$value['day'].' '.str_pad($value['hour'], 2, '0', STR_PAD_LEFT).':'.' '.str_pad($value['minute'], 2, '0', STR_PAD_LEFT);

            if(!isset($test_array[$date])){
                foreach($mod_list as $mod_list_key => $mod_list_value){
                    $test_array[$date][$mod_list_value['mod_name']] = 0;
                }
            }

            $test_array[$date][$value['mod_name']] = $value['mod_lobbies'];
        }

        $super_array = array();
        $i = 0;
        foreach ($test_array as $key => $value) {
            $super_array[$i] = array('c' => array(array('v' => $key)));

            foreach($value as $key2 => $value2){
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

        foreach($mod_list as $key => $value){
            $data['cols'][] = array('id' => '', 'label' => $value['mod_name'], 'type' => 'number');
        }

        $chart_width = max(count($test_array) * 4, 800);

        $options = array(
            //'title' => 'Average spins in ' . $hits . ' attacks',
            //'theme' => 'maximized',
            'axisTitlesPosition' => 'in',
            'width' => $chart_width,
            'bar' => array(
                'groupWidth' => 2,
            ),
            'height' => 300,
            'chartArea' => array(
                'width' => '100%',
                'height' => '90%',
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

        echo '<div id="lobby_count" style="overflow-x: scroll; width: 800px;"></div>';

        $chart->load(json_encode($data));
        echo $chart->draw('lobby_count', $options);

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