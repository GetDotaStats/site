<?php
require_once('../global_functions.php');
require_once('../connections/parameters.php');

try {
    $db = new dbWrapper_v2($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site);

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    if ($db) {
        $modListActive = simple_cached_query('d2mods_directory_active',
            'SELECT
                    ml.*,
                    gu.`user_name`,
                    gu.`user_avatar`,
                    (SELECT COUNT(*) FROM `mod_match_overview` WHERE `mod_id` = ml.`mod_identifier` GROUP BY `mod_id`) AS num_games
                FROM `mod_list` ml
                LEFT JOIN `gds_users` gu ON ml.`steam_id64` = gu.`user_id64`
                WHERE ml.`mod_active` = 1
                ORDER BY num_games DESC, `date_recorded` DESC;'
            , 30
        );

        $modListInactive = simple_cached_query('d2mods_directory_inactive',
            'SELECT ml.*, gu.`user_name`, gu.`user_avatar`
                FROM `mod_list` ml
                LEFT JOIN `gds_users` gu ON ml.`steam_id64` = gu.`user_id64`
                WHERE ml.`mod_active` = 0
                ORDER BY `date_recorded` DESC LIMIT 0,10;'
            , 10
        );

        echo '<div class="page-header"><h2>Mod Directory <small>BETA</small></h2></div>';

        echo '<p>This is a directory of all the games that developers are planning to implement stats for. This section is a Work-In-Progress, so check back later.</p>';

        echo '<h5><a class="nav-clickable" href="#d2mods__signup">Add a new mod</a></h5>';

        if (!empty($modListActive)) {
            echo '<h3>Approved Mods</h3>';
            echo '<div class="table-responsive">
		        <table class="table table-striped table-hover">';
            echo '<tr>
                        <th width="40">&nbsp;</th>
                        <th>&nbsp;</th>
                        <th class="text-right">Games</th>
                        <th width="170" class="text-left">Owner</th>
                        <th width="80" class="text-center">Links</th>
                        <th width="120" class="text-center">Added</th>
                    </tr>';

            foreach ($modListActive as $key => $value) {
                $sg = !empty($value['mod_steam_group'])
                    ? '<a href="http://steamcommunity.com/groups/' . $value['mod_steam_group'] . '" target="_new">SG</a>'
                    : 'SG';

                $wg = !empty($value['mod_workshop_link'])
                    ? '<a href="http://steamcommunity.com/sharedfiles/filedetails/?id=' . $value['mod_workshop_link'] . '" target="_new">WS</a>'
                    : 'WG';

                echo '<tr>
                        <td>' . ($key + 1) . '</td>
                        <th><a class="nav-clickable" href="#d2mods__stats?id=' . $value['mod_id'] . '">' . $value['mod_name'] . '</a></th>
                        <th class="text-right">' . number_format($value['num_games']) . '</th>
                        <td>' . '<img width="20" height="20" src="' . $value['user_avatar'] . '"/> ' . $value['user_name'] . '</td>
                        <td class="text-center">' . $wg . ' || ' . $sg . '</td>
                        <td class="text-left">' . relative_time($value['date_recorded']) . '</td>
                    </tr>
                    <tr>
                        <td colspan="6">' . $value['mod_description'] . '<br /><br /></td>
                    </tr>';
            }

            echo '</table></div>';
        } else {
            echo '<div class="alert alert-danger" role="alert"><strong>Oh Snap:</strong> No active mods added yet!</div>';
        }

        if (!empty($modListInactive)) {
            echo '<hr />';
            echo '<h3>Mods waiting approval <small>Last 10</small></h3>';
            echo '<div class="table-responsive">
		        <table class="table table-striped table-hover">';
            echo '<tr>
                        <th width="40">&nbsp;</th>
                        <th>&nbsp;</th>
                        <th width="170" class="text-left">Owner</th>
                        <th width="80" class="text-center">Links</th>
                        <th width="120" class="text-center">Added</th>
                    </tr>';

            foreach ($modListInactive as $key => $value) {
                $sg = !empty($value['mod_steam_group'])
                    ? '<a href="http://steamcommunity.com/groups/' . $value['mod_steam_group'] . '" target="_new">SG</a>'
                    : 'SG';

                $wg = !empty($value['mod_workshop_link'])
                    ? '<a href="http://steamcommunity.com/sharedfiles/filedetails/?id=' . $value['mod_workshop_link'] . '" target="_new">WS</a>'
                    : 'WG';

                echo '<tr>
                        <td>' . ($key + 1) . '</td>
                        <th>' . $value['mod_name'] . '</th>
                        <td>' . '<img width="20" height="20" src="' . $value['user_avatar'] . '"/> ' . $value['user_name'] . '</td>
                        <td class="text-center">' . $wg . ' || ' . $sg . '</td>
                        <td class="text-left">' . relative_time($value['date_recorded']) . '</td>
                    </tr>
                    <tr>
                        <td colspan="6">' . $value['mod_description'] . '<br /><br /></td>
                    </tr>';
            }

            echo '</table></div>';
        } else {
            echo '<div class="alert alert-danger" role="alert"><strong>Oh Snap:</strong> No mods added yet!</div>';
        }
    }

    $memcache->close();
} catch (Exception $e) {
    echo '<div class="page-header"><div class="alert alert-danger" role="alert"><strong>Oh Snap:</strong> Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage() . '</div></div>';
}