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

        <li>
            <ul>
                <a class="nav-clickable" href="#admin__csp_reports">CSP Reports (All exceptions due to permissions)</a>
            </ul>
        </li>
    <?php
    } else {
        echo bootstrapMessage('Oh Snap', 'Not logged in or not admin!', 'danger');
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
