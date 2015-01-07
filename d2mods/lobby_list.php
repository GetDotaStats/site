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
                    `lobby_id`,
                    `mod_id`,
                    `lobby_ttl`,
                    `lobby_min_players`,
                    `lobby_max_players`,
                    `lobby_public`,
                    `lobby_leader`,
                    `lobby_active`,
                    `date_recorded`
                FROM `lobby_list`
                WHERE `lobby_active` = 1
                ORDER BY `date_recorded` ASC;'
            , 5
        );

        echo '<div class="page-header"><h2>Lobby List <small>BETA</small></h2></div>';

        echo '<p>This is a list of all of the active lobbies.</p>';

        if (!empty($lobbyListActive)) {
            echo '<div class="table-responsive">
		        <table class="table table-striped table-hover">';
            echo '<tr>
                        <th width="40">ID</th>
                        <th>Mod</th>
                        <th>Leader</th>
                        <th>Public</th>
                        <th>Min</th>
                        <th>Max</th>
                        <th>Active</th>
                        <th>TTL</th>
                        <th>Created</th>
                    </tr>';

            foreach ($lobbyListActive as $key => $value) {
                echo '<tr>
                        <td>' . $value['lobby_id'] . '</td>
                        <td>' . $value['mod_id'] . '</td>
                        <td>' . $value['lobby_leader'] . '</td>
                        <td>' . $value['lobby_public'] . '</td>
                        <td>' . $value['lobby_min_players'] . '</td>
                        <td>' . $value['lobby_max_players'] . '</td>
                        <td>' . $value['lobby_active'] . '</td>
                        <td>' . $value['lobby_ttl'] . '</td>
                        <td>' . $value['date_recorded'] . '</td>
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