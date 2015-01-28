<?php
require_once('../global_functions.php');
require_once('./functions.php');
require_once('../connections/parameters.php');

try {
    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    if ($db) {
        $steamWebAPI = new steam_webapi($api_key1);

        echo '<pre>';
        print_r($steamWebAPI->GetFriendList('76561197989020883'));
        echo '</pre>';
    } else {
        echo bootstrapMessage('Oh Snap', 'No DB!');
    }

    echo '<p>
            <div class="text-center">
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__directory">Mod Directory</a>
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__recent_games">Recent Games</a>
            </div>
        </p>';

    $memcache->close();
} catch
(Exception $e) {
    echo bootstrapMessage('Oh Snap', 'Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage());
}