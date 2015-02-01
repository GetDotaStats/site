<?php
require_once('../global_functions.php');
require_once('../connections/parameters.php');

try {
    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    if ($db) {
        $lobbyListActive = simple_cached_query('d2mods_lobby_list_active',
            'SELECT
                    ll.`lobby_id`,
                    ll.`mod_id`,
                    ll.`lobby_name`,
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
                WHERE ll.`lobby_active` = 1 AND ll.`lobby_hosted` = 1
                ORDER BY lobby_current_players DESC;'
            , 5
        );

        $lobbyListDead = simple_cached_query('d2mods_lobby_list_dead',
            'SELECT
                    ll.`lobby_id`,
                    ll.`mod_id`,
                    ll.`lobby_name`,
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
                WHERE ll.`lobby_active` = 0
                ORDER BY ll.`date_recorded` DESC
                LIMIT 0,20;'
            , 5
        );

        $lobbyListCount = simple_cached_query('d2mods_lobby_list_count',
            'SELECT
                    COUNT(*) AS lobby_count
                FROM `lobby_list` ll
                LIMIT 0,1;'
            , 60
        );

        echo '<div class="page-header"><h2>Lobby List <small>BETA</small></h2></div>';

        echo '<p>This is a list of all of the active lobbies. There have been <strong>' . number_format($lobbyListCount[0]['lobby_count']) . '</strong> lobbies created. Please leave suggestions on how we can improve this tool in the chatbox.</p>';

        echo '<div class="alert alert-info" role="alert"><strong><a class="nav-clickable" href="#d2mods__lobby_guide">Check out our guide to installing the Lobby Explorer</a></strong> OR hop right in and
        <a target="_blank" class="btn btn-success btn-sm" href="https://github.com/GetDotaStats/GetDotaLobby/raw/lobbybrowser/play_weekend_tourney.zip">Download the Lobby Explorer</a></div>';

        echo '<div class="page-header"><h3>Active Lobbies</h3></div>';
        if (!empty($lobbyListActive)) {

            echo '<div class="table-responsive">
		        <table class="table table-striped table-hover">';
            echo '<tr>
                        <th class="text-center">Lobby</th>
                        <th class="text-center">Leader</th>
                        <th class="text-center">Mod</th>
                        <th class="text-center col-md-2">Players <span class="glyphicon glyphicon-question-sign" title="Number of players in lobby (Maximum players allowed in lobby)"></span></th>
                        <th class="text-center col-md-2">Created <span class="glyphicon glyphicon-question-sign" title="When this lobby was created. (How long it will be advertised)."></span></th>
                        <th class="text-center col-md-1">&nbsp;</th>
                    </tr>';

            foreach ($lobbyListActive as $key => $value) {
                $lobbyName = !empty($value['lobby_name'])
                    ? htmlentities($value['lobby_name'])
                    : 'Custom Game #' . $value['lobby_id'];

                echo '<tr>
                        <td class="vert-align"><a class="nav-clickable" href="#d2mods__lobby?id=' . $value['lobby_id'] . '">' . urldecode($lobbyName) . '</a></td>
                        <td class="vert-align">' . urldecode($value['lobby_leader_name']) . ' <a target="_blank" href="#d2mods__search?user=' . $value['lobby_leader'] . '"><span class="glyphicon glyphicon-search"></span></a></td>
                        <td class="vert-align"><a class="nav-clickable" href="#d2mods__stats?id=' . $value['mod_id'] . '">' . $value['mod_name'] . '</a></td>
                        <td class="text-center vert-align">' . $value['lobby_current_players'] . ' (' . $value['lobby_max_players'] . ')</td>
                        <td class="text-center vert-align">' . relative_time($value['lobby_date_recorded']) . ' <strong>(' . $value['lobby_ttl'] . ')</strong></td>
                        <td class="text-center vert-align"><a class="nav-clickable btn btn-success btn-sm" href="#d2mods__lobby?id=' . $value['lobby_id'] . '">JOIN</a></td>
                    </tr>';
            }

            echo '</table></div>';
        } else {
            echo bootstrapMessage('Oh Snap', 'No active lobbies! You should probably go make one.', 'danger');
        }

        echo '<div class="page-header"><h3>Recently Closed Lobbies</h3></div>';
        if (!empty($lobbyListDead)) {

            echo '<div class="table-responsive">
		        <table class="table table-striped table-hover">';
            echo '<tr>
                        <th class="text-center">Lobby</th>
                        <th class="text-center">Leader</th>
                        <th class="text-center">Mod</th>
                        <th class="text-center col-md-2">Players <span class="glyphicon glyphicon-question-sign" title="Number of players in lobby (Maximum players allowed in lobby)"></span></th>
                        <th class="text-center col-md-2">Created <span class="glyphicon glyphicon-question-sign" title="When this lobby was created."></span></th>
                    </tr>';

            foreach ($lobbyListDead as $key => $value) {
                $lobbyName = !empty($value['lobby_name'])
                    ? htmlentities($value['lobby_name'])
                    : 'Custom Game #' . $value['lobby_id'];

                echo '<tr>
                        <td class="vert-align"><a class="nav-clickable" href="#d2mods__lobby?id=' . $value['lobby_id'] . '">' . urldecode($lobbyName) . '</a></td>
                        <td class="vert-align">' . urldecode($value['lobby_leader_name']) . ' <a target="_blank" href="#d2mods__search?user=' . $value['lobby_leader'] . '"><span class="glyphicon glyphicon-search"></span></a></td>
                        <td class="vert-align"><a class="nav-clickable" href="#d2mods__stats?id=' . $value['mod_id'] . '">' . $value['mod_name'] . '</a></td>
                        <td class="text-center vert-align">' . $value['lobby_current_players'] . ' (' . $value['lobby_max_players'] . ')</td>
                        <td class="text-right vert-align">' . relative_time($value['lobby_date_recorded']) . '</td>
                    </tr>';
            }

            echo '</table></div>';
        } else {
            echo bootstrapMessage('Oh Snap', 'No lobbies have ever been made!.', 'danger');
        }
    } else {
        echo bootstrapMessage('Oh Snap', 'No db!', 'danger');
    }

    ?>
    <script>
        $(document).ready(function () {
            pageReloader = setTimeout(function () {
                if (document.getElementById("nav-refresh-holder").getAttribute("href") == "#d2mods__lobby_list") {
                    loadPage("#d2mods__lobby_list", 1);
                }
                else {
                    clearTimeout(pageReloader);
                }
            }, 10000);
        });
    </script>
    <?php

    echo '<p>
            <div class="text-center">
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__lobby_list">Lobby List</a>
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__recent_games">Recent Games</a>
           </div>
        </p>';

    $memcache->close();
} catch (Exception $e) {
    $message = 'Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage();
    echo bootstrapMessage('Oh Snap', $message, 'danger');
}