<?php
require_once('../global_functions.php');
require_once('../connections/parameters.php');

try {
    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    $order_col = !empty($_GET['o']) && is_numeric($_GET['o'])
        ? $_GET['o']
        : -1;

    switch ($order_col) {
        case 1:
            $order_clause = 'ml.`workshop_updated` ASC';
            break;
        case 2:
            $order_clause = 'ml.`workshop_updated` DESC';
            break;
        case 3:
            $order_clause = 'ml.`mod_size` ASC';
            break;
        case 4:
            $order_clause = 'ml.`mod_size` DESC';
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
        case 9:
            $order_clause = 'ml.`date_recorded` ASC';
            break;
        case 10:
            $order_clause = 'ml.`date_recorded` DESC';
            break;
        default:
            $order_clause = 'games_last_week DESC, ml.`workshop_updated` DESC';
            break;
    }

    $modWorkshopList = cached_query(
        's2_directory_recently_updated' . $order_col,
        'SELECT
              ml.`mod_id`,
              ml.`mod_identifier`,
              ml.`mod_name`,
              ml.`mod_steam_group`,
              ml.`mod_workshop_link`,
              ml.`mod_size`,
              ml.`workshop_updated`,
              ml.`date_recorded` AS mod_date_added,

              (SELECT
                    SUM(`gamesPlayed`)
                  FROM `cache_mod_matches` cmm
                  WHERE cmm.`modID` = ml.`mod_id` AND cmm.`gamePhase` = 3 AND cmm.`dateRecorded` >= now() - INTERVAL 7 DAY
              ) AS games_last_week,
              (SELECT
                    SUM(`gamesPlayed`)
                  FROM `cache_mod_matches` cmm
                  WHERE cmm.`modID` = ml.`mod_id` AND cmm.`gamePhase` = 3
              ) AS games_all_time

            FROM `mod_list` ml
            WHERE ml.`mod_active` = 1
            ORDER BY ' . $order_clause . ';',
        NULL,
        NULL,
        5
    );

    echo '<h2>Mod Directory</h2>';

    echo '<p>Download all of the mods below by <a target="_blank" href="http://steamcommunity.com/sharedfiles/filedetails/?id=537809143">subscribing to our collection <span class="glyphicon glyphicon-new-window"></span></a>.</p>';

    //FEATURE REQUEST
    echo '<div class="alert alert-danger"><strong>Help Wanted!</strong> We are re-designing every page. If there are features you would like to
        see on this page, please let us know by making a post per feature on this page\'s
        <a target="_blank" href="https://github.com/GetDotaStats/site/issues/165">issue</a>.</div>';

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
                        <a class="nav-clickable" href="#s2__directory?o=6">' . $glpyh_down . '</a>
                        <a class="nav-clickable" href="#s2__directory?o=5">' . $glpyh_up . '</a>
                    </div>
                    <div class="col-sm-1 text-center"><strong>All</strong><br />
                        <a class="nav-clickable" href="#s2__directory?o=8">' . $glpyh_down . '</a>
                        <a class="nav-clickable" href="#s2__directory?o=7">' . $glpyh_up . '</a>
                    </div>
                    <div class="col-sm-1 text-center"><strong>Size</strong><br />
                        <a class="nav-clickable" href="#s2__directory?o=4">' . $glpyh_down . '</a>
                        <a class="nav-clickable" href="#s2__directory?o=3">' . $glpyh_up . '</a>
                    </div>
                    <div class="col-sm-1 text-center"><strong>Links</strong></div>
                    <div class="col-sm-3 text-center">
                        <div class="row">
                            <div class="col-sm-6 text-center">
                                <strong>Update</strong><br />
                                <a class="nav-clickable" href="#s2__directory?o=2">' . $glpyh_down . '</a>
                                <a class="nav-clickable" href="#s2__directory?o=1">' . $glpyh_up . '</a>
                            </div>
                            <div class="col-sm-6 text-center">
                                <strong>Added</strong><br />
                                <a class="nav-clickable" href="#s2__directory?o=10">' . $glpyh_down . '</a>
                                <a class="nav-clickable" href="#s2__directory?o=9">' . $glpyh_up . '</a>
                            </div>
                        </div>
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

            $modThumb = is_file('../images/mods/thumbs/' . $value['mod_id'] . '.png')
                ? $CDN_image . '/images/mods/thumbs/' . $value['mod_id'] . '.png'
                : $CDN_image . '/images/misc/steam/blank_avatar.jpg';

            if (!empty($value['workshop_updated'])) {
                $modLastUpdate = relative_time_v3($value['workshop_updated'], 0, 'day', 1);
                $modLastUpdate = $modLastUpdate['number'] . ' <span class="db_link">days</span>';
            } else {
                $modLastUpdate = '??';
            }

            if (!empty($value['mod_date_added'])) {
                $modAdded = relative_time_v3($value['mod_date_added'], 0, 'day', 1);
                $modAdded = $modAdded['number'] . ' <span class="db_link">days</span>';
            } else {
                $modAdded = '??';
            }

            echo '<div class="row">
                    <div class="col-sm-5"><img width="25" height="25" src="' . $modThumb . '" /> <a class="nav-clickable" href="#s2__mod?id=' . $value['mod_id'] . '">' . $value['mod_name'] . '</a></div>
                    <div class="col-sm-1 text-right">' . number_format($value['games_last_week']) . '</div>
                    <div class="col-sm-1 text-right">' . number_format($value['games_all_time']) . '</div>
                    <div class="col-sm-1 text-right">' . $modSize . '</div>
                    <div class="col-sm-1 text-center">' . $modLinks . '</div>
                    <div class="col-sm-3 text-center">
                        <div class="row">
                            <div class="col-sm-6 text-right">' . $modLastUpdate . '</div>
                            <div class="col-sm-6 text-right">' . $modAdded . '</div>
                        </div>
                    </div>
                </div>';

            echo '<span class="h5">&nbsp;</span>';
        }

        $totalModSize = !empty($totalModSize)
            ? filesize_human_readable($totalModSize, 1, 'GB', true)
            : '??';

        $totalModSize = !empty($totalModSize) && is_array($totalModSize)
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
            <a class="nav-clickable btn btn-default btn-lg" href="#s2__recent_games">Recent Games</a>
           </div>';

    echo '<span class="h4">&nbsp;</span>';
} catch (Exception $e) {
    echo formatExceptionHandling($e);
} finally {
    if (isset($memcache)) $memcache->close();
}