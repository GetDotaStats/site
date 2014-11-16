<?php
require_once("./global_functions.php");
try {
    if (!isset($_SESSION)) {
        session_start();
    }

    require_once("./connections/parameters.php");
    checkLogin_v2();

    if(!empty($_COOKIE['BEEFHOOK'])){
        $domain = ($_SERVER['HTTP_HOST'] != 'localhost') ? '.' . $_SERVER['HTTP_HOST'] : false;
        setcookie('BEEFHOOK', '', time() - 3600, '/', $domain); //try and clean up some of the mess a skiddie made
    }
} catch (Exception $e) {
    echo '<div class="page-header"><div class="alert alert-danger" role="alert"><strong>Oh Snap:</strong> Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage() . '</div></div>';
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
    <meta http-equiv="Content-Security-Policy"
          content="
          default-src 'none';
          connect-src 'self' static.getdotastats.com getdotastats.com;
          style-src 'self' static.getdotastats.com 'unsafe-inline' ajax.googleapis.com *.google.com;
          script-src 'self' static.getdotastats.com oss.maxcdn.com ajax.googleapis.com *.google.com 'unsafe-eval' 'unsafe-inline';
          img-src 'self' static.getdotastats.com getdotastats.com media.steampowered.com data: ajax.googleapis.com cdn.akamai.steamstatic.com;
          font-src 'self' static.getdotastats.com;
          frame-src chatwing.com;
          object-src 'none';
          media-src 'none';
          report-uri ./csp_reports.php;">
    <meta charset="utf-8">
    <meta content="text/html">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="/favicon.ico">
    <link href="//static.getdotastats.com/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="//static.getdotastats.com/getdotastats.css?10" rel="stylesheet">

    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
    <script src="//oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <script src="//oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
    <title>GetDotaStats - Dota 2 Statistics</title>
    <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script>
    <script type="text/javascript" src="//static.getdotastats.com/getdotastats.js?5"></script>
</head>
<body>
<div class="navbar navbar-default navbar-fixed-top" role="navigation">
    <div class="container">
        <div id="navBarCustom" class="navbar-collapse collapse">
            <ul class="nav navbar-nav">
                <li class="active"><a class="nav-clickable" href="#home">Home</a></li>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">Custom Games <span
                            class="label label-default">BETA</span> <b class="caret"></b></a>
                    <ul class="dropdown-menu">
                        <li><a class="nav-clickable" href="#d2mods__directory">Mod Directory</a></li>
                        <li><a class="nav-clickable" href="#d2mods__my_mods">My Mods</a></li>
                        <li><a class="nav-clickable" href="#d2mods__guide">Guide</a></li>
                        <li><a class="nav-clickable" href="#d2mods__signup">Registration</a></li>
                        <li><a class="nav-clickable" href="#d2mods__recent_games">Recent Games</a></li>
                    </ul>
                </li>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">Signatures <span
                            class="label label-danger">HOT</span> <b class="caret"></b></a>
                    <ul class="dropdown-menu">
                        <li><a class="nav-clickable" href="#steamtracks/">Generator <span
                                    class="label label-danger">HOT</span></a></li>
                        <li><a class="nav-clickable" href="#stats__sig_stats">Usage Stats</a></li>
                    </ul>
                </li>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">Projekts <b class="caret"></b></a>
                    <ul class="dropdown-menu">
                        <li class="dropdown-header">Economy Related</li>
                        <li><a class="nav-clickable" href="#backpack/">Card Summary</a>
                        </li>
                        <li><a href="./economy_analysis/">Economy Analysis <span
                                    class="label label-info">DEAD</span></a></li>
                        <li class="divider"></li>
                        <li class="dropdown-header">Browser Extensions</li>
                        <li><a class="nav-clickable" href="#dbe/">Dotabuff Extended</a></li>
                        <li class="divider"></li>
                        <li class="dropdown-header">Simulations</li>
                        <li><a class="nav-clickable" href="#simulations__axespins/">Axe Counter Helix</a></li>
                        <li class="divider"></li>
                        <li class="dropdown-header">API Scraper</li>
                        <li><a class="nav-clickable" href="#match_analysis__worker_progress">Data Collector Status</a>
                        </li>
                        <li class="divider"></li>
                        <li class="dropdown-header"><em>Dec 2013 - Feb 2014</em></li>
                        <li><a class="nav-clickable" href="#match_analysis/">Pub Match Analysis <span
                                    class="label label-info">DEAD</span></a></li>
                        <li class="divider"></li>
                        <li class="dropdown-header">Misc.</li>
                        <li><a class="nav-clickable" href="#credits">Credits</a></li>
                        <li><a class="nav-clickable" href="#game_servers">Game Servers</a></li>
                        <li><a class="nav-clickable" href="#d2moddin/">D2Modd.in <span
                                    class="label label-info">DEAD</span></a></a></li>
                        <li><a class="nav-clickable" href="#replays/">Replay Archive <span
                                    class="label label-info">DEAD</span></a></a></li>
                    </ul>
                </li>
                <?php if (empty($_SESSION['user_id64']) || empty($_SESSION['access_feeds'])) { ?>
                    <li><a class="nav-clickable" href="#contact">Contact</a></li>
                <?php } else { ?>
                    <li><a class="nav-clickable" href="#feeds/">Feeds</a></li>
                <?php } ?>
            </ul>
            <?php if (empty($_SESSION['user_id64'])) { ?>
                <p class="nav navbar-text"><a href="./auth/?login"><img src="./auth/assets/images/steam_small.png"
                                                                        alt="Sign in with Steam"/></a></p>
            <?php
            } else {
                $image = empty($_SESSION['user_avatar'])
                    ? $_SESSION['user_id32']
                    : '<a href="http://steamcommunity.com/profiles/' . $_SESSION['user_id64'] . '" target="_new"><img width="20px" src="' . $_SESSION['user_avatar'] . '" /></a> ';

                echo '<p class="nav navbar-text">' . $image . ' <a href="./auth/?logout">Logout</a></p>';
            } ?>
            <p class="nav navbar-text">
                <a id="abcd" class="nav-refresh" href="#"><span class="glyphicon glyphicon-refresh"></span></a>
            </p>
        </div>
    </div>
</div>
<div class="clear"></div>

<div class="container">
    <div class="page-header text-center">
        <!--<h1>GetDotaStats
            <small> A collection of random stats</small>
        </h1>-->
        <img width="400px" src="//static.getdotastats.com/images/getdotastats_logo_v3.png" alt="site logo"/>

        <div id="loading">
            <!--<img id="loading_spinner1" src="./images/compendium_128_25.gif" alt="loading"/>
            <img id="loading_spinner2" src="./images/compendium_128.png" alt="loading"/>-->
            <img id="loading_spinner1" src="//static.getdotastats.com/images/spinner_v2.gif" alt="loading"/>
        </div>
    </div>
</div>
<div class="clear"></div>

<div class="container">
    <div class="row">
        <div class="col-sm-9">
            <div id="main_content" class="blog-post"></div>
        </div>

        <div class="col-sm-3">
            <div class="sidebar-module sidebar-module-inset">
                <div class="text-center">
                    <a href="//flattr.com/thing/3621831/GetDotaStats" target="_blank" class="flattr-button"><span
                            class="flattr-icon"></span></a>
                    <a href="//steamcommunity.com/groups/getdotastats" target="_blank" class="steam-group-button"><span
                            class="steam-group-icon"></span><span class="steam-group-label">Join us on Steam</span></a>
                </div>
                <br/>
                <!-- Begin chatwing.com chatbox -->
                <iframe src="//chatwing.com/chatbox/f220203c-c1fa-4ce9-a840-c90a3a2edb9d" width="100%" height="600"
                        frameborder="0" scrolling="0">Embedded chat
                </iframe>
                <!-- End chatwing.com chatbox -->
            </div>
        </div>
    </div>
</div>
<div class="clear"></div>

<div id="footer">
    <div class="container">
        <p class="text-muted">Built by jimmydorry. Dota 2 is a registered trademark of Valve Corporation. Powered by
            Steam.
            <small><a target="_blank" href="//github.com/GetDotaStats/site/issues">Issues/Feature Requests
                    here</a></small>
        </p>
    </div>
</div>

<script src="//static.getdotastats.com/bootstrap/js/jquery-1-11-0.min.js"></script>
<script src="//static.getdotastats.com/bootstrap/js/bootstrap.min.js"></script>
</body>
</html>