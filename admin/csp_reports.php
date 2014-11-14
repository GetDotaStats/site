<?php
try {
    require_once('../global_functions.php');
    require_once('../connections/parameters.php');

    $db = new dbWrapper($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if ($db) {
        $reports = $db->q(
            "SELECT * FROM `reports_csp` ORDER BY `reportDate` DESC;"
        );

        echo '<pre>';
        foreach ($reports as $key => $value) {
            $reportIP = empty($value['reportIP'])
                ? ''
                : " - " . $value['reportIP'];

            echo relative_time($value['reportDate']) . $reportIP . "<br />";

            echo json_encode(json_decode($value['reportContent'], 1), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            echo '<br /><br />';

            echo json_encode(json_decode($value['reportHeaders'], 1), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            echo "<hr />";
        }
        echo '</pre>';
    } else {
        echo 'No DB!';
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
