<?php
require_once('./functions.php');
require_once('./connections/parameters.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
    <meta charset="utf-8">
    <meta content="text/html">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="/favicon.ico">
    <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../getdotastats.css" rel="stylesheet">

    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
    <title>GetDotaStats - Dota 2 Statistics</title>
</head>

<body>
<div class="navbar navbar-default navbar-fixed-top" role="navigation">
    <div class="container">
        <div class="navbar-collapse collapse">
            <ul class="nav navbar-nav">
                <li class="active"><a class="nav-clickable" href="../">Home</a></li>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">Match Analysis <b class="caret"></b></a>
                    <ul class="dropdown-menu">
                        <li><a class="nav-clickable" href="../match_analysis/">Home</a></li>
                        <li><a class="nav-clickable" href="../match_analysis/general_stats.php">General Stats</a></li>
                        <li><a class="nav-clickable" href="../match_analysis/game_modes.php">Game Modes</a></li>
                        <li><a class="nav-clickable" href="../match_analysis/clusters.php">Region Breakdown</a></li>
                        <li class="divider"></li>
                        <li class="dropdown-header">Misc.</li>
                        <li><a class="nav-clickable" href="../match_analysis/worker_progress.php">Data Collector
                                Status</a>
                        </li>
                    </ul>
                </li>
                <li><a href="../steamtracks/">Signature Generator</a></li>
                <li><a class="nav-clickable" href="../dbe/">Dotabuff Extended</a></li>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">Simulations <b class="caret"></b></a>
                    <ul class="dropdown-menu">
                        <li><a>Axe Spins</a></li>
                        <li><a>Shield Block</a></li>
                    </ul>
                </li>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">Dead Projekts <b class="caret"></b></a>
                    <ul class="dropdown-menu">
                        <li><a class="nav-clickable" href="../replays/">Replay Archive</a></li>
                        <li><a class="nav-clickable" href="../economy_analysis/">Economy Analysis</a></li>
                        <li>D2Ware</li>
                    </ul>
                </li>
                <li><a class="nav-clickable" href="../game_servers.php">Game Servers</a></li>
                <li><a class="nav-clickable" href="../contact.php">Contact</a></li>
            </ul>
        </div>
    </div>
</div>
<div class="clear"></div>

<div class="container">
    <div class="row">
        <div class="col-sm-8 blog-main">
            <div class="blog-post">
                <?php
                try {
                    if (!isset($_SESSION)) {
                        session_start();
                    }

                    $db = new dbWrapper($hostname, $username, $password, $database, false);
                    $steamtracks = new steamtracks($steamtracks_api_key, $steamtracks_api_secret, false);

                    if (!empty($_SESSION['user_id'])) {
                        $steamid64 = $_SESSION['user_id'];
                        $steamid32 = convert_steamid($steamid64);
                    }

                    $user_details = !empty($_SESSION['user_details'])
                        ? $_SESSION['user_details']
                        : NULL;

                    $user_name = !empty($user_details->personaname)
                        ? $user_details->personaname
                        : NULL;

                    if ($_GET['status'] == 'success' && !empty($steamid32)) {
                        $file_name_location = '../sig/images/generated/' . $steamid32 . '.png';

                        if (file_exists($file_name_location)) {
                            @unlink($file_name_location);
                        }
                    }


                    if (empty($steamid32)) {
                        echo 'To get your own Dota2 signature, login via steam. Logging in does not grant us access to your private stats, like MMR. After logging in, you will be presented with your signature and also have the option of adding your MMR to your signature via SteamTracks OAuth.<br /><br />';
                        echo '<a href="./auth/?login"><img src="./assets/images/steam_small.png" alt="Sign in with Steam"/></a><br /><br />';

                    } else {
                        echo '<strong>Logged in as:</strong> ' . $user_name . '<br />';

                        echo '<a href="./auth/?logout">Logout</a><br /><br />';

                        echo '<img src="http://getdotastats.com/sig/' . $steamid32 . '.png" /><br />';
                        echo '<strong>Your signature link:</strong> <a target="__new" href="http://getdotastats.com/sig/' . $steamid32 . '.png">http://getdotastats.com/sig/' . $steamid32 . '.png</a><br /><br />';

                        echo 'Signatures are cached for up to 2hours. MMR stats are updated every 12hours. As long as you have the bot added, do not fret! Your stats will eventually update.<br /><br />';

                        echo '<strong>Adding MMR to your sig:</strong><br />';
                    }

                    $gotDBstats = $db->q(
                        'SELECT * FROM `mmr` WHERE `steam_id` = ? LIMIT 0,1;',
                        'i',
                        $steamid32
                    );

                    if (((!isset($_GET['status']) && empty($gotDBstats)) || $_GET['status'] == 'readd') && !empty($steamid32)) {
                        $token_response = $steamtracks->signup_token($steamid32, 'true'); //GET TOKEN

                        if (!empty($token_response['result']['token'])) {
                            $token = $token_response['result']['token'];
                            echo '<br /><br /><a href="https://steamtracks.com/appauth/' . $token . '">CLICK HERE TO GIVE US ACCESS TO ADD YOUR MRR TO THE ABOVE SIGNATURE</a><br /><br />';
                        } else {
                            var_dump($token_response);
                        }
                    } else if (!empty($gotDBstats)) {
                        echo 'We already have stats for you. If you removed yourself from the app, you can <a href="./?status=readd">re-add yourself here</a>.<br />';
                    } else if (isset($_GET['status'])) {
                        switch ($_GET['status']) {
                            case 'success':
                                echo 'Sucessfully enrolled as new user!';
                                break;
                            case 'sqlfailure':
                                echo 'Could not insert your stats into database. This means that we may already have stats for you. If you can see the app listed under your steamtracks apps list, and you have the bot added, then we will automatically grab your stats later.';
                                break;
                            case 'apifailure':
                                echo 'Failure receiving account stats. If you signed up correctly, we will retry grabbing your stats automatically at a later date.';
                                break;
                            case 'missingidtoken':
                                echo 'Missing steam_id or token. <a href="./">Please try again.</a>';
                                break;
                        }
                        echo '<br />';
                    }
                } catch (Exception $e) {
                    echo $e->getMessage();
                }
                ?>
            </div>
        </div>

        <div class="col-sm-3 col-sm-offset-1 blog-sidebar">
            <div class="sidebar-module sidebar-module-inset">
                <!-- Begin chatwing.com chatbox -->
                <iframe src="http://chatwing.com/chatbox/f220203c-c1fa-4ce9-a840-c90a3a2edb9d" width="100%" height="600"
                        frameborder="0" scrolling="0">Embedded chat
                </iframe>
                <!-- End chatwing.com chatbox -->
            </div>
        </div>
    </div>
</div>

<div id="footer">
    <div class="container">
        <p class="text-muted">Built by jimmydorry. Dota 2 is a registered trademark of Valve Corporation. Powered by
            Steam.</p>
    </div>
</div>

<script src="../bootstrap/js/jquery-1-11-0.min.js"></script>
<script src="../bootstrap/js/bootstrap.min.js"></script>
</body>
</html>
