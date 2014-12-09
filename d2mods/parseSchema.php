<?php
require_once('../global_functions.php');
require_once('./functions.php');
require_once('../connections/parameters.php');

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
            $dota2_webapi = new dota2_webapi($api_key1);

            $gameItems = $dota2_webapi->GetGameItems();

            if (is_array($gameItems) && !empty($gameItems['result']['items']) && $gameItems['result']['status'] == 200) {
                foreach ($gameItems['result']['items'] as $key => $value) {
                    $db->q(
                        'INSERT INTO `game_regular_items`
                            (`item_id`, `item_name`, `item_nice_name`)
                            VALUES (?, ?, ?)
                         ON DUPLICATE KEY UPDATE
                            `item_name` = VALUES(`item_name`),
                            `item_nice_name` = VALUES(`item_nice_name`);',
                        'iss',
                        $value['id'], $value['name'], $value['localized_name']
                    );

                    echo '<strong>INSERTED:</strong>: ' . $value['id'] . ' | ' . $value['name'].'<br />';
                }
            } else {
                echo bootstrapMessage('Oh Snap', 'Unexpected type!');
                echo '<pre>';
                print_r($gameItems);
                echo '</pre>';
            }
        } else {
            echo bootstrapMessage('Oh Snap', 'No DB!');
        }
    } else {
        echo bootstrapMessage('Oh Snap', 'Not logged in!');
        echo '<a href="../">Go back to main site</a>';
    }
} catch (Exception $e) {
    echo '<div class="page-header"><div class="alert alert-danger" role="alert"><strong>Oh Snap:</strong> Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage() . '</div></div>';
}