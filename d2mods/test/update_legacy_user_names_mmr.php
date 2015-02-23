<?php
try {
    require_once('../../global_functions.php');
    require_once('../../connections/parameters.php');

    if (!isset($_SESSION)) {
        session_start();
    }

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    checkLogin_v2();
    if (empty($_SESSION['user_id64'])) throw new Exception('Not logged in!');

    $adminCheck = adminCheck($_SESSION['user_id64'], 'admin');
    if (empty($adminCheck)) throw new Exception('Not an admin!');


    $badUserNames = $db->q("SELECT * FROM `gds_users_mmr`;");

    if (!empty($badUserNames)) {
        foreach ($badUserNames as $key => $value) {
            $sqlResult = $db->q(
                'UPDATE `gds_users_mmr` SET `user_name` = ? WHERE `user_id64` = ?;',
                'ss',
                array(htmlentities_custom(html_entity_decode($value['user_name'])), $value['user_id64'])
            );

            if ($sqlResult) {
                echo "[SUCCESS] Updated name for [" . $value['user_id64'] . "]!<br />";
            }
            else{
                echo "[FAILURE] Did not update name for [" . $value['user_id64'] . "]!<br />";
            }

            flush();
        }
    } else {
        echo bootstrapMessage('Oh Snap', 'No username entries!', 'danger');
    }

    $memcache->close();
} catch (Exception $e) {
    $message = 'Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage();
    echo bootstrapMessage('Oh Snap', $message, 'danger');
}