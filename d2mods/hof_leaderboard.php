<?php
require_once('./functions.php');
require_once('../global_functions.php');
require_once('../connections/parameters.php');

try {
    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    $hof_id = !empty($_GET['hof']) && is_numeric($_GET['hof'])
        ? $_GET['hof']
        : 1;

    $hof_users = cached_query(
        'hof_users' . $hof_id,
        'SELECT
                `player_sid64`,
                `hof_rank`,
                `hof_score1`,
                `hof_score2`,
                `hof_score3`
            FROM `cron_hof`
            WHERE `hof_id` = ?
            ORDER BY hof_rank ASC
            LIMIT 0,100;',
        'i',
        $hof_id,
        10
    );

    $hof_details = cached_query(
        'hof_details' . $hof_id,
        'SELECT
              `hof_id`,
              `hof_name`,
              `hof_description`,
              `hof_score1_enabled`,
              `hof_score1_name`,
              `hof_score2_enabled`,
              `hof_score2_name`,
              `hof_score3_enabled`,
              `hof_score3_name`,
              `date_recorded`
            FROM cron_hof_schema
            WHERE `hof_id` = ?
            LIMIT 0,1;',
        'i',
        $hof_id,
        10
    );

    if (empty($hof_users) || empty($hof_details)) {
        throw new Exception('No entries to the Hall of Fame!');
    }

    $hofName = !empty($hof_details[0]['hof_name'])
        ? $hof_details[0]['hof_name']
        : 'Unknown HoF';

    $hofDescription = !empty($hof_details[0]['hof_description'])
        ? $hof_details[0]['hof_description']
        : 'This Hall of Fame has no description.';

    echo '<h2>' . $hofName . ' - Hall of Fame</h2>';

    echo '<p>' . $hofDescription . '</p>';

    echo '<span class="h4">&nbsp;</span>';
    echo '<div class="text-center">
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__hof">Hall of Fame</a>
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__lobby_list_old">Lobby List</a>
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__recent_games">Recent Games</a>
           </div>';
    echo '<span class="h4">&nbsp;</span>';

    echo '<div class="row">';

    echo '<div class="col-md-1 text-center"><span class="h4">Rank</span></div>';
    echo '<div class="col-md-2 text-center"><span class="h4">' . $hof_details[0]['hof_score1_name'] . '</span></div>';

    if ($hof_details[0]['hof_score2_enabled']) {
        echo '<div class="col-md-2 text-center"><span class="h4">' . $hof_details[0]['hof_score2_name'] . '</span></div>';
    }

    if ($hof_details[0]['hof_score3_enabled']) {
        echo '<div class="col-md-2 text-center"><span class="h4">' . $hof_details[0]['hof_score3_name'] . '</span></div>';
    }

    echo '</div>';

    echo '<span class="h4">&nbsp;</span>';

    foreach ($hof_users as $key => $value) {
        try {
            if ($value['player_sid64'] != 0) {
                $hof_user_details = cached_query(
                    'hof_user_details' . $value['player_sid64'],
                    'SELECT
                            `user_id64`,
                            `user_id32`,
                            `user_name`,
                            `user_avatar`,
                            `user_avatar_medium`,
                            `user_avatar_large`
                    FROM `gds_users`
                    WHERE `user_id64` = ?
                    LIMIT 0,1;',
                    's',
                    $value['player_sid64'],
                    1 * 60
                );
            } else {
                $hof_user_details = false;
            }

            if (!empty($hof_user_details)) {
                $userAvatar = !empty($hof_user_details[0]['user_avatar'])
                    ? $hof_user_details[0]['user_avatar']
                    : $CDN_image . '/images/misc/steam/blank_avatar.jpg';

                if (!empty($hof_user_details[0]['user_name']) && strlen($hof_user_details[0]['user_name']) > 28) {
                    $hof_user_details[0]['user_name'] = substr($hof_user_details[0]['user_name'], 0, 24) . '...';
                }

                $userName = !empty($hof_user_details[0]['user_name'])
                    ? '<span class="h3">
                            <a class="nav-clickable" href="#d2mods__profile?id=' . $value['player_sid64'] . '">
                                ' . $hof_user_details[0]['user_name'] . '
                            </a>
                        </span>'
                    : '<span class="h3">
                            <a class="nav-clickable" href="#d2mods__profile?id=' . $value['player_sid64'] . '">
                                ?UNKNOWN?
                            </a>
                            <small>Sign in to update profile!</small>
                        </span>';

                echo '<div class="row">
                            <div class="col-md-1 text-center">
                                <span class="h3">' . ($key + 1) . '</span>
                            </div>
                            <div class="col-md-2 text-center">
                                <span class="h3">' . $value['hof_score1'] . '</span>
                            </div>
                            <div class="col-md-1">
                                <a class="nav-clickable" href="#d2mods__profile?id=' . $value['player_sid64'] . '">
                                    <img alt="User Avatar" class="hof_avatar img-responsive center-block" src="' . $userAvatar . '" />
                                </a>
                            </div>
                            <div class="col-md-8">
                                ' . $userName . '
                            </div>
                        </div>';
                echo '<span class="h4">&nbsp;</span>';
            } else {
                $userName = $value['player_sid64'] == 0
                    ? '<span class="h3">Bots</span>'
                    : 'Private Profile!';

                echo '<div class="row">
                            <div class="col-md-1 text-center">
                                <span class="h3">' . ($key + 1) . '</span>
                            </div>
                            <div class="col-md-2 text-center">
                                <span class="h3">' . $value['hof_score1'] . '</span>
                            </div>
                            <div class="col-md-1">
                                <a class="nav-clickable" href="#d2mods__profile?id=' . $value['player_sid64'] . '">
                                    <img alt="User Avatar" class="hof_avatar img-responsive center-block" src="' . $CDN_image . '/images/misc/steam/blank_avatar.jpg' . '" />
                                </a>
                            </div>
                            <div class="col-md-8">
                                ' . $userName . '
                            </div>
                        </div>';
                echo '<span class="h4">&nbsp;</span>';
            }
        } catch (Exception $e) {
            echo '<div class="row">
                            <div class="col-md-1 text-center">
                                <span class="h3">' . ($key + 1) . '</span>
                            </div>
                            <div class="col-md-2 text-center">
                                <span class="h3">??</span>
                            </div>
                            <div class="col-md-1">
                                <img alt="User Avatar" class="hof_avatar img-responsive center-block" src="' . $CDN_image . '/images/misc/steam/blank_avatar.jpg' . '" />
                            </div>
                            <div class="col-md-8">
                                ' . $e->getMessage() . '
                            </div>
                        </div>';
            echo '<span class="h4">&nbsp;</span>';
        }
    }

    echo '<span class="h4">&nbsp;</span>';
    echo '<div class="text-center">
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__hof">Hall of Fame</a>
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__lobby_list_old">Lobby List</a>
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__recent_games">Recent Games</a>
           </div>';
    echo '<span class="h4">&nbsp;</span>';

    $memcache->close();
} catch (Exception $e) {
    echo formatExceptionHandling($e);
}