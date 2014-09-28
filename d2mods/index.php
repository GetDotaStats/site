<?php
require_once('../global_functions.php');
require_once('../connections/parameters.php');

try {
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
    <meta charset="utf-8">
    <meta content="text/html">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="/favicon.ico">
    <link href="//static.getdotastats.com/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="//static.getdotastats.com/getdotastats.css" rel="stylesheet">

    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
    <script type="text/javascript" src="//oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <script type="text/javascript" src="//oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
    <title>GetDotaStats - Dota 2 Statistics</title>
    <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script>
    <script type="text/javascript" src="//static.getdotastats.com/getdotastats.js"></script>
</head>
<body>
<div class="container">
    <div class="row">
        <?php
        $messageID = empty($_GET['custom_match']) || !is_numeric($_GET['custom_match'])
            ? NULL
            : $_GET['custom_match'];

        $db = new dbWrapper_v2($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site);

        if ($db) {
            if (!empty($messageID)) {

                $messages = $db->q(
                    'SELECT * FROM `node_listener` WHERE `test_id` = ? LIMIT 0,1;',
                    'i',
                    $messageID
                );

                if (!empty($messages)) {
                    $messages = $messages[0];

                    echo '<pre>';
                    echo '<h3>Recorded from ' . $messages['remote_ip'] . ':' . $messages['remote_port'] . ' <small>' . relative_time($messages['date_recorded']) . '</small></h3>';
                    echo json_encode(json_decode($messages['message'],1), JSON_PRETTY_PRINT);
                    echo '</pre>';
                } else {
                    echo '<div class="page-header"><div class="alert alert-danger" role="alert"><strong>Oh Snap:</strong> Message not found!</div></div>';
                }
            } else {
                echo '<div class="page-header"><div class="alert alert-danger" role="alert"><strong>Oh Snap:</strong> No message ID!</div></div>';
            }
        } else {
            echo '<div class="page-header"><div class="alert alert-danger" role="alert"><strong>Oh Snap:</strong> No DB!</div></div>';
        }

        } catch
        (Exception $e) {
            echo '<div class="page-header"><div class="alert alert-danger" role="alert"><strong>Oh Snap:</strong> Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage() . '</div></div>';
        }
        ?>
    </div>
</div>
<script type="text/javascript" src="//static.getdotastats.com/bootstrap/js/jquery-1-11-0.min.js"></script>
<script type="text/javascript" src="//static.getdotastats.com/bootstrap/js/bootstrap.min.js"></script>
</body>
</html>