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
        <div class="page-header text-center">
            <h2>Terminal out for:
                <a href="./log-test.html" target="_blank">test</a>
                ||
                <a href="./log-live.html" target="_blank">live</a></h2>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <p><a href="../">Return to main site</a></p>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <?php
            if (!empty($_SESSION['user_id64'])) {
                $db = new dbWrapper_v2($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site);
                if ($db) {
                    $messages = $db->q('SELECT * FROM `node_listener` ORDER BY date_recorded DESC LIMIT 0,50;');
                    $mods = $db->q('SELECT * FROM `mod_list`;');

                    $modList = array();
                    foreach ($mods as $key => $value) {
                        $modList[$value['mod_identifier']]['mod_name'] = $value['mod_name'];
                        $modList[$value['mod_identifier']]['mod_workshop_link'] = $value['mod_workshop_link'];
                    }

                    $modCount = array();

                    $table = '';
                    $table .= '<div class="table-responsive">
                        <table class="table table-striped table-hover" style="word-wrap:break-word; table-layout:fixed;">';
                    $table .= '<tr>
                            <th width="50">&nbsp;</th>
                            <th>Message</th>
                            <th width="150">Mod</th>
                            <th width="100">Match ID</th>
                            <th width="70">Players</th>
                            <th width="115">IP</th>
                            <th width="120">Recorded</th>
                        </tr>';
                    foreach ($messages as $key => $value) {
                        $json = json_decode($value['message'], 1);

                        $modName = !empty($json)
                            ? $modList[$json['modID']]['mod_name']
                            : '??';

                        $modMatchId = !empty($json)
                            ? $json['matchID']
                            : '??';

                        $modPlayers = !empty($json)
                            ? count($json['rounds']['players'])
                            : '??';

                        if (!empty($modCount[$modName])) {
                            $modCount[$modName]++;
                        } else {
                            $modCount[$modName] = 1;
                        }

                        $table .= '<tr>
                            <td>' . $value['test_id'] . '</td>
                            <td>' . stripslashes($value['message']) . '</td>
                            <td>' . $modName . '</td>
                            <td>' . $modMatchId . '</td>
                            <td class="text-center">' . $modPlayers . '</td>
                            <td>' . $value['remote_ip'] . '<br />' . $value['remote_port'] . '</td>
                            <td>' . relative_time($value['date_recorded']) . '</td>
                        </tr>';
                    }
                    $table .= '</table></div>';

                    $table2 = '';
                    $table2 .= '<div class="col-sm-6">
                        <div class="table-responsive">
                        <table class="table table-striped table-hover table-condensed" style="width: auto !important">';
                    $table2 .= '<tr>
                            <th>Mod</th>
                            <th>Frequency</th>
                        </tr>';
                    foreach ($modCount as $key => $value) {
                        $table2 .= '<tr>
                            <td>' . $key . '</td>
                            <td class="text-center">' . $value . '</td>
                        </tr>';
                    }
                    $table2 .= '</table></div></div>';

                    echo $table2;

                    echo '<br />';

                    echo $table;

                } else {
                    echo '<div class="page-header"><div class="alert alert-danger" role="alert"><strong>Oh Snap:</strong> No DB!</div></div>';
                }
            } else {
                echo '<div class="page-header"><div class="alert alert-danger" role="alert"><strong>Oh Snap:</strong> Not logged in!</div></div>';
                echo '<a href="../">Go back to main site</a>';
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