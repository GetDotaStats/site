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
                legendary are able to rack up a sufficient number of lobbies with more than a single player to steal the admiration of even the leechers
                of the community. Are you one of those legendary few?</p>';

        echo '<span class="h4">&nbsp;</span>';
        echo '<div class="text-center">
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__hof">Hall of Fame</a>
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__lobby_list">Lobby List</a>
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__recent_games">Recent Games</a>
           </div>';
        echo '<span class="h4">&nbsp;</span>';

        $hof3_users = cached_query(
            'hof2_lobbies_users',
            'SELECT
                    lobby_leader,
                    SUM(num_players) as num_lobbies
                FROM (
                    SELECT
                        ll.`lobby_leader`,
                        ll.`lobby_id`,
                        COUNT(*) AS num_players
                    FROM `lobby_list` ll
                    JOIN `lobby_list_players` llp ON ll.`lobby_id` = llp.`lobby_id`
                    GROUP BY ll.`lobby_leader`, llp.`lobby_id`
                    HAVING num_players > 1
                    ORDER BY ll.`lobby_leader`
                ) as t1
                GROUP BY lobby_leader
                ORDER BY num_lobbies DESC
                LIMIT 0,50;',
            30 * 60
        );

        if (!empty($hof3_users)) {
            echo '<div class="row">
                            <div class="col-md-1 text-center">
                                <span class="h4">Rank</span>
                            </div>
                            <div class="col-md-2 text-center">
                                <span class="h4">Lobbies</span>
                            </div>
                        </div>';
            echo '<span class="h4">&nbsp;</span>';

            foreach ($hof3_users as $key => $value) {
                try {
                    if ($value['lobby_leader'] != 0) {
                        $hof3_user_details = cached_query(
                            'hof3_lobbies_users_details' . $value['lobby_leader'],
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
                            $value['lobby_leader'],
                            1 * 60
                        );

                        if (empty($hof3_user_details)) {
                            if (!isset($steamID)) {
                                $steamID = new SteamID();
                            }

                            $steamID->setSteamID($value['lobby_leader']);

                            $steamWebAPI = new steam_webapi($api_key1);
                            $hof3_user_details_temp = $steamWebAPI->GetPlayerSummariesV2($steamID->getSteamID64());

                            if (!empty($hof3_user_details_temp)) {
                                $hof3_user_details[0]['user_id64'] = $steamID->getSteamID64();
                                $hof3_user_details[0]['user_id32'] = $steamID->getSteamID32();
                                $hof3_user_details[0]['user_name'] = $hof3_user_details_temp['response']['players'][0]['personaname'];
                                $hof3_user_details[0]['user_avatar'] = $hof3_user_details_temp['response']['players'][0]['avatar'];
                                $hof3_user_details[0]['user_avatar_medium'] = $hof3_user_details_temp['response']['players'][0]['avatarmedium'];
                                $hof3_user_details[0]['user_avatar_large'] = $hof3_user_details_temp['response']['players'][0]['avatarfull'];


                                $db->q(
                                    'INSERT INTO `gds_users`
                                        (`user_id64`, `user_id32`, `user_name`, `user_avatar`, `user_avatar_medium`, `user_avatar_large`)
                                        VALUES (?, ?, ?, ?, ?, ?)',
                                    'ssssss',
                                    array(
                                        $hof3_user_details[0]['user_id64'],
                                        $hof3_user_details[0]['user_id32'],
                                        $hof3_user_details[0]['user_name'],
                                        $hof3_user_details[0]['user_avatar'],
                                        $hof3_user_details[0]['user_avatar_medium'],
                                        $hof3_user_details[0]['user_avatar_large']
                                    )
                                );

                                $memcache->set('hof3_lobbies_users_details' . $value['lobby_leader'], $hof3_user_details, 0, 15);
                            }
                        }

                        $userAvatar = !empty($hof3_user_details[0]['user_avatar'])
                            ? $hof3_user_details[0]['user_avatar']
                            : $imageCDN . '/images/misc/steam/blank_avatar.jpg';

                        $userName = !empty($hof3_user_details[0]['user_name'])
                            ? '<span class="h3">
                            <a target="_blank" href="#d2mods__search?user=' . $value['lobby_leader'] . '">
                                ' . htmlentities($hof3_user_details[0]['user_name']) . '
                            </a>
                        </span>'
                            : '<span class="h3">
                            <a target="_blank" href="#d2mods__search?user=' . $value['lobby_leader'] . '">
                                ??
                            </a>
                            <small>Sign in to update profile!</small>
                        </span>';
                    } else {
                        $userAvatar = $imageCDN . '/images/misc/steam/blank_avatar.jpg';
                        $userName = '<span class="h3">[NPC] Dota 2 Bots</span>';
                    }

                    $numLobbies = !empty($value['num_lobbies'])
                        ? number_format($value['num_lobbies'])
                        : '??';

                    echo '<div class="row">
                            <div class="col-md-1 text-center">
                                <span class="h3">' . ($key + 1) . '</span>
                            </div>
                            <div class="col-md-2 text-center">
                                <span class="h3">' . $numLobbies . '</span>
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