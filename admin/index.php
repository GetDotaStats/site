<?php
try {
    require_once('../global_functions.php');
    require_once('../connections/parameters.php');

    if (!isset($_SESSION)) {
        session_start();
    }

    checkLogin_v2();
    if (empty($_SESSION['user_id64'])) throw new Exception('Not logged in!');

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if(empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    $adminCheck = adminCheck($_SESSION['user_id64'], 'admin');
    if (empty($adminCheck)) {
        throw new Exception('Not an admin!');
    }

    echo '<h2>Administrative Functions</h2>';

    echo '<div class="container">
            <p><a class="nav-clickable btn btn-default btn-lg" href="#admin__mod_approve">Approve Mods</a></p>
            <p><a class="nav-clickable btn btn-default btn-lg" href="#admin__minigame_create">Create Mini Game</a></p>
            <p><a class="nav-clickable btn btn-default btn-lg" href="#admin__csp_reports_filtered_lw">CSP Reports (Last Week)</a> <a class="nav-clickable btn btn-default btn-sm" href="#admin__csp_reports_filtered">CSP Reports</a> <a class="nav-clickable btn btn-default btn-sm" href="#admin__csp_reports">CSP Reports (Last 100)</a></p>
        </div>';

    $memcache->close();
} catch (Exception $e) {
    $message = 'Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage();
    echo bootstrapMessage('Oh Snap', $message, 'danger');
}
