<?php
try {
    require_once('../global_functions.php');
    require_once('../connections/parameters.php');

    if (!isset($_SESSION)) {
        session_start();
    }

    checkLogin_v2();

    if (!empty($_SESSION['user_id64']) && !empty($_SESSION['isAdmin'])) {
        echo '<p>
                <div class="text-center">
                    <a class="nav-clickable btn btn-default btn-lg" href="#admin/">Back to Admin Panel</a>
                </div>
            </p>';

        $db = new dbWrapper($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
        if ($db) {
            $reports = $db->q(
                "SELECT * FROM `reports_csp` ORDER BY `reportDate` DESC LIMIT 0,100;"
            );

            if (!empty($reports)) {
                echo '<h2>CSP Reports (Last 100)</h2>';

                foreach ($reports as $key => $value) {
                    $reportIP = empty($value['reportIP'])
                        ? ''
                        : " - " . $value['reportIP'];

                    echo relative_time($value['reportDate']) . $reportIP . "<br />";

                    echo '<pre>';

                    $reportContect = json_decode($value['reportContent'], 1);
                    unset($reportContect['csp-report']['original-policy']);

                    echo print_r($reportContect['csp-report']);

                    echo '<br /><br />';

                    $reportHeaders = json_decode($value['reportHeaders'], 1);
                    echo print_r($reportHeaders);

                    echo '</pre>';

                    echo "<hr />";
                }
            } else {
                echo bootstrapMessage('Oh Snap', 'No reports!', 'danger');
            }
        } else {
            echo bootstrapMessage('Oh Snap', 'No DB!', 'danger');
        }

        echo '<p>
                <div class="text-center">
                    <a class="nav-clickable btn btn-default btn-lg" href="#admin/">Back to Admin Panel</a>
                </div>
            </p>';
    } else {
        echo bootstrapMessage('Oh Snap', 'Not logged in or not admin!', 'danger');
    }
} catch (Exception $e) {
    $message = 'Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage();
    echo bootstrapMessage('Oh Snap', $message, 'danger');
}
