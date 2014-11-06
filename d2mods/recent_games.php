<?php
require_once('../global_functions.php');
require_once('../connections/parameters.php');

try {
    $db = new dbWrapper_v2($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site);

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    if ($db) {
        $modListActive = simple_cached_query('d2mods_directory_active',
            //,(SELECT COUNT(DISTINCT mmp.`player_sid32`) FROM `mod_match_players` mmp WHERE mmp.`mod_id` = ml.`mod_identifier` GROUP BY `mod_id`) AS players_all_time
            'SELECT
                    mmo.`match_id`,
                    mmo.`mod_id`,
                    mmo.`match_duration`,
                    mmo.`match_num_players`,
                    mmo.`match_recorded`,
                    ml.`mod_id` as modFakeID,
                    ml.`mod_name`,
                    ml.`mod_active`
                FROM `mod_match_overview` mmo
                LEFT JOIN `mod_list` ml ON mmo.`mod_id` = ml.`mod_identifier`
                ORDER BY `date_recorded` DESC
                LIMIT 0,50;'
            , 30
        );

        echo '<div class="page-header"><h2>Recent Games <small>BETA</small></h2></div>';

        echo '<p>This is a directory of the last 50 games played that developers have implemented stats for.</p>';

        if (!empty($modListActive)) {

            echo '<div class="table-responsive">
		        <table class="table table-striped table-hover">';
            echo '
                <tr>
                    <th>Mod</th>
                    <th>Match ID</th>
                    <th>Duration</th>
                    <th>Players</th>
                </tr>';

            foreach ($modListActive as $key => $value) {
                echo '
                    <tr>
                        <td>' . $value['mod_name'] . '</td>
                        <td>' . $value['match_id'] . '</td>
                        <td>' . number_format($value['match_duration'] / 60) . ' mins</td>
                        <td>' . $value['match_num_players'] . '</td>
                    </tr>';
            }

            echo '</table></div>';
        } else {
            echo bootstrapMessage('Oh Snap', 'No games played yet!');
        }
    }

    $memcache->close();
} catch (Exception $e) {
    echo '<div class="page-header"><div class="alert alert-danger" role="alert"><strong>Oh Snap:</strong> Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage() . '</div></div>';
}