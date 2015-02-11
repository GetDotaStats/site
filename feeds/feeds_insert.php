<?php
try {
    require_once('../global_functions.php');
    require_once('../connections/parameters.php');

    if (!isset($_SESSION)) {
        session_start();
    }

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    checkLogin_v2();
    if (empty($_SESSION['user_id64'])) throw new Exception('Not logged in!');

    $adminCheck = adminCheck($_SESSION['user_id64'], 'animufeed');
    if (empty($adminCheck)) {
        throw new Exception('Not an admin!');
    }

    $db = new dbWrapper_v3($hostname_gds_feeds, $username_gds_feeds, $password_gds_feeds, $database_gds_feeds, true);
    if (empty($db)) throw new Exception('No DB!');

    if (!empty($_POST['feed_title']) && !empty($_POST['feed_url'])) {

        $feed_title = $db->escape($_POST['feed_title']);
        $feed_url = $db->escape($_POST['feed_url']);
        $feed_category = $db->escape($_POST['feed_category']);

        $insertSQL = $db->q('INSERT INTO `feeds_list` (`feed_title`, `feed_url`, `feed_category`) VALUES (?, ?, ?);',
            'ssi',
            $feed_title, $feed_url, $feed_category);

        if ($insertSQL) {
            echo bootstrapMessage('Oh Snap', 'Insert Success!', 'success');
        } else {
            echo bootstrapMessage('Oh Snap', 'Insert Failure!');
        }
    } else {
        echo bootstrapMessage('Oh Snap', 'One or more of the required variables are missing or empty!');

    }
} catch (Exception $e) {
    echo $e->getMessage();
}