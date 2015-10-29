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
          script-src 'self' static.getdotastats.com getdotastats.com oss.maxcdn.com ajax.googleapis.com *.google.com *.google-analytics.com *.changetip.com 'unsafe-eval' 'unsafe-inline' data:;
          img-src 'self' dota2.photography static.getdotastats.com getdotastats.com media.steampowered.com data: ajax.googleapis.com cdn.akamai.steamstatic.com cdn.dota2.com *.gstatic.com *.akamaihd.net  *.google-analytics.com *.steamusercontent.com;
          font-src 'self' static.getdotastats.com getdotastats.com;
          frame-src chatwing.com *.youtube.com *.mibbit.com *.changetip.com;
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
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">Custom Games <b class="caret"></b></a>
                    <ul class="dropdown-menu">
                        <li class="dropdown-header">Mod Section</li>
                        <li><a class="nav-clickable" href="#s2__directory">Directory</a></li>
                        <li><a class="nav-clickable" href="#s2__search">Search</a></li>
                        <li><a class="nav-clickable" href="#s2__recent_games">Recent Games</a></li>
                        <li><a class="nav-clickable" href="#s2__mod_aggregate">Aggregate Analysis</a></li>
                        <?php if (!empty($_SESSION['user_id64'])) { ?>
                            <li class="divider"></li>
                            <li class="dropdown-header">My Section</li>
                            <li><a class="nav-clickable" href="#s2__user?id=<?= $_SESSION['user_id64'] ?>">Public
                                    Profile</a></li>
                            <li><a class="nav-clickable" href="#s2__my__profile">Private Profile</a></li>
                            <li><a class="nav-clickable" href="#s2__my__give_feedback">Give Feedback</a></li>
                            <li><a class="nav-clickable" href="#s2__my__mods">Mods</a></li>
                            <li><a class="nav-clickable" href="#s2__my__mods_feedback">Feedback</a></li>
                        <?php } ?>
                        <li class="divider"></li>
                        <li class="dropdown-header">Developers</li>
                        <li><a class="nav-clickable" href="#s2__guide_stat_collection">Implementing Stats</a></li>
                        <li><a class="nav-clickable" href="#s2__schema_matches">Schema stat-collection</a></li>
                        <li><a class="nav-clickable" href="#s2__schema_highscore">Schema stat-highscore <span
                                    class="label label-danger">SOON</span></a></li>
                        <li><a>Schema stat-rpg <span
                                    class="label label-danger">SOON</span></a></li>
                        <li><a class="nav-clickable" href="#source2__beta_changes">Dota 2 Reborn Changes</a></li>
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
                        <li><a class="nav-clickable" href="#site__who">Who are we?</a></li>
                        <li><a class="nav-clickable" href="#site__game_servers">Game Servers</a></li>
                        <li><a class="nav-clickable" href="#site__contact">Contact</a></li>
                    </ul>
                </li>
                <?php if (!empty($adminCheck)) { ?>
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown">Admin <span
                                class="label label-danger">NEW</span> <b class="caret"></b></a>
                        <ul class="dropdown-menu">
                            <li class="dropdown-header">Management</li>
                            <li><a class="nav-clickable" href="#admin__mod_approve">Mod Approve</a></li>
                            <li><a class="nav-clickable" href="#admin__mod_edit">Mod Edit</a></li>
                            <li><a class="nav-clickable" href="#admin__mod_rejected">Mods Rejected</a></li>
                            <li><a class="nav-clickable" href="#admin__mod_schema">Mod Schema</a></li>
                            <li><a class="nav-clickable" href="#admin__mod_contact_devs">Contact Devs</a></li>
                            <li><a class="nav-clickable" href="#admin__service_stats">Service Stats <span
                                        class="label label-warning">NEW</span></a></li>
                            <li><a class="nav-clickable" href="#admin__mod_version">Mod Versions <span
                                        class="label label-warning">NEW</span></a></li>
                            <li class="divider"></li>
                            <li class="dropdown-header">Misc.</li>
                            <li><a class="nav-clickable" href="#admin__moderator_list">Moderator List</a></li>
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
                    : '<a class="nav-clickable" href="#s2__user?id=' . $_SESSION['user_id64'] . '"><img width="20px" src="' . $_SESSION['user_avatar'] . '" /></a> ';

                echo '<p class="nav navbar-text">' . $image . ' <a href="./auth/?logout">Logout</a></p>';
            } ?>
            <p class="nav navbar-text">
                <a id="nav-refresh-holder" class="nav-refresh" href="#s2__directory" title="Refresh page"><span
                        class="glyphicon glyphicon-refresh"></span></a>
            </p>
        </div>
    </div>
</div>
<div class="clear"></div>

<span class="h4 clearfix hidden">&nbsp;</span>

<div class="container">
    <div class="text-center">
        <a class="nav-clickable" href="#s2__directory"><img width="300px"
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
                    <a href="//steamcommunity.com/groups/getdotastats" target="_blank" class="steam-group-button"><span
                            class="steam-group-icon"></span> <span class="steam-group-label">Steam Group</span></a>

                    <a href="https://www.changetip.com/tipme/getdotastats" target="_blank"
                       class="changetip-button"><span
                            class="changetip-icon"></span> <span class="changetip-label">Tip.me</span></a>
                </div>

                <!-- Begin chatwing.com chatbox -->
                <iframe src="//chatwing.com/chatbox/e7f2bbd0-e292-4596-ab15-1667b4319e95" width="100%" height="650"
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
            <small><a target="_blank" href="https://github.com/GetDotaStats/stat-collection/issues">stat-collection
                    Issues</a>
            </small>
            ||
            <small><a target="_blank" href="https://github.com/GetDotaStats/site/issues">Site Issues</a></small>
        </p>
    </div>
</div>

<script type="text/javascript" src="<?= $path_lib_jQuery2_full ?>"></script>
<script type="text/javascript" src="<?= $path_lib_jQuery3_full ?>"></script>
<script type="text/javascript" src="<?= $path_lib_bootstrap_full ?>"></script>
<script type="text/javascript" src="<?= $path_lib_highcharts_full ?>"></script>
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