<?php
require_once('./functions.php');
require_once('../global_functions.php');
require_once('../connections/parameters.php');

try {
    $db = new dbWrapper_v2($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site);
    $db->q('SET NAMES utf8;');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    if ($db) {
        echo '<div class="page-header"><h2>Lobby Creation Trends</h2></div>';

        echo '<p>This graph captures how many lobbies have been created over time. It is read right to left, with the data on the farthest left being the most recent.</p>';

        $lobbiesMadeSQL = simple_cached_query(
            'd2mods_lobby_graph',
            'SELECT
                DAY(ll.`date_recorded`) as day,
                MONTH(ll.`date_recorded`) as month,
                YEAR(ll.`date_recorded`) as year,
                COUNT(*) as num_lobbies
            FROM `lobby_list` ll
            GROUP BY 3,2,1
            ORDER BY 3,2,1;',
            1 * 60
        );

        //LOBBIES MADE
        {
            if (!empty($lobbiesMadeSQL)) {
                $testArray = array();
                foreach ($lobbiesMadeSQL as $key => $value) {
                    $modDate = $value['day'] . '-' . $value['month'] . '-' . $value['year'];

                    $testArray[$modDate]['num_lobbies'] = $value['num_lobbies'];
                }

                $options = array(
                    'bar' => array(
                        'groupWidth' => 8,
                    ),
                    'height' => 300,
                    'chartArea' => array(
                        'width' => '100%',
                        'height' => '85%',
                        'left' => 80,
                        'top' => 10,
                    ),
                    'hAxis' => array(
                        'textPosition' => 'none',
                        'direction' => -1,
                        //'title' => 'Date',
                    ),
                    'vAxes' => array(
                        0 => array(
                            'title' => 'Num. of Lobbies',
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
                        0 => array(
                            'type' => 'bar',
                            'targetAxisIndex' => 0,
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
                    $numActualLobbies = !empty($value2['num_lobbies'])
                        ? $value2['num_lobbies']
                        : 0;

                    $super_array[] = array('c' => array(
                        array('v' => $key2),
                        array('v' => $numActualLobbies),
                        array('v' => number_format($numActualLobbies)),
                    ));
                }

                $data = array(
                    'cols' => array(
                        array('id' => '', 'label' => 'Date', 'type' => 'string'),
                        array('id' => '', 'label' => 'Lobbies', 'type' => 'number'),
                        array('id' => '', 'label' => 'Tooltip', 'type' => 'string', 'role' => 'tooltip', 'p' => array('html' => 1)),
                    ),
                    'rows' => $super_array
                );

                $chart_width = max(count($super_array) * 10, 700);
                $options['width'] = $chart_width;
                $options['hAxis']['gridlines']['count'] = count($super_array);

                echo '<div id="breakdown_num_lobbies_per_day" class="d2mods-graph d2mods-graph-overflow"></div>';

                $chart->load(json_encode($data));
                echo $chart->draw('breakdown_num_lobbies_per_day', $options);
            } else {
                echo bootstrapMessage('Oh Snap', 'No lobby data!', 'danger');
            }
        }

        echo '<div class="page-header"><h2>Custom Games Played Trends</h2></div>';

        echo '<p>This graph captures how many custom games have been played over time. It is read right to left, with the data on the farthest left being the most recent.</p>';

        $gamesPlayedSQL = simple_cached_query(
            'd2mods_custom_games_played_graph',
            'SELECT
                DAY(mmo.`match_recorded`) as day,
                MONTH(mmo.`match_recorded`) as month,
                YEAR(mmo.`match_recorded`) as year,
                COUNT(*) as num_customs
            FROM `mod_match_overview` mmo
            GROUP BY 3,2,1
            ORDER BY 3,2,1;',
            1 * 60
        );

        //GAMES PLAYED
        {
            if (!empty($gamesPlayedSQL)) {
                $testArray = array();
                foreach ($gamesPlayedSQL as $key => $value) {
                    $modDate = $value['day'] . '-' . $value['month'] . '-' . $value['year'];

                    $testArray[$modDate]['num_customs'] = $value['num_customs'];
                }

                $options = array(
                    'bar' => array(
                        'groupWidth' => 8,
                    ),
                    'height' => 300,
                    'chartArea' => array(
                        'width' => '100%',
                        'height' => '85%',
                        'left' => 80,
                        'top' => 10,
                    ),
                    'hAxis' => array(
                        'textPosition' => 'none',
                        'direction' => -1,
                        //'title' => 'Date',
                    ),
                    'vAxes' => array(
                        0 => array(
                            'title' => 'Num. of Customs',
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
                        0 => array(
                            'type' => 'bar',
                            'targetAxisIndex' => 0,
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
                    $numActualCustoms = !empty($value2['num_customs'])
                        ? $value2['num_customs']
                        : 0;

                    $super_array[] = array('c' => array(
                        array('v' => $key2),
                        array('v' => $numActualCustoms),
                        array('v' => number_format($numActualCustoms)),
                    ));
                }

                $data = array(
                    'cols' => array(
                        array('id' => '', 'label' => 'Date', 'type' => 'string'),
                        array('id' => '', 'label' => 'Customs', 'type' => 'number'),
                        array('id' => '', 'label' => 'Tooltip', 'type' => 'string', 'role' => 'tooltip', 'p' => array('html' => 1)),
                    ),
                    'rows' => $super_array
                );

                $chart_width = max(count($super_array) * 10, 700);
                $options['width'] = $chart_width;
                $options['hAxis']['gridlines']['count'] = count($super_array);

                echo '<div id="breakdown_num_customs_per_day" class="d2mods-graph d2mods-graph-overflow"></div>';

                $chart->load(json_encode($data));
                echo $chart->draw('breakdown_num_customs_per_day', $options);
            } else {
                echo bootstrapMessage('Oh Snap', 'No custom games data!', 'danger');
            }
        }
    } else {
        echo bootstrapMessage('Oh Snap', 'No db!', 'danger');
    }

    echo '<p>
            <div class="text-center">
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__lobby_list">Lobby List</a>
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__recent_games">Recent Games</a>
           </div>
        </p>';
} catch (Exception $e) {
    $message = 'Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage();
    echo bootstrapMessage('Oh Snap', $message, 'danger');
}