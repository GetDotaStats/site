<?php
require_once('../global_functions.php');
require_once('../connections/parameters.php');

try {
    $db = new dbWrapper_v2($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site);
    $db->q('SET NAMES utf8;');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    if ($db) {
        $lobbyListActive = simple_cached_query('d2mods_lobby_list_active',
            'SELECT
                    ll.`lobby_id`,
                    ll.`mod_id`,
                    ll.`lobby_ttl`,
                    ll.`lobby_min_players`,
                    ll.`lobby_max_players`,
                    ll.`lobby_public`,
                    ll.`lobby_leader`,
                    ll.`lobby_pass`,
                    ll.`date_recorded` as lobby_date_recorded,
                    ml.*,
                    gu.`user_name` as lobby_leader_username,
                    (
                      SELECT
                          COUNT(`user_id64`)
                        FROM `lobby_list_players`
                        WHERE `lobby_id` = ll.`lobby_id`
                        LIMIT 0,1
                    ) AS lobby_current_players
                FROM `lobby_list` ll
                LEFT JOIN `mod_list` ml ON ll.`mod_id` = ml.`mod_id`
                LEFT JOIN `gds_users` gu ON ll.`lobby_leader` = gu.`user_id64`
                WHERE ll.`lobby_active` = 1
                ORDER BY ll.`date_recorded` ASC;'
            , 5
        );

        echo '<div class="page-header"><h2>Lobby List <small>BETA</small></h2></div>';

        echo '<p>This is a list of all of the active lobbies.</p>';

        if (!empty($lobbyListActive)) {
            echo '<div class="table-responsive">
		        <table class="table table-striped table-hover">';
            echo '<tr>
                        <th class="text-center">Mod</th>
                        <th class="text-center">Leader</th>
                        <th class="text-center col-md-2">Players <span class="glyphicon glyphicon-question-sign" title="Number of players in lobby (Minimum / Maximum)"></span></th>
                        <th class="text-center col-md-2">Public <span class="glyphicon glyphicon-question-sign" title="Whether this lobby uses the public password"></span></th>
                        <th class="text-center col-md-1">TTL <span class="glyphicon glyphicon-question-sign" title="How long this lobby is open for"></span></th>
                        <th class="text-center col-md-2">Created <span class="glyphicon glyphicon-question-sign" title="When this lobby was created"></span></th>
                        <th class="text-center col-md-1">&nbsp;</th>
                    </tr>';

            foreach ($lobbyListActive as $key => $value) {
                $lobbyPublicContextual = $value['lobby_public'] == 1
                    ? '<span class="glyphicon glyphicon-ok"></span>'
                    : '<span class="glyphicon glyphicon-remove"></span>';

                echo '<tr>
                        <td class="vert-align"><a class="nav-clickable" href="#d2mods__stats?id=' . $value['mod_id'] . '">' . $value['mod_name'] . '</a></td>
                        <td class="vert-align">' . $value['lobby_leader_username'] . '</td>
                        <td class="text-center vert-align">' . $value['lobby_current_players'] . ' (' . $value['lobby_min_players'] . ' / ' . $value['lobby_max_players'] . ')</td>
                        <td class="text-center vert-align">' . $lobbyPublicContextual . '</td>
                        <td class="text-center vert-align">' . $value['lobby_ttl'] . ' mins</td>
                        <td class="text-right vert-align">' . relative_time($value['lobby_date_recorded']) . '</td>
                        <td class="text-center vert-align"><a class="nav-clickable btn btn-success btn-sm" href="#d2mods__lobby?id=' . $value['lobby_id'] . '">JOIN</a></td>
                    </tr>';
            }

            echo '</table></div>';
        } else {
            echo bootstrapMessage('Oh Snap', 'No active lobbies!', 'danger');
        }
    }

    echo '<p>
            <div class="text-center">
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__lobby_create">Create Lobby</a>
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__lobby_list">Lobby List</a>
           </div>
        </p>';

    $memcache->close();
} catch (Exception $e) {
    $message = 'Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage();
    echo bootstrapMessage('Oh Snap', $message, 'danger');
}