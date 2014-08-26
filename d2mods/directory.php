<?php
require_once('../global_functions.php');
require_once('../connections/parameters.php');

try {
    $db = new dbWrapper_v2($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site);

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    if ($db) {
        $modList = simple_cached_query('d2mods_directory', 'SELECT ml.*, gu.`user_name`, gu.`user_avatar` FROM `mod_list` ml LEFT JOIN `gds_users` gu ON ml.`steam_id64` = gu.`user_id64`;', 10);

        echo '<div class="page-header"><h2>Mod Directory <small>BETA</small></h2></div>';

        echo '<p>This is a directory of all the games that developers are planning to implement stats for. This section is a Work-In-Progress, so check back later.</p>';

        if (!empty($modList)) {
            echo '<div class="table-responsive">
		        <table class="table table-striped table-hover">';
            echo '<tr>
                        <th width="40">&nbsp;</th>
                        <th>&nbsp;</th>
                        <th class="text-center">Owner</th>
                        <th width="80" class="text-center">Links</th>
                        <th width="120" class="text-center">Added</th>
                    </tr>';

            foreach ($modList as $key => $value) {
                echo '<tr>
                        <td>' . ($key + 1) . '</td>
                        <th>' . $value['mod_name'] . '</th>
                        <td class="text-right">' . $value['user_name'] . ' <img width="20" height="20" src="' . $value['user_avatar'] . '"/></td>
                        <td class="text-center"><a href="' . $value['mod_workshop_link'] . '" target="_new">WS</a> || <a href="' . $value['mod_steam_group'] . '" target="_new">SG</a></td>
                        <td>' . relative_time($value['date_recorded']) . '</td>
                    </tr>
                    <tr>
                        <td colspan="6">' . $value['mod_description'] . '</td>
                    </tr>
                    <tr><td colspan="6">&nbsp;</td></tr>';
            }

            echo '</table></div>';
            echo '<h5><a class="nav-clickable" href="#d2mods__signup">Add a new mod</a></h5>';
        } else {
            echo '<div class="alert alert-danger" role="alert"><strong>Oh Snap:</strong> No mods added yet!</div>';
        }
    } else {
        echo '<div class="page-header"><div class="alert alert-danger" role="alert"><strong>Oh Snap:</strong> No DB!</div></div>';
    }

    $memcache->close();
} catch (Exception $e) {
    echo '<div class="page-header"><div class="alert alert-danger" role="alert"><strong>Oh Snap:</strong> Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage() . '</div></div>';
}