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
                <li><a href="/match_analysis">Home</a></li>
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
					<p>The latest feature in the DBE Chrome Extension places each user's Dota 2 signature below their posts on the Dotabuff forums.</p>
					<p>Feel free to leave comments in the chatbox on the side, or ping me on Reddit (u/jimmydorry). If users support this improvement, we will consider adding it to Firefox too!</p>
					<p>You can find the page to re-install <a href="https://chrome.google.com/webstore/detail/dotabuff-extended/oggpckdeaofelnblpijaiijmgfckncdf?hl=en&gl=AU">HERE</a>. Read more about the extension <a href="http://getdotastats.com/dbe">HERE</a>.</p>
					<p><strong>In a week or two, a decision will be made to keep or remove the signatures... so every vote counts!</strong></p>
					<p><center><iframe src="http://strawpoll.me/embed_1/1511023" style="width: 600px; height: 373px; border: 0;">Loading poll...</iframe></center></p>
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