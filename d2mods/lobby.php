<?php
require_once('../global_functions.php');
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
            $lobbyID = !empty($_GET['id']) && is_numeric($_GET['id'])
                ? $_GET['id']
                : NULL;

            if (!empty($lobbyID)) {
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
                        if ($lobbyDetails['lobby_leader'] == $_SESSION['user_id64']) {
                            if (!empty($lobbyDetails['mod_maps'])) {
                                $modMapsArray = json_decode($lobbyDetails['mod_maps'], 1);

                                if (!empty($modMapsArray)) {
                                    $modMaps = '<select name="lobby_map" size="' . count($modMapsArray) . '" onchange="changeMap(this.value)">';
                                    foreach ($modMapsArray as $key => $value) {
                                        if ($value == $lobbyDetails['lobby_map']) {
                                            $modMapsSelect = ' selected';
                                        } else {
                                            $modMapsSelect = '';
                                        }
                                        $modMaps .= '<option' . $modMapsSelect . ' value="' . $value . '">' . $value . '</option>';
                                    }
                                    $modMaps .= '</select>';
                                } else {
                                    $modMaps = 'dota_pvp?';
                                }
                            } else {
                                $modMaps = 'dota_pvp?';
                            }
                        } else {
                            if (!empty($lobbyDetails['lobby_map'])) {
                                $modMaps = $lobbyDetails['lobby_map'];
                            } else {
                                $modMaps = 'dota_pvp??';
                            }
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
                                            <td><a class="nav-clickable" href="#d2mods__stats?id=' . $modID .'">' . $lobbyDetails['mod_name'] . '</a></td>
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
                                            <th>Password</th>
                                            <td><span class="glyphicon glyphicon-question-sign" title="The password people must use to enter the lobby in-game"></span></td>
                                            <td>' . $lobbyDetails['lobby_pass'] . '</td>
                                            <td>&nbsp;</td>
                                        </tr>
                                        <tr>
                                            <th>Created</th>
                                            <td><span class="glyphicon glyphicon-question-sign" title="When this mod was created. (How long it will be advertised)."></span></td>
                                            <td>' . relative_time($lobbyDetails['date_recorded']) . ' <strong>(' . $lobbyDetails['lobby_ttl'] . ' mins)</strong></td>
                                            <td>&nbsp;</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>';
                    }

                    $lobbyPlayers = $db->q(
                        'SELECT
                                llp.`lobby_id`,
                                llp.`user_id64`,
                                llp.`user_confirmed`,
                                gu.`user_name`
                            FROM `lobby_list_players` llp
                            JOIN `gds_users` gu ON llp.`user_id64` = gu.`user_id64`
                            WHERE lobby_id = ?;',
                        'i',
                        $lobbyID
                    );

                    $lobbyPlayersArray = array();
                    if (!empty($lobbyPlayers)) {
                        foreach ($lobbyPlayers as $key => $value) {
                            $lobbyPlayersArray[] = $value['user_id64'];
                        }
                    }

                    //LOBBY ACTION BUTTONS
                    {
                        if ($lobbyDetails['lobby_active'] == 1) {
                            echo '<div class="col-sm-3">';
                            echo '<div class="panel panel-primary" id="lobby_user_actions">';
                            echo '<div class="panel-body">';

                            if ($lobbyDetails['lobby_leader'] == $_SESSION['user_id64']) {
                                echo '<form id="lobbyClose" class="pull-left">
                                <input type="hidden" name="lobby_id" value="' . $lobbyID . '">
                                <button>Close</button>
                            </form>';
                            }

                            if (!in_array($_SESSION['user_id64'], $lobbyPlayersArray)) {
                                echo '<form id="lobbyJoin" class="pull-left">
                                <input type="hidden" name="lobby_id" value="' . $lobbyID . '">
                                <button>Join</button>
                            </form>';
                            }

                            if (in_array($_SESSION['user_id64'], $lobbyPlayersArray)) {
                                echo '<form id="lobbyLeave" class="pull-left">
                                <input type="hidden" name="lobby_id" value="' . $lobbyID . '">
                                <button>Leave</button>
                            </form>';
                            }

                            echo '</div></div></div>';
                        }

                        echo '</div>';
                    }

                    echo '<div class="container"><span id="lobbyResult" class="label label-danger"></span></div>';

                    if (!empty($lobbyDetails) && $lobbyDetails['lobby_hosted'] == 1) {
                        echo '<div class="alert alert-success" role="alert">Lobby is ready to join!</div>';
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

                                $lobbyLeaderMark = $lobbyDetails['lobby_leader'] == $_SESSION['user_id64']
                                    ? '<span class="glyphicon glyphicon-asterisk"></span> '
                                    : '';

                                echo '<tr>
                                    <td class="vert-align">' . $lobbyLeaderMark . $value['user_name'] . ' <a target="_blank" href="#d2mods__search?user=' . $value['user_id64'] . '"><span class="glyphicon glyphicon-search"></span></a></td>
                                    <td class="text-center vert-align">' . $lobbyConfirmedContextual . '</td>
                                </tr>';
                            }

                            $playersInLobby = count($lobbyPlayers);
                            if ($playersInLobby < $lobbyDetails['lobby_max_players']) {
                                for ($i = 1; $i < $lobbyDetails['lobby_max_players']; $i++) {
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
                        function changeMap(mapName) {
                            console.log("triggered!!!");
                            clearTimeout(pageReloader);
                            $.post("./d2mods/lobby_update_map.php", {"lobby_map": mapName, "lobby_id": <?=$lobbyID?>}, function (data) {
                                if (data) {
                                    try {
                                        data = JSON.parse(data);

                                        if (data.error) {
                                            $("#lobbyResult").html(data.error);
                                        }
                                        else {
                                            loadPage("#d2mods__lobby?id=<?=$lobbyID?>", 0);
                                        }
                                    }
                                    catch (err) {
                                        $("#lobbyResult").html("Failed to update lobby map.");
                                        console.log("Failed to parse JSON. " + err.message);
                                    }
                                }
                                else {
                                    $("#lobbyResult").html("Failed to update lobby map.");
                                }
                            }, "text");
                        }

                        $(document).ready(function () {
                            $("#lobbyClose").submit(function (event) {
                                event.preventDefault();

                                $.post("./d2mods/lobby_close.php", $("#lobbyClose").serialize(), function (data) {
                                    if (data) {
                                        try {
                                            data = JSON.parse(data);

                                            if (data.error) {
                                                $("#lobbyResult").html(data.error);
                                            }
                                            else {
                                                loadPage("#d2mods__lobby_list", 0);
                                            }
                                        }
                                        catch (err) {
                                            $("#lobbyResult").html("Failed to close lobby.");
                                            console.log("Failed to parse JSON. " + err.message);
                                        }
                                    }
                                    else {
                                        $("#lobbyResult").html("Failed to close lobby.");
                                    }
                                }, "text");
                            });

                            $("#lobbyJoin").submit(function (event) {
                                event.preventDefault();

                                $.post("./d2mods/lobby_join.php", $("#lobbyJoin").serialize(), function (data) {
                                    if (data) {
                                        try {
                                            data = JSON.parse(data);

                                            if (data.error) {
                                                $("#lobbyResult").html(data.error);
                                            }
                                            else {
                                                loadPage("#d2mods__lobby?id=<?=$lobbyID?>", 0);
                                            }
                                        }
                                        catch (err) {
                                            $("#lobbyResult").html("Failed to join lobby.");
                                            console.log("Failed to parse JSON. " + err.message);
                                        }
                                    }
                                    else {
                                        $("#lobbyResult").html("Failed to join lobby.");
                                    }
                                }, "text");
                            });

                            $("#lobbyLeave").submit(function (event) {
                                event.preventDefault();

                                $.post("./d2mods/lobby_leave.php", $("#lobbyLeave").serialize(), function (data) {
                                    if (data) {
                                        try {
                                            data = JSON.parse(data);

                                            if (data.error) {
                                                $("#lobbyResult").html(data.error);
                                            }
                                            else {
                                                loadPage("#d2mods__lobby_list", 0);
                                            }
                                        }
                                        catch (err) {
                                            $("#lobbyResult").html("Failed to leave lobby.");
                                            console.log("Failed to parse JSON. " + err.message);
                                        }
                                    }
                                    else {
                                        $("#lobbyResult").html("Failed to leave lobby.");
                                    }
                                }, "text");
                            });

                            pageReloader = setTimeout(function () {
                                if (document.getElementById("nav-refresh-holder").getAttribute("href") == "#d2mods__lobby?id=<?=$lobbyID?>") {
                                    loadPage("#d2mods__lobby?id=<?=$lobbyID?>", 1);
                                }
                                else {
                                    clearTimeout(pageReloader);
                                }
                            }, 5000);
                        });
                    </script>
                <?php
                } else {
                    echo bootstrapMessage('Oh Snap', 'Bad lobby ID!', 'danger');
                }
            } else {
                echo bootstrapMessage('Oh Snap', 'No lobby specified!', 'danger');
            }
        } else {
            echo bootstrapMessage('Oh Snap', 'No db!', 'danger');
        }

        $memcache->close();
    } else {
        echo bootstrapMessage('Oh Snap', 'Not logged in!', 'danger');
    }

    echo '<p>
            <div class="text-center">
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__lobby_create">Create Lobby</a>
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__lobby_list">Lobby List</a>
           </div>
        </p>';

} catch (Exception $e) {
    $message = 'Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage();
    echo bootstrapMessage('Oh Snap', $message, 'danger');
}