<?php
try {
    require_once('../global_functions.php');
    require_once('../connections/parameters.php');

    if (!isset($_SESSION)) {
        session_start();
    }

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    checkLogin_v2();
    if (empty($_SESSION['user_id64'])) throw new Exception('Not logged in!');

    $adminCheck = adminCheck($_SESSION['user_id64'], 'admin');
    if (empty($adminCheck)) throw new Exception('Not an admin!');

    $userMMRs = cached_query(
        'admin_user_mmrs',
        'SELECT
                gum.`user_id32`,
                gum.`user_id64`,
                gum.`user_name`,
                gum.`user_games`,
                gum.`user_mmr_solo`,
                gum.`user_mmr_party`,
                gum.`user_stats_disabled`,
                `date_recorded`
            FROM `gds_users_mmr` gum
            JOIN (
                  SELECT
                      `user_id32`,
                      `user_mmr_solo`,
                      MAX(`date_recorded`) as most_recent_mmr
                  FROM `gds_users_mmr`
                  GROUP BY `user_id32`
                  ORDER BY `user_mmr_solo` DESC
            ) gum2 ON gum.`user_id32` = gum2.`user_id32` AND gum.`date_recorded` = gum2.`most_recent_mmr`;',
        null,
        null,
        10
    );

    $userMMRsCount = cached_query(
        'admin_user_mmrs_count',
        'SELECT
              COUNT(DISTINCT `user_id64`) as total_users
            FROM `gds_users_mmr`;',
        null,
        null,
        10
    );

    if (empty($userMMRs) || empty($userMMRsCount)) throw new Exception('No MMRs recorded!');

    echo '<h2>User MMR List</h2>';

    echo '<h3>Total Users: <small>' . $userMMRsCount[0]['total_users'] . '</small></h3>';

    echo '<p>This section likely won\'t be permament. It will serve as a temporary page to diagnose issues with MMR reporting.</p>';

    echo '<hr />';

    echo '<div class="row">
                <div class="col-md-2"><span class="h4">UserID</span></div>
                <div class="col-md-4"><span class="h4">Username</span></div>
                <div class="col-md-1 text-center"><span class="h4">Games</span></div>
                <div class="col-md-1 text-center"><span class="h4">Solo</span></div>
                <div class="col-md-1 text-center"><span class="h4">Party</span></div>
                <div class="col-md-1 text-center"><span class="h4">Disabled</span></div>
                <div class="col-md-2 text-center"><span class="h4">Updated</span></div>
            </div>';

    foreach ($userMMRs as $key => $value) {
        $relativeTime = relative_time_v3($value['date_recorded'], 1, 'day');
        $dateColour = $relativeTime < 1.5
            ? 'boldGreenText'
            : '';

        echo '<div class="row">
                <div class="col-md-2">' . $value['user_id64'] . '</div>
                <div class="col-md-4"><a class="nav-clickable" href="#d2mods__profile?id=' . $value['user_id64'] . '"><span class="glyphicon glyphicon-search"></span></a> ' . $value['user_name'] . '</div>
                <div class="col-md-1 text-center">' . number_format($value['user_games']) . '</div>
                <div class="col-md-1 text-center">' . number_format($value['user_mmr_solo']) . '</div>
                <div class="col-md-1 text-center">' . number_format($value['user_mmr_party']) . '</div>
                <div class="col-md-1 text-center">' . number_format($value['user_stats_disabled']) . '</div>
                <div class="col-md-2 text-right ' . $dateColour . '">' . $relativeTime . '</div>
            </div>';
    }

} catch (Exception $e) {
    echo formatExceptionHandling($e);
} finally {
    if (isset($memcache)) $memcache->close();
}