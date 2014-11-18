<?php
try {
    require_once('../global_functions.php');
    require_once('../connections/parameters.php');

    if (!isset($_SESSION)) {
        session_start();
    }

    checkLogin_v2();

    if (!empty($_SESSION['user_id64']) && !empty($_SESSION['isAdmin'])) {
        ?>
        <h2>Administrative Functions</h2>

        <ul>
            <li><a class="nav-clickable" href="#admin__csp_reports">CSP Reports (Last 100)</a></li>
            <li><a class="nav-clickable" href="#admin__csp_reports_filtered">CSP Reports (Filtered)</a></li>
        </ul>
    <?php
    } else {
        echo bootstrapMessage('Oh Snap', 'Not logged in or not admin!', 'danger');
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
