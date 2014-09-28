<?php
require_once('../global_functions.php');
require_once('../connections/parameters.php');

try {
if (!isset($_SESSION)) {
    session_start();
}

if (isset($_COOKIE['session']) && empty($_SESSION['user_id64'])) {
    checkLogin_v2();
}
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
        <div class="col-sm-12">
            <?php
            $modID = empty($_GET['id']) || !is_numeric($_GET['id'])
                ? NULL
                : $_GET['id'];


            $db = new dbWrapper_v2($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site);
            if ($db) {
                $mods = $db->q('SELECT * FROM `mod_list` WHERE `mod_active` = 1;');

                $modCheckList = array();

                echo '<div class="col-sm-6">
                        <div class="table-responsive">
                        <table class="table table-striped table-hover table-condensed" style="width: auto !important">';
                foreach ($mods as $key => $value) {
                    $modCheckList[$value['mod_id']] = $value['mod_identifier'];
                    echo '<tr><td><a href="./mod_breakdown.php?id=' . $value['mod_id'] . '">' . $value['mod_name'] . '</a></td></tr>';
                }
                echo '</table></div></div>';


                if ((!empty($modID) && !empty($modCheckList[$modID])) || empty($modID)) {

                    if (empty($modID)) {
                        $messages = $db->q('SELECT * FROM `node_listener` ORDER BY date_recorded DESC LIMIT 0,50;');
                    } else {
                        $messages = $db->q('SELECT * FROM `node_listener` WHERE `mod_id` = ? ORDER BY date_recorded DESC LIMIT 0,50;',
                            's',
                            $modCheckList[$modID]
                        );

                        echo '<h3>' . $modCheckList[$modID] . ' <small>Last 50 games</small></h3>';
                    }

                    $table = '';
                    $table .= '<div class="table-responsive">
                        <table class="table table-striped table-hover" style="width: auto !important">';
                    $table .= '<tr>
                            <th width="50">&nbsp;</th>
                            <th width="100">Match ID</th>
                            <th width="115">IP</th>
                            <th width="120">Recorded</th>
                        </tr>';

                    $jsonMessage = array();

                    foreach ($messages as $key => $value) {
                        $json = json_decode($value['message'], 1);

                        $modMatchId = !empty($json)
                            ? $json['matchID']
                            : '??';

                        $jsonMessage[$key]['message_id'] = $value['test_id'];
                        $jsonMessage[$key]['match_id'] = $modMatchId;
                        $jsonMessage[$key]['remote_ip'] = $value['remote_ip'];
                        $jsonMessage[$key]['date_recorded'] = $value['date_recorded'];

                        $table .= '<tr>
                            <td>' . $value['test_id'] . '</td>
                            <td>' . $modMatchId . '</td>
                            <td>' . $value['remote_ip'] . '<br />' . $value['remote_port'] . '</td>
                            <td>' . relative_time($value['date_recorded']) . '</td>
                        </tr>';
                    }
                    $table .= '</table></div>';

                    echo '<br />';

                    echo $table;

                    echo '<hr />';

                    echo '<pre>' . json_encode($jsonMessage, JSON_PRETTY_PRINT) . '</pre>';
                } else {
                    echo '<div class="page-header"><div class="alert alert-danger" role="alert"><strong>Oh Snap:</strong> Bad mod id!</div></div>';
                }
            } else {
                echo '<div class="page-header"><div class="alert alert-danger" role="alert"><strong>Oh Snap:</strong> No DB!</div></div>';
            }
            } catch (Exception $e) {
                echo '<div class="page-header"><div class="alert alert-danger" role="alert"><strong>Oh Snap:</strong> Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage() . '</div></div>';
            }
            ?>
        </div>
    </div>
</div>
<script type="text/javascript" src="//static.getdotastats.com/bootstrap/js/jquery-1-11-0.min.js"></script>
<script type="text/javascript" src="//static.getdotastats.com/bootstrap/js/bootstrap.min.js"></script>
</body>
</html>