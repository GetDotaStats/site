<?php
try {
    require_once('../global_functions.php');
    require_once('../connections/parameters.php');

    if (!isset($_SESSION)) {
        session_start();
    }

    checkLogin_v2();

    if (!empty($_SESSION['user_id64']) && !empty($_SESSION['isAdmin'])) {

        echo '<h2>Administrative Functions</h2>';

        echo '
            <div class="container">
                <p><a class="nav-clickable btn btn-default btn-lg" href="#admin__mod_approve">Approve Mods</a></p>
                <p><a class="nav-clickable btn btn-default btn-lg" href="#admin__csp_reports_filtered_lw">CSP Reports (Last Week)</a> <a class="nav-clickable btn btn-default btn-sm" href="#admin__csp_reports_filtered">CSP Reports</a> <a class="nav-clickable btn btn-default btn-sm" href="#admin__csp_reports">CSP Reports (Last 100)</a></p>
            </div>';

    } else {
        echo bootstrapMessage('Oh Snap', 'Not logged in or not admin!', 'danger');
    }
} catch (Exception $e) {
    $message = 'Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage();
    echo bootstrapMessage('Oh Snap', $message, 'danger');
}
