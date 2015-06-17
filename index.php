<?php
try {
    require_once("./global_functions.php");
    require_once("./connections/parameters.php");

    if (!isset($_SESSION)) {
        session_start();
    }

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, false);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    checkLogin_v2();

    $adminCheck = !empty($_SESSION['user_id64'])
        ? adminCheck($_SESSION['user_id64'], 'admin')
        : false;

    $feedCheck = !empty($_SESSION['user_id64'])
        ? adminCheck($_SESSION['user_id64'], 'animufeed')
        : false;
} catch (Exception $e) {
    $message = 'Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage();
    echo bootstrapMessage('Oh Snap', $message, 'danger');
}

try {
    if (!empty($memcache)) {
        $memcache->close();
    }
} catch (Exception $e) {
    $message = 'Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage();
    echo bootstrapMessage('Oh Snap', $message, 'danger');
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
          style-src 'self' static.getdotastats.com getdotastats.com 'unsafe-inline' ajax.googleapis.com *.google.com;
          script-src 'self' static.getdotastats.com getdotastats.com oss.maxcdn.com ajax.googleapis.com *.google.com *.google-analytics.com 'unsafe-eval' 'unsafe-inline' data:;
          img-src 'self' dota2.photography static.getdotastats.com getdotastats.com media.steampowered.com data: ajax.googleapis.com cdn.akamai.steamstatic.com cdn.dota2.com *.gstatic.com *.akamaihd.net  *.google-analytics.com *.steamusercontent.com;
          font-src 'self' static.getdotastats.com getdotastats.com;
          frame-src chatwing.com *.youtube.com *.mibbit.com;
          object-src 'none';
          media-src 'none';
          report-uri ./csp_reports.php;">
    <meta charset="utf-8">
    <meta content="text/html">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="/favicon.ico">
    <link rel="stylesheet" href="<?= $path_css_bootstrap_full ?>">
    <link rel="stylesheet" href="<?= $path_css_site_full ?>">

    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
    <script type="text/javascript" src="<?= $path_lib_html5shivJS_full ?>"></script>
    <script type="text/javascript" src="<?= $path_lib_respondJS_full ?>"></script>
    <![endif]-->
    <title>GetDotaStats - Dota 2 Statistics</title>
    <script type="text/javascript" src="<?= $path_lib_jQuery_full ?>"></script>
    <script type="text/javascript" src="<?= $path_lib_siteJS_full ?>"></script>
</head>
<body>
<div class="navbar navbar-default navbar-fixed-top" role="navigation">
    <div class="container">
        <div id="navBarCustom" class="navbar-collapse collapse">
            <ul class="nav navbar-nav">
                <li class="dropdown">
                    <!--<span class="label label-success">UPDATED</span>-->
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">Custom Games <span
                            class="label label-success">NEW</span><b class="caret"></b></a>
                    <ul class="dropdown-menu">
                        <li class="dropdown-header">Guides</li>
                        <li><a class="nav-clickable" href="#d2mods__lobby_guide">Lobby Explorer</a></li>
                        <li><a class="nav-clickable" href="#d2mods__guide">Mod Developer</a></li>
                        <li><a class="nav-clickable" href="#d2mods__guide">Dota 2 Reborn Changes <span
                                    class="label label-success">NEW</span></a></li>
                        <li><a class="nav-clickable" href="#d2mods__minigame_guide">Minigame Developer</a></li>
                        <li class="divider"></li>
                        <li class="dropdown-header">Lobby Explorer</li>
                        <li><a class="nav-clickable" href="#d2mods__lobby_list">Lobby List</a></li>
                        <li><a class="nav-clickable" href="#d2mods__lobby_graph">Trends</a></li>
                        <li class="divider"></li>
                        <li class="dropdown-header">Mod Section</li>
                        <li><a class="nav-clickable" href="#d2mods__directory">Directory</a></li>
                        <li><a class="nav-clickable" href="#d2mods__feedback">Feedback <span
                                    class="label label-danger">HOT</span></a></li>
                        <li><a class="nav-clickable" href="#d2mods__hof">Hall of Fame</a></li>
                        <li><a class="nav-clickable" href="#d2mods__mod_highscores">Highscores</a></li>
                        <li><a class="nav-clickable" href="#d2mods__recent_games">Recently Played Games</a></li>
                        <li><a class="nav-clickable" href="#d2mods__search">Search</a></li>
                        <li class="divider"></li>
                        <li class="dropdown-header">Mini Games Section</li>
                        <li><a class="nav-clickable" href="#d2mods__minigame_highscores">Highscores</a></li>
                        <?php if (!empty($_SESSION['user_id64'])) { ?>
                            <li class="divider"></li>
                            <li class="dropdown-header">My Section</li>
                            <li><a class="nav-clickable" href="#d2mods__my_mmr">My MMR</a></li>
                            <li><a class="nav-clickable" href="#d2mods__my_games">My Recent Games</a></li>
                            <li><a class="nav-clickable" href="#d2mods__my_mods">My Mods</a></li>
                            <li><a class="nav-clickable" href="#d2mods__my_minigames">My Mini Games</a></li>
                        <?php } ?>
                    </ul>
                </li>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">Projekts <b class="caret"></b></a>
                    <ul class="dropdown-menu">
                        <li class="dropdown-header">Signatures</li>
                        <li><a class="nav-clickable" href="#sig__generator">Generator</a></li>
                        <li><a class="nav-clickable" href="#sig__usage">Trends</a></li>
                        <li class="divider"></li>
                        <li class="dropdown-header">Halls of Fame</li>
                        <li><a class="nav-clickable" href="#hof__golden_profiles">Golden Profiles</a></li>
                        <li class="divider"></li>
                        <li class="dropdown-header">Browser Extensions</li>
                        <li><a class="nav-clickable" href="#dbe/">Dotabuff Extended</a></li>
                        <li class="divider"></li>
                        <li class="dropdown-header">Misc.</li>
                        <li><a class="nav-clickable" href="#credits">Credits</a></li>
                        <li><a class="nav-clickable" href="#game_servers">Game Servers</a></li>
                        <li><a class="nav-clickable" href="#d2moddin/">D2Modd.in <span
                                    class="label label-info">DEAD</span></a></li>
                        <li><a class="nav-clickable" href="#contact">Contact</a></li>
                    </ul>
                </li>
                <?php if (!empty($adminCheck)) { ?>
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown">Admin <b class="caret"></b></a>
                        <ul class="dropdown-menu">
                            <li class="dropdown-header">Management</li>
                            <li><a class="nav-clickable" href="#admin__mod_approve">Mod Approve</a></li>
                            <li><a class="nav-clickable" href="#admin__mod_edit">Mod Edit</a></li>
                            <li><a class="nav-clickable" href="#admin__mod_rejected">Mods Rejected</a></li>
                            <li><a class="nav-clickable" href="#admin__hs_mod">Mod Highscore Manage</a></li>
                            <li><a class="nav-clickable" href="#admin__minigames">Mini-Game Manage</a></li>
                            <li><a class="nav-clickable" href="#admin__mod_feedback">Mod Feedback</a></li>
                            <li class="divider"></li>
                            <li class="dropdown-header">Users</li>
                            <li><a class="nav-clickable" href="#admin__user_mmr">MMR List</a></li>
                            <li><a class="nav-clickable" href="#admin__user_mmr_graphs">MMR Graphs</a></li>
                            <li class="divider"></li>
                            <li class="dropdown-header">Misc.</li>
                            <li><a class="nav-clickable" href="#admin__moderator_list">Moderator List</a></li>
                            <li class="divider"></li>
                            <li class="dropdown-header">Lobbies</li>
                            <li><a class="nav-clickable" href="#admin__failed_lobbies">Failed Lobbies</a></li>
                            <li class="divider"></li>
                            <li class="dropdown-header">CSP Reports</li>
                            <li><a class="nav-clickable" href="#admin__csp_reports_filtered_lw">Last Week</a></li>
                            <li><a class="nav-clickable" href="#admin__csp_reports_filtered">Total</a></li>
                            <li><a class="nav-clickable" href="#admin__csp_reports">Last 100</a></li>
                            <?php if (!empty($feedCheck)) { ?>
                                <li class="divider"></li>
                                <li class="dropdown-header">Feeds</li>
                                <li><a class="nav-clickable" href="#feeds/">Animu</a></li>
                            <?php } ?>
                        </ul>
                    </li>
                <?php } ?>
            </ul>
            <?php if (empty($_SESSION['user_id64'])) { ?>
                <p class="nav navbar-text"><a href="./auth/?login"><img
                            src="<?= $CDN_generic ?>/auth/assets/images/steam_small.png"
                            alt="Sign in with Steam"/></a></p>
            <?php
            } else {
                $image = empty($_SESSION['user_avatar'])
                    ? $_SESSION['user_id32']
                    : '<a class="nav-clickable" href="#d2mods__profile?id=' . $_SESSION['user_id64'] . '"><img width="20px" src="' . $_SESSION['user_avatar'] . '" /></a> ';

                echo '<p class="nav navbar-text">' . $image . ' <a href="./auth/?logout">Logout</a></p>';
            } ?>
            <p class="nav navbar-text">
                <a id="nav-refresh-holder" class="nav-refresh" href="#home" title="Refresh page"><span
                        class="glyphicon glyphicon-refresh"></span></a>
            </p>
        </div>
    </div>
</div>
<div class="clear"></div>

<span class="h4 clearfix">&nbsp;</span>

<div class="container">
    <div class="text-center">
        <a class="nav-clickable" href="#d2mods__lobby_list"><img width="400px"
                                                                 src="<?= $CDN_generic ?>/images/getdotastats_logo_v3.png"
                                                                 alt="site logo"/></a>

        <div id="loading">
            <img id="loading_spinner1" src="<?= $CDN_generic ?>/images/spinner_v2.gif" alt="loading"/>
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

                <iframe width="100%" height="550" scrolling="no"
                        src="http://widget.mibbit.com/?settings=444700653d2683b29d7f0965230f38af&server=irc.web.gamesurge.net&channel=%23getdotastats-chat&autoConnect=false&delay=5&noServerMotd=true&noServerNotices=true&noServerTab=true&nick=gds_%3F%3F%3F%3F">
                    Embedded chat
                </iframe>
            </div>
        </div>
    </div>
</div>
<div class="clear"></div>

<div id="footer">
    <div class="container">
        <p class="text-muted">Built by jimmydorry. Dota 2 is a registered trademark of Valve Corporation. Powered by
            Steam.
            <small><a target="_blank" href="//github.com/GetDotaStats/GetDotaLobby/issues">Lobby Explorer Issues</a>
            </small>
            ||
            <small><a target="_blank" href="//github.com/GetDotaStats/site/issues">Site Issues</a></small>
        </p>
    </div>
</div>

<script type="text/javascript" src="<?= $path_lib_jQuery2_full ?>"></script>
<script type="text/javascript" src="<?= $path_lib_bootstrap_full ?>"></script>
<script type="text/javascript">var _gaq = [
        ['_setAccount', 'UA-45573043-1'],
        ['_trackPageview']
    ];
    (function (d, t) {
        var g = d.createElement(t), s = d.getElementsByTagName(t)[0];
        g.async = 1;
        g.src = '//www.google-analytics.com/ga.js';
        s.parentNode.insertBefore(g, s)
    })(document, 'script')</script>
</body>
</html>