<?php
require_once('../global_functions.php');
require_once('./functions.php');
require_once('../connections/parameters.php');

try {
    if (!isset($_SESSION)) {
        session_start();
    }

    checkLogin_v2();

    if (!empty($_SESSION['user_id64'])) {
        $db = new dbWrapper_v2($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site);
        $db->q('SET NAMES utf8;');

        $memcache = new Memcache;
        $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

        if ($db) {
            $steamID = new SteamID($_SESSION['user_id64']);

            $gamesList = $db->q(
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
                    ORDER BY `date_recorded` DESC;',
                's', //STUPID x64 windows PHP is actually x86
                $steamID->getSteamID32());

            echo '<div class="page-header"><h2>My Games <small>BETA</small></h2></div>';

            echo '<p>This is a list of the games you have played. This section is a Work-In-Progress, so check back later.</p>';
            echo '<p>Please note that old games did not record connection status. Do not worry if those old games have marked you as a non-loader.</p>';

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

                    $arrayGoodConnectionStatus = array(2, 3, 5);
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
        } else {
            echo '<div class="page-header"><div class="alert alert-danger" role="alert"><strong>Oh Snap:</strong> No DB!</div></div>';
        }

        $memcache->close();
    } else {
        echo '<div class="page-header"><div class="alert alert-danger" role="alert"><strong>Oh Snap:</strong> Not logged in!</div></div>';
    }

    echo '<p>
            <div class="text-center">
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__directory">Mod Directory</a>
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__recent_games">Recent Games</a>
            </div>
        </p>';

} catch
(Exception $e) {
    echo '<div class="page-header"><div class="alert alert-danger" role="alert"><strong>Oh Snap:</strong> Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage() . '</div></div>';
}