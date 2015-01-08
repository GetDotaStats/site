<?php
require_once('../global_functions.php');
require_once('../connections/parameters.php');

try {
    $db = new dbWrapper_v2($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site);
    $db->q('SET NAMES utf8;');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    if ($db) {
        $modListActive = simple_cached_query('d2mods_directory_active',
            'SELECT
                    ml.*,
                    gu.`user_name`,
                    gu.`user_avatar`,
                    (SELECT COUNT(*) FROM `mod_match_overview` mmo WHERE mmo.`mod_id` = ml.`mod_identifier` AND mmo.`match_recorded` >= now() - INTERVAL 7 DAY AND mmo.`match_duration` > 130 GROUP BY `mod_id`) AS games_last_week,
                    (SELECT COUNT(*) FROM `mod_match_overview` mmo WHERE mmo.`mod_id` = ml.`mod_identifier` AND mmo.`match_duration` > 130 GROUP BY `mod_id`) AS games_all_time
                FROM `mod_list` ml
                LEFT JOIN `gds_users` gu ON ml.`steam_id64` = gu.`user_id64`
                WHERE ml.`mod_active` = 1
                ORDER BY games_last_week DESC, games_all_time DESC;'
            , 60
        );

        echo '<div class="page-header"><h2>Create Lobby <small>BETA</small></h2></div>';

        echo '<p>Create a lobby that others can join, to assist in organising games that random people can join.</p>';

        if (!empty($modListActive)) {
            echo '
                    <form id="lobbyCreate">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <tr>
                                    <th class="col-md-2">Mod <span class="glyphicon glyphicon-question-sign" title="The mod this lobby will be for."></span></th>
                                    <td class="col-md-4">
                                        <select name="mod_id" size="10" required>';

            foreach ($modListActive as $key => $value) {
                echo '<option value="' . $value['mod_id'] . '">' . $value['mod_name'] . '</option>';
            }

            echo '                      </select>
                                    </td>
                                    <th class="col-md-2">TTL <span class="glyphicon glyphicon-question-sign" title="The number of minutes this lobby will be advertised for."></span></th>
                                    <td class="col-md-4">
                                        <select name="lobby_ttl" size="6" required>
                                            <option value="5">5mins</option>
                                            <option selected value="10">10mins</option>
                                            <option value="15">15mins</option>
                                            <option value="20">20mins</option>
                                            <option value="25">25mins</option>
                                            <option value="30">30mins</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="col-md-2">Max Players <span class="glyphicon glyphicon-question-sign" title="The maximum number of slots in the game."></span></th>
                                    <td class="col-md-4" colspan="3">
                                        <select name="lobby_max_players" size="4" required>
                                            <option value="10">10 players</option>
                                            <option value="9">9 players</option>
                                            <option value="8">8 players</option>
                                            <option value="7">7 players</option>
                                            <option value="6">6 players</option>
                                            <option value="5">5 players</option>
                                            <option selected value="4">4 players</option>
                                            <option value="3">3 players</option>
                                            <option value="2">2 players</option>
                                        </select>
                                        <input type="hidden" name="lobby_public" value="1"><!-- Public game <span class="glyphicon glyphicon-question-sign" title="Whether this lobby will use the public password."></span>-->
                                        <input type="hidden" name="lobby_min_players" value="2">
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="4" align="center">
                                        <button>Create Lobby</button>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </form>';

            echo '<div class="container"><span id="lobbyCreateResult" class="label label-danger"></span></div>';
            ?>

            <script type="application/javascript">
                $("#lobbyCreate").submit(function (event) {
                    event.preventDefault();

                    $.post("./d2mods/lobby_create_insert.php", $("#lobbyCreate").serialize(), function (data) {
                        if (data) {
                            try {
                                data = JSON.parse(data);

                                if (data.error) {
                                    $("#lobbyCreateResult").html(data.error);
                                }
                                else {
                                    loadPage("#d2mods__lobby?id=" + data.lobby_id, 0);
                                }
                            }
                            catch (err) {
                                $("#lobbyCreateResult").html("Failed to create lobby." + err.message);
                                console.log("Failed to parse JSON");
                            }
                        }
                        else {
                            $("#lobbyCreateResult").html("Failed to create lobby.");
                        }
                    }, "text");
                });
            </script>

        <?php
        } else {
            echo bootstrapMessage('Oh Snap', 'No active mods added yet!', 'danger');
        }
    } else {
        echo bootstrapMessage('Oh Snap', 'No db!', 'danger');
    }

    echo '<p>
            <div class="text-center">
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__lobby_list">Lobby List</a>
           </div>
        </p>';

    $memcache->close();
} catch (Exception $e) {
    $message = 'Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage();
    echo bootstrapMessage('Oh Snap', $message, 'danger');
}