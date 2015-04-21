<?php
require_once('../global_functions.php');
require_once('../connections/parameters.php');

try {
    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    /*$modWorkshopList = cached_query(
        'd2mods_recently_updated',
        'SELECT
              mw.`mod_identifier`,
              mw.`mod_workshop_id`,
              mw.`mod_size`,
              mw.`mod_hcontent_file`,
              mw.`mod_hcontent_preview`,
              mw.`mod_thumbnail`,
              mw.`mod_views`,
              mw.`mod_subs`,
              mw.`mod_favs`,
              mw.`mod_subs_life`,
              mw.`mod_favs_life`,
              mw.`date_last_updated`,
              mw.`date_recorded`,

              ml.`mod_name`,
              ml.`mod_id`
            FROM `mod_workshop` mw
            LEFT JOIN `mod_list` ml ON mw.`mod_identifier` = ml.`mod_identifier`
            GROUP BY mw.`mod_workshop_id`
            ORDER BY `date_last_updated` DESC;',
        NULL,
        NULL,
        15
    );*/

    $order_col = !empty($_GET['o']) && is_numeric($_GET['o'])
        ? $_GET['o']
        : 2;

    switch ($order_col) {
        case 1:
            $order_clause = 'mw.`date_last_updated` ASC';
            break;
        case 2:
            $order_clause = 'mw.`date_last_updated` DESC';
            break;
        case 3:
            $order_clause = 'mw.`mod_size` ASC';
            break;
        case 4:
            $order_clause = 'mw.`mod_size` DESC';
            break;
        case 5:
            $order_clause = 'games_last_week ASC';
            break;
        case 6:
            $order_clause = 'games_last_week DESC';
            break;
        case 7:
            $order_clause = 'games_all_time ASC';
            break;
        case 8:
            $order_clause = 'games_all_time DESC';
            break;
        default:
            $order_clause = 'mw.`date_last_updated` DESC';
            break;
    }

    $modWorkshopList = cached_query(
        'd2mods_recently_updated_' . $order_col,
        'SELECT
              mw.`mod_identifier`,
              mw.`mod_workshop_id`,
              mw.`mod_size`,
              mw.`mod_hcontent_file`,
              mw.`mod_hcontent_preview`,
              mw.`mod_thumbnail`,
              mw.`mod_views`,
              mw.`mod_subs`,
              mw.`mod_favs`,
              mw.`mod_subs_life`,
              mw.`mod_favs_life`,
              mw.`date_last_updated`,
              mw.`date_recorded`,

              ml.`mod_id`,
              ml.`mod_name`,
              ml.`mod_steam_group`,
              ml.`mod_workshop_link`,

              (SELECT
                    COUNT(*)
                  FROM `mod_match_overview` mmo
                  WHERE mmo.`mod_id` = ml.`mod_identifier` AND mmo.`match_recorded` >= now() - INTERVAL 7 DAY AND mmo.`match_duration` > 130
                  GROUP BY `mod_id`
              ) AS games_last_week,
              (SELECT
                    COUNT(*)
                  FROM `mod_match_overview` mmo
                  WHERE mmo.`mod_id` = ml.`mod_identifier` AND mmo.`match_duration` > 130
                  GROUP BY `mod_id`
              ) AS games_all_time

            FROM `mod_workshop` mw
            JOIN (
                SELECT
                    `mod_identifier`,
                    MAX(`date_recorded`) AS `most_recent_date`
                FROM `mod_workshop`
                GROUP BY `mod_identifier`
            ) mw2 ON mw.`mod_identifier` = mw2.`mod_identifier` AND mw.`date_recorded` = mw2.`most_recent_date`
            RIGHT JOIN `mod_list` ml ON mw.`mod_identifier` = ml.`mod_identifier`
            WHERE ml.`mod_active` = 1
            ORDER BY ' . $order_clause . ';',
        NULL,
        NULL,
        15
    );

    echo '<h2>Mod Directory</h2>';

    echo '<p>Below is the list of mods that have integrated our statistic gathering library. They are
    available to play via the Lobby Explorer. The "Last Updated" column indicates when the mod was
    last updated in the workshop, and is checked once a day.</p>';

    echo '<span class="h4">&nbsp;</span>';

    echo '<div class="text-center">
            <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__lobby_list">Lobby List</a>
            <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__recent_games">Recent Games</a>
           </div>';

    echo '<span class="h4">&nbsp;</span>';

    if (!empty($modWorkshopList)) {
        $totalModSize = 0;
        $totalGamesLastWeek = 0;
        $totalGamesAllTime = 0;

        $glpyh_test = '<span class="glyphicon glyphicon-question-sign" title="Games Recorded Last Week / Games Recorded in Total"></span>';

        $glpyh_up = '<span class="glyphicon glyphicon-arrow-up"></span>';
        $glpyh_down = '<span class="glyphicon glyphicon-arrow-down"></span>';

        echo '<div class="row h4">
                    <div class="col-sm-5">&nbsp;</div>
                    <div class="col-sm-2 text-center">Games</div>
                </div>';

        echo '<div class="row">
                    <div class="col-sm-5 text-center"><strong>Mod</strong></div>
                    <div class="col-sm-1 text-center"><strong>Week</strong><br />
                        <a class="nav-clickable" href="#d2mods__directory?o=6">' . $glpyh_down . '</a>
                        <a class="nav-clickable" href="#d2mods__directory?o=5">' . $glpyh_up . '</a>
                    </div>
                    <div class="col-sm-1 text-center"><strong>All</strong><br />
                        <a class="nav-clickable" href="#d2mods__directory?o=8">' . $glpyh_down . '</a>
                        <a class="nav-clickable" href="#d2mods__directory?o=7">' . $glpyh_up . '</a>
                    </div>
                    <div class="col-sm-1">&nbsp;</div>
                    <div class="col-sm-1 text-center"><strong>Size</strong><br />
                        <a class="nav-clickable" href="#d2mods__directory?o=4">' . $glpyh_down . '</a>
                        <a class="nav-clickable" href="#d2mods__directory?o=3">' . $glpyh_up . '</a>
                    </div>
                    <div class="col-sm-1 text-center"><strong>Links</strong></div>
                    <div class="col-sm-2 text-center"><strong>Last Updated</strong><br />
                        <a class="nav-clickable" href="#d2mods__directory?o=2">' . $glpyh_down . '</a>
                        <a class="nav-clickable" href="#d2mods__directory?o=1">' . $glpyh_up . '</a>
                    </div>
                </div>';

        foreach ($modWorkshopList as $key => $value) {
            $totalModSize += !empty($value['mod_size'])
                ? $value['mod_size']
                : 0;

            $totalGamesLastWeek += !empty($value['games_last_week'])
                ? $value['games_last_week']
                : 0;

            $totalGamesAllTime += !empty($value['games_all_time'])
                ? $value['games_all_time']
                : 0;

            $workshopLink = !empty($value['mod_workshop_link'])
                ? '<a target="_blank" class="db_link" href="http://steamcommunity.com/sharedfiles/filedetails/?id=' . $value['mod_workshop_link'] . '">WS</a>'
                : '<span class="db_link">WS</span>';

            $steamGroupLink = !empty($value['mod_steam_group'])
                ? '<a target="_blank" class="db_link" href="http://steamcommunity.com/groups/' . $value['mod_steam_group'] . '">SG</a>'
                : '<span class="db_link">SG</span>';

            $modSize = !empty($value['mod_size'])
                ? filesize_human_readable($value['mod_size'], 0, 'MB', true)
                : NULL;

            $modSize = !empty($modSize)
                ? $modSize['number'] . '<span class="db_link"> ' . $modSize['string'] . '</span>'
                : '??<span class="db_link"> MB</span>';

            $modLinks = $workshopLink . ' || ' . $steamGroupLink;

            $modThumb = !empty($value['mod_thumbnail'])
                ? $value['mod_thumbnail']
                : $CDN_image . '/images/misc/steam/blank_avatar.jpg';

            $modLastUpdate =  !empty($value['date_last_updated'])
                ? relative_time_v3($value['date_last_updated'], 0, 'day')
                : '??';

            echo '<div class="row">
                    <div class="col-sm-5"><img width="25" height="25" src="' . $modThumb . '" /> <a class="nav-clickable" href="#d2mods__stats?id=' . $value['mod_id'] . '">' . $value['mod_name'] . '</a></div>
                    <div class="col-sm-1 text-right">' . number_format($value['games_last_week']) . '</div>
                    <div class="col-sm-1 text-right">' . number_format($value['games_all_time']) . '</div>
                    <div class="col-sm-1">&nbsp;</div>
                    <div class="col-sm-1 text-right">' . $modSize . '</div>
                    <div class="col-sm-1 text-center">' . $modLinks . '</div>
                    <div class="col-sm-2 text-right">' . $modLastUpdate . '</div>
                </div>';

            echo '<span class="h5">&nbsp;</span>';
        }

        $totalModSize = !empty($totalModSize)
            ? filesize_human_readable($totalModSize, 1, 'GB', true)
            : '??';

        $totalModSize = !empty($totalModSize)
            ? $totalModSize['number'] . '<span class="db_link"> ' . $totalModSize['string'] . '</span>'
            : '??<span class="db_link"> GB</span>';

        echo '<div class="row">
                    <div class="col-sm-5 text-right">&nbsp;</div>
                    <div class="col-sm-1 text-right"><strong>' . number_format($totalGamesLastWeek) . '</strong></div>
                    <div class="col-sm-1 text-right"><strong>' . number_format($totalGamesAllTime) . '</strong></div>
                    <div class="col-sm-1">&nbsp;</div>
                    <div class="col-sm-1 text-right"><strong>' . $totalModSize . '</strong></div>
                </div>';
    } else {
        echo bootstrapMessage('No data!');
    }


    echo '<span class="h4">&nbsp;</span>';

    echo '<div class="text-center">
            <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__lobby_list">Lobby List</a>
            <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__recent_games">Recent Games</a>
           </div>';

    echo '<span class="h4">&nbsp;</span>';
} catch (Exception $e) {
    echo formatExceptionHandling($e);
} finally {
    if (isset($memcache)) $memcache->close();
}