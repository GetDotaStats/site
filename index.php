<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
    <meta charset="utf-8">
    <meta content="text/html">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="/favicon.ico">
    <link href="./bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="./getdotastats.css" rel="stylesheet">

    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
    <title>GetDotaStats - Dota 2 Statistics</title>
    <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script>
    <script type="text/javascript" src="./getdotastats.js"></script>
</head>
<?php
if (!isset($_SESSION)) {
    session_start();
}
?>
<body>
<div class="navbar navbar-default navbar-fixed-top" role="navigation">
    <div class="container">
        <div class="navbar-collapse collapse">
            <ul class="nav navbar-nav">
                <li class="active"><a class="nav-clickable" href="#home">Home</a></li>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">D2Modd.in Stats <span class="label label-default">NEW</span> <b class="caret"></b></a>
                    <ul class="dropdown-menu">
                        <li><a class="nav-clickable" href="#d2moddin__lobbies">Lobby Types</a></li>
                        <li><a class="nav-clickable" href="#d2moddin__mods">Lobbies per Mod</a></li>
                        <li><a class="nav-clickable" href="#d2moddin__regions">Lobbies per Region</a></li>
                        <li><a class="nav-clickable" href="#d2moddin__servers">Lobbies per Server</a></li>
                        <li><a class="nav-clickable" href="#d2moddin__queue">Queue Join Rate</a></li>

                    </ul>
                </li>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">Match Analysis <b class="caret"></b></a>
                    <ul class="dropdown-menu">
                        <li><a class="nav-clickable" href="#match_analysis/">Overview</a></li>
                        <li><a class="nav-clickable" href="#match_analysis__general_stats">General Stats</a></li>
                        <li><a class="nav-clickable" href="#match_analysis__game_modes">Game Modes</a></li>
                        <li><a class="nav-clickable" href="#match_analysis__clusters">Region Breakdown</a></li>
                        <li class="divider"></li>
                        <li class="dropdown-header">Misc.</li>
                        <li><a class="nav-clickable" href="#match_analysis__worker_progress">Data Collector Status</a>
                        </li>
                    </ul>
                </li>
                <li><a class="nav-clickable" href="#steamtracks/">Signature Generator</a></li>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">Projekts <span class="label label-default">NEW</span> <b class="caret"></b></a>
                    <ul class="dropdown-menu">
                        <li class="dropdown-header">Economy Related</li>
                        <li><a class="nav-clickable" href="#backpack/">Card Summary <span class="label label-default">NEW</span></a></li>
                        <li><a href="./economy_analysis/">Economy Analysis <span class="label label-info">DEAD</span></a></li>
                        <li class="divider"></li>
                        <li class="dropdown-header">Browser Extensions</li>
                        <li><a class="nav-clickable" href="#dbe/">Dotabuff Extended</a></li>
                        <li class="divider"></li>
                        <li class="dropdown-header">Simulations</li>
                        <li><a class="nav-clickable" href="#simulations__axespins/">Axe Counter Helix</a></li>
                        <li class="divider"></li>
                        <li class="dropdown-header">Misc.</li>
                        <li><a class="nav-clickable" href="#game_servers">Game Servers</a></li>
                        <li><a class="nav-clickable" href="#replays/">Replay Archive <span class="label label-info">DEAD</span></a></a></li>
                    </ul>
                </li>
                <li><a class="nav-clickable" href="#contact">Contact</a></li>
            </ul>
        </div>
    </div>
</div>
<div class="clear"></div>

<div class="container">
    <div class="jumbotron text-center">
        <h1>Welcome to GetDotaStats!</h1>

        <div id="loading">
            <img id="loading_spinner1" src="./images/compendium_128_25.gif" alt="loading"/>
            <img id="loading_spinner2" src="./images/compendium_128.png" alt="loading"/>
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
                <!-- Begin chatwing.com chatbox -->
                <iframe src="http://chatwing.com/chatbox/f220203c-c1fa-4ce9-a840-c90a3a2edb9d" width="100%" height="600"
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
            <small><a target="_blank" href="https://github.com/GetDotaStats/site/issues">Issues/Feature Requests
                    here</a></small>
        </p>
    </div>
</div>

<script src="./bootstrap/js/jquery-1-11-0.min.js"></script>
<script src="./bootstrap/js/bootstrap.min.js"></script>
</body>
</html>