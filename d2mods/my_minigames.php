<?php
require_once('../global_functions.php');
require_once('../connections/parameters.php');

try {
    if (!isset($_SESSION)) {
        session_start();
    }

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    checkLogin_v2();
    if (empty($_SESSION['user_id64'])) throw new Exception('Not logged in!');

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
        echo bootstrapMessage('Oh Snap', 'You don\'t have any mini games added yet!', 'danger');
    }

    echo '<p>
                    <div class="text-center">
                        <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__minigame_request">Add a new mini game</a>
                    </div>
                </p>';

    $memcache->close();
} catch (Exception $e) {
    echo formatExceptionHandling($e);
}