<?php
require_once('./functions.php');
require_once('../global_functions.php');
require_once('../connections/parameters.php');

try {
    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    if ($db) {
        echo '<h2>Connections - Hall of Fame</h2>';

        echo '<p>Only the worthy are able to successfully connect to games, and only the legendary remain connected to the
            game until the very end. Are you one of those legendary few?</p>';

        echo '<span class="h4">&nbsp;</span>';
        echo '<div class="text-center">
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__hof">Hall of Fame</a>
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__lobby_list">Lobby List</a>
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__recent_games">Recent Games</a>
           </div>';
        echo '<span class="h4">&nbsp;</span>';

        $hof1_users = cached_query(
            'hof1_connects_users',
            'SELECT
                    mmp.`player_sid32`,
                    mmp.`player_name`,
                    COUNT(*) as num_games
                FROM `mod_match_players` mmp
                WHERE mmp.`connection_status` = 2
                GROUP BY mmp.`player_sid32`
                ORDER BY num_games DESC
                LIMIT 0,50;',
            NULL,
            NULL,
            30 * 60
        );

        if (!empty($hof1_users)) {
            echo '<div class="row">
                            <div class="col-md-1 text-center">
                                <span class="h4">Rank</span>
                            </div>
                            <div class="col-md-2 text-center">
                                <span class="h4">Connections</span>
                            </div>
                        </div>';
            echo '<span class="h4">&nbsp;</span>';

            foreach ($hof1_users as $key => $value) {
                try {
                    $hof1_user_details = cached_query(
                        'hof1_connects_users_details' . $value['player_sid32'],
                        'SELECT
                                `user_id64`,
                                `user_id32`,
                                `user_name`,
                                `user_avatar`,
                                `user_avatar_medium`,
                                `user_avatar_large`
                        FROM `gds_users`
                        WHERE `user_id32` = ?
                        LIMIT 0,1;',
                        's',
                        $value['player_sid32'],
                        1 * 60
                    );

                    if (empty($hof1_user_details)) {
                        echo '<div class="row">
                            <div class="col-md-1 text-center">
                                <span>Next user was looked up!</span>
                            </div>
                        </div>';

                        if (!isset($steamID)) {
                            $steamID = new SteamID();
                        }

                        $steamID->setSteamID($value['player_sid32']);

                        $steamWebAPI = new steam_webapi($api_key1);
                        $hof1_user_details_temp = $steamWebAPI->GetPlayerSummariesV2($steamID->getSteamID64());

                        if (!empty($hof1_user_details_temp)) {
                            $hof1_user_details[0]['user_id64'] = $steamID->getSteamID64();
                            $hof1_user_details[0]['user_id32'] = $steamID->getSteamID32();
                            $hof1_user_details[0]['user_name'] = $hof1_user_details_temp['response']['players'][0]['personaname'];
                            $hof1_user_details[0]['user_avatar'] = $hof1_user_details_temp['response']['players'][0]['avatar'];
                            $hof1_user_details[0]['user_avatar_medium'] = $hof1_user_details_temp['response']['players'][0]['avatarmedium'];
                            $hof1_user_details[0]['user_avatar_large'] = $hof1_user_details_temp['response']['players'][0]['avatarfull'];


                            $db->q(
                                'INSERT INTO `gds_users`
                                    (`user_id64`, `user_id32`, `user_name`, `user_avatar`, `user_avatar_medium`, `user_avatar_large`)
                                    VALUES (?, ?, ?, ?, ?, ?)',
                                'ssssss',
                                array(
                                    $hof1_user_details[0]['user_id64'],
                                    $hof1_user_details[0]['user_id32'],
                                    $hof1_user_details[0]['user_name'],
                                    $hof1_user_details[0]['user_avatar'],
                                    $hof1_user_details[0]['user_avatar_medium'],
                                    $hof1_user_details[0]['user_avatar_large']
                                )
                            );

                            $memcache->set('hof1_connects_users_details' . $value['player_sid32'], $hof1_user_details, 0, 15);
                        }
                    }

                    $userAvatar = !empty($hof1_user_details[0]['user_avatar'])
                        ? $hof1_user_details[0]['user_avatar']
                        : $imageCDN . '/images/misc/steam/blank_avatar.jpg';

                    $userName = !empty($hof1_user_details[0]['user_name'])
                        ? '<span class="h3">
                            <a target="_blank" href="#d2mods__search?user=' . $value['player_sid32'] . '">
                                ' . htmlentities($hof1_user_details[0]['user_name']) . '
                            </a>
                        </span>'
                        : '<span class="h3">
                            <a target="_blank" href="#d2mods__search?user=' . $value['player_sid32'] . '">
                                ' . htmlentities($value['player_name']) . '
                            </a>
                            <small>Sign in to update profile!</small>
                        </span>';

                    $numGames = !empty($value['num_games'])
                        ? number_format($value['num_games'])
                        : '??';

                    echo '<div class="row">
                            <div class="col-md-1 text-center">
                                <span class="h3">' . ($key + 1) . '</span>
                            </div>
                            <div class="col-md-2 text-center">
                                <span class="h3">' . $numGames . '</span>
                            </div>
                            <div class="col-md-1">
                                <img alt="User Avatar" class="hof_avatar img-responsive center-block" src="' . $userAvatar . '" />
                            </div>
                            <div class="col-md-8">
                                ' . $userName . '
                            </div>
                        </div>';
                    echo '<span class="h4">&nbsp;</span>';
                } catch (Exception $e) {
                    echo '<div class="row">
                            <div class="col-md-1 text-center">
                                <span class="h3">' . ($key + 1) . '</span>
                            </div>
                            <div class="col-md-2 text-center">
                                <span class="h3">??</span>
                            </div>
                            <div class="col-md-1">
                                <img alt="User Avatar" class="hof_avatar img-responsive center-block" src="' . $imageCDN . '/images/misc/steam/blank_avatar.jpg' . '" />
                            </div>
                            <div class="col-md-8">
                                EXCEPTION OCCURRED!! COULDN\'T LOOKUP!!
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