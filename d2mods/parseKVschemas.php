<?php
require_once('../global_functions.php');
require_once('./functions.php');
require_once('../connections/parameters.php');
require_once('./vdfparser.php');


try {
    if (!isset($_SESSION)) {
        session_start();
    }

    checkLogin_v2();

    echo '
        <head>
            <link href="//getdotastats.com/bootstrap/css/bootstrap.min.css" rel="stylesheet">
            <link href="//getdotastats.com/getdotastats.css?10" rel="stylesheet">
        </head>
    ';

    if (!empty($_SESSION['user_id64'])) {
        $db = new dbWrapper_v2($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site);
        if ($db) {
            $file_path = './mod_schemas/3 - 2374504c2c518fafc9731a120e67fdf5/npc_units_custom.txt';
            $mod_id = '2374504c2c518fafc9731a120e67fdf5';
            $schema_type = 'npc_units_custom';

            $schema = VDFParse($file_path);
            $schemaJSON = json_encode($schema);

            $db->q(
                'INSERT INTO `mod_schemas`(`mod_id`, `schema_content`, `schema_type`)
                    VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    `mod_id` = VALUES(`mod_id`),
                    `schema_content` = VALUES(`schema_content`),
                    `schema_type` = VALUES(`schema_type`);',
                'sss',
                $mod_id, $schemaJSON, $schema_type
            );

            echo '<pre>';
            print_r($schema);
            echo '</pre>';
        } else {
            echo bootstrapMessage('Oh Snap', 'No DB!');
        }
    } else {
        echo bootstrapMessage('Oh Snap', 'Not logged in!');
        echo '<a href="../">Go back to main site</a>';
    }
} catch (Exception $e) {
    $eMsg = 'Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage();
    echo bootstrapMessage('Oh Snap', $eMsg);
}