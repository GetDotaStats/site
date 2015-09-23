<?php
require_once('../global_functions.php');
require_once('../connections/parameters.php');

try {
    if (!isset($_SESSION)) {
        session_start();
    }

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    $lobbyID = !empty($_GET['id']) && is_numeric($_GET['id'])
        ? $_GET['id']
        : NULL;

    if (!empty($lobbyID)) {
        $lobbyDetails = $memcache->get('d2mods_lobby_details1_' . $lobbyID);
        if (!$lobbyDetails) {
            $lobbyDetails = $db->q(
                'SELECT
                        ll.`lobby_id`,
                        ll.`mod_id`,
                        ll.`lobby_ttl`,
                        ll.`lobby_min_players`,
                        ll.`lobby_max_players`,
                        ll.`lobby_public`,
                        ll.`lobby_leader`,
                        ll.`lobby_active`,
                        ll.`lobby_hosted`,
                        ll.`lobby_pass`,
                        ll.`lobby_map`,
                        ll.`date_recorded`,
                        ml.`mod_name`,
                        ml.`mod_maps`
                    FROM `lobby_list` ll
                    JOIN `mod_list` ml ON ll.`mod_id` = ml.`mod_id`
                    WHERE ll.`lobby_id` = ?
                    LIMIT 0,1;',
                'i',
                $lobbyID
            );
            $memcache->set('d2mods_lobby_details1_' . $lobbyID, $lobbyDetails, 0, 5);
        }

        echo '<div class="page-header"><h2>Lobby Details <small>BETA</small></h2></div>';

        if (!empty($lobbyDetails)) {
            $lobbyDetails = $lobbyDetails[0];

            $modID = $lobbyDetails['mod_id'];

            $modDetails = $memcache->get('d2mods_mod_details' . $modID);
            if (!$modDetails) {
                $modDetails = $db->q(
                    'SELECT * FROM `mod_list` WHERE `mod_id` = ? LIMIT 0,1;',
                    'i',
                    $modID
                );
                if (!empty($modDetails)) {
                    $modDetails = $modDetails[0];
                }
                $memcache->set('d2mods_mod_details' . $lobbyDetails['mod_id'], $modDetails, 0, 5 * 60);
            }

            //LOBBY DETAILS
            {
                if (!empty($lobbyDetails['lobby_map'])) {
                    $modMaps = $lobbyDetails['lobby_map'];
                } else {
                    $modMaps = 'dota_pvp??';
                }

                $wg = !empty($modDetails['mod_workshop_link'])
                    ? '<strong><a href="http://steamcommunity.com/sharedfiles/filedetails/?id=' . $modDetails['mod_workshop_link'] . '" target="_new">WS</a></strong>'
                    : '<strong>WS</strong>';

                echo '<div class="container">
                            <div class="col-sm-4">
                                <div class="table-responsive">
                                    <table class="table">
                                        <tr>
                                            <th>Mod</th>
                                            <td width="20"><span class="glyphicon glyphicon-question-sign" title="The mod this lobby will be for."></span></td>
                                            <td><a class="nav-clickable" href="#s2__mod?id=' . $modID . '">' . $lobbyDetails['mod_name'] . '</a></td>
                                            <td width="20">' . $wg . '</td>
                                        </tr>
                                        <tr>
                                            <th>Max Players</th>
                                            <td><span class="glyphicon glyphicon-question-sign" title="The maximum number of players this host will wait for."></span></td>
                                            <td>' . $lobbyDetails['lobby_max_players'] . '</td>
                                            <td>&nbsp;</td>
                                        </tr>
                                        <tr>
                                            <th>Map</th>
                                            <td><span class="glyphicon glyphicon-question-sign" title="The map that the host has selected."></span></td>
                                            <td>' . $modMaps . '</td>
                                            <td>&nbsp;</td>
                                        </tr>
                                        <tr>
                                            <th>Created</th>
                                            <td><span class="glyphicon glyphicon-question-sign" title="When this mod was created. (How long it will be advertised)."></span></td>
                                            <td>' . relative_time_v3($lobbyDetails['date_recorded']) . ' <strong>(' . $lobbyDetails['lobby_ttl'] . ' mins)</strong></td>
                                            <td>&nbsp;</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>';
            }

            $lobbyPlayers = $memcache->get('d2mods_lobby_players1_' . $lobbyID);
            if (!$lobbyPlayers) {
                $lobbyPlayers = $db->q(
                    'SELECT
                            llp.`lobby_id`,
                            llp.`user_id64`,
                            llp.`user_confirmed`,
                            llp.`user_name`
                        FROM `lobby_list_players` llp
                        WHERE lobby_id = ?;',
                    'i',
                    $lobbyID
                );
                $memcache->set('d2mods_lobby_players1_' . $lobbyID, $lobbyPlayers, 0, 5);
            }

            echo '</div>';

            $lobbyPlayersArray = array();
            if (!empty($lobbyPlayers)) {
                foreach ($lobbyPlayers as $key => $value) {
                    $lobbyPlayersArray[] = $value['user_id64'];
                }
            }

            if (!empty($lobbyDetails) && $lobbyDetails['lobby_active'] == 0) {
                echo '<div class="alert alert-danger" role="alert">Lobby has now expired! <a class="nav-clickable btn btn-danger btn-md" href="#d2mods__lobby_list_old">Lobby List</a></div>';
            } else if (!empty($lobbyDetails) && $lobbyDetails['lobby_hosted'] == 1) {
                echo '<div class="alert alert-success" role="alert">Lobby is ready to join! <a class="btn btn-sm btn-success" href="steam://launch/570 ">START DOTA CLIENT</a></div>';
            }

            if (!empty($lobbyPlayers)) {
                //LOBBY PLAYER LIST
                {
                    echo '<div class="container">';
                    echo '<div class="col-sm-5">';
                    echo '<div class="table-responsive">
		                    <table class="table table-striped table-hover">';
                    echo '<tr>
                                <th class="text-center">Player</th>
                                <th class="col-sm-1 text-center">Confirmed</th>
                            </tr>';

                    foreach ($lobbyPlayers as $key => $value) {
                        $lobbyConfirmedContextual = $value['user_confirmed'] == 1
                            ? '<span class="glyphicon glyphicon-ok"></span>'
                            : '<span class="glyphicon glyphicon-remove"></span>';

                        $lobbyLeaderMark = $lobbyDetails['lobby_leader'] == $value['user_id64']
                            ? '<span class="glyphicon glyphicon-asterisk"></span> '
                            : '';

                        echo '<tr>
                                    <td class="vert-align">' . $lobbyLeaderMark . strip_tags($value['user_name']) . ' <a class="nav-clickable" href="#d2mods__profile?id=' . $value['user_id64'] . '"><span class="glyphicon glyphicon-search"></span></a></td>
                                    <td class="text-center vert-align">' . $lobbyConfirmedContextual . '</td>
                                </tr>';
                    }

                    $playersInLobby = count($lobbyPlayers);
                    if ($playersInLobby < $lobbyDetails['lobby_max_players']) {
                        for ($i = $playersInLobby; $i < $lobbyDetails['lobby_max_players']; $i++) {
                            echo '<tr>
                                            <td class="vert-align">EMPTY</td>
                                            <td class="text-center vert-align">&nbsp;</td>
                                        </tr>';
                        }
                    }

                    echo '</table></div>';
                    echo '</div></div>';
                }
            } else {
                echo bootstrapMessage('Oh Snap', 'No players in lobby!', 'danger');
            }


            ?>
            <script type="application/javascript">
                $(document).ready(function () {
                    pageReloader = setTimeout(function () {
                        if (document.getElementById("nav-refresh-holder").getAttribute("href") == "#d2mods__lobby?id=<?=$lobbyID?>" && <?=!empty($lobbyDetails) && $lobbyDetails['lobby_active'] == 1 ? 1 : 0?> == 1
                        )
                        {
                            loadPage("#d2mods__lobby?id=<?=$lobbyID?>", 1);
                        }
                        else
                        {
                            clearTimeout(pageReloader);
                        }
                    }, 10000);
                });
            </script>
        <?php
        } else {
            echo bootstrapMessage('Oh Snap', 'Bad lobby ID!', 'danger');
        }
    } else {
        echo bootstrapMessage('Oh Snap', 'No lobby specified!', 'danger');
    }

    $memcache->close();

    echo '<p>
            <div class="text-center">
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__lobby_list_old">Lobby List</a>
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__recent_games">Recent Games</a>
           </div>
        </p>';

} catch (Exception $e) {
    echo formatExceptionHandling($e);
}