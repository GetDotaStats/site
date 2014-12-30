<?php
require_once('../global_functions.php');
require_once('../connections/parameters.php');

try {
    $db = new dbWrapper_v2($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site);
    $db->q('SET NAMES utf8;');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    if ($db) {
        $SQLpage = !empty($_GET['p']) && is_numeric($_GET['p'])
            ? $_GET['p'] - 1
            : 0;

        $resultsPerPage = 25;
        $SQLpageTemp = $SQLpage * $resultsPerPage;

        $modIDFilter = !empty($_GET['f']) && is_numeric($_GET['f'])
            ? $_GET['f']
            : NULL;

        if (!empty($modIDFilter)) {
            $recentGameList = $memcache->get('d2mods_recent_games_p' . $SQLpage . '_f' . $modIDFilter);
            if (!$recentGameList) {
                $recentGameList = $db->q('SELECT
                            mmo.`match_id`,
                            mmo.`mod_id`,
                            mmo.`match_duration`,
                            mmo.`match_num_players`,
                            mmo.`match_recorded`,
                            ml.`mod_id` as modFakeID,
                            ml.`mod_name`,
                            ml.`mod_active`
                        FROM `mod_match_overview` mmo
                        JOIN `mod_list` ml ON mmo.`mod_id` = ml.`mod_identifier`
                        WHERE ml.`mod_id` = ?
                        ORDER BY mmo.`match_recorded` DESC
                        LIMIT ' . $SQLpageTemp . ',' . $resultsPerPage . ';',
                    'i',
                    $modIDFilter
                );
                $memcache->set('d2mods_recent_games_p' . $SQLpage . '_f' . $modIDFilter, $recentGameList, 0, 30);
            }

            $recentGameListCount = $memcache->get('d2mods_recent_games_count_f' . $modIDFilter);
            if (!$recentGameListCount) {
                $recentGameListCount = $db->q('SELECT COUNT(*) AS total_games
                        FROM `mod_match_overview` mmo
                        JOIN `mod_list` ml ON mmo.`mod_id` = ml.`mod_identifier`
                        WHERE ml.`mod_id` = ?
                        ORDER BY mmo.`match_recorded` DESC;',
                    'i',
                    $modIDFilter
                );
                $memcache->set('d2mods_recent_games_count_f' . $modIDFilter, $recentGameListCount, 0, 30);
            }
        } else {
            $recentGameList = $memcache->get('d2mods_recent_games_p' . $SQLpage);
            if (!$recentGameList) {
                $recentGameList = $db->q('SELECT
                        mmo.`match_id`,
                        mmo.`mod_id`,
                        mmo.`match_duration`,
                        mmo.`match_num_players`,
                        mmo.`match_recorded`,
                        ml.`mod_id` as modFakeID,
                        ml.`mod_name`,
                        ml.`mod_active`
                    FROM `mod_match_overview` mmo
                    JOIN `mod_list` ml ON mmo.`mod_id` = ml.`mod_identifier`
                    ORDER BY mmo.`match_recorded` DESC
                    LIMIT ' . $SQLpageTemp . ',' . $resultsPerPage . ';'
                );
                $memcache->set('d2mods_recent_games_p' . $SQLpage, $recentGameList, 0, 30);
            }

            $recentGameListCount = simple_cached_query('d2mods_recent_games_count',
                'SELECT COUNT(*) AS total_games
                    FROM `mod_match_overview` mmo
                    ORDER BY mmo.`match_recorded` DESC;'
                , 30
            );
        }

        $recentGameListCount = !empty($recentGameListCount)
            ? $recentGameListCount[0]['total_games']
            : 0;

        $pages = ceil($recentGameListCount / $resultsPerPage);

        echo '<div class="page-header"><h2>Recent Games <small>BETA</small></h2></div>';

        echo '<p>This is a list of the last ' . $resultsPerPage . ' games played that developers have implemented stats for.</p>';

        if (!empty($recentGameList)) {

            echo '<div class="table-responsive">
		        <table class="table table-striped table-hover">';
            echo '
                <tr>
                    <th>Mod</th>
                    <th>&nbsp;</th>
                    <th>Match ID</th>
                    <th>Duration</th>
                    <th>Players</th>
                    <th>Recorded</th>
                </tr>';

            foreach ($recentGameList as $key => $value) {
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
                        <td><a class="nav-clickable" href="#d2mods__recent_games?f=' . $value['modFakeID'] . '&p=' . ($SQLpage + 1) . '"><span class="glyphicon glyphicon-search"></span></a></td>
                        <td><a class="nav-clickable" href="#d2mods__match?id=' . $matchID . '">' . $matchID . '</a></td>
                        <td>' . $matchDuration . ' mins</td>
                        <td>' . $numPlayers . '</td>
                        <td>' . $matchDate . '</td>
                    </tr>';
            }

            echo '</table></div>';

            $pagination = '';
            if ($pages > 1) {
                if (!empty($modIDFilter)) {
                    $urlFilterQuery = '&f=' . $modIDFilter;
                } else {
                    $urlFilterQuery = '';
                }

                $pagination .= '<nav class="text-center"><ul class="pagination pagination-md">';

                $pagination_cap = 4;

                if ($pages > 20) {
                    for ($i = 1; $i < $pages && $i <= $pagination_cap; $i++) {
                        $liClass = $i == ($SQLpage + 1)
                            ? " class='active'"
                            : NULL;
                        $pagination .= '<li ' . $liClass . '><a class="nav-clickable" href="#d2mods__recent_games?p=' . $i . $urlFilterQuery . '" id="' . $i . '-page">' . $i . '</a></li>';
                    }

                    if ($SQLpage > ($pagination_cap - 1) && ($SQLpage + 4) < ($pages - $pagination_cap)) {
                        $pagination .= '<li class="disabled"><span>...</span></li>';

                        for ($i = ($SQLpage); $i < $pages && $i <= ($SQLpage + $pagination_cap); $i++) {
                            $liClass = $i == ($SQLpage + 1)
                                ? " class='active'"
                                : NULL;
                            $pagination .= '<li ' . $liClass . '><a class="nav-clickable" href="#d2mods__recent_games?p=' . $i . $urlFilterQuery . '" id="' . $i . '-page">' . $i . '</a></li>';
                        }
                    } else if ($SQLpage == ($pagination_cap - 1)) {
                        for ($i = ($SQLpage + 2); $i < $pages && $i <= ($SQLpage + $pagination_cap); $i++) {
                            $liClass = $i == ($SQLpage + 1)
                                ? " class='active'"
                                : NULL;
                            $pagination .= '<li ' . $liClass . '><a class="nav-clickable" href="#d2mods__recent_games?p=' . $i . $urlFilterQuery . '" id="' . $i . '-page">' . $i . '</a></li>';
                        }
                    }

                    $pagination .= '<li class="disabled"><span>...</span></li>';

                    $bottom_upper_portion = $pages - $pagination_cap;
                    for ($i = $bottom_upper_portion; $i < $pages; $i++) {
                        $liClass = $i == ($SQLpage + 1)
                            ? " class='active'"
                            : NULL;
                        $pagination .= '<li ' . $liClass . '><a class="nav-clickable" href="#d2mods__recent_games?p=' . $i . $urlFilterQuery . '" id="' . $i . '-page">' . $i . '</a></li>';
                    }
                } else {
                    for ($i = 1; $i < $pages; $i++) {
                        $liClass = $i == ($SQLpage + 1)
                            ? " class='active'"
                            : NULL;
                        $pagination .= '<li ' . $liClass . '><a class="nav-clickable" href="#d2mods__recent_games?p=' . $i . $urlFilterQuery . '" id="' . $i . '-page">' . $i . '</a></li>';
                    }
                }

                $pagination .= '</ul></nav>';

                echo $pagination;
            }

        } else {
            echo bootstrapMessage('Oh Snap', 'No games played yet!');
        }
    }

    echo '<p>
            <div class="text-center">
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__recent_games">Recent Games</a>
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__directory">Mod Directory</a>
            </div>
        </p>';

    $memcache->close();
} catch (Exception $e) {
    $message = 'Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage();
    echo bootstrapMessage('Oh Snap', $message, 'danger');
}