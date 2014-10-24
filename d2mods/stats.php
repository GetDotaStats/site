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
                    // FAILURES PER DAY
                    //////////////////////

                    {
                        $modFailStats_numgames = $db->q(
                            'SELECT
                                DAY(mmo.`match_recorded`) as day,
                                MONTH(mmo.`match_recorded`) as month,
                                YEAR(mmo.`match_recorded`) as year,
                                COUNT(*) as num_games
                            FROM `mod_match_overview` mmo
                            LEFT JOIN `mod_list` ml ON ml.mod_identifier = mmo.mod_id
                            WHERE ml.`mod_id` = ?
                            GROUP BY 3,2,1
                            ORDER BY 3,2,1;',
                            'i',
                            $modID
                        );

                        /*echo '<pre>';
                        print_r($modFailStats_numfails);
                        echo '</pre>';
                        //exit();*/


                        $testArray = array();

                        foreach ($modFailStats_numgames as $key => $value) {
                            $modDate = $value['day'] . '-' . $value['month'] . '-' . $value['year'];

                            $testArray[$modDate]['num_games'] = $value['num_games'];
                        }

                        /*echo '<pre>';
                        print_r($testArray);
                        echo '</pre>';
                        //exit();*/


                        $options = array(
                            //'title' => 'Average spins in ' . $hits . ' attacks',
                            //'theme' => 'maximized',
                            'bar' => array(
                                'groupWidth' => 10,
                            ),
                            'height' => 300,
                            'chartArea' => array(
                                'width' => '100%',
                                'height' => '75%',
                                'left' => 80,
                                'top' => 10,
                            ),
                            'hAxis' => array(
                                //'title' => 'Date',
                                //'maxAlternation' => 1,
                                //'textPosition' => 'none',
                                //'textPosition' => 'in',
                                //'viewWindowMode' => 'maximized'
                                //'slantedText' => 1,
                                //'slantedTextAngle' => 60,
                            ),
                            'vAxes' => array(
                                array(
                                    'title' => 'Games',
                                    //'textPosition' => 'in',
                                    //'logScale' => 1,
                                ),
                                array(
                                    'title' => 'Avg. Players',
                                    //'textPosition' => 'in',
                                    //'logScale' => 1,
                                ),
                            ),
                            'legend' => array(
                                'position' => 'none',
                                //'alignment' => 'center',
                            ),
                            'seriesType' => 'bars',
                            'series' => array(
                                2 => array(
                                    'type' => 'line',
                                    'targetAxisIndex' => 1,
                                ),
                            ),
                            'tooltip' => array(
                                //'isHtml' => 1,
                            ),
                            'isStacked' => 1,
                            'focusTarget' => 'category',
                        );

                        $chart = new chart2('ComboChart');

                        $super_array = array();
                        foreach ($testArray as $key2 => $value2) {

                            $numGames = !empty($value2['num_games'])
                                ? $value2['num_games']
                                : 0;

                            $super_array[] = array('c' => array(array('v' => $key2), array('v' => $numGames))); //, array('v' => '<div style="padding:5px 5px 5px 5px;"><strong>' . $key2 . '</strong> players<br />Games?: <strong>' . number_format($numFails) . '</strong></div>')));
                        }

                        $data = array(
                            'cols' => array(
                                array('id' => '', 'label' => 'Date', 'type' => 'string'),
                                array('id' => '', 'label' => 'Games', 'type' => 'number'),
                                //array('id' => '', 'label' => 'Tooltip', 'type' => 'string', 'role' => 'tooltip', 'p' => array('html' => 1)),
                            ),
                            'rows' => $super_array
                        );

                        $chart_width = max(count($super_array) * 9, 700);
                        $options['width'] = $chart_width;
                        $options['hAxis']['gridlines']['count'] = count($super_array);

                        echo '<h3>Games Played per Day <small>Only including games that load</small></h3>';
                        echo '<div id="breakdown_num_games_per_day" style="width: 400px;"></div>';

                        $chart->load(json_encode($data));
                        echo $chart->draw('breakdown_num_games_per_day', $options);
                    }

                    echo '<hr />';

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
                            'height' => 300,
                            'chartArea' => array(
                                'width' => '100%',
                                'height' => '75%',
                                'left' => 80,
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
                                //'logScale' => 1,
                                'scaleType' => 'mirrorLog',
                                //'minValue' => 0.01,
                                /*'viewWindow' => array(
                                    'min' => 0
                                ),*/
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
                            $super_array[] = array('c' => array(array('v' => $key2), array('v' => $value2), array('v' => '<div style="padding:5px 5px 5px 5px;"><strong>' . $key2 . '</strong> mins<br />Games: <strong>' . number_format($value2) . '</strong><br />(' . number_format(100 * $value2 / array_sum($testArray), 2) . '%)</div>')));
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


                        $chart_width = max(count($super_array) * 9, 700);
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
                    // NUM PLAYERS
                    //////////////////////

                    {
                        $modStats = $db->q(
                            'SELECT
                                match_num_players as `num_players`,
                                COUNT(*) as `num_games`
                            FROM `mod_match_overview` mmo
                            LEFT JOIN `mod_list` ml ON ml.mod_identifier = mmo.mod_id
                            WHERE ml.`mod_id` = ?
                            GROUP BY 1
                            ORDER BY 1;',
                            'i',
                            $modID
                        );

                        $testArray = array();
                        $lastNum = 0; //NEED TO BE NEGATIVE TO GRAPH 0 TOO

                        foreach ($modStats as $key => $value) {
                            if ($value['num_players'] > ($lastNum + 1)) {
                                while ($value['num_players'] > ($lastNum + 1)) {
                                    $testArray[$lastNum + 1] = 0;
                                    $lastNum += 1;
                                }
                            }

                            $testArray[$value['num_players']] = $value['num_games'];

                            $lastNum = $value['num_players'];
                        }

                        /*echo '<pre>';
                        print_r($testArray);
                        echo '</pre>';
                        //exit();*/


                        $options = array(
                            //'title' => 'Average spins in ' . $hits . ' attacks',
                            //'theme' => 'maximized',
                            'bar' => array(
                                'groupWidth' => 10,
                            ),
                            'height' => 300,
                            'chartArea' => array(
                                'width' => '100%',
                                'height' => '80%',
                                'left' => 80,
                                'top' => 10,
                            ),
                            'hAxis' => array(
                                'title' => 'Number of Players',
                                //'maxAlternation' => 1,
                                //'textPosition' => 'none',
                                //'textPosition' => 'in',
                                //'viewWindowMode' => 'maximized'
                                //'slantedText' => 1,
                                //'slantedTextAngle' => 60,
                            ),
                            'vAxis' => array(
                                'title' => 'Games',
                                //'textPosition' => 'in',
                                //'logScale' => 1,
                            ),
                            'legend' => array(
                                'position' => 'none',
                            ),
                            'seriesType' => "bars",
                            'tooltip' => array(
                                'isHtml' => 1,
                            ),
                        );

                        $chart = new chart2('ComboChart');

                        $super_array = array();
                        foreach ($testArray as $key2 => $value2) {
                            $super_array[] = array('c' => array(array('v' => $key2), array('v' => $value2), array('v' => '<div style="padding:5px 5px 5px 5px;"><strong>' . $key2 . '</strong> players<br />Games: <strong>' . number_format($value2) . '</strong><br />(' . number_format(100 * $value2 / array_sum($testArray), 2) . '%)</div>')));
                        }

                        $data = array(
                            'cols' => array(
                                array('id' => '', 'label' => 'Number of Players', 'type' => 'number'),
                                array('id' => '', 'label' => 'Games', 'type' => 'number'),
                                array('id' => '', 'label' => 'Tooltip', 'type' => 'string', 'role' => 'tooltip', 'p' => array('html' => 1)),
                            ),
                            'rows' => $super_array
                        );

                        $chart_width = max(count($super_array) * 9, 700);
                        $options['width'] = $chart_width;
                        $options['hAxis']['maxValue'] = $maxKey;
                        $options['hAxis']['gridlines']['count'] = count($super_array);

                        echo '<h3>Players per Game</h3>';
                        echo '<div id="breakdown_num_players" style="width: 400px;"></div>';

                        $chart->load(json_encode($data));
                        echo $chart->draw('breakdown_num_players', $options);
                    }

                    echo '<hr />';

                    //////////////////////
                    // FAILURES PER DAY
                    //////////////////////

                    {
                        $modFailStats_numgames = $db->q(
                            'SELECT
                                DAY(mmo.`match_recorded`) as day,
                                MONTH(mmo.`match_recorded`) as month,
                                YEAR(mmo.`match_recorded`) as year,
                                COUNT(*) as num_games
                            FROM `mod_match_overview` mmo
                            LEFT JOIN `mod_list` ml ON ml.mod_identifier = mmo.mod_id
                            WHERE ml.`mod_id` = ?
                            GROUP BY 3,2,1
                            ORDER BY 3,2,1;',
                            'i',
                            $modID
                        );

                        $modFailStats_numfails = $db->q(
                            'SELECT
                                DAY(mmo.`match_recorded`) as day,
                                MONTH(mmo.`match_recorded`) as month,
                                YEAR(mmo.`match_recorded`) as year,
                                COUNT(*) as num_games
                            FROM `mod_match_overview` mmo
                            LEFT JOIN `mod_list` ml ON ml.mod_identifier = mmo.mod_id
                            WHERE ml.`mod_id` = ? AND `match_duration` <=130
                            GROUP BY 3,2,1
                            ORDER BY 3,2,1;',
                            'i',
                            $modID
                        );

                        $modFailStats_averageplayers = $db->q(
                            'SELECT
                                DAY(mmo.`match_recorded`) as day,
                                MONTH(mmo.`match_recorded`) as month,
                                YEAR(mmo.`match_recorded`) as year,
                                AVG(`match_num_players`) as avg_players
                            FROM `mod_match_overview` mmo
                            LEFT JOIN `mod_list` ml ON ml.mod_identifier = mmo.mod_id
                            WHERE ml.`mod_id` = ?
                            GROUP BY 3,2,1
                            ORDER BY 3,2,1;',
                            'i',
                            $modID
                        );

                        /*echo '<pre>';
                        print_r($modFailStats_numfails);
                        echo '</pre>';
                        //exit();*/


                        $testArray = array();

                        foreach ($modFailStats_numgames as $key => $value) {
                            $modDate = $value['day'] . '-' . $value['month'] . '-' . $value['year'];

                            $testArray[$modDate]['num_games'] = $value['num_games'];
                        }

                        foreach ($modFailStats_numfails as $key => $value) {
                            $modDate = $value['day'] . '-' . $value['month'] . '-' . $value['year'];

                            $testArray[$modDate]['num_fails'] = $value['num_games'];
                        }

                        foreach ($modFailStats_averageplayers as $key => $value) {
                            $modDate = $value['day'] . '-' . $value['month'] . '-' . $value['year'];

                            $testArray[$modDate]['avg_players'] = $value['avg_players'];
                        }

                        /*echo '<pre>';
                        print_r($testArray);
                        echo '</pre>';
                        //exit();*/


                        $options = array(
                            //'title' => 'Average spins in ' . $hits . ' attacks',
                            //'theme' => 'maximized',
                            'bar' => array(
                                'groupWidth' => 10,
                            ),
                            'height' => 300,
                            'chartArea' => array(
                                'width' => '100%',
                                'height' => '75%',
                                'left' => 80,
                                'top' => 10,
                            ),
                            'hAxis' => array(
                                //'title' => 'Date',
                                //'maxAlternation' => 1,
                                //'textPosition' => 'none',
                                //'textPosition' => 'in',
                                //'viewWindowMode' => 'maximized'
                                //'slantedText' => 1,
                                //'slantedTextAngle' => 60,
                            ),
                            'vAxes' => array(
                                array(
                                    'title' => 'Games (%)',
                                    //'textPosition' => 'in',
                                    //'logScale' => 1,
                                ),
                                array(
                                    'title' => 'Avg. Players',
                                    //'textPosition' => 'in',
                                    //'logScale' => 1,
                                ),
                            ),
                            'legend' => array(
                                'position' => 'bottom',
                                'alignment' => 'center',
                            ),
                            'seriesType' => 'bars',
                            'series' => array(
                                1 => array(
                                    'type' => 'line',
                                    'targetAxisIndex' => 1,
                                ),
                            ),
                            'tooltip' => array(
                                //'isHtml' => 1,
                            ),
                            'isStacked' => 1,
                            'focusTarget' => 'category',
                        );

                        $chart = new chart2('ComboChart');

                        $super_array = array();
                        foreach ($testArray as $key2 => $value2) {

                            $numFails = !empty($value2['num_fails'])
                                ? $value2['num_fails']
                                : 0;

                            $gamesPercentageSuccess = !empty($value2['num_games'])
                                ? $value2['num_fails'] / $value2['num_games'] * 100
                                : 100;

                            $avgPlayers = !empty($value2['avg_players'])
                                ? $value2['avg_players']
                                : 0;

                            $super_array[] = array('c' => array(
                                array('v' => $key2),
                                array('v' => $gamesPercentageSuccess),
                                array('v' => number_format($gamesPercentageSuccess) . '%'),
                                array('v' => $avgPlayers),
                                array('v' => number_format($avgPlayers,1)),
                                //, array('v' => '<div style="padding:5px 5px 5px 5px;"><strong>' . $key2 . '</strong> players<br />Games?: <strong>' . number_format($numFails) . '</strong></div>')));
                            ));
                        }

                        $data = array(
                            'cols' => array(
                                array('id' => '', 'label' => 'Date', 'type' => 'string'),
                                array('id' => '', 'label' => 'Games (%)', 'type' => 'number'),
                                array('id' => '', 'label' => 'Tooltip', 'type' => 'string', 'role' => 'tooltip', 'p' => array('html' => 1)),
                                array('id' => '', 'label' => 'Avg. Players', 'type' => 'number'),
                                array('id' => '', 'label' => 'Tooltip', 'type' => 'string', 'role' => 'tooltip', 'p' => array('html' => 1)),
                                //array('id' => '', 'label' => 'Tooltip', 'type' => 'string', 'role' => 'tooltip', 'p' => array('html' => 1)),
                            ),
                            'rows' => $super_array
                        );

                        $chart_width = max(count($super_array) * 9, 700);
                        $options['width'] = $chart_width;
                        $options['hAxis']['gridlines']['count'] = count($super_array);

                        echo '<h3>Games Failed to Start vs. Average Players <small>per day</small></h3>';
                        echo '<div id="breakdown_num_failed_games_avg_players" style="width: 400px;"></div>';

                        $chart->load(json_encode($data));
                        echo $chart->draw('breakdown_num_failed_games_avg_players', $options);
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