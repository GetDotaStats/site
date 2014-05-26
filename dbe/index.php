<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
  <head>
    <meta charset="utf-8">
    <meta content="text/html">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="/favicon.ico">
    <link href="/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="/getdotastats.css" rel="stylesheet">
	
	<link href="/match_analysis/getdotastats-matchanalysis.css" rel="stylesheet" type="text/css" />

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
            <li><a href="/">Home</a></li>
            <li class="dropdown">
              <a href="#" class="dropdown-toggle" data-toggle="dropdown">Match Analysis <b class="caret"></b></a>
              <ul class="dropdown-menu">
                <li><a href="/match_analysis/">Home</a></li>
                <li><a href="/match_analysis/general_stats.php">General Stats</a></li>
                <li><a href="/match_analysis/game_modes.php">Game Modes</a></li>
                <li><a href="/match_analysis/clusters.php">Region Breakdown</a></li>
                <li class="divider"></li>
                <li class="dropdown-header">Misc.</li>
                <li><a href="/match_analysis/worker_progress.php">Data Collector Status</a></li>
              </ul>
            </li>
            <li><a href="/steamtracks">Signature Generator</a></li>
            <li><a href="/replays">Replay Archive</a></li>
            <li><a href="/economy_analysis">Economy Analysis</a></li>
            <li class="active"><a href="/dbe">Dotabuff Extended</a></li>
            <li><a href="/contact.php">Contact</a></li>
          </ul>
        </div>
      </div>
    </div>

    <div class="container">
		<div class="jumbotron">
        	<h1>DotaBuff Extended Plugin</h1>
        	<p>This plugin extends functionality for the DotaBuff website.</p>
		</div>
    </div>
    
    <div class="container">
		<div class="row">
			<div class="col-sm-8 blog-main">
				<div class="blog-post">
					<h2>About</h2>
					<p>The DotaBuff thread can be found here: <a href="http://dotabuff.com/topics/2013-03-05-dotabuff-extended-mozilla-addon">http://dotabuff.com/topics/2013-03-05-dotabuff-extended-mozilla-addon</a></p>
					<p>This plugin adds the following to DotaBuff pages:</p>
					<ul>
						<li>Signature generator tab on user profiles</li>
						<li>Tower & Barracks Status tab on match detail pages</a></li>
					</ul>
					<p><a href="./images/1.png" target="__new"><img src="./images/1.png" width="100px" /></a> <a href="./images/2.png" target="__new"><img src="./images/2.png" width="100px" /></a></p>
					<h2>Download Links</h2>
					<p><a href="https://chrome.google.com/webstore/detail/dotabuff-extended/oggpckdeaofelnblpijaiijmgfckncdf?hl=en&gl=AU">Chrome Extension</a> (<a href="./DotabuffExtended-0.7.crx">Manual install v7</a> - save to desktop and drag into extensions folder)</p>
					<p><a href="https://addons.mozilla.org/en-US/firefox/addon/dotabuff-extended/">Firefox Extension</a></p>
					<h2>Change Log:</h2>
					<ul>
						<li>v 0.8 - Add signatures to forum</li>
						<li>v 0.7 - Fixed bug caused by change to layout of Dotabuff page (changed match_id)</li>
						<li>v 0.6 - Various bug fixes</li>
						<li>v 0.5.2 - Added to chrome store</li>
						<li>v 0.5.1 - Added direct link for signature below image</li>
						<li>v 0.5 - Added Signature Generator (credits: http://dotabuff.com/players/28755155)</li>
						<li>v 0.4.3 - Removed hero_dmg, hero_heal, tower_dmg</li>
						<li>v 0.4.2 - Some links changed to point new domain</li>
						<li>v 0.4 - Tower and Barracks Status is back :)</li>
						<li>v 0.3.2 - Fixed bug that causes addon didn't work for some time. Tower and Barracks Status still not working.</li>
						<li>v 0.2 - Added Tower and Barracks Status</li>
						<li>v 0.1 - First release</li>
					</ul>
					<h2>Credits</h2>
					<p>Originally developed by gmilanche (gmilanche.gm AT gmail DOT com). Proudly hosted by GetDotaStats. Chrome plugin maintained by GetDotaStats</p>
				</div>
            </div>
            
            <div class="col-sm-3 col-sm-offset-1 blog-sidebar">
                <div class="sidebar-module sidebar-module-inset">
                    <!-- Begin chatwing.com chatbox -->
                    <iframe src="http://chatwing.com/chatbox/f220203c-c1fa-4ce9-a840-c90a3a2edb9d" width="100%" height="600" frameborder="0" scrolling="0">Embedded chat</iframe>
                    <!-- End chatwing.com chatbox -->
                </div>
            </div>
		</div>
    </div>

    <div id="footer">
      <div class="container">
        <p class="text-muted">Built by jimmydorry. Dota 2 is a registered trademark of Valve Corporation. Powered by Steam.</p>
      </div>
    </div>

    <script src="/bootstrap/js/jquery-1-11-0.min.js"></script>
    <script src="/bootstrap/js/bootstrap.min.js"></script>
  </body>
</html>