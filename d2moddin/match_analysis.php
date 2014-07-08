<?php
require_once('./functions.php');
require_once('../global_functions.php');
require_once('../connections/parameters.php');

$start = time();

if (!isset($_SESSION)) {
    session_start();
}

try {
    $db = new dbWrapper($hostname_d2moddin, $username_d2moddin, $password_d2moddin, $database_d2moddin, true);
    if ($db) {
        $memcache = new Memcache;
        $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

        $match = file_get_contents('./formatted-subset.txt');
        $match = json_decode($match, true);

        echo '<pre>';
        print_r($match);
        echo '</pre>';


    } else {
        echo 'No DB';
    }
} catch (Exception $e) {
    echo $e->getMessage();
}