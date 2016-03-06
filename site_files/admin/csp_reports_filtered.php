<?php
try {
    require_once('../global_functions.php');
    require_once('../connections/parameters.php');

    if (!isset($_SESSION)) {
        session_start();
    }

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcached = new Cache(NULL, NULL, $localDev);

    checkLogin_v2();
    if (empty($_SESSION['user_id64'])) throw new Exception('Not logged in!');

    $adminCheck = adminCheck($_SESSION['user_id64'], 'admin');
    if (empty($adminCheck)) throw new Exception('Not an admin!');

    echo '<h2>CSP Reports</h2>';

    $reports = simple_cached_query(
        'csp_reports_filtered',
        "SELECT
                `violated-directive`,
                `blocked-uri`,
                `source-file`,
                COUNT(DISTINCT `remote-ip`) as sumReports
            FROM `reports_csp_filter`
            GROUP BY 1,2,3
            ORDER BY sumReports DESC;",
        60
    );

    $reportsStats = simple_cached_query(
        'csp_report_stats',
        "SELECT
            (
                SELECT
                    COUNT(*) as total_reports_lw
                FROM `reports_csp_filter`
                WHERE `dateRecorded` >= now() - INTERVAL 7 DAY
                LIMIT 0,1
            ) AS total_reports_lw,
            (
                SELECT
                    COUNT(*) as total_reports_lw
                FROM `reports_csp_filter`
                LIMIT 0,1
            ) AS total_reports;",
        60
    );

    if (!empty($reportsStats)) {
        echo
            '<div class="alert alert-danger">
                <span class="h3">Reports</span><br />
                <strong>Total:</strong> ' . number_format($reportsStats[0]['total_reports']) . '<br />
                        <strong>Last Week:</strong> ' . number_format($reportsStats[0]['total_reports_lw']) . '
                    </div>';
    }

    if (!empty($reports)) {
        echo '<div class="table-responsive">
		            <table class="table table-striped table-hover bigTable">';
        echo '<tr>
                        <th class="col-sm-2 text-center">Directive</th>
                        <th>Blocked URI</th>
                        <th>Source URI</th>
                        <th class="col-sm-1 text-center">Unique Reports</th>
                    </tr>';
        foreach ($reports as $key => $value) {
            $blockedURI = str_replace('http://', '', str_replace('https://', '', $value['blocked-uri']));
            $sourceFile = str_replace('http://', '', str_replace('https://', '', $value['source-file']));

            echo '<tr>
                            <td class="text-center">' . $value['violated-directive'] . '</td>
                            <td>' . $blockedURI . '</td>
                            <td>' . $sourceFile . '</td>
                            <td class="text-center">' . $value['sumReports'] . '</td>
                        </tr>';
        }
        echo '</table></div>';
    } else {
        echo bootstrapMessage('Oh Snap', 'No reports!', 'danger');
    }

} catch (Exception $e) {
    echo formatExceptionHandling($e);
} finally {
    if (isset($memcached)) $memcached->close();
}