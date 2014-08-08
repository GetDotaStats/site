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

        echo '<h2>Games played Per Duration per Mod Based on Match Data<br />';

        $mod_range = simple_cached_query('d2moddin_games_mods_duration_range',
            //'SELECT * FROM `stats_mods_duration` ORDER BY `mod`, `range_end`;',
            'SELECT MIN(`match_date`) as date_end, MAX(`match_date`) as date_start FROM `match_stats`;',
            60);
        echo '<small>'.relative_time($mod_range[0]['date_start']).' - ' . relative_time($mod_range[0]['date_end']) . '</small></h2>';

        ////////////////////////////////////////////////////////
        // LAST WEEK
        ////////////////////////////////////////////////////////

        {

            /*$db -> q('CREATE TABLE IF NOT EXISTS `stats_mods_duration` SELECT
                300 * floor(`duration` / 300) as `range_start`,
                300 * floor(`duration` / 300) + 300 as `range_end`,
                COUNT(*) as `num_games`,
                `mod`
            FROM `match_stats`
            GROUP BY `mod`, 2
            ORDER BY `mod`, 2;');*/

            $mod_stats = simple_cached_query('d2moddin_games_mods_duration',
                //'SELECT * FROM `stats_mods_duration` ORDER BY `mod`, `range_end`;',
                'SELECT
                    120 * floor(`duration` / 120) as `range_start`,
                    120 * floor(`duration` / 120) + 120 as `range_end`,
                    COUNT(*) as `num_games`,
                    `mod`
                FROM `match_stats` GROUP BY `mod`, 2 ORDER BY `mod`, 2;',
                60);

            $testArray = array();
            $lastNum = 0;
            $lastMod = '';

            $durationArray = array();

            foreach ($mod_stats as $key => $value) {
                //$testArray[$value['mod']]['duration'] = $value['total_length'];
                $value['range_end'] = $value['range_end'] / 60;


                if ($value['mod'] != $lastMod) {
                    $lastNum = 0;
                }

                if ($value['range_end'] > ($lastNum + 2)) {
                    while ($value['range_end'] > ($lastNum + 2)) {
                        $testArray[$value['mod']][($lastNum + 2)] = 0;
                        $lastNum += 2;
                    }
                }

                $testArray[$value['mod']][$value['range_end']] = $value['num_games'];

                $lastNum = $value['range_end'];
                $lastMod = $value['mod'];

                if(isset($durationArray[$value['mod']])){
                    $durationArray[$value['mod']] += ($value['range_end'] * $value['num_games']);
                }
                else{
                    $durationArray[$value['mod']] = ($value['range_end'] * $value['num_games']);
                }
            }


            /*echo '<pre>';
            print_r($testArray);
            echo '</pre>';
            exit();*/


            $options = array(
                //'title' => 'Average spins in ' . $hits . ' attacks',
                //'theme' => 'maximized',
                'bar' => array(
                    'groupWidth' => 3,
                ),
                'height' => 300,
                'chartArea' => array(
                    'width' => '100%',
                    'height' => '85%',
                    'left' => 50,
                    'top' => 10,
                ),
                'hAxis' => array(
                    'title' => 'Duration',
                    //'maxAlternation' => 1,
                    //'textPosition' => 'none',
                    //'textPosition' => 'in',
                    //'viewWindowMode' => 'maximized'
                ),
                'vAxis' => array(
                    'title' => 'Games',
                    //'textPosition' => 'in',
                ),
                'legend' => array(
                    'position' => 'none',
                ),
                'seriesType' => "bars",
            );

            $optionsDataTable = array(
                'sortColumn' => 0,
                'sortAscending' => true,
                'alternatingRowStyle' => true,
                'page' => 'enable',
                'pageSize' => 5);


            foreach ($testArray as $key => $value) {
                $chart = new chart2('ComboChart');

                echo '<hr /><h3>' . $key . ' <small>' . number_format(array_sum($value), 0) . ' games - ' . number_format($durationArray[$key], 0) . ' mins</small></h3>';

                $super_array = array();
                foreach ($value as $key2 => $value2) {
                    $super_array[] = array('c' => array(array('v' => $key2), array('v' => $value2)));
                }

                $data = array(
                    'cols' => array(
                        array('id' => '', 'label' => 'Duration', 'type' => 'number'),
                        array('id' => '', 'label' => 'Games', 'type' => 'number'),
                    ),
                    'rows' => $super_array
                );

                end($value);
                $maxKey = key($value);


                $chart_width = max(count($super_array) * 5, 400);
                $options['width'] = $chart_width;
                $options['hAxis']['maxValue'] = $maxKey + 2;
                $options['hAxis']['gridlines']['count'] = ($maxKey + 2) / 2;

                echo '<div id="duration_breakdown_' . $key . '" style="width: 400px;"></div>';
                //echo '<div style="width: 800px;"><h4 class="text-center">' . date('Y-m-d', strtotime($mod_range[0]['max_date'])) . ' --> ' . date('Y-m-d', strtotime($mod_range[0]['min_date'])) . '</h4></div>';

                /*echo '<div class="panel panel-default" style="width: 800px;">
                <div class="panel-heading">
                    <h4 class="panel-title text-center">
                        <a data-toggle="collapse" data-target="#collapseTwo" class="btn btn btn-success collapsed" type="button">Data Table</a>
                    </h4>
                </div>
                <div id="collapseTwo" class="panel-collapse collapse">
                    <div class="panel-body">
                        <div id="lobby_count_dataTable" style="width: 100%;"></div>
                    </div>
                </div>';*/
                echo '</div>';

                $chart->load(json_encode($data));
                echo $chart->draw('duration_breakdown_' . $key, $options);

            }
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