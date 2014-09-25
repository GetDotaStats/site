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

                echo $parsed['matchID'] . ' || ' . $parsed['modID'] . ' || ' . $parsed['duration'] . '||' . $value['date_recorded'] . '<br />';

                $db->q(
                    'INSERT INTO `mod_match_overview` (`match_id`, `mod_id`, `match_duration`, `match_recorded`)
                        VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE
                            `match_id` = VALUES(`match_id`),
                            `mod_id` = VALUES(`mod_id`),
                            `match_duration` = VALUES(`match_duration`),
                            `match_recorded` = VALUES(`match_recorded`);'
                    , 'ssds'
                    , $parsed['matchID'], $parsed['modID'], $parsed['duration'], $value['date_recorded']
                );
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