<?php
require_once('../../global_functions.php');
require_once('../../connections/parameters.php');

try {
    $big_array = array();
    $big_array['gets'] = $_GET;
    $big_array['posts'] = $_POST;

} catch (Exception $e) {
    unset($big_array);
    $big_array['error'] = 'Caught Exception: ' . $e->getMessage() . '<br /> Contact getdotastats.com';
}

try {
    header('Content-Type: application/json');
    echo utf8_encode(json_encode($big_array));
} catch (Exception $e) {
    unset($big_array);
    $big_array['error'] = 'Contact getdotastats.com - Caught Exception: ' . $e->getMessage();
    echo utf8_encode(json_encode($big_array));
}