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
        $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);

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

                    $itemIcon = $value['name'];
                    if (stristr($itemIcon, 'recipe_')) {
                        $itemIcon = 'recipe';
                    } else if (stristr($itemIcon, 'item_')) {
                        $itemIcon = str_replace('item_', '', $itemIcon);
                    }

                    $db->q(
                        'INSERT INTO `mod_items` (`mod_id`, `item_name`, `item_icon`, `item_nice_name`, `item_custom_icon`)
                            VALUES (?, ?, ?, ?, ?)
                         ON DUPLICATE KEY UPDATE
                            `mod_id` = VALUES(`mod_id`),
                            `item_name` = VALUES(`item_name`),
                            `item_icon` = VALUES(`item_icon`),
                            `item_nice_name` = VALUES(`item_nice_name`),
                            `item_custom_icon` = VALUES(`item_custom_icon`);',
                        'ssssi',
                        0, $value['name'], $itemIcon, $value['localized_name'], 0
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
        echo '<a href="../../">Go back to main site</a>';
    }
} catch (Exception $e) {
    $eMsg = 'Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage();
    echo bootstrapMessage('Oh Snap', $eMsg);
}