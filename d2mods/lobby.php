<?php
require_once('../global_functions.php');
require_once('../connections/parameters.php');

try {
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
                        `lobby_id`,
                        `mod_id`,
                        `lobby_ttl`,
                        `lobby_min_players`,
                        `lobby_max_players`,
                        `lobby_public`,
                        `lobby_leader`,
                        `lobby_active`,
                        `lobby_pass`,
                        `date_recorded`
                    FROM `lobby_list`
                    WHERE `lobby_id` = ?
                    LIMIT 0,1;',
                'i',
                $lobbyID
            );

            echo '<div class="page-header"><h2>Lobby Details <small>BETA</small></h2></div>';

            if (!empty($lobbyDetails)) {
                echo '<div><form id="lobbyClose">
                        <input type="hidden" name="lobby_id" value="' . $lobbyID . '">
                        <button>Close Lobby</button>
                    </form></div>';

                echo '<pre>';
                print_r($lobbyDetails);
                echo '</pre>';

                ?>
                <script type="application/javascript">
                    $("#lobbyClose").submit(function (event) {
                        event.preventDefault();

                        $.post("./d2mods/lobby_close.php", $("#lobbyClose").serialize(), function (data) {
                            if (data) {
                                try {
                                    data = JSON.parse(data);

                                    if (data.error) {
                                        $("#lobbyCloseResult").html(data.error);
                                    }
                                    else {
                                        loadPage("#d2mods__lobby_list", 0);
                                    }
                                }
                                catch (err) {
                                    $("#lobbyCloseResult").html("Failed to close lobby.");
                                    console.log("Failed to parse JSON");
                                }
                            }
                            else {
                                $("#lobbyCloseResult").html("Failed to close lobby. " + err.message);
                            }
                        }, "text");
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