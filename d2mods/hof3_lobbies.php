<?php
require_once('./functions.php');
require_once('../global_functions.php');
require_once('../connections/parameters.php');

try {
    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    if ($db) {
        echo '<h2>Lobbies - Hall of Fame</h2>';

        echo '<p>Only the most excellent of the swarm of consumers have what it takes to successfully create lobbies. Only the
                legendary are able to rack up a sufficient number of lobbies, with more than a single player, to steal the admiration of even the most apathetic of leechers
                in the community. Are you one of those legendary few?</p>';

        echo '<span class="h4">&nbsp;</span>';
        echo '<div class="text-center">
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__hof">Hall of Fame</a>
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__lobby_list">Lobby List</a>
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__recent_games">Recent Games</a>
           </div>';
        echo '<span class="h4">&nbsp;</span>';

        $hof_users = cached_query(
            'hof3_lobbies_users',
            'SELECT `player_sid64`, `num_lobbies` FROM `cron_hof3`;',
            NULL,
            NULL,
            1 * 60
        );

        $scoreName = 'num_lobbies';

        if (!empty($hof_users)) {
            echo '<div class="row">
                            <div class="col-md-1 text-center">
                                <span class="h4">Rank</span>
                            </div>
                            <div class="col-md-2 text-center">
                                <span class="h4">Lobbies</span>
                            </div>
                        </div>';
            echo '<span class="h4">&nbsp;</span>';

            foreach ($hof_users as $key => $value) {
                $score = !empty($value[$scoreName])
                    ? number_format($value[$scoreName])
                    : '??';

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
                        : $imageCDN . '/images/misc/steam/blank_avatar.jpg';

                    if (!empty($hof_user_details[0]['user_name']) && strlen($hof_user_details[0]['user_name']) > 21) {
                        $hof_user_details[0]['user_name'] = substr($hof_user_details[0]['user_name'], 0, 17) . '...';
                    }

                    $userName = !empty($hof_user_details[0]['user_name'])
                        ? '<span class="h3">
                            <a target="_blank" href="#d2mods__search?user=' . $value['player_sid64'] . '">
                                ' . $hof_user_details[0]['user_name'] . '
                            </a>
                        </span>'
                        : '<span class="h3">
                            <a target="_blank" href="#d2mods__search?user=' . $value['player_sid64'] . '">
                                ?UNKNOWN?
                            </a>
                            <small>Sign in to update profile!</small>
                        </span>';

                    echo '<div class="row">
                            <div class="col-md-1 text-center">
                                <span class="h3">' . ($key + 1) . '</span>
                            </div>
                            <div class="col-md-2 text-center">
                                <span class="h3">' . $score . '</span>
                            </div>
                            <div class="col-md-1">
                                <a target="_blank" href="#d2mods__search?user=' . $value['player_sid64'] . '">
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
                                <span class="h3">' . $score . '</span>
                            </div>
                            <div class="col-md-1">
                                <a target="_blank" href="#d2mods__search?user=' . $value['player_sid64'] . '">
                                    <img alt="User Avatar" class="hof_avatar img-responsive center-block" src="' . $imageCDN . '/images/misc/steam/blank_avatar.jpg' . '" />
                                </a>
                            </div>
                            <div class="col-md-8">
                                ' . $userName . '
                            </div>
                        </div>';
                    echo '<span class="h4">&nbsp;</span>';
                }
            }
        } else {
            echo bootstrapMessage('Oh Snap', 'No entries to the Hall of Fame!.', 'danger');
        }
    } else {
        echo bootstrapMessage('Oh Snap', 'No db!', 'danger');
    }

    echo '<span class="h4">&nbsp;</span>';
    echo '<div class="text-center">
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__hof">Hall of Fame</a>
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__lobby_list">Lobby List</a>
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__recent_games">Recent Games</a>
           </div>';
    echo '<span class="h4">&nbsp;</span>';

    $memcache->close();
} catch (Exception $e) {
    $message = 'Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage();
    echo bootstrapMessage('Oh Snap', $message, 'danger');
}