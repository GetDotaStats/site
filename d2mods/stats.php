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
                $modRange = simple_cached_query('d2mods_mod_duration_range' . $modID,
                    //'SELECT * FROM `stats_mods_duration` ORDER BY `mod`, `range_end`;',
                    'SELECT MIN(`match_recorded`) as date_end, MAX(`match_recorded`) as date_start FROM `mod_match_overview` LIMIT 0,1;',
                    60);

                echo '<h2>' . $modDetails[0]['mod_name'] . '</h2>';
                echo '<p><a class="nav-clickable" href="#d2mods__directory">Back to Mod Directory</a></p>';

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

                    $modStats = $db->q(
                        'SELECT
                            240 * floor(mmo.`match_duration` / 240) as `range_start`,
                            240 * floor(mmo.`match_duration` / 240) + 240 as `range_end`,
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

                    $periodGrouping = 4; //CHANGE SQL TOO IF YOU MODIFY THIS
                    $periodCutoff = 48;

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
                            'groupWidth' => 3,
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


                    $chart_width = max(count($super_array) * 5, 400);
                    $options['width'] = $chart_width;
                    $options['hAxis']['maxValue'] = $maxKey + 2;
                    $options['hAxis']['gridlines']['count'] = ($maxKey + 2) / 2;

                    echo '<h3>Games Played per Duration</h3>';
                    echo '<div id="duration_breakdown" style="width: 400px;"></div>';

                    echo '<div class="container">
                        <div class="col-sm-6">
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

                echo '<p><a class="nav-clickable" href="#d2mods__directory">Back to Mod Directory</a></p>';

                echo '<div id="pagerendertime" style="font-size: 12px;">';
                echo '<hr />Page generated in ' . (time() - $start) . 'secs';
                echo '</div>';

            } else {
                echo 'No mods with that ID';
            }
        } else {
            echo 'Invalid modID';
        }
        $memcache->close();
    } else {
        echo 'No DB';
    }
} catch (Exception $e) {
    echo $e->getMessage();
}