<?php
require_once('../global_functions.php');
require_once('../connections/parameters.php');

try {
    if (!isset($_SESSION)) {
        session_start();
    }

    checkLogin_v2();

    if (!empty($_SESSION['user_id64'])) {
        $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);

        $memcache = new Memcache;
        $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

        if ($db) {
            $minigameList = cached_query(
                'my_minigames_' . $_SESSION['user_id64'],
                'SELECT
                    `minigameID`,
                    `minigameName`,
                    `minigameDeveloper`,
                    `minigameSteamGroup`,
                    `minigameActive`,
                    `date_recorded`
                FROM `stat_highscore_minigames`
                WHERE `minigameDeveloper` = ?
                ORDER BY `date_recorded` DESC;',
                's', //STUPID x64 windows PHP is actually x86
                $_SESSION['user_id64'],
                60
            );

            echo '<div class="page-header"><h2>My Mini Games <small>BETA</small></h2></div>';

            echo '<p>This is a list of the mini games you have had added for yourself. Each mod has an associated minigameID key that you will require.
            Please read our guide on how to integrate your mini-game into our Lobby Explorer pack. This section is a Work-In-Progress, so check back later.</p>';

            if (!empty($minigameList)) {
                foreach ($minigameList as $key => $value) {
                    $sg = !empty($value['minigameSteamGroup'])
                        ? '<a href="http://steamcommunity.com/groups/' . $value['minigameSteamGroup'] . '" target="_blank">' . $value['minigameSteamGroup'] . '</a>'
                        : 'None';

                    $activeMod = $value['minigameActive'] == 1
                        ? 'Approved'
                        : 'Waiting approval';

                    $minigameDescription = !empty($value['minigameDescription'])
                        ? $value['minigameDescription']
                        : 'No descrption';

                    echo '<div class="panel panel-default">
                            <div class="panel-heading"><h4>' . $value['minigameName'] . ' <small>' . $activeMod . '</small></h4></div>
                            <div class="panel-body">
                                <div class="well well-sm"><strong>modID:</strong> ' . $value['minigameID'] . '</div>
                                <!--<div class="well well-sm"><strong>Description:</strong> ' . $minigameDescription . '</div>-->
                                <div class="well well-sm"><strong>Steam Group:</strong> ' . $sg . ' </div >
                                <div class="well well-sm"><strong>Date Added:</strong > ' . relative_time($value['date_recorded']) . ' </div >
                            </div >
                        </div > ';
                }

            } else {
                echo '<div class="alert alert-danger" role = "alert" ><strong > Oh Snap:</strong > You don\'t have any mods added yet!</div>';
            }

            echo '<p>
                    <div class="text-center">
                        <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__mod_request">Add a new mod</a>
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