<?php
require_once('../connections/parameters.php');
require_once('./functions.php');

try {
    $db = new dbWrapper($hostname_d2moddin, $username_d2moddin, $password_d2moddin, $database_d2moddin, true);

    $stats = json_decode(curl('http://ddp2.d2modd.in/stats/general', NULL, NULL, NULL, NULL, 20), 1);

    echo '<pre>';
    print_r($stats);
    echo '</pre>';
} catch (Exception $e) {
    echo $e->getMessage();
}