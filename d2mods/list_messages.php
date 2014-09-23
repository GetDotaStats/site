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
    <script src="//oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <script src="//oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
    <title>GetDotaStats - Dota 2 Statistics</title>
    <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script>
    <script type="text/javascript" src="//static.getdotastats.com/getdotastats.js"></script>
</head>
<body>

<h2>Don't forget that you can look at the raw messages (from terminal) <a href="./log-test.html" target="_blank">test</a> || <a href="./log-live.html" target="_blank">live</a>.
</h2>

<?php
    if (!empty($_SESSION['user_id64'])) {
        $db = new dbWrapper_v2($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site);
        if ($db) {
            $messages = $db->q('SELECT * FROM `node_listener` ORDER BY date_recorded DESC;');

            echo '<div class="table-responsive">
                    <table class="table table-striped table-hover">';
            echo '<tr>
                            <th width="50">&nbsp;</th>
                            <th>Message</th>
                            <th width="100">IP</th>
                            <th width="120">Recorded</th>
                        </tr>';
            foreach ($messages as $key => $value) {
                echo '<tr>
                            <td>' . $value['test_id'] . '</td>
                            <td>' . stripslashes($value['message']) . '</td>
                            <td>' . $value['remote_ip'] . '</td>
                            <td>' . relative_time($value['date_recorded']) . '</td>
                        </tr>';
            }
            echo '</table></div>';

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
<script src="//static.getdotastats.com/bootstrap/js/jquery-1-11-0.min.js"></script>
<script src="//static.getdotastats.com/bootstrap/js/bootstrap.min.js"></script>
</body>
</html>