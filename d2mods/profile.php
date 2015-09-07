<?php
require_once('../global_functions.php');
require_once('./functions.php');
require_once('../connections/parameters.php');

try {
    if (!isset($_SESSION)) {
        session_start();
    }

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    //DO LOGIN CHECKS AND CLEANUP
    checkLogin_v2();

    //IF LOGGED IN, CHECK IF ADMIN
    $adminCheck = !empty($_SESSION['user_id64'])
        ? adminCheck($_SESSION['user_id64'], 'admin')
        : NULL;

    $steamWebAPI = new steam_webapi($api_key1);

    $steamIDconvertor = new SteamID();

    $steamID_unknown = !empty($_GET['id'])
        ? $_GET['id']
        : NULL;

    if (!empty($steamID_unknown)) {
        /*echo '<pre>';
        print_r($steamWebAPI->GetFriendList('76561197989020883'));
        echo '</pre>';*/

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
            $steamID64 = $steamIDconvertor->getSteamID64();
        } else {
            $vanitySQL = $db->q('SELECT * FROM `mod_match_players_names` WHERE `player_vanity_url` = ? LIMIT 0,1;',
                's',
                $steamID_unknown);

            if (!empty($vanitySQL) && !empty($vanitySQL[0]['player_sid32'])) {
                $steamIDconvertor->setSteamID($vanitySQL[0]['player_sid32']);
                $steamID32 = $steamIDconvertor->getSteamID32();
                $steamID64 = $steamIDconvertor->getSteamID64();
            } else {
                $customURL = $steamWebAPI->ResolveVanityURL($steamID_unknown);
                if (!empty($customURL) && !empty($customURL['response']['success']) && $customURL['response']['success'] == 1 && !empty($customURL['response']['steamid'])) {
                    $steamIDconvertor->setSteamID($customURL['response']['steamid']);
                    $steamID32 = $steamIDconvertor->getSteamID32();
                    $steamID64 = $steamIDconvertor->getSteamID64();

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
            $userOptions = cached_query(
                'user_options_' . $steamID32,
                'SELECT
                        `user_id32`,
                        `user_id64`,
                        `mmr_public`,
                        `date_updated`,
                        `date_recorded`
                    FROM `gds_users_options`
                    WHERE `user_id32` = ?
                    LIMIT 0,1;',
                's',
                $steamID32,
                15
            );

            $userOptions = !empty($userOptions)
                ? $userOptions[0]
                : NULL;

            $dbLink = $steamID64 . ' <a class="db_link" href="http://dotabuff.com/players/' . $steamID32 . '" target="_new">' . $steamID32 . '</a>';
            echo '<div class="page-header"><h1>User Profile for: <small>' . $dbLink . '</small></h1></div>';

            echo '<div class="text-center">
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__lobby_list_old">Lobby List</a>
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__search">Find Another User</a>
               </div>';

            echo '<span class="h4">&nbsp;</span>';

            /////////////////////////
            // Match Summary
            /////////////////////////
            {
                echo '<h2>Match Summary</h2>';

                echo '<p>Summary of all of the games that the user has played. (Includes the games that fail to start)</p>';

                try {
                    $matchSummaryMods = cached_query(
                        'profile_msm_' . $steamID32,
                        'SELECT
                                ml.`mod_id`,
                                ml.`mod_identifier`,
                                ml.`mod_name`,
                                (SELECT COUNT(*) FROM `mod_match_players` WHERE `player_sid32` = mmp.`player_sid32` AND `mod_id` = mmp.`mod_id` LIMIT 0,1) as total_games,
                                (SELECT COUNT(*) FROM `mod_match_players` WHERE `player_sid32` = mmp.`player_sid32` AND `mod_id` = mmp.`mod_id` AND `connection_status` IN (2,3,4) LIMIT 0,1) as connected_games,
                                COUNT(*) AS num_games,
                                SUM(mmp.`player_won`) AS num_games_won,
                                MAX(mmp.`date_recorded`) AS most_recent_game
                            FROM `mod_match_players` mmp
                            JOIN `mod_list` ml ON mmp.`mod_id` = ml.`mod_identifier`
                            WHERE mmp.`player_sid32` = ? AND (SELECT COUNT(*) FROM `mod_match_players` WHERE `match_id` = mmp.`match_id` AND `connection_status` NOT IN (2,3,4) AND `player_sid32` > 0 LIMIT 0,1) = 0
                            GROUP BY ml.`mod_id`
                            ORDER BY num_games DESC;',
                        's',
                        array($steamID32),
                        5
                    );

                    if (empty($matchSummaryMods)) throw new Exception('User has not played any mods!');

                    $numGames = 0;
                    foreach ($matchSummaryMods as $key => $value) {
                        if (!empty($value['num_games'])) $numGames += $value['num_games'];
                    }

                    echo '<h4>' . number_format($numGames) . ' games played</h4>';

                    echo '<div class="row">
                            <div class="col-md-4"><strong>Mod</strong></div>
                            <div class="col-md-4 text-center">
                                <div class="row">
                                    <div class="col-md-4 text-center"><strong>Total</strong></div>
                                    <!--<div class="col-md-3 text-center"><strong>Connected</strong></div>-->
                                    <div class="col-md-4 text-center"><strong>Played</strong></div>
                                    <div class="col-md-4 text-center"><strong>Won</strong></div>
                                </div>
                            </div>
                            <div class="col-md-2 text-center"><strong>Most Recent</strong></div>
                        </div>';

                    foreach ($matchSummaryMods as $key => $value) {
                        echo '<div class="row">
                            <div class="col-md-4"><a class="nav-clickable" href="#d2mods__stats?id=' . $value['mod_id'] . '">' . $value['mod_name'] . '</a></div>
                            <div class="col-md-4">
                                <div class="row">
                                    <div class="col-md-4 text-right">' . number_format($value['total_games']) . '</div>
                                    <!--<div class="col-md-3 text-right">' . number_format($value['connected_games']) . '</div>-->
                                    <div class="col-md-4 text-right">' . number_format($value['num_games']) . '</div>
                                    <div class="col-md-4 text-right">' . number_format($value['num_games_won'] / $value['num_games'] * 100, 1) . ' %</div>
                                </div>
                            </div>
                            <div class="col-md-2 text-right">' . relative_time_v3($value['most_recent_game'], 1, 'day') . '</div>
                        </div>';
                    }

                    echo '<hr />';
                } catch (Exception $e) {
                    echo formatExceptionHandling($e);
                }
            }

            /////////////////////////
            // Lobby Summary
            /////////////////////////
            {
                echo '<h2>Lobby Summary</h2>';

                echo '<p>Summary of all of the lobbies that the user has created. (As measured by the host launching the game)</p>';

                try {
                    $lobbySummaryMods = cached_query(
                        'profile_lsm_' . $steamID32,
                        'SELECT
                                ml.`mod_id`,
                                ml.`mod_identifier`,
                                ml.`mod_name`,
                                COUNT(*) AS num_lobbies,
                                SUM(ll.`lobby_started`) AS num_lobbies_started,
                                MAX(ll.`date_recorded`) AS most_recent_lobby
                            FROM `mod_list` ml
                            JOIN `lobby_list` ll ON ml.`mod_id` = ll.`mod_id`
                            WHERE ll.`lobby_leader` = ?
                            GROUP BY ml.`mod_id`
                            ORDER BY num_lobbies DESC;',
                        's',
                        $steamID64,
                        60
                    );

                    if (empty($lobbySummaryMods)) throw new Exception('User has not created any lobbies!');

                    $numLobbies = 0;
                    foreach ($lobbySummaryMods as $key => $value) {
                        if (!empty($value['num_lobbies'])) $numLobbies += $value['num_lobbies'];
                    }

                    echo '<h4>' . number_format($numLobbies) . ' lobbies created</h4>';

                    echo '<div class="row">
                            <div class="col-md-4"><strong>Mod</strong></div>
                            <div class="col-md-2 text-center"><strong>Created</strong></div>
                            <div class="col-md-2 text-center"><strong>Started</strong></div>
                            <div class="col-md-2 text-center"><strong>Most Recent</strong></div>
                        </div>';

                    foreach ($lobbySummaryMods as $key => $value) {
                        echo '<div class="row">
                            <div class="col-md-4"><a class="nav-clickable" href="#d2mods__stats?id=' . $value['mod_id'] . '">' . $value['mod_name'] . '</a></div>
                            <div class="col-md-2 text-right">' . number_format($value['num_lobbies']) . '</div>
                            <div class="col-md-2 text-right">' . number_format($value['num_lobbies_started'] / $value['num_lobbies'] * 100, 1) . ' %</div>
                            <div class="col-md-2 text-right">' . relative_time_v3($value['most_recent_lobby'], 1, 'day') . '</div>
                        </div>';
                    }

                    echo '<hr />';
                } catch (Exception $e) {
                    echo formatExceptionHandling($e);
                }
            }

            echo '<h2>MMR History</h2>';

            echo '<p>Below is a graph that shows the history of this user\'s MMR (averaged for each day).</p>';

            //MMR History
            {
                try {
                    //SKIP OPTION CHECK IF ADMIN
                    if (empty($userOptions['mmr_public'])) {
                        if (empty($adminCheck)) {
                            throw new Exception('User has not given permission to share MMR history!');
                        } else {
                            echo '<div class="alert alert-danger" role="alert">Logged in as an admin! This role allows your to view this private data.</div>';
                        }
                    }

                    $myMMR = cached_query(
                        'user_mmr' . $steamID32,
                        'SELECT
                                HOUR(`date_recorded`) AS date_hour,
                                DAY(`date_recorded`) AS date_day,
                                MONTH(`date_recorded`) AS date_month,
                                YEAR(`date_recorded`) AS date_year,
                                `user_id32`,
                                `user_id64`,
                                `user_name`,
                                MAX(`user_games`) AS user_games,
                                AVG(`user_mmr_solo`) AS user_mmr_solo,
                                AVG(`user_mmr_party`) AS user_mmr_party,
                                `date_recorded`
                            FROM `gds_users_mmr`
                            WHERE `user_id32` = ?
                            GROUP BY 4,3,2,1
                            ORDER BY 4 DESC, 3 DESC, 2 DESC, 1 DESC;',
                        's',
                        $steamID32,
                        60
                    );

                    if (empty($myMMR)) throw new Exception('No MMRs recorded for this user!');

                    $testArray = array();

                    foreach ($myMMR as $key => $value) {
                        $modDate = $value['date_year'] . '-' . $value['date_month'] . '-' . $value['date_day'];
                        $testArray[$modDate]['solo_mmr'] = $value['user_mmr_solo'];
                    }

                    foreach ($myMMR as $key => $value) {
                        $modDate = $value['date_year'] . '-' . $value['date_month'] . '-' . $value['date_day'];
                        $testArray[$modDate]['party_mmr'] = $value['user_mmr_party'];
                    }

                    foreach ($myMMR as $key => $value) {
                        $modDate = $value['date_year'] . '-' . $value['date_month'] . '-' . $value['date_day'];
                        $testArray[$modDate]['num_games'] = $value['user_games'];
                    }

                    $options = array(
                        'height' => 400,
                        'chartArea' => array(
                            'width' => '80%',
                            'height' => '85%',
                            'left' => 80,
                            'top' => 10,
                        ),
                        'hAxis' => array(
                            'textPosition' => 'none',
                        ),
                        'vAxes' => array(
                            0 => array(
                                'title' => 'MMR',
                                //'textPosition' => 'in',
                                //'logScale' => 1,
                            ),
                            1 => array(
                                'title' => 'Games',
                                'textPosition' => 'out',
                                //'logScale' => 1,
                            ),
                        ),
                        'legend' => array(
                            'position' => 'bottom',
                            'alignment' => 'start',
                        ),
                        'seriesType' => 'line',
                        'series' => array(
                            0 => array(
                                'type' => 'line',
                                'targetAxisIndex' => 0,
                            ),
                            1 => array(
                                'type' => 'line',
                                'targetAxisIndex' => 0,
                            ),
                            2 => array(
                                'type' => 'line',
                                'targetAxisIndex' => 1,
                            ),
                        ),
                        'tooltip' => array( //'isHtml' => 1,
                        ),
                        'isStacked' => 1,
                        'focusTarget' => 'category',
                    );

                    $chart = new chart2('ComboChart');

                    $super_array = array();
                    foreach ($testArray as $key2 => $value2) {
                        $soloMMR = !empty($value2['solo_mmr'])
                            ? $value2['solo_mmr']
                            : 0;

                        $partyMMR = !empty($value2['party_mmr'])
                            ? $value2['party_mmr']
                            : 0;

                        $numGames = !empty($value2['num_games'])
                            ? $value2['num_games']
                            : 0;

                        $super_array[] = array('c' => array(
                            array('v' => $key2),
                            array('v' => $soloMMR),
                            array('v' => number_format($soloMMR)),
                            array('v' => $partyMMR),
                            array('v' => number_format($partyMMR)),
                            array('v' => $numGames),
                            array('v' => number_format($numGames)),
                        ));
                    }

                    $data = array(
                        'cols' => array(
                            array('id' => '', 'label' => 'Date', 'type' => 'string'),
                            array('id' => '', 'label' => 'Solo', 'type' => 'number'),
                            array('id' => '', 'label' => 'Tooltip', 'type' => 'string', 'role' => 'tooltip', 'p' => array('html' => 1)),
                            array('id' => '', 'label' => 'Party', 'type' => 'number'),
                            array('id' => '', 'label' => 'Tooltip', 'type' => 'string', 'role' => 'tooltip', 'p' => array('html' => 1)),
                            array('id' => '', 'label' => 'Games', 'type' => 'number'),
                            array('id' => '', 'label' => 'Tooltip', 'type' => 'string', 'role' => 'tooltip', 'p' => array('html' => 1)),
                        ),
                        'rows' => $super_array
                    );

                    $chart_width = max(count($super_array) * 8, 800);
                    $options['width'] = $chart_width;

                    echo '<div id="breakdown_mmr_history" class="d2mods-graph-wide-tall d2mods-graph-overflow"></div>';

                    $chart->load(json_encode($data));
                    echo $chart->draw('breakdown_mmr_history', $options);

                    echo '<hr />';
                } catch (Exception $e) {
                    echo formatExceptionHandling($e);
                }
            }
        } else {
            echo bootstrapMessage('Oh Snap', 'Invalid SteamID');
        }
    } else {
        echo bootstrapMessage('Oh Snap', 'Invalid search query!');
    }

    echo '<span class="h4">&nbsp;</span>';

    echo '<div class="text-center">
            <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__lobby_list_old">Lobby List</a>
            <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__search">Find Another User</a>
        </div>';

    echo '<span class="h4">&nbsp;</span>';
} catch (Exception $e) {
    echo formatExceptionHandling($e);
} finally {
    if (isset($memcache)) $memcache->close();
}