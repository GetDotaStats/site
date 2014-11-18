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

            $remoteIP = empty($_SERVER['REMOTE_ADDR'])
                ? NULL
                : $_SERVER['REMOTE_ADDR'];

            $reportURI = empty($_SERVER["REQUEST_URI"])
                ? NULL
                : htmlentities($_SERVER["REQUEST_URI"]);

            $db->q(
                "INSERT INTO `reports_csp` (`reportContent`, `reportHeaders`, `reportIP`) VALUES (?, ?, ?);",
                'sss',
                $data, $headers, $remoteIP
            );

            $reportContect = json_decode($data, 1);

            $documentURI = $reportContect['csp-report']['document-uri'];

            $violatedDirective = explode(' ', $reportContect['csp-report']['violated-directive'], 2);
            $violatedDirective = $violatedDirective[0];

            $blockedURI = $reportContect['csp-report']['blocked-uri'];

            $sourceFile = $reportContect['csp-report']['source-file'];

            $db->q(
                "INSERT INTO `reports_csp_filter` (`document-uri`, `violated-directive`, `blocked-uri`, `source-file`, `remote-ip`) VALUES (?, ?, ?, ?, ?);",
                'sssss',
                $documentURI, $violatedDirective, $blockedURI, $sourceFile, $remoteIP
            );
        } else {
            echo 'No DB!';
        }
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
