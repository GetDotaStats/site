<?php
require_once('../global_functions.php');
require_once('../connections/parameters.php');

try {
    if (!isset($_SESSION)) {
        session_start();
    }

    checkLogin_v2();

    if (!empty($_SESSION['user_id64'])) {
        $db = new dbWrapper_v2($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site);
        $db->q('SET NAMES utf8;');

        $memcache = new Memcache;
        $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

        if ($db) {
            $modList = $db->q('SELECT * FROM `mod_list` WHERE `steam_id64` = ? ORDER BY `date_recorded` DESC;',
                's', //STUPID x64 windows PHP is actually x86
                $_SESSION['user_id64']);

            echo '<div class="page-header"><h2>My Mods <small>BETA</small></h2></div>';

            echo '<p>This is a list of the mods you have added. Each mod has an associated encryption key that you will require. Please <a class="nav-clickable" href="#d2mods__guide">read our guide</a> on how to integrate statistic gathering into your mod. This section is a Work-In-Progress, so check back later.</p>';

            if (!empty($modList)) {
                foreach ($modList as $key => $value) {
                    $sg = !empty($value['mod_steam_group'])
                        ? '<a href="http://steamcommunity.com/groups/' . $value['mod_steam_group'] . '" target="_new">SG</a>'
                        : 'SG';

                    $wg = !empty($value['mod_workshop_link'])
                        ? '<a href="http://steamcommunity.com/sharedfiles/filedetails/?id=' . $value['mod_workshop_link'] . '" target="_new">WS</a>'
                        : 'WG';

                    $activeMod = $value['mod_active'] == 1
                        ? 'Approved'
                        : 'Waiting approval';

                    //<div class="well well-sm" style="white-space: normal;word-break: break-all;"><strong>Encryption Key:</strong> ' . $value['mod_public_key'] . '</div>
                    echo '<div class="panel panel-default">
                            <div class="panel-heading"><h4>' . $value['mod_name'] . ' <small>' . $activeMod . '</small></h4></div>
                            <div class="panel-body">
                                <div class="well well-sm"><strong>modID:</strong> ' . $value['mod_identifier'] . '</div>
                                <div class="well well-sm"><strong>Description:</strong> ' . $value['mod_description'] . '</div>
                                <div class="well well-sm"><strong>Links:</strong> ' . $wg . ' || ' . $sg . ' </div >
                                <div class="well well-sm" ><strong > Date Added:</strong > ' . relative_time($value['date_recorded']) . ' </div >
                            </div >
                        </div > ';
                }

            } else {
                echo '<div class="alert alert-danger" role = "alert" ><strong > Oh Snap:</strong > You don\'t have any mods added yet!</div>';
            }

            echo '<p>
                    <div class="text-center">
                        <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__signup">Add a new mod</a>
                        <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__directory">Mod Directory</a>
                        <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__recent_games">Recent Games</a>
                    </div>
                </p>';
        } else {
            echo '<div class="page-header"><div class="alert alert-danger" role="alert"><strong>Oh Snap:</strong> No DB!</div></div>';
        }

        $memcache->close();
    } else {
        echo '<div class="page-header"><div class="alert alert-danger" role="alert"><strong>Oh Snap:</strong> Not logged in!</div></div>';
    }
} catch
(Exception $e) {
    echo '<div class="page-header"><div class="alert alert-danger" role="alert"><strong>Oh Snap:</strong> Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage() . '</div></div>';
}