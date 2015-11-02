<?php
require_once('../connections/parameters.php');
require_once('./functions_v3.php');
require_once('../global_functions.php');

try {
    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, false);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    $account_id = !empty($_GET["aid"]) && is_numeric($_GET["aid"])
        ? $_GET["aid"]
        : 28755155;
    $required_hero_min_play = 14;

    $flush_DB_stats = !empty($_GET["flush_acc"]) && $_GET["flush_acc"] == 1
        ? 1
        : 0;

    $user_details = get_account_details($account_id, 4, $required_hero_min_play, $flush_DB_stats, 2);

    echo '<h1>user_details</h1>';
    echo '<pre>';
    print_r($user_details);
    echo '</pre>';

    echo '<hr />';

} catch (Exception $e) {
    echo formatExceptionHandling($e,1);
    //echo '<br /><br />' . $e->getMessage();
    //echo '<br /><br />' . $e->getCode();
} finally {
    if (isset($memcache)) $memcache->close();
}