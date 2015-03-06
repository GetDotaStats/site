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
                MAX(gum.`user_games`) AS user_games,
                MAX(gum.`user_mmr_solo`) AS user_mmr_solo,
                MAX(gum.`user_mmr_party`) AS user_mmr_party,
                (SELECT `user_stats_disabled` FROM `gds_users_mmr` WHERE `user_id32` = gum.`user_id32` ORDER BY `date_recorded` DESC LIMIT 0,1) AS `user_stats_disabled`,
                `date_recorded`
            FROM `gds_users_mmr` gum
            GROUP BY gum.`user_id64`
            ORDER BY gum.`user_mmr_solo` DESC;',
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
            </div>';

    foreach ($userMMRs as $key => $value) {
        echo '<div class="row">
                <div class="col-md-2">' . $value['user_id64'] . '</div>
                <div class="col-md-4">' . $value['user_name'] . '</div>
                <div class="col-md-1 text-center">' . number_format($value['user_games']) . '</div>
                <div class="col-md-1 text-center">' . number_format($value['user_mmr_solo']) . '</div>
                <div class="col-md-1 text-center">' . number_format($value['user_mmr_party']) . '</div>
                <div class="col-md-1 text-center">' . number_format($value['user_stats_disabled']) . '</div>
            </div>';
    }


    $memcache->close();
} catch (Exception $e) {
    $message = 'Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage();
    echo bootstrapMessage('Oh Snap', $message, 'danger');
}