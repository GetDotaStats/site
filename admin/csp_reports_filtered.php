<?php
try {
    require_once('../global_functions.php');
    require_once('../connections/parameters.php');

    if (!isset($_SESSION)) {
        session_start();
    }

    checkLogin_v2();

    if (!empty($_SESSION['user_id64']) && !empty($_SESSION['isAdmin'])) {
        $db = new dbWrapper($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
        if ($db) {
            $reports = $db->q(
                "SELECT
                        `document-uri`,
                        `violated-directive`,
                        `blocked-uri`,
                        `source-file`,
                        COUNT(DISTINCT `remote-ip`) as sumReports
                    FROM `reports_csp_filter`
                    GROUP BY 1,2,3,4
                    ORDER BY sumReports DESC;"
            );

            if (!empty($reports)) {
                echo '<h2>CSP Reports</h2>';

                echo '<div class="table-responsive">
		            <table class="table table-striped table-hover">';
                echo '<tr>
                        <th>Document</th>
                        <th class="text-center">Directive</th>
                        <th>Blocked URI</th>
                        <th>Source URI</th>
                        <th class="text-center">Unique Reports</th>
                    </tr>';
                foreach ($reports as $key => $value) {
                    echo '<tr>
                            <td>' . $value['document-uri'] . '</td>
                            <td class="text-center">' . $value['violated-directive'] . '</td>
                            <td>' . $value['blocked-uri'] . '</td>
                            <td>' . $value['source-file'] . '</td>
                            <td class="text-center">' . $value['sumReports'] . '</td>
                        </tr>';
                }
                echo '</table></div>';

                echo '<p><a class="nav-clickable" href="#admin/">Back to Admin Panel</a></p>';
            } else {
                echo bootstrapMessage('Oh Snap', 'No reports!', 'danger');
            }
        } else {
            echo bootstrapMessage('Oh Snap', 'No DB!', 'danger');
        }
    } else {
        echo bootstrapMessage('Oh Snap', 'Not logged in or not admin!', 'danger');
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
