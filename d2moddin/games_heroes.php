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

            {
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

            {
                $chart = new chart2('ComboChart');

                /*
                CREATE TABLE IF NOT EXISTS `stats_mods_heroes` (
                    `mod_name` varchar(255) NOT NULL,
                    `hero_id` int(255) NOT NULL,
                    `picked` bigint(21) NOT NULL DEFAULT '0',
                    `wins` bigint(21) NOT NULL DEFAULT '0',
                    PRIMARY KEY (`mod_name`,`hero_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1
                    SELECT
                        ms.`mod` AS mod_name,
                        mp.`hero_id`,
                        COUNT( mp.`hero_id` ) AS picked,
                        SUM(
                            CASE
                                WHEN `good_guys_win` = 1 AND `team_id` = 0 THEN 1
                                WHEN `good_guys_win` = 0 AND `team_id` = 1 THEN 1
                                ELSE 0
                            END) AS wins
                    FROM  `match_players` mp
                    LEFT JOIN  `match_stats` ms ON mp.`match_id` = ms.`match_id`
                    GROUP BY ms.`mod` , mp.`hero_id`
                    ORDER BY 1 , 2;
                 */

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

                if (empty($orderingClause)) {
                    $orderingClause[] = 'smh.`picked` DESC';
                }

                $orderingClause = implode(' , ', $orderingClause);

                $mod_stats = $db->q('SELECT smh.`mod_name`, smh.`hero_id`, gh.`localized_name` as hero_name, smh.`picked`, smh.`wins` FROM `stats_mods_heroes` smh LEFT JOIN `game_heroes` gh ON smh.`hero_id` = gh.`hero_id` WHERE smh.`mod_name` = ? ORDER BY ' . $orderingClause . ';',
                    's',
                    $mod_name);
                $mod_range = simple_cached_query('d2moddin_games_mods_range_alltime',
                    'SELECT MIN(`match_ended`) as min_date, MAX(`match_ended`) as max_date FROM `match_stats`;',
                    60);


                //////////////////////////////////////////////

                $extra_m = !empty($mod_name)
                    ? '&m=' . $mod_name
                    : '';

                //////////////////////////////////////////////

                $extra = isset($_GET['w']) && ($_GET['w'] == 1 || $_GET['w'] == 0)
                    ? '&w=' . $_GET['w']
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

                $extra = isset($_GET['p']) && ($_GET['p'] == 1 || $_GET['p'] == 0)
                    ? '&p=' . $_GET['p']
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

                echo '<div style="width: 800px;"><h4 class="text-center">' . relative_time($mod_range[0]['max_date']) . ' --> ' . relative_time($mod_range[0]['min_date']) . '</h4></div>';

                echo '<div class="table-responsive">
		        <table class="table table-striped table-hover">';
                echo '<tr>
                        <th>&nbsp;</th>
                        <th>Hero</th>
                        <th>Picked ' . $arrow_p . '</th>
                        <th>Win Rate ' . $arrow_w . '</th>
                    </tr>';
                foreach ($mod_stats as $key => $value) {
                    if (empty($value['hero_name'])) {
                        $value['hero_name'] = 'No hero';
                        $img = './images/heroes/aaa_blank.png';
                    } else {
                        $img = './images/heroes/' . str_replace('\'', '', str_replace(' ', '-', strtolower($value['hero_name']))) . '.png';
                    }

                    $winrate = number_format($value['wins'] / $value['picked'] * 100, 1) . '%';


                    echo '<tr>';
                    echo '<td width="50"><img width="45" src="' . $img . '" /></td>';
                    echo '<td>' . $value['hero_name'] . '</td>';
                    echo '<td>' . number_format($value['picked'], 0) . '</td>';
                    echo '<td>' . $winrate . '</td>';
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