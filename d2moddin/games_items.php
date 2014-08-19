<?php
require_once('./functions.php');
require_once('../global_functions.php');
require_once('../connections/parameters.php');

$start = time();

if (!isset($_SESSION)) {
    session_start();
}

try {
    if (isset($_GET['m'])) {
        $db = new dbWrapper($hostname_d2moddin, $username_d2moddin, $password_d2moddin, $database_d2moddin, true);
        if ($db) {
            $memcache = new Memcache;
            $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

            $mod_name = $db->escape($_GET['m']);

            echo '<h2>Items Picked per Mod</h2>';
            echo '<h4><a class="nav-clickable" href="#d2moddin__games_mods">Back to Mod List</a></h4>';

            if (!empty($_GET['m'])) {

                ////////////////////////////////////////////////////////
                // LAST WEEK PIE
                ////////////////////////////////////////////////////////

                if (1) {
                    echo '<h3>Top 10 Picked Items</h3>';

                    $mod_stats_pie = simple_cached_query('d2moddin_games_mods_items_pie' . $mod_name,
                        'SELECT smi.`item` as item_id, gi.`localized_name` as item_name, smi.`purchased` FROM `stats_mods_items` smi LEFT JOIN game_items gi ON smi.`item` = gi.`item_id` WHERE smi.`mod_name` = \'' . $mod_name . '\' ORDER BY smi.`purchased` DESC LIMIT 0,10;',
                        60);


                    if (!empty($mod_stats_pie)) {
                        $chart = new chart2('PieChart');

                        $super_array = array();
                        foreach ($mod_stats_pie as $key => $value) {
                            $super_array[] = array('c' => array(array('v' => $value['item_name']), array('v' => $value['purchased'])));
                        }

                        $data = array(
                            'cols' => array(
                                array('id' => '', 'label' => 'Item', 'type' => 'string'),
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

                    $orderingClause = '';
                    $orderingClauseMC = '';
                    if (1) {
                        $orderingClause = array();
                        $orderingClauseMC = array();

                        if (isset($_GET['p']) && $_GET['p'] == 0) {
                            $orderingClause[] = 'smi.`purchased` DESC';
                            $orderingClauseMC[] = 'p0';
                        } else if (isset($_GET['p']) && $_GET['p'] == 1) {
                            $orderingClause[] = 'smi.`purchased` ASC';
                            $orderingClauseMC[] = 'p1';
                        }

                        if (isset($_GET['i']) && $_GET['i'] == 0) {
                            $orderingClause[] = 'smi.`item` DESC';
                            $orderingClauseMC[] = 'i0';
                        } else if (isset($_GET['i']) && $_GET['i'] == 1) {
                            $orderingClause[] = 'smi.`item` ASC';
                            $orderingClauseMC[] = 'i1';
                        }

                        if (empty($orderingClause)) {
                            $orderingClause[] = 'smi.`purchased` DESC';
                            $orderingClauseMC[] = 'p0';
                        }

                        $orderingClause = implode(', ', $orderingClause);
                        $orderingClauseMC = implode(', ', $orderingClauseMC);
                    }

                    $mod_stats = simple_cached_query('d2moddin_games_mods_items_alltime' . $mod_name.$orderingClauseMC,
                        'SELECT smi.`mod_name`, smi.`item` as item_id, gi.`localized_name` as item_name, gi.`name` as item_npc_name, smi.`purchased` FROM `stats_mods_items` smi LEFT JOIN game_items gi ON smi.`item` = gi.`item_id` WHERE smi.`mod_name` = \'' . $mod_name . '\' ORDER BY ' . $orderingClause . ';',
                        60);
                    $mod_range = simple_cached_query('d2moddin_games_mods_items_range_alltime' . $mod_name,
                        'SELECT MIN(`match_ended`) as min_date, MAX(`match_ended`) as max_date FROM `match_stats` WHERE `mod` = \'' . $mod_name . '\';',
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
                        $extra .= isset($_GET['i']) && ($_GET['i'] == 1 || $_GET['i'] == 0)
                            ? '&i=' . $_GET['i']
                            : '';
                        $extra .= $extra_m;

                        if (isset($_GET['p'])) {
                            $arrow_p = $_GET['p'] == 1
                                ? '<span class="glyphicon glyphicon-arrow-up"></span> <a class="nav-clickable" href="#d2moddin__games_items?p=0' . $extra . '"><span class="glyphicon glyphicon-arrow-down"></span></a>'
                                : '<a class="nav-clickable" href="#d2moddin__games_items?p=1' . $extra . '"><span class="glyphicon glyphicon-arrow-up"></span></a> <span class="glyphicon glyphicon-arrow-down"></span>';
                        } else {
                            $arrow_p = '<a class="nav-clickable" href="#d2moddin__games_items?p=1' . $extra . '"><span class="glyphicon glyphicon-arrow-up"></span></a> <a class="nav-clickable" href="#d2moddin__games_items?p=0' . $extra . '"><span class="glyphicon glyphicon-arrow-down"></span></a>';
                        }

                        //////////////////////////////////////////////

                        $extra = '';
                        $extra .= isset($_GET['w']) && ($_GET['w'] == 1 || $_GET['w'] == 0)
                            ? '&w=' . $_GET['w']
                            : '';
                        $extra .= $extra_m;

                        if (isset($_GET['i'])) {
                            $arrow_i = $_GET['i'] == 1
                                ? '<span class="glyphicon glyphicon-arrow-up"></span> <a class="nav-clickable" href="#d2moddin__games_items?i=0' . $extra . '"><span class="glyphicon glyphicon-arrow-down"></span></a>'
                                : '<a class="nav-clickable" href="#d2moddin__games_items?i=1' . $extra . '"><span class="glyphicon glyphicon-arrow-up"></span></a> <span class="glyphicon glyphicon-arrow-down"></span>';
                        } else {
                            $arrow_i = '<a class="nav-clickable" href="#d2moddin__games_items?i=1' . $extra . '"><span class="glyphicon glyphicon-arrow-up"></span></a> <a class="nav-clickable" href="#d2moddin__games_items?i=0' . $extra . '"><span class="glyphicon glyphicon-arrow-down"></span></a>';
                        }
                    }
                    //////////////////////////////////////////////

                    echo '<div style="width: 800px;"><h4 class="text-center">' . relative_time($mod_range[0]['max_date']) . ' --> ' . relative_time($mod_range[0]['min_date']) . '</h4></div>';

                    echo '<div class="table-responsive">
		        <table class="table table-striped table-hover">';
                    echo '<tr>
                        <th width="50">&nbsp;</th>
                        <th>Item ' . $arrow_i . '</th>
                        <th>Purchased ' . $arrow_p . '</th>
                        <th width="20"><a class="nav-clickable" href="#d2moddin__games_items?' . $extra_m . '"><span class="glyphicon glyphicon-remove-circle"></span></a></th>
                    </tr>';

                    $staticContentDomain = '//static.getdotastats.com/';
                    $staticContentDomain = '//localhost/getdotastats/';
                    foreach ($mod_stats as $key => $value) {
                        if (empty($value['item_name']) && !empty($value['item_id'])) {
                            $value['item_name'] = 'Item: #' . $value['item_id'];
                            $img = $staticContentDomain . '/images/items/aaa_blank.png';
                        } else if (empty($value['item_name'])) {
                            $value['item_name'] = 'No item';
                            $img = $staticContentDomain . '/images/items/aaa_blank.png';
                        } else if(stristr($value['item_npc_name'],'recipe')){
                            $img = $staticContentDomain . '/images/items/recipe.png';
                        }
                        else {
                            $img = $staticContentDomain . '/images/items/' . str_replace('item_', '', $value['item_npc_name']) . '.png';
                        }

                        echo '<tr>';
                        echo '<td><img width="45" height="24" src="' . $img . '" /></td>';
                        echo '<td>' . $value['item_name'] . '</td>';
                        echo '<td>' . number_format($value['purchased'], 0) . '</td>';
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
            echo '<div class="page-header"><div class="alert alert-danger" role="alert"><strong>Oh Snap:</strong> No DB!</div></div>';
        }
    } else {
        echo '<div class="page-header"><div class="alert alert-danger" role="alert"><strong>Oh Snap:</strong> No mod selected!</div></div>';
        echo '<h4><a class="nav-clickable" href="#d2moddin__games_mods">Back to Mod List</a></h4>';
    }
} catch (Exception $e) {
    echo $e->getMessage();
}