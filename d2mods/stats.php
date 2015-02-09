<?php
require_once('./functions.php');
require_once('../global_functions.php');
require_once('../connections/parameters.php');

$start = time();

if (!isset($_SESSION)) {
    session_start();
}

try {
    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);

    if ($db) {
        $memcache = new Memcache;
        $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

        $modID = empty($_GET['id']) || !is_numeric($_GET['id'])
            ? NULL
            : $_GET['id'];

        if (!empty($modID)) {
            $modDetails = $db->q(
                'SELECT
                            ml.*,
                            gu.`user_name`,
                            gu.`user_avatar`,
                            (
                                  SELECT COUNT(*)
                                  FROM `mod_match_overview` mmo
                                  WHERE
                                    mmo.`mod_id` = ml.`mod_identifier`
                                    AND mmo.`match_recorded` >= now() - INTERVAL 7 DAY
                                    AND mmo.`match_duration` > 130
                                  GROUP BY `mod_id`
                            ) AS games_last_week,
                            (
                                  SELECT COUNT(*)
                                  FROM `mod_match_overview` mmo
                                  WHERE
                                    mmo.`mod_id` = ml.`mod_identifier`
                                    AND mmo.`match_duration` > 130
                                  GROUP BY `mod_id`
                            ) AS games_all_time,
                            (
                                  SELECT SUM(mmo.`match_duration`)
                                  FROM `mod_match_overview` mmo
                                  WHERE
                                    mmo.`mod_id` = ml.`mod_identifier`
                                    AND mmo.`match_duration` > 130
                                  GROUP BY `mod_id`
                            ) AS game_duration,
                            (
                                  SELECT COUNT(*)
                                  FROM `mod_match_overview` mmo
                                  WHERE
                                    mmo.`mod_id` = ml.`mod_identifier`
                                    AND mmo.`match_duration` <= 130
                                  GROUP BY `mod_id`
                            ) AS games_failed
                        FROM `mod_list` ml
                        LEFT JOIN `gds_users` gu ON ml.`steam_id64` = gu.`user_id64`
                        WHERE ml.`mod_active` = 1 AND `mod_id` = ?
                        LIMIT 0,1;',
                'i',
                $modID
            );

            $modGUID = $modDetails[0]['mod_identifier'];
            $modListID = $modDetails[0]['mod_id'];

            if (!empty($modDetails) && !empty($modGUID)) {
                echo '<h2>' . $modDetails[0]['mod_name'] . '</h2>';
                echo '<p><a class="nav-clickable" href="#d2mods__directory">Back to Mod Directory</a></p>';

                echo '<hr />';

                ////////////////////////////////////////////////////////

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

                $sg = !empty($modDetails[0]['mod_steam_group'])
                    ? '<a href="http://steamcommunity.com/groups/' . $modDetails[0]['mod_steam_group'] . '" target="_new">SG</a>'
                    : 'SG';

                $wg = !empty($modDetails[0]['mod_workshop_link'])
                    ? '<a href="http://steamcommunity.com/sharedfiles/filedetails/?id=' . $modDetails[0]['mod_workshop_link'] . '" target="_new">WS</a>'
                    : 'WS';

                $dataRange = !empty($modRange[0]['date_start'])
                    ? relative_time($modRange[0]['date_start']) . ' - ' . relative_time($modRange[0]['date_end'])
                    : 'No data';

                $developerName = !empty($modDetails[0]['user_name'])
                    ? $modDetails[0]['user_name']
                    : 'Hasn\'t logged in';

                $developerAvatar = !empty($modDetails[0]['user_avatar'])
                    ? '<img width="20" height="20" src="' . $modDetails[0]['user_avatar'] . '"/> '
                    : '';

                $developerCombined = $developerAvatar . $developerName;

                $modID_row = '';
                if (!empty($_SESSION['user_id64']) && !empty($_SESSION['isAdmin'])) {
                    $modID_row = '  <tr>
                                        <th>Mod ID</th>
                                        <td>' . $modGUID . '</td>
                                    </tr>';
                }

                if (!empty($modDetails[0]['mod_maps'])) {
                    $modMaps = implode(", ", json_decode($modDetails[0]['mod_maps'], 1));
                }
                else{
                    $modMaps = 'unknown';
                }

                echo '<div class="container">
                        <div class="col-sm-7">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    ' . $modID_row . '
                                    <tr>
                                        <th>Last Week</th>
                                        <td>' . number_format($modDetails[0]['games_last_week']) . ' games played</td>
                                    </tr>
                                    <tr>
                                        <th>All Time</th>
                                        <td>' . number_format($modDetails[0]['games_all_time']) . ' games played</td>
                                    </tr>
                                    <tr>
                                        <th>Failed</th>
                                        <td>' . number_format($modDetails[0]['games_failed']) . ' games failed to load</td>
                                    </tr>
                                    <tr>
                                        <th>Gameplay</th>
                                        <td>' . number_format($modDetails[0]['game_duration'] / 60) . ' mins</td>
                                    </tr>
                                    <tr>
                                        <th>Data Range</th>
                                        <td>' . $dataRange . '</td>
                                    </tr>
                                    <tr>
                                        <th>Developer</th>
                                        <td>' . $developerCombined . '</td>
                                    </tr>
                                    <tr>
                                        <th>Maps</th>
                                        <td>' . $modMaps . '</td>
                                    </tr>
                                    <tr>
                                        <th>Links <span class="glyphicon glyphicon-question-sign" title="Steam workshop / Steam group"></span></th>
                                        <td>' . $wg . ' || ' . $sg . '</td>
                                    </tr>
                                    <tr>
                                        <th>Description</th>
                                        <td>' . $modDetails[0]['mod_description'] . '</td>
                                    </tr>
                                    <tr>
                                        <th colspan="2" class="text-center">Added ' . relative_time($modDetails[0]['date_recorded']) . '</th>
                                    </tr>
                                </table>
                            </div>
                        </div>
                      </div>';

                echo '<hr />';

                ////////////////////////////////////////////////////////

                if (!empty($modRange[0]['date_end'])) {

                    //////////////////////
                    // GAMES PER DAY
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

                        $modFailStats_numuniques = $db->q(
                            'SELECT
                                DAY(mmp.`date_recorded`) as day,
                                MONTH(mmp.`date_recorded`) as month,
                                YEAR(mmp.`date_recorded`) as year,
                                COUNT(DISTINCT mmp.`player_sid32`) as num_players
                            FROM `mod_match_players` mmp
                            LEFT JOIN `mod_list` ml ON ml.mod_identifier = mmp.mod_id
                            WHERE ml.`mod_id` = ?
                            GROUP BY 3,2,1
                            ORDER BY 3,2,1',
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

                        foreach ($modFailStats_numuniques as $key => $value) {
                            $modDate = $value['day'] . '-' . $value['month'] . '-' . $value['year'];

                            $testArray[$modDate]['num_uniques'] = $value['num_players'];
                        }

                        /*echo '<pre>';
                        print_r($testArray);
                        echo '</pre>';
                        //exit();*/


                        $options = array(
                            //'title' => 'Average spins in ' . $hits . ' attacks',
                            //'theme' => 'maximized',
                            'bar' => array(
                                'groupWidth' => 8,
                            ),
                            'height' => 300,
                            'chartArea' => array(
                                'width' => '80%',
                                'height' => '75%',
                                'left' => 80,
                                'top' => 10,
                            ),
                            'hAxis' => array(
                                'textPosition' => 'none',
                                'direction' => -1,
                                //'title' => 'Date',
                                //'maxAlternation' => 1,
                                //'textPosition' => 'none',
                                //'textPosition' => 'in',
                                //'viewWindowMode' => 'maximized'
                                //'slantedText' => 1,
                                //'slantedTextAngle' => 60,
                            ),
                            'vAxes' => array(
                                0 => array(
                                    'title' => 'Num. of Games',
                                    //'textPosition' => 'in',
                                    //'logScale' => 1,
                                ),
                                1 => array(
                                    'title' => 'Failure Rate (%)',
                                    'textPosition' => 'out',
                                    //'textPosition' => 'in',
                                    //'logScale' => 1,
                                ),
                                2 => array(
                                    'title' => 'Unique Players',
                                    'textPosition' => 'in',
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
                                0 => array(
                                    'type' => 'bar',
                                    'targetAxisIndex' => 0,
                                ),
                                1 => array(
                                    'type' => 'line',
                                    'targetAxisIndex' => 1,
                                ),
                                2 => array(
                                    'type' => 'line',
                                    'targetAxisIndex' => 2,
                                ),
                            ),
                            'tooltip' => array( //'isHtml' => 1,
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

                            $numActualGames = !empty($value2['num_games'])
                                ? $value2['num_games'] - $numFails
                                : 0;

                            if (!empty($value2['num_fails'])) {
                                $gamesPercentageSuccess = !empty($value2['num_games'])
                                    ? $value2['num_fails'] / $value2['num_games'] * 100
                                    : 100;
                            } else {
                                $gamesPercentageSuccess = 0;
                            }

                            $numUniquePlayers = !empty($value2['num_uniques'])
                                ? $value2['num_uniques']
                                : 0;

                            $super_array[] = array('c' => array(
                                array('v' => $key2),
                                array('v' => $numActualGames),
                                array('v' => number_format($numActualGames)),
                                array('v' => $gamesPercentageSuccess),
                                array('v' => number_format($gamesPercentageSuccess) . '%'),
                                array('v' => $numUniquePlayers),
                                array('v' => number_format($numUniquePlayers)),
                            ));
                        }

                        $data = array(
                            'cols' => array(
                                array('id' => '', 'label' => 'Date', 'type' => 'string'),
                                array('id' => '', 'label' => 'Actual Games', 'type' => 'number'),
                                array('id' => '', 'label' => 'Tooltip', 'type' => 'string', 'role' => 'tooltip', 'p' => array('html' => 1)),
                                array('id' => '', 'label' => 'Failure Rate (%)', 'type' => 'number'),
                                array('id' => '', 'label' => 'Tooltip', 'type' => 'string', 'role' => 'tooltip', 'p' => array('html' => 1)),
                                array('id' => '', 'label' => 'Unique Players', 'type' => 'number'),
                                array('id' => '', 'label' => 'Tooltip', 'type' => 'string', 'role' => 'tooltip', 'p' => array('html' => 1)),
                            ),
                            'rows' => $super_array
                        );

                        $chart_width = max(count($super_array) * 10, 700);
                        $options['width'] = $chart_width;
                        $options['hAxis']['gridlines']['count'] = count($super_array);

                        echo '<h3>Games Played per Day</h3>';
                        echo '<div id="breakdown_num_games_per_day" class="d2mods-graph d2mods-graph-overflow"></div>';

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

                        if (!empty($modStats)) {

                            $testArray = array();
                            $lastNum = 0; //NEED TO BE NEGATIVE TO GRAPH 0 TOO

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
                            }

                            /*echo '<pre>';
                            print_r($testArray);
                            echo '</pre>';
                            //exit();*/

                            $options = array(
                                'bar' => array(
                                    'groupWidth' => 7,
                                ),
                                'height' => 300,
                                'chartArea' => array(
                                    'width' => '100%',
                                    'height' => '55%',
                                    'left' => 80,
                                    //'bottom' => 20,
                                    //'top' => 10,
                                ),
                                'hAxis' => array(
                                    'title' => 'Duration',
                                    'slantedText' => 1,
                                    'slantedTextAngle' => 60,
                                ),
                                'vAxis' => array(
                                    'title' => 'Games',
                                    'scaleType' => 'mirrorLog',
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
                                $super_array[] = array('c' => array(array('v' => $key2), array('v' => $value2), array('v' => '<div class="d2mods-graph-tooltips"><strong>' . $key2 . '</strong> mins<br />Games: <strong>' . number_format($value2) . '</strong><br />(' . number_format(100 * $value2 / array_sum($testArray), 2) . '%)</div>')));
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
                            echo '<div id="duration_breakdown" class="d2mods-graph"></div>';

                            $chart->load(json_encode($data));
                            echo $chart->draw('duration_breakdown', $options);
                        } else {
                            echo '<h3>Games Played per Duration</h3>';
                            echo 'No available stats!';
                        }
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

                        if (!empty($modStats)) {

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
                                'bar' => array(
                                    'groupWidth' => 10,
                                ),
                                'height' => 300,
                                'chartArea' => array(
                                    'width' => '100%',
                                    'height' => '80%',
                                    'left' => 80,
                                    //'top' => 10,
                                ),
                                'hAxis' => array(
                                    'title' => 'Number of Players',
                                ),
                                'vAxis' => array(
                                    'title' => 'Games',
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
                                $super_array[] = array('c' => array(array('v' => $key2), array('v' => $value2), array('v' => '<div class="d2mods-graph-tooltips"><strong>' . $key2 . '</strong> players<br />Games: <strong>' . number_format($value2) . '</strong><br />(' . number_format(100 * $value2 / array_sum($testArray), 2) . '%)</div>')));
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

                            echo '<h3>Players per Game <span class="glyphicon glyphicon-question-sign" title="Includes failed games"></span></h3>';
                            echo '<div id="breakdown_num_players" class="d2mods-graph"></div>';

                            $chart->load(json_encode($data));
                            echo $chart->draw('breakdown_num_players', $options);
                        } else {
                            echo '<h3>Players per Game</h3>';
                            echo 'No player stats!';
                        }
                    }

                    echo '<hr />';

                    //////////////////////
                    // NUM HEROES USED
                    //////////////////////

                    {
                        $modStats = $db->q(
                            'SELECT ' .
                            //cmh.`player_hero_id`,
                            'gh.`localized_name`,
                            cmh.`numPicks`
                          FROM `cron_mod_heroes` cmh
                          LEFT JOIN `mod_list` ml ON ml.`mod_identifier` = cmh.`mod_id`
                          LEFT JOIN `game_heroes` gh ON cmh.`player_hero_id` = gh.`hero_id`
                          WHERE ml.`mod_id` = ?
                          ORDER BY 2 DESC;',
                            'i',
                            $modID
                        );

                        if (!empty($modStats)) {

                            $testArray = array();

                            foreach ($modStats as $key => $value) {
                                $testArray[$value['localized_name']] = $value['numPicks'];
                            }

                            /*echo '<pre>';
                            print_r($testArray);
                            echo '</pre>';
                            //exit();*/


                            $options = array(
                                'height' => 400,
                                'chartArea' => array(
                                    'width' => '100%',
                                    'height' => '80%',
                                ),
                                'hAxis' => array(
                                    'title' => 'Number of Players',
                                ),
                                'vAxis' => array(
                                    'title' => 'Games',
                                ),
                                //'pieSliceText' => 'label',
                                'pieResidueSliceLabel' => 'Other',
                                'sliceVisibilityThreshold' => 1 / 270, //minimum degrees to be rendered
                                'legend' => array(
                                    'position' => 'top',
                                    'maxLines' => 2,
                                ),
                                'seriesType' => "bars",
                                'tooltip' => array(
                                    'isHtml' => 1,
                                ),
                            );

                            $chart = new chart2('PieChart');

                            $super_array = array();
                            foreach ($testArray as $key2 => $value2) {
                                $super_array[] = array('c' => array(array('v' => $key2), array('v' => $value2), array('v' => '<div class="d2mods-graph-tooltips"><strong>' . $key2 . '</strong> players<br />Games: <strong>' . number_format($value2) . '</strong><br />(' . number_format(100 * $value2 / array_sum($testArray), 2) . '%)</div>')));
                            }

                            $data = array(
                                'cols' => array(
                                    array('id' => '', 'label' => 'Hero', 'type' => 'string'),
                                    array('id' => '', 'label' => 'Games', 'type' => 'number'),
                                    array('id' => '', 'label' => 'Tooltip', 'type' => 'string', 'role' => 'tooltip', 'p' => array('html' => 1)),
                                ),
                                'rows' => $super_array
                            );

                            $chart_width = max(count($super_array) * 9, 700);
                            $options['width'] = $chart_width;

                            echo '<h3>Heroes Picked <span class="glyphicon glyphicon-question-sign" title="Includes failed games... Updated daily"></span></h3>';
                            echo '<div id="breakdown_heroes_picked" class="d2mods-graph"></div>';

                            $chart->load(json_encode($data));
                            echo $chart->draw('breakdown_heroes_picked', $options);
                        } else {
                            echo '<h3>Heroes Picked</h3>';
                            echo 'No hero stats!';
                        }
                    }

                    echo '<hr />';

                    //////////////////////
                    // RECENT GAMES
                    //////////////////////

                    {
                        $recentGames = $db->q(
                            'SELECT
                              `match_id`,
                              `match_duration`,
                              `match_num_players`,
                              `match_recorded`
                            FROM `mod_match_overview` mmo
                            WHERE mmo.`mod_id` = ?
                            ORDER BY `match_recorded` DESC
                            LIMIT 0,20;',
                            's',
                            $modGUID
                        );

                        if (!empty($recentGames)) {
                            echo '<h3>Recent Matches <small><a class="nav-clickable" href="#d2mods__recent_games?f=' . $modListID . '">more</a></small></h3>';

                            echo '<div class="table-responsive">
		                        <table class="table table-striped table-hover">';
                            echo '
                                <tr>
                                    <th class="col-ld-7">Match ID</th>
                                    <th class="col-ld-1 text-center">Loaded <span class="glyphicon glyphicon-question-sign" title="Whether the game managed to successfully start"></span></th>
                                    <th class="col-ld-1 text-center">Duration</th>
                                    <th class="col-ld-1 text-center">Players</th>
                                    <th class="col-ld-2 text-center">Recorded</th>
                                </tr>';

                            foreach ($recentGames as $key => $value) {
                                $matchID = !empty($value['match_id'])
                                    ? $value['match_id']
                                    : 'Unknown';

                                $matchDuration = !empty($value['match_duration'])
                                    ? number_format($value['match_duration'] / 60)
                                    : 'Unknown';

                                $matchLoaded = !empty($value['match_duration']) && $value['match_duration'] > 130
                                    ? '<span class="label-success label"><span class="glyphicon glyphicon-ok"></span></span>'
                                    : '<span class="label-danger label"><span class="glyphicon glyphicon-remove"></span></span>';

                                $numPlayers = !empty($value['match_num_players'])
                                    ? $value['match_num_players']
                                    : 'Unknown';

                                $matchDate = !empty($value['match_recorded'])
                                    ? relative_time($value['match_recorded'])
                                    : 'Unknown';

                                echo '
                                    <tr>
                                        <td><a class="nav-clickable" href="#d2mods__match?id=' . $matchID . '">' . $matchID . '</a></td>
                                        <td class="text-center">' . $matchLoaded . '</td>
                                        <td class="text-right">' . $matchDuration . ' mins</td>
                                        <td class="text-center">' . $numPlayers . '</td>
                                        <td class="text-right">' . $matchDate . '</td>
                                    </tr>';
                            }

                            echo '</table></div>';
                        } else {
                            echo '<h3>Recent Matches</h3>';
                            echo 'No recent matches!';
                        }
                    }

                    echo '<hr />';

                }
            } else {
                echo bootstrapMessage('Oh Snap', 'No mods with that modID!', 'danger');
            }
        } else {
            echo bootstrapMessage('Oh Snap', 'Invalid modID!', 'danger');
        }
    } else {
        echo bootstrapMessage('Oh Snap', 'No DB!', 'danger');
    }

    echo '<p>
            <div class="text-center">
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__directory">Mod Directory</a>
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__recent_games">Recent Games</a>
            </div>
        </p>';

    echo '<div id="pagerendertime" class="pagerendertime">';
    echo '<hr />Page generated in ' . (time() - $start) . 'secs';
    echo '</div>';

    $memcache->close();
} catch (Exception $e) {
    $message = 'Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage();
    echo bootstrapMessage('Oh Snap', $message, 'danger');
}