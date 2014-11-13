<?php
try {
    // Send `204 No Content` status code
    http_response_code(204);

    // Get the raw POST data
    $data = file_get_contents('php://input');

    // Only continue if it’s valid JSON that is not just `null`, `0`, `false` or an empty string, i.e. if it could be a CSP violation report.
    if (json_decode($data)) {
        require_once('./global_functions.php');
        require_once('./connections/parameters.php');

        $db = new dbWrapper($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
        if ($db) {
            $headers = json_encode(getallheaders());

            $db->q(
                "INSERT INTO `reports_csp`(`reportContent`, `reportHeaders`) VALUES (?, ?);",
                'ss',
                $data, $headers
            );
        } else {
            echo 'No DB!';
        }
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
