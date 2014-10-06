<?php
require_once('../global_functions.php');
require_once('../connections/parameters.php');

try {
    if (!isset($_SESSION)) {
        session_start();
    }

    if (isset($_COOKIE['session']) && empty($_SESSION['user_id64'])) {
        checkLogin_v2();
    }
    if (!empty($_SESSION['user_id64'])) {
        $db = new dbWrapper_v2($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site);
        if ($db) {
            $messages = $db->q('SELECT * FROM `node_listener` ORDER BY date_recorded DESC;');

            foreach ($messages as $key => $value) {
                $parsed = json_decode($value['message'],1);

                $numPlayers = !empty($parsed['rounds']['players'])
                    ? count($parsed['rounds']['players'])
                    : NULL;

                echo $parsed['matchID'] . ' || ' . $numPlayers . '<br />';

                $db->q(
                    'INSERT INTO `mod_match_overview` (`match_id`, `match_num_players`)
                        VALUES (?, ?) ON DUPLICATE KEY UPDATE
                            `match_id` = VALUES(`match_id`),
                            `match_num_players` = VALUES(`match_num_players`);'
                    , 'si'
                    , $parsed['matchID'], $numPlayers
                );

                /*echo $parsed['matchID'] . ' || ' . $parsed['modID'] . ' || ' . $parsed['duration'] . '||' . $value['date_recorded'] . '<br />';
                $db->q(
                    'INSERT INTO `node_listener` (`test_id`, `mod_id`)
                        VALUES (?, ?) ON DUPLICATE KEY UPDATE
                            `test_id` = VALUES(`test_id`),
                            `mod_id` = VALUES(`mod_id`);'
                    , 'is'
                    , $value['test_id'], $parsed['modID']
                );*/

                flush();
                ob_flush();
            }
        } else {
            echo '<div class="page-header"><div class="alert alert-danger" role="alert"><strong>Oh Snap:</strong> No DB!</div></div>';
        }
    } else {
        echo '<div class="page-header"><div class="alert alert-danger" role="alert"><strong>Oh Snap:</strong> Not logged in!</div></div>';
        echo '<a href="../">Go back to main site</a>';
    }
} catch (Exception $e) {
    echo '<div class="page-header"><div class="alert alert-danger" role="alert"><strong>Oh Snap:</strong> Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage() . '</div></div>';
}