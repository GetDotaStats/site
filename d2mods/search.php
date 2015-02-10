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

        $steamIDconvertor = new SteamID();

        $steamID_unknown = !empty($_GET['user'])
            ? $_GET['user']
            : NULL;

        if (!empty($steamID_unknown)) {
            if (!is_numeric($steamID_unknown)) {
                if (stristr($steamID_unknown, 'steamcommunity.com/id/')) {
                    $steamID_unknown = cut_str($steamID_unknown, 'steamcommunity.com/id/');
                    $steamID_unknown = rtrim($steamID_unknown, '/');
                } else if (stristr($steamID_unknown, 'steamcommunity.com/profiles/')) {
                    $steamID_unknown = cut_str($steamID_unknown, 'steamcommunity.com/profiles/');
                    $steamID_unknown = rtrim($steamID_unknown, '/');
                }
            }

            $steamID_unknown = htmlentities($steamID_unknown);

            if (is_numeric($steamID_unknown)) {
                $steamIDconvertor->setSteamID($steamID_unknown);
                $steamID32 = $steamIDconvertor->getSteamID32();
            } else {
                $vanitySQL = $db->q('SELECT * FROM `mod_match_players_names` WHERE `player_vanity_url` = ? LIMIT 0,1;',
                    's',
                    $steamID_unknown);

                if (!empty($vanitySQL) && !empty($vanitySQL[0]['player_sid32'])) {
                    $steamIDconvertor->setSteamID($vanitySQL[0]['player_sid32']);
                    $steamID32 = $steamIDconvertor->getSteamID32();
                } else {
                    $customURL = $steamWebAPI->ResolveVanityURL($steamID_unknown);
                    if (!empty($customURL) && !empty($customURL['response']['success']) && $customURL['response']['success'] == 1 && !empty($customURL['response']['steamid'])) {
                        $steamIDconvertor->setSteamID($customURL['response']['steamid']);
                        $steamID32 = $steamIDconvertor->getSteamID32();

                        $vanitySQLinsert = $db->q(
                            'INSERT INTO `mod_match_players_names` (`player_sid32`, `player_vanity_url`)
                                VALUES (?, ?)
                                ON DUPLICATE KEY UPDATE `player_vanity_url` = VALUES(`player_vanity_url`);',
                            'ss',
                            $steamID32, $steamID_unknown);
                    } else {
                        $errorCode = !empty($customURL['response']['success'])
                            ? $customURL['response']['success']
                            : 'Unknown Error Code';

                        $errorMsg = !empty($customURL['response']['message'])
                            ? $customURL['response']['message']
                            : 'Unknown Error';

                        echo bootstrapMessage('Oh Snap', 'Bad VanityURL {' . $errorCode . '}: ' . $errorMsg);
                    }
                }
            }

            if (!empty($steamID32)) {
                $dbLink = $steamIDconvertor->getSteamID64() . ' <a class="db_link" href="http://dotabuff.com/players/' . $steamIDconvertor->getSteamID32() . '" target="_new">' . $steamIDconvertor->getSteamID32() . '</a>';
                echo '<div class="page-header"><h2>User Profile for: <small>' . $dbLink . '</small></h2></div>';

                $gamesList = cached_query(
                    'user_profile_matches_list_' . $steamID32,
                    'SELECT
                            mmp.*,
                            mmo.*,

                            ml.`mod_id` as modFakeID,
                            ml.`mod_name`,
                            ml.`mod_active`,

                            gcs.`cs_id`,
                            gcs.`cs_string`,
                            gcs.`cs_name`
                        FROM `mod_match_players` mmp
                        LEFT JOIN `mod_match_overview` mmo
                            ON mmp.`match_id` = mmo.`match_id`
                        LEFT JOIN `mod_list` ml
                            ON mmp.`mod_id` = ml.`mod_identifier`
                        LEFT JOIN `game_connection_status` gcs
                            ON mmp.`connection_status` = gcs.`cs_id`
                        WHERE `player_sid32` = ?
                        ORDER BY `date_recorded` DESC
                        LIMIT 0,50;',
                    's', //STUPID x64 windows PHP is actually x86
                    $steamID32,
                    60
                );

                echo '<h3>Matches <small>Last 50</small></h3>';

                if (!empty($gamesList)) {
                    echo '<div class="table-responsive">
		                    <table class="table table-striped table-hover">';
                    echo '
                            <tr>
                                <th class="text-center">Mod</th>
                                <th class="text-center">Match ID</th>
                                <th class="text-center">Connection</th>
                                <th class="text-center">Duration</th>
                                <th class="text-center">Players</th>
                                <th class="text-center">Recorded</th>
                            </tr>';

                    foreach ($gamesList as $key => $value) {
                        $modName = !empty($value['mod_name'])
                            ? $value['mod_name']
                            : 'Unknown';

                        $matchID = !empty($value['match_id'])
                            ? $value['match_id']
                            : 'Unknown';

                        $matchDuration = !empty($value['match_duration'])
                            ? number_format($value['match_duration'] / 60)
                            : 'Unknown';

                        $arrayGoodConnectionStatus = array(1, 2, 3, 5);
                        if (!empty($value['connection_status']) && in_array($value['connection_status'], $arrayGoodConnectionStatus)) {
                            $connectionStatus = '<span class="glyphicon glyphicon-ok-sign" title="' . $value['cs_string'] . '"></span>';
                        } else if (!empty($value['connection_status']) && $value['connection_status'] == 0) {
                            $connectionStatus = '<span class="glyphicon glyphicon-question-sign" title="' . $value['cs_string'] . '"></span>';
                        } else {
                            $connectionStatus = '<span class="glyphicon glyphicon-remove-sign" title="' . $value['cs_string'] . '"></span>';
                        }

                        $numPlayers = !empty($value['match_num_players'])
                            ? $value['match_num_players']
                            : 'Unknown';

                        $matchDate = !empty($value['match_recorded'])
                            ? relative_time($value['match_recorded'])
                            : 'Unknown';

                        echo '
                                <tr>
                                    <td><a class="nav-clickable" href="#d2mods__stats?id=' . $value['modFakeID'] . '">' . $modName . '</a></td>
                                    <td><a class="nav-clickable" href="#d2mods__match?id=' . $matchID . '">' . $matchID . '</a></td>
                                    <td class="text-center">' . $connectionStatus . '</td>
                                    <td class="text-right">' . $matchDuration . ' mins</td>
                                    <td class="text-center">' . $numPlayers . '</td>
                                    <td class="text-right">' . $matchDate . '</td>
                                </tr>';
                    }

                    echo '</table></div>';
                } else {
                    echo bootstrapMessage('Oh Snap', 'No games played yet!');
                }

                $lobbiesList = cached_query(
                    'user_profile_lobbies_list_' . $steamID32,
                    'SELECT
                            ll.`lobby_id`,
                            ll.`mod_id`,
                            ll.`lobby_name`,
                            ll.`lobby_started`,
                            ll.`lobby_region`,
                            ll.`lobby_ttl`,
                            ll.`lobby_max_players`,
                            ll.`lobby_public`,
                            ll.`lobby_leader`,
                            ll.`lobby_leader_name`,
                            ll.`lobby_pass`,
                            ll.`date_recorded` as lobby_date_recorded,
                            ml.*,
                            (
                              SELECT
                                  COUNT(`user_id64`)
                                FROM `lobby_list_players`
                                WHERE `lobby_id` = ll.`lobby_id`
                                LIMIT 0,1
                            ) AS lobby_current_players
                        FROM `lobby_list` ll
                        LEFT JOIN `mod_list` ml ON ll.`mod_id` = ml.`mod_id`
                        WHERE ll.`lobby_leader` = ?
                        ORDER BY lobby_date_recorded DESC
                        LIMIT 0,50;',
                    's', //STUPID x64 windows PHP is actually x86
                    $steamIDconvertor->getSteamID64(),
                    60
                );

                echo '<hr />';

                echo '<h3>Lobbies <small>Last 50</small></h3>';

                if (!empty($lobbiesList)) {
                    echo '<div class="table-responsive">
		                <table class="table table-striped table-hover">';

                    echo '<tr>
                            <th class="text-center col-md-2">Lobby</th>
                            <th class="text-center col-md-2">Leader</th>
                            <th class="text-center col-md-3">Mod</th>
                            <th class="text-center col-md-2">Players <span class="glyphicon glyphicon-question-sign" title="Number of players in lobby (Maximum players allowed in lobby)"></span></th>
                            <th class="text-center col-md-1">&nbsp;</th>
                            <th class="text-center col-md-2">Created <span class="glyphicon glyphicon-question-sign" title="When this lobby was created."></span></th>
                        </tr>';

                    foreach ($lobbiesList as $key => $value) {
                        $lobbyLeaderName = urldecode($value['lobby_leader_name']);
                        if (!empty($lobbyLeaderName)) {
                            if (strlen($lobbyLeaderName) > 12) {
                                $lobbyLeaderName = strip_tags(substr($lobbyLeaderName, 0, 9) . '...');
                            } else {
                                $lobbyLeaderName = strip_tags($lobbyLeaderName);
                            }
                        } else {
                            $lobbyLeaderName = 'Unknown User';
                        }

                        $lobbyName = urldecode($value['lobby_name']);
                        if (!empty($lobbyName)) {
                            if (strlen($lobbyName) > 13) {
                                $lobbyName = strip_tags(substr($lobbyName, 0, 10) . '...');
                            } else {
                                $lobbyName = strip_tags($lobbyName);
                            }
                        } else {
                            $lobbyName = 'Custom Game #' . $value['lobby_id'];
                        }

                        $lobbyStarted = !empty($value['lobby_started']) && $value['lobby_started'] == 1
                            ? '<span class="label-success label"><span class="glyphicon glyphicon-ok"></span></span>'
                            : '<span class="label-danger label"><span class="glyphicon glyphicon-remove"></span></span>';

                        echo '<tr>
                                <td class="vert-align"><a class="nav-clickable" href="#d2mods__lobby?id=' . $value['lobby_id'] . '">' . urldecode($lobbyName) . '</a></td>
                                <td class="vert-align">' . urldecode($value['lobby_leader_name']) . ' <a target="_blank" href="#d2mods__search?user=' . $value['lobby_leader'] . '"><span class="glyphicon glyphicon-search"></span></a></td>
                                <td class="vert-align"><a class="nav-clickable" href="#d2mods__stats?id=' . $value['mod_id'] . '">' . $value['mod_name'] . '</a></td>
                                <td class="text-center vert-align">' . $value['lobby_current_players'] . ' (' . $value['lobby_max_players'] . ')</td>
                                <td class="text-center vert-align">' . $lobbyStarted . '</td>
                                <td class="text-right vert-align">' . relative_time_v3($value['lobby_date_recorded'], 1) . '</td>
                            </tr>';
                    }

                    echo '</table></div>';
                } else {
                    echo bootstrapMessage('Oh Snap', 'No lobbies made yet!');
                }
            } else {
                echo bootstrapMessage('Oh Snap', 'Invalid SteamID');
            }
        } else {
            echo bootstrapMessage('Oh Snap', 'Invalid search query!');
        }
    } else {
        echo bootstrapMessage('Oh Snap', 'No DB!');
    }

    echo '<p>&nbsp;</p>
            <div class="text-center">
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__directory">Mod Directory</a>
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__recent_games">Recent Games</a>
            </div>';

    $memcache->close();
} catch
(Exception $e) {
    echo bootstrapMessage('Oh Snap', 'Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage());
}