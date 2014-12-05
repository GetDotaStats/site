<?php
require_once('../global_functions.php');
require_once('../connections/parameters.php');

try {
    $db = new dbWrapper_v2($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site);

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    if ($db) {
        $SQLpage = !empty($_GET['p']) && is_numeric($_GET['p'])
            ? $_GET['p'] - 1
            : 0;

        $resultsPerPage = 25;
        $SQLpageTemp = $SQLpage * $resultsPerPage;

        $modListActive = simple_cached_query('d2mods_recent_games_p' . $SQLpage,
            'SELECT
                    mmo.`match_id`,
                    mmo.`mod_id`,
                    mmo.`match_duration`,
                    mmo.`match_num_players`,
                    mmo.`match_recorded`,
                    ml.`mod_id` as modFakeID,
                    ml.`mod_name`,
                    ml.`mod_active`
                FROM `mod_match_overview` mmo
                LEFT JOIN `mod_list` ml ON mmo.`mod_id` = ml.`mod_identifier`
                ORDER BY mmo.`match_recorded` DESC
                LIMIT ' . $SQLpageTemp . ',' . $resultsPerPage . ';'
            , 30
        );

        $modListCount = simple_cached_query('d2mods_recent_games_count',
            'SELECT count(*) AS total_games
                FROM `mod_match_overview` mmo
                LEFT JOIN `mod_list` ml ON mmo.`mod_id` = ml.`mod_identifier`
                ORDER BY mmo.`match_recorded` DESC;'
            , 30
        );

        $modListCount = !empty($modListCount)
            ? $modListCount[0]['total_games']
            : 0;

        $pages = ceil($modListCount / $resultsPerPage);

        echo '<div class="page-header"><h2>Recent Games <small>BETA</small></h2></div>';

        echo '<p>This is a directory of the last ' . $resultsPerPage . ' games played that developers have implemented stats for.</p>';

        if (!empty($modListActive)) {

            echo '<div class="table-responsive">
		        <table class="table table-striped table-hover">';
            echo '
                <tr>
                    <th>Mod</th>
                    <th>Match ID</th>
                    <th>Duration</th>
                    <th>Players</th>
                    <th>Recorded</th>
                </tr>';

            foreach ($modListActive as $key => $value) {
                $modName = !empty($value['mod_name'])
                    ? $value['mod_name']
                    : 'Unknown';

                $matchID = !empty($value['match_id'])
                    ? $value['match_id']
                    : 'Unknown';

                $matchDuration = !empty($value['match_duration'])
                    ? number_format($value['match_duration'] / 60)
                    : 'Unknown';

                $numPlayers = !empty($value['match_num_players'])
                    ? $value['match_num_players']
                    : 'Unknown';

                $matchDate = !empty($value['match_recorded'])
                    ? relative_time($value['match_recorded'])
                    : 'Unknown';

                echo '
                    <tr>
                        <td><a class="nav-clickable" href="#d2mods__stats?id=' . $value['modFakeID'] . '">' . $modName . '</a></td>
                        <td><a class="nav-clickable" href="#d2mods__match?id=' . $matchID . '">' . $matchID . '</a></td>
                        <td>' . $matchDuration . ' mins</td>
                        <td>' . $numPlayers . '</td>
                        <td>' . $matchDate . '</td>
                    </tr>';
            }

            echo '</table></div>';

            $pagination = '';
            if ($pages > 1) {
                $pagination .= '<nav class="text-center"><ul class="pagination pagination-md">';
                //$pagination .= '<li><a href="#"><span aria-hidden="true">&laquo;</span><span class="sr-only">Previous</span></a></li>';

                $pagination_cap = 4;

                if ($pages > 24) {
                    for ($i = 1; $i < $pages && $i <= $pagination_cap; $i++) {
                        $liClass = $i == ($SQLpage + 1)
                            ? " class='active'"
                            : NULL;
                        $pagination .= '<li ' . $liClass . '><a class="nav-clickable" href="#d2mods__recent_games?p=' . $i . '" id="' . $i . '-page">' . $i . '</a></li>';
                    }

                    if($SQLpage > ($pagination_cap - 1)){
                        $pagination .= '<li class="disabled"><span>...</span></li>';

                        for ($i = ($SQLpage); $i < $pages && $i <= ($SQLpage + $pagination_cap); $i++) {
                            $liClass = $i == ($SQLpage + 1)
                                ? " class='active'"
                                : NULL;
                            $pagination .= '<li ' . $liClass . '><a class="nav-clickable" href="#d2mods__recent_games?p=' . $i . '" id="' . $i . '-page">' . $i . '</a></li>';
                        }
                    }
                    else if($SQLpage == ($pagination_cap - 1)){
                        for ($i = ($SQLpage + 2); $i < $pages && $i <= ($SQLpage + $pagination_cap); $i++) {
                            $liClass = $i == ($SQLpage + 1)
                                ? " class='active'"
                                : NULL;
                            $pagination .= '<li ' . $liClass . '><a class="nav-clickable" href="#d2mods__recent_games?p=' . $i . '" id="' . $i . '-page">' . $i . '</a></li>';
                        }
                    }

                    $pagination .= '<li class="disabled"><span>...</span></li>';

                    $bottom_upper_portion = $pages - $pagination_cap;
                    for ($i = $bottom_upper_portion; $i < $pages; $i++) {
                        $liClass = $i == ($SQLpage + 1)
                            ? " class='active'"
                            : NULL;
                        $pagination .= '<li ' . $liClass . '><a class="nav-clickable" href="#d2mods__recent_games?p=' . $i . '" id="' . $i . '-page">' . $i . '</a></li>';
                    }
                } else {
                    for ($i = 1; $i < $pages; $i++) {
                        $liClass = $i == ($SQLpage + 1)
                            ? " class='active'"
                            : NULL;
                        $pagination .= '<li ' . $liClass . '><a class="nav-clickable" href="#d2mods__recent_games?p=' . $i . '" id="' . $i . '-page">' . $i . '</a></li>';
                    }
                }

                //$pagination .= '<li><a href="#"><span aria-hidden="true">&raquo;</span><span class="sr-only">Next</span></a></li>';
                $pagination .= '</ul></nav>';

                echo $pagination;
            }

        } else {
            echo bootstrapMessage('Oh Snap', 'No games played yet!');
        }
    }

    echo '<p>
            <div class="text-center">
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__directory">Mod Directory</a>
            </div>
        </p>';

    $memcache->close();
} catch (Exception $e) {
    echo '<div class="page-header"><div class="alert alert-danger" role="alert"><strong>Oh Snap:</strong> Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage() . '</div></div>';
}