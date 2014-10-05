<?php
require_once('./functions.php');
require_once('../global_functions.php');
require_once('../connections/parameters.php');

$start = time();

if (!isset($_SESSION)) {
    session_start();
}

try {
    $db = new dbWrapper($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if ($db) {
        $memcache = new Memcache;
        $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

        $modID = empty($_GET['id']) || !is_numeric($_GET['id'])
            ? NULL
            : $_GET['id'];

        if (!empty($modID)) {
            $modDetails = $db->q('SELECT * FROM `mod_list` WHERE `mod_id` = ? LIMIT 0,1;',
                'i',
                $modID
            );

            if (!empty($modDetails)) {
                echo '<h2>' . $modDetails[0]['mod_name'] . '</h2>';
                echo '<p><a class="nav-clickable" href="#d2mods__directory">Back to Mod Directory</a></p>';

                $modRange = $db->q(
                    'SELECT
                            MIN(mmo.`match_recorded`) as date_end,
                            MAX(mmo.`match_recorded`) as date_start
                        FROM `mod_match_overview` mmo
                        LEFT JOIN `mod_list` ml ON ml.mod_identifier = mmo.mod_id
                        WHERE ml.`mod_id` = ?
                        LIMIT 0,1;',
                    'i',
                    $modID
                );

                if (!empty($modRange[0]['date_end'])) {

                    //////////////////////
                    //MOD_DURATION
                    //////////////////////

                    {
                        /*$db -> q('CREATE TABLE IF NOT EXISTS `stats_mods_duration` SELECT
                            300 * floor(`duration` / 300) as `range_start`,
                            300 * floor(`duration` / 300) + 300 as `range_end`,
                            COUNT(*) as `num_games`,
                            `mod`
                        FROM `match_stats`
                        GROUP BY `mod`, 2
                        ORDER BY `mod`, 2;');*/

                        $modStats = $db->q(
                            'SELECT
                                180 * floor(mmo.`match_duration` / 180) as `range_start`,
                                180 * floor(mmo.`match_duration` / 180) + 180 as `range_end`,
                                COUNT(*) as `num_games`
                            FROM `mod_match_overview` mmo
                            LEFT JOIN `mod_list` ml ON ml.mod_identifier = mmo.mod_id
                            WHERE ml.`mod_id` = ?
                            GROUP BY 2
                            ORDER BY 2;',
                            'i',
                            $modID
                        );

                        $testArray = array();
                        $lastNum = 0; //NEED TO BE NEGATIVE TO GRAPH 0 TOO

                        $durationArray = '';

                        $periodGrouping = 3; //CHANGE SQL TOO IF YOU MODIFY THIS
                        $periodCutoff = 60;

                        foreach ($modStats as $key => $value) {
                            $value['range_end'] = $value['range_end'] / 60;

                            if ($value['range_end'] > $periodCutoff) {
                                if (empty($testArray[$periodCutoff . '+'])) {
                                    $testArray[$periodCutoff . '+'] = $value['num_games'];
                                } else {
                                    $testArray[$periodCutoff . '+'] += $value['num_games'];
                                }
                            } else {
                                if ($value['range_end'] > ($lastNum + $periodGrouping)) {
                                    while ($value['range_end'] > ($lastNum + $periodGrouping)) {
                                        $testArray[$lastNum . ' - ' . ($lastNum + $periodGrouping)] = 0;
                                        $lastNum += $periodGrouping;
                                    }
                                }

                                $testArray[$lastNum . ' - ' . $value['range_end']] = $value['num_games'];

                                $lastNum = $value['range_end'];
                            }

                            if (isset($durationArray)) {
                                $durationArray += ($value['range_end'] * $value['num_games']);
                            } else {
                                $durationArray = ($value['range_end'] * $value['num_games']);
                            }
                        }

                        /*echo '<pre>';
                        print_r($testArray);
                        echo '</pre>';
                        //exit();*/


                        $options = array(
                            //'title' => 'Average spins in ' . $hits . ' attacks',
                            //'theme' => 'maximized',
                            'bar' => array(
                                'groupWidth' => 7,
                            ),
                            'height' => 400,
                            'chartArea' => array(
                                'width' => '100%',
                                'height' => '80%',
                                'left' => 50,
                                'top' => 10,
                            ),
                            'hAxis' => array(
                                'title' => 'Duration',
                                //'maxAlternation' => 1,
                                //'textPosition' => 'none',
                                //'textPosition' => 'in',
                                //'viewWindowMode' => 'maximized'
                                'slantedText' => 1,
                                'slantedTextAngle' => 60,
                            ),
                            'vAxis' => array(
                                'title' => 'Games',
                                //'textPosition' => 'in',
                                'logScale' => 1,
                            ),
                            'legend' => array(
                                'position' => 'none',
                            ),
                            'seriesType' => "bars",
                            'tooltip' => array(
                                'isHtml' => 1,
                            ),
                        );

                        $optionsDataTable = array(
                            'sortColumn' => 0,
                            'sortAscending' => true,
                            'alternatingRowStyle' => true,
                            'page' => 'enable',
                            'pageSize' => 5);


                        $chart = new chart2('ComboChart');

                        $super_array = array();
                        foreach ($testArray as $key2 => $value2) {
                            $super_array[] = array('c' => array(array('v' => $key2), array('v' => $value2), array('v' => '<strong>' . $key2 . '</strong> mins<br />Games: <strong>' . $value2 . '</strong>')));
                        }

                        $data = array(
                            'cols' => array(
                                array('id' => '', 'label' => 'Duration', 'type' => 'string'),
                                array('id' => '', 'label' => 'Games', 'type' => 'number'),
                                array('id' => '', 'label' => 'Tooltip', 'type' => 'string', 'role' => 'tooltip', 'p' => array('html' => 1)),
                            ),
                            'rows' => $super_array
                        );

                        end($value);
                        $maxKey = key($value);


                        $chart_width = max(count($super_array) * 9, 500);
                        $options['width'] = $chart_width;
                        $options['hAxis']['maxValue'] = $maxKey + 2;
                        $options['hAxis']['gridlines']['count'] = ($maxKey + 2) / 2;

                        echo '<h3>Games Played per Duration</h3>';
                        echo '<div id="duration_breakdown" style="width: 400px;"></div>';

                        echo '<div class="container">
                        <div class="col-sm-5">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">';
                        echo '<tr><th>Range</th><td>' . relative_time($modRange[0]['date_start']) . ' - ' . relative_time($modRange[0]['date_end']) . '</td></tr>';
                        echo '<tr><th>Games Played</th><td>' . number_format(array_sum($testArray), 0) . '</td></tr>';
                        echo '<tr><th>Combined Game Time</th><td>' . number_format($durationArray, 0) . ' mins</td></tr>';
                        echo '</table>';
                        echo '</div>
                        </div>
                        </div>';

                        $chart->load(json_encode($data));
                        echo $chart->draw('duration_breakdown', $options);
                    }

                    echo '<hr />';

                    //////////////////////
                    // PLAYED OVER TIME
                    //////////////////////

                    {
                        $chart = new chart2('ComboChart');

                        echo '<h3>Games Played Over Time</h3>';

                        //$stats = json_decode(curl('http://ddp2.d2modd.in/stats/general', NULL, NULL, NULL, NULL, 20), 1);
                        $mod_stats = $db->q(
                            'SELECT
                                    HOUR(mmo.`match_recorded`) as hour,
                                    DAY(mmo.`match_recorded`) as day,
                                    MONTH(mmo.`match_recorded`) as month,
                                    YEAR(mmo.`match_recorded`) as year,
                                    COUNT(*) as num_games
                                FROM `mod_match_overview` mmo
                                LEFT JOIN `mod_list` ml ON ml.mod_identifier = mmo.mod_id
                                WHERE ml.`mod_id` = ?
                                GROUP BY 4,3,2,1
                                ORDER BY 4 DESC,3 DESC,2 DESC,1 DESC;',
                            'i',
                            $modID
                        );

                        $test_array = array();
                        foreach ($mod_stats as $key => $value) {
                            $date = $value['year'] . '-' . $value['month'] . '-' . $value['day'] . ' ' . str_pad($value['hour'], 2, '0', STR_PAD_LEFT) . ':00';


                            //NEED TO FIX GAPS!!!!!!!!!

                            /*
                             if (!isset($test_array[$date])) {
                                foreach ($mod_list as $mod_list_key => $mod_list_value) {
                                    $test_array[$date][$mod_list_value['mod_name']] = 0;
                                }
                            }
                             */

                            $test_array[$date] = $value['num_games'];
                        }

                        /*echo '<pre>';
                        print_r($test_array);
                        echo '</pre>';
                        exit();*/

                        $super_array = array();
                        foreach ($test_array as $key => $value) {
                            $super_array[] = array('c' => array(array('v' => $key), array('v' => $value)));
                        }

                        $data = array(
                            'cols' => array(
                                array('id' => '', 'label' => 'Date', 'type' => 'string'),
                                array('id' => '', 'label' => 'Games', 'type' => 'number'),
                            ),
                            'rows' => $super_array
                        );

                        $chart_width = max(count($test_array) * 2, 500);

                        $options = array(
                            //'title' => 'Average spins in ' . $hits . ' attacks',
                            //'theme' => 'maximized',
                            'width' => $chart_width,
                            'bar' => array(
                                'groupWidth' => 1,
                            ),
                            'height' => 400,
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
                                'title' => 'Lobbies',
                                //'textPosition' => 'in',
                                'format' => '0'
                            ),
                            'legend' => array(
                                'position' => 'none',
                                //'position' => 'bottom',
                                //'alignment' => 'start',
                                //'textStyle' => array(
                                //    'fontSize' => 10
                                //)
                            ),
                            'seriesType' => "bars",
                            /*'series' => array(
                                3 => array(
                                    'type' => "line"
                                ),
                            ),*/
                            //'isStacked' => 'true',
                        );

                        echo '<div id="lobby_count_alltime" style="overflow-x: scroll; width: 800px;"></div>';

                        echo '<div class="panel-heading" style="width: 800px;">
                    <h4 class="text-center">
                        <a class="btn btn-success collapsed" type="button" onclick="downloadCSV(\'mods' . time() . '.csv\')">Download to CSV</a>
                    </h4>
                </div>';

                        $chart->load(json_encode($data));
                        echo $chart->draw('lobby_count_alltime', $options, false, array(), true);
                    }

                    echo '<hr />';

                    echo '<p><a class="nav-clickable" href="#d2mods__directory">Back to Mod Directory</a></p>';

                    echo '<div id="pagerendertime" style="font-size: 12px;">';
                    echo '<hr />Page generated in ' . (time() - $start) . 'secs';
                    echo '</div>';


                } else {
                    echo 'No games played with that modID';
                }
            } else {
                echo 'No mods with that modID';
            }
        } else {
            echo 'Invalid modID';
        }
        $memcache->close();
    } else {
        echo 'No DB';
    }
} catch (Exception $e) {
    echo '<div class="page-header"><div class="alert alert-danger" role="alert"><strong>Oh Snap:</strong> Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage() . '</div></div>';
}