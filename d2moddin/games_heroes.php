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


        $mod_name = $db->escape($_GET['m']);

        echo '<h2>Heroes Picked per Mod</h2>';
        echo '<h4><a class="nav-clickable" href="#d2moddin__games_mods">Back to Mod List</a></h4>';

        if (!empty($_GET['m'])) {

            ////////////////////////////////////////////////////////
            // LAST WEEK PIE
            ////////////////////////////////////////////////////////

            if (1) {
                echo '<h3>Top 10 Picked Heroes</h3>';

                $mod_stats_pie = simple_cached_query('d2moddin_games_mods_pie' . $mod_name,
                    'SELECT smh.`mod_name`, smh.`hero_id`, gh.`localized_name` as hero_name, smh.`picked`, smh.`wins` FROM `stats_mods_heroes` smh LEFT JOIN `game_heroes` gh ON smh.`hero_id` = gh.`hero_id` WHERE smh.`mod_name` = \'' . $mod_name . '\' ORDER BY smh.`picked` DESC LIMIT 0,10;',
                    60);

                if (!empty($mod_stats_pie)) {
                    $chart = new chart2('PieChart');

                    $super_array = array();
                    foreach ($mod_stats_pie as $key => $value) {
                        $super_array[] = array('c' => array(array('v' => $value['hero_name']), array('v' => $value['picked'])));
                    }

                    $data = array(
                        'cols' => array(
                            array('id' => '', 'label' => 'Heroes', 'type' => 'string'),
                            array('id' => '', 'label' => 'Games', 'type' => 'number'),
                        ),
                        'rows' => $super_array
                    );

                    $options = array(
                        'width' => 800,
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
                            ),
                            'maxLines' => 2
                        ),
                        'is3D' => 'true'
                    );

                    echo '<div id="lobby_count_pie" style="width: 800px;"></div>';

                    $chart->load(json_encode($data));
                    echo $chart->draw('lobby_count_pie', $options);
                } else {
                    echo 'No hero data for pie<br />';
                }
            }

            ////////////////////////////////////////////////////////
            // ALL TIME
            ////////////////////////////////////////////////////////

            if (1) {
                $chart = new chart2('ComboChart');

                //////////////////////////
                // SQL Ordering
                //////////////////////////

                if (1) {
                    $orderingClause = array();

                    if (isset($_GET['p']) && $_GET['p'] == 0) {
                        $orderingClause[] = 'smh.`picked` DESC';
                    } else if (isset($_GET['p']) && $_GET['p'] == 1) {
                        $orderingClause[] = 'smh.`picked` ASC';
                    }

                    if (isset($_GET['w']) && $_GET['w'] == 0) {
                        $orderingClause[] = 'smh.`wins` DESC';
                    } else if (isset($_GET['w']) && $_GET['w'] == 1) {
                        $orderingClause[] = 'smh.`wins` ASC';
                    }

                    if (isset($_GET['wr']) && $_GET['wr'] == 0) {
                        $orderingClause[] = 'win_rate DESC';
                    } else if (isset($_GET['wr']) && $_GET['wr'] == 1) {
                        $orderingClause[] = 'win_rate ASC';
                    }

                    if (empty($orderingClause)) {
                        $orderingClause[] = 'smh.`picked` DESC';
                    }

                    $orderingClause = implode(', ', $orderingClause);
                }

                $mod_stats = $db->q('SELECT smh.`mod_name`, smh.`hero_id`, gh.`localized_name` as hero_name, smh.`picked`, smh.`wins`, ROUND(smh.`wins` / smh.`picked` * 100, 2) as win_rate FROM `stats_mods_heroes` smh LEFT JOIN `game_heroes` gh ON smh.`hero_id` = gh.`hero_id` WHERE smh.`mod_name` = ? ORDER BY ' . $orderingClause . ';',
                    's',
                    $mod_name);
                $mod_range = simple_cached_query('d2moddin_games_mods_range_alltime',
                    'SELECT MIN(`match_ended`) as min_date, MAX(`match_ended`) as max_date FROM `match_stats`;',
                    60);


                //////////////////////////////////////////////
                // DO FANCY ARROWS ON TABLE
                //////////////////////////////////////////////
                if (1) {
                    $extra_m = !empty($mod_name)
                        ? '&m=' . $mod_name
                        : '';

                    //////////////////////////////////////////////

                    $extra = '';
                    $extra .= isset($_GET['w']) && ($_GET['w'] == 1 || $_GET['w'] == 0)
                        ? '&w=' . $_GET['w']
                        : '';
                    $extra .= isset($_GET['wr']) && ($_GET['wr'] == 1 || $_GET['wr'] == 0)
                        ? '&wr=' . $_GET['wr']
                        : '';
                    $extra .= $extra_m;

                    if (isset($_GET['p'])) {
                        $arrow_p = $_GET['p'] == 1
                            ? '<span class="glyphicon glyphicon-arrow-up"></span> <a class="nav-clickable" href="#d2moddin__games_heroes?p=0' . $extra . '"><span class="glyphicon glyphicon-arrow-down"></span></a>'
                            : '<a class="nav-clickable" href="#d2moddin__games_heroes?p=1' . $extra . '"><span class="glyphicon glyphicon-arrow-up"></span></a> <span class="glyphicon glyphicon-arrow-down"></span>';
                    } else {
                        $arrow_p = '<a class="nav-clickable" href="#d2moddin__games_heroes?p=1' . $extra . '"><span class="glyphicon glyphicon-arrow-up"></span></a> <a class="nav-clickable" href="#d2moddin__games_heroes?p=0' . $extra . '"><span class="glyphicon glyphicon-arrow-down"></span></a>';
                    }

                    //////////////////////////////////////////////

                    $extra = '';
                    $extra .= isset($_GET['p']) && ($_GET['p'] == 1 || $_GET['p'] == 0)
                        ? '&p=' . $_GET['p']
                        : '';
                    $extra .= isset($_GET['wr']) && ($_GET['wr'] == 1 || $_GET['wr'] == 0)
                        ? '&wr=' . $_GET['wr']
                        : '';
                    $extra .= $extra_m;

                    if (isset($_GET['w'])) {
                        $arrow_w = $_GET['w'] == 1
                            ? '<span class="glyphicon glyphicon-arrow-up"></span> <a class="nav-clickable" href="#d2moddin__games_heroes?w=0' . $extra . '"><span class="glyphicon glyphicon-arrow-down"></span></a>'
                            : '<a class="nav-clickable" href="#d2moddin__games_heroes?w=1' . $extra . '"><span class="glyphicon glyphicon-arrow-up"></span></a> <span class="glyphicon glyphicon-arrow-down"></span>';
                    } else {
                        $arrow_w = '<a class="nav-clickable" href="#d2moddin__games_heroes?w=1' . $extra . '"><span class="glyphicon glyphicon-arrow-up"></span></a> <a class="nav-clickable" href="#d2moddin__games_heroes?w=0' . $extra . '"><span class="glyphicon glyphicon-arrow-down"></span></a>';
                    }

                    //////////////////////////////////////////////

                    $extra = '';
                    $extra .= isset($_GET['p']) && ($_GET['p'] == 1 || $_GET['p'] == 0)
                        ? '&p=' . $_GET['p']
                        : '';
                    $extra .= isset($_GET['w']) && ($_GET['w'] == 1 || $_GET['w'] == 0)
                        ? '&w=' . $_GET['w']
                        : '';
                    $extra .= $extra_m;

                    if (isset($_GET['wr'])) {
                        $arrow_wr = $_GET['wr'] == 1
                            ? '<span class="glyphicon glyphicon-arrow-up"></span> <a class="nav-clickable" href="#d2moddin__games_heroes?wr=0' . $extra . '"><span class="glyphicon glyphicon-arrow-down"></span></a>'
                            : '<a class="nav-clickable" href="#d2moddin__games_heroes?wr=1' . $extra . '"><span class="glyphicon glyphicon-arrow-up"></span></a> <span class="glyphicon glyphicon-arrow-down"></span>';
                    } else {
                        $arrow_wr = '<a class="nav-clickable" href="#d2moddin__games_heroes?wr=1' . $extra . '"><span class="glyphicon glyphicon-arrow-up"></span></a> <a class="nav-clickable" href="#d2moddin__games_heroes?wr=0' . $extra . '"><span class="glyphicon glyphicon-arrow-down"></span></a>';
                    }
                }
                //////////////////////////////////////////////

                echo '<div style="width: 800px;"><h4 class="text-center">' . relative_time($mod_range[0]['max_date']) . ' --> ' . relative_time($mod_range[0]['min_date']) . '</h4></div>';

                echo '<div class="table-responsive">
		        <table class="table table-striped table-hover">';
                echo '<tr>
                        <th width="50">&nbsp;</th>
                        <th>Hero</th>
                        <th>Picked ' . $arrow_p . '</th>
                        <th>Wins ' . $arrow_w . '</th>
                        <th>Win Rate ' . $arrow_wr . '</th>
                        <th width="20"><a class="nav-clickable" href="#d2moddin__games_heroes?' . $extra_m . '"><span class="glyphicon glyphicon-remove-circle"></span></a></th>
                    </tr>';
                foreach ($mod_stats as $key => $value) {
                    if (empty($value['hero_name'])) {
                        $value['hero_name'] = 'No hero';
                        $img = './images/heroes/aaa_blank.png';
                    } else {
                        $img = './images/heroes/' . str_replace('\'', '', str_replace(' ', '-', strtolower($value['hero_name']))) . '.png';
                    }

                    echo '<tr>';
                    echo '<td><img width="45" height="25" src="' . $img . '" /></td>';
                    echo '<td>' . $value['hero_name'] . '</td>';
                    echo '<td>' . number_format($value['picked'], 0) . '</td>';
                    echo '<td>' . number_format($value['wins'], 0) . '</td>';
                    echo '<td>' . number_format($value['win_rate'], 0) . '%</td>';
                    echo '<td>&nbsp;</td>';
                    echo '</tr>';
                }
                echo '</table></div>';

            }
        } else {
            echo 'No data for this mod.';
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