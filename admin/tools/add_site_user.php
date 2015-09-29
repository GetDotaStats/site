<?php
try {
    require_once('../../global_functions.php');
    require_once('../../connections/parameters.php');

    if (!isset($_SESSION)) {
        session_start();
    }

    if (empty($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception('Selected schemaID is invalid!');
    }

    $userID = $_GET['id'];

    $steamIDmanipulation = new SteamID($userID);
    $steamID64 = $steamIDmanipulation->getSteamID64();

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    checkLogin_v2();
    if (empty($_SESSION['user_id64'])) throw new Exception('Not logged in!');

    $adminCheck = adminCheck($_SESSION['user_id64'], 'admin');
    if (empty($adminCheck)) throw new Exception('Not an admin!');

    echo '<h2>Add Site User</h2>';
    echo '<p>This is a tool for adding a user to the site user cache.</p>';

    $playerDBStatus = updateUserDetails($steamID64, $api_key3);

    var_dump($playerDBStatus);
} catch (Exception $e) {
    echo formatExceptionHandling($e);
} finally {
    if (isset($memcache)) $memcache->close();
}