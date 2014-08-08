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

        echo '<h2>Breakdown of Players</h2>';

        ////////////////////////////////////////////////////////
        // LAST 2 DAYS
        ////////////////////////////////////////////////////////

        {
            $chart = new chart2('ComboChart');

            echo '<h3>Last 4days</h3>';

            $production_stats = simple_cached_query('d2moddin_production_players_last4days',
                'SELECT MINUTE(`date_recorded`) as minute, HOUR(`date_recorded`) as hour, DAY(`date_recorded`) as day, MONTH(`date_recorded`) as month, YEAR(`date_recorded`) as year, `players_online`, `players_playing` FROM `stats_production_players` WHERE `date_recorded` >= now() - INTERVAL 4 DAY ORDER BY 5 DESC,4 DESC,3 DESC,2 DESC,1 DESC;',
                60);
            $mod_range = simple_cached_query('d2moddin_production_players_range_last4days',
                'SELECT MIN(`date_recorded`) as min_date, MAX(`date_recorded`) as max_date FROM `stats_production_players` WHERE `date_recorded` >= now() - INTERVAL 4 DAY;',
                60);

            $super_array = array();
            foreach ($production_stats as $key => $value) {
                $value1 = round($value['players_online'],0);
                $value2 = round($value['players_playing'],0);

                $date = $value['year'] . '-' . $value['month'] . '-' . $value['day'] . ' ' . str_pad($value['hour'], 2, '0', STR_PAD_LEFT) . ':' . ' ' . str_pad($value['minute'], 2, '0', STR_PAD_LEFT);
                $super_array[] = array('c' => array(array('v' => $date), array('v' => $value1), array('v' => $value2)));
            }

            $data = array(
                'cols' => array(
                    array('id' => '', 'label' => 'Date', 'type' => 'string'),
                    array('id' => '', 'label' => 'Online', 'type' => 'number'),
                    array('id' => '', 'label' => 'Playing', 'type' => 'number'),
                ),
                'rows' => $super_array
            );

            $chart_width = max(count($production_stats) * 2, 800);
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
                    //'maxValue' => 2000,
                    'title' => 'Players',
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
                'series' => array(
                    1 => array(
                        'type' => "line"
                    ),
                ),
                'isStacked' => 'false',
            );

            $optionsDataTable = array(
                'sortColumn' => 0,
                'sortAscending' => true,
                'alternatingRowStyle' => true,
                'page' => 'enable',
                'pageSize' => 5);

            echo '<div id="player_count" style="width: 800px;"></div>';
            echo '<div style="width: 800px;"><h4 class="text-center">' . relative_time($mod_range[0]['max_date']) . ' --> ' . relative_time($mod_range[0]['min_date']) . '</h4></div>';

            echo '<div class="panel panel-default" style="width: 800px;">
                <div class="panel-heading">
                    <h4 class="panel-title text-center">
                        <a data-toggle="collapse" data-target="#collapseTwo" class="btn btn btn-success collapsed" type="button">Data Table</a>
                    </h4>
                </div>
                <div id="collapseTwo" class="panel-collapse collapse">
                    <div class="panel-body">
                        <div id="player_count_dataTable" style="width: 100%;"></div>
                    </div>
                </div>
            </div>';

            //echo '<div id="lobby_count_dataTable" style="overflow-x: hidden; width: 800px;"></div>';

            $chart->load(json_encode($data));
            echo $chart->draw('player_count', $options, true, $optionsDataTable);
        }

        echo '<hr />';

        ////////////////////////////////////////////////////////
        // ALL TIME STATS
        ////////////////////////////////////////////////////////

        {
            $chart = new chart2('ComboChart');

            echo '<h3>All Time</h3>';

            $production_stats = simple_cached_query('d2moddin_production_players_alltime',
                'SELECT HOUR(`date_recorded`) as hour, DAY(`date_recorded`) as day, MONTH(`date_recorded`) as month, YEAR(`date_recorded`) as year, AVG(`players_online`) as players_online, AVG(`players_playing`) as players_playing FROM `stats_production_players` GROUP BY 4,3,2,1 ORDER BY 4 DESC,3 DESC,2 DESC,1 DESC;',
                60);
            $mod_range = simple_cached_query('d2moddin_production_players_range_alltime',
                'SELECT MIN(`date_recorded`) as min_date, MAX(`date_recorded`) as max_date FROM `stats_production_players`;',
                60);

            $super_array = array();
            foreach ($production_stats as $key => $value) {
                $value1 = round($value['players_online'],0);
                $value2 = round($value['players_playing'],0);

                $date = $value['year'] . '-' . $value['month'] . '-' . $value['day'] . ' ' . str_pad($value['hour'], 2, '0', STR_PAD_LEFT) . ':00';
                $super_array[] = array('c' => array(array('v' => $date), array('v' => $value1), array('v' => $value2)));
            }

            $data = array(
                'cols' => array(
                    array('id' => '', 'label' => 'Date', 'type' => 'string'),
                    array('id' => '', 'label' => 'Online', 'type' => 'number'),
                    array('id' => '', 'label' => 'Playing', 'type' => 'number'),
                ),
                'rows' => $super_array
            );

            $chart_width = max(count($production_stats) * 3, 800);
            $options = array(
                //'title' => 'Average spins in ' . $hits . ' attacks',
                //'theme' => 'maximized',
                'reverseCategories' => 0,
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
                    'title' => 'Players',
                    /*array(
                        'title' => 'Players_1',
                        //'textPosition' => 'in',
                    ),
                    array(
                        'title' => 'Players_2',
                    ),*/
                ),
                'legend' => array(
                    'position' => 'bottom',
                    'alignment' => 'start',
                    'textStyle' => array(
                        'fontSize' => 10
                    )
                ),
                'seriesType' => "bars",
                'series' => array(
                    /*0 => array(
                        'targetAxisIndex' => 0,
                    ),*/
                    1 => array(
                        //'targetAxisIndex' => 1,
                        'type' => "line"
                    ),
                ),
                'isStacked' => 'false',
            );

            echo '<div id="player_count_alltime" style="overflow-x: scroll; width: 800px;"></div>';
            //echo '<div style="width: 800px;"><h4 class="text-center">' . date('Y-m-d', strtotime($mod_range[0]['max_date'])) . ' --> ' . date('Y-m-d', strtotime($mod_range[0]['min_date'])) . '</h4></div>';
            echo '<div style="width: 800px;"><h4 class="text-center">' . relative_time($mod_range[0]['max_date']) . ' --> ' . relative_time($mod_range[0]['min_date']) . '</h4></div>';

            echo '<div class="panel-heading" style="width: 800px;">
                    <h4 class="text-center">
                        <a class="btn btn-success collapsed" type="button" onclick="downloadCSV(\'players' . time() . '.csv\')">Download to CSV</a>
                    </h4>
                </div>';

            $chart->load(json_encode($data));
            echo $chart->draw('player_count_alltime', $options, false, array(), true);
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