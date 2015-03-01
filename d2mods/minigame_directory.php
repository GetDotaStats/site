<?php
require_once('../global_functions.php');
require_once('../connections/parameters.php');

try {
    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    echo '<div class="page-header"><h2>Mini Game Directory <small>BETA</small></h2></div>';

    echo '<p>This is a directory of all the mini-games that are or will soon be in the Lobby Explorer pack.
        All mini-games are screened by site staff to ensure quality.</p>';

    echo '<div class="alert alert-danger" role="alert"><strong>Oh Snap:</strong> No active mini games added yet!</div>';

    echo '<p>
            <div class="text-center">
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__minigame_request">Add a new Mini Game</a>
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__minigame_guide">Mini Game Guide</a>
           </div>
        </p>';

    $memcache->close();
} catch (Exception $e) {
    echo formatExceptionHandling($e);
}