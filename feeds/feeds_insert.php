<?php
require_once('../global_functions.php');
require_once('../connections/parameters.php');

if (!isset($_SESSION)) {
    session_start();
}

if (isset($_COOKIE['session']) && empty($_SESSION['user_id64'])) {
    checkLogin_v2();
}

try {
    if (!empty($_SESSION['user_id64'])) {
        $db = new dbWrapper($hostname_gds_feeds, $username_gds_feeds, $password_gds_feeds, $database_gds_feeds, true);
        if ($db) {
            $accessCheck = $db->q('SELECT * FROM `access_list` WHERE `steam_id64` = ? LIMIT 0,1;',
                'i',
                $_SESSION['user_id64']);

            if (!empty($accessCheck)) {
                if (!empty($_POST['feed_title']) && !empty($_POST['feed_url'])) {

                    $feed_title = $db->escape($_POST['feed_title']);
                    $feed_url = $db->escape($_POST['feed_url']);
                    $feed_category = $db->escape($_POST['feed_category']);

                    $insertSQL = $db->q('INSERT INTO `feeds_list` (`feed_title`, `feed_url`, `feed_category`) VALUES (?, ?, ?);',
                        'ssi',
                        $feed_title, $feed_url, $feed_category);

                    if ($insertSQL) {
                        echo 'Insert Success!';
                    } else {
                        echo 'Insert Failure!';
                    }
                } else {
                    echo 'One or more of the required variables are missing or empty!';
                }
            } else {
                echo 'This user account does not have access!';
            }

        } else {
            echo 'No DB';
        }
    } else {
        echo 'Not logged in!';
    }
} catch (Exception $e) {
    echo $e->getMessage();
}