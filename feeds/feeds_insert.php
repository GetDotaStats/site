<?php
require_once('../global_functions.php');
require_once('../connections/parameters.php');

try {
    $db = new dbWrapper($hostname_gds_feeds, $username_gds_feeds, $password_gds_feeds, $database_gds_feeds, false);
    if ($db) {
        if (!empty($_POST['feed_title']) && !empty($_POST['feed_url'])) {

            $feed_title = $db->escape($_POST['feed_title']);
            $feed_url = $db->escape($_POST['feed_url']);
            $feed_category = $db->escape($_POST['feed_category']);

            $insertSQL = $db->q('INSERT INTO `feeds_list` (`feed_title`, `feed_url`, `feed_category`) VALUES (?, ?, ?);',
                'ssi',
                $feed_title, $feed_url, $feed_category);

            if($insertSQL){
                echo 'Insert Success!';
            }
            else{
                echo 'Insert Failure!';
            }
        } else {
            echo 'One or more of the required variables are missing or empty!';
        }
    } else {
        echo 'No DB!';
    }
} catch (Exception $e) {
    echo $e->getMessage();
}