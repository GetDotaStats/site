<?php
require_once('../../global_functions.php');
require_once('../../connections/parameters.php');

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

    $userID64 = $_SESSION['user_id64'];

    $modList = cached_query(
        's2_my_mods_' . $userID64,
        'SELECT
                ml.*,
                gu.`user_name`,
                gu.`user_avatar`,
                (SELECT COUNT(*) FROM `s2_match` WHERE `modID` = ml.`mod_id`) as games_recorded
            FROM `mod_list` ml
            LEFT JOIN `gds_users` gu ON ml.`steam_id64` = gu.`user_id64`
            WHERE ml.`steam_id64` = ?
            ORDER BY ml.date_recorded DESC;',
        's',
        $userID64,
        5
    );

    echo '<div class="page-header"><h2>My Mods</h2></div>';

    echo '<span class="h4">&nbsp;</span>';

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
                                <div class="well well-sm"><strong>modID:</strong> <a class="nav-clickable" href="#s2__mod?id=' . $value['mod_id'] . '">' . $value['mod_identifier'] . '</a></div>
                                <div class="well well-sm"><strong>Games Recorded:</strong> ' . number_format($value['games_recorded']) . '</div>
                                <div class="well well-sm"><strong>Description:</strong> ' . $value['mod_description'] . '</div>
                                <div class="well well-sm"><strong>Links:</strong> ' . $wg . ' || ' . $sg . ' </div >
                                <div class="well well-sm"><strong>Date Added:</strong> ' . relative_time_v3($value['date_recorded']) . ' </div >
                            </div >
                        </div > ';
        }

    } else {
        echo bootstrapMessage('Oh Snap', 'You don\'t have any mods added yet!', 'danger');
    }

    echo '<hr />';

    echo '<span class="h4">&nbsp;</span>';

    echo '<div class="text-center">
            <a class="nav-clickable btn btn-default btn-lg" href="#s2__my__profile">My Profile</a>
            <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__mod_request">Add a new mod</a>
            <a class="nav-clickable btn btn-default btn-lg" href="#s2__schema_matches">About Stats</a>
            <a class="nav-clickable btn btn-default btn-lg" href="#s2__my__mods_feedback">My Feedback</a>
        </div>';

    echo '<span class="h4">&nbsp;</span>';
} catch (Exception $e) {
    echo formatExceptionHandling($e);
} finally {
    if (isset($memcache)) $memcache->close();
}