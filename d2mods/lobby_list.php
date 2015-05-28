<?php
require_once('../global_functions.php');
require_once('../connections/parameters.php');

try {
    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    $lobbyListActive = simple_cached_query('d2mods_lobby_list_active',
        'SELECT
                ll.`lobby_id`,
                ll.`mod_id`,
                ll.`lobby_name`,
                ll.`lobby_region`,
                ll.`lobby_ttl`,
                ll.`lobby_max_players`,
                ll.`lobby_public`,
                ll.`lobby_leader`,
                ll.`lobby_leader_name`,
                ll.`lobby_pass`,
                ll.`date_recorded` as lobby_date_recorded,
                lr.`region_name`,
                lr.`region_code`,
                ml.*,
                (
                  SELECT
                      COUNT(`user_id64`)
                    FROM `lobby_list_players`
                    WHERE `lobby_id` = ll.`lobby_id`
                    LIMIT 0,1
                ) AS lobby_current_players
            FROM `lobby_list` ll
            LEFT JOIN `mod_list` ml ON ll.`mod_id` = ml.`mod_id`
            LEFT JOIN `lobby_regions` lr ON ll.`lobby_region` = lr.`region_id`
            WHERE ll.`lobby_active` = 1 AND ll.`lobby_hosted` = 1
            ORDER BY lobby_current_players DESC;'
        , 5
    );

    $lobbyListDead = simple_cached_query('d2mods_lobby_list_dead',
        'SELECT
                ll.`lobby_id`,
                ll.`mod_id`,
                ll.`lobby_name`,
                ll.`lobby_started`,
                ll.`lobby_region`,
                ll.`lobby_ttl`,
                ll.`lobby_max_players`,
                ll.`lobby_public`,
                ll.`lobby_leader`,
                ll.`lobby_leader_name`,
                ll.`lobby_pass`,
                ll.`date_recorded` as lobby_date_recorded,
                lr.`region_name`,
                lr.`region_code`,
                ml.*,
                (
                  SELECT
                      COUNT(`user_id64`)
                    FROM `lobby_list_players`
                    WHERE `lobby_id` = ll.`lobby_id`
                    LIMIT 0,1
                ) AS lobby_current_players
            FROM `lobby_list` ll
            LEFT JOIN `mod_list` ml ON ll.`mod_id` = ml.`mod_id`
            LEFT JOIN `lobby_regions` lr ON ll.`lobby_region` = lr.`region_id`
            WHERE ll.`lobby_started` = 1
            ORDER BY ll.`date_recorded` DESC
            LIMIT 0,20;'
        , 5
    );

    echo '<div class="page-header"><h2>Lobby List <small>BETA</small></h2></div>';

    echo '<div class="alert alert-danger" role="alert"><strong>Update your Lobby Explorer client!</strong><br />We had to roll out a non-backwards compatible update that requires everyone to upgrade. Join our Steam group to ensure you always get the latest news!</div>';

    echo '<div class="alert alert-info" role="alert">
                <a target="_blank" class="btn btn-success btn-sm" href="#d2mods__lobby_guide">Installation guide</a> OR hop right in and
                <a target="_blank" class="btn btn-warning btn-sm" href="https://github.com/GetDotaStats/GetDotaLobby/raw/master/LXUpdater.zip">Download the LXUpdater</a> AND subscribe to our
                <a target="_blank" class="btn btn-info btn-sm" href="http://steamcommunity.com/sharedfiles/filedetails/?id=419876116">Workshop Collection</a>
            </div>';

    echo '<div class="page-header"><h3>Active Lobbies</h3></div>';
    if (!empty($lobbyListActive)) {

        echo '<div class="table-responsive">
		        <table class="table table-striped table-hover">';
        echo '<tr>
                        <th class="text-center col-md-2">Lobby</th>
                        <th class="text-center col-md-3">Leader</th>
                        <th class="text-center col-md-3">Mod</th>
                        <th class="text-center col-md-2">Players <span class="glyphicon glyphicon-question-sign" title="Number of players in lobby (Maximum players allowed in lobby)"></span></th>
                        <th class="text-center col-md-2">Created <span class="glyphicon glyphicon-question-sign" title="When this lobby was created."></span></th>
                    </tr>';

        foreach ($lobbyListActive as $key => $value) {
            $workshopLink = !empty($value['mod_workshop_link'])
                ? '<a target="_blank" class="db_link" href="http://steamcommunity.com/sharedfiles/filedetails/?id=' . $value['mod_workshop_link'] . '">[WS]</a>'
                : '';

            $lobbyLeaderName = htmlentitiesdecode_custom($value['lobby_leader_name']);
            if (!empty($lobbyLeaderName)) {
                if (strlen($lobbyLeaderName) > 20) {
                    $lobbyLeaderName = strip_tags(htmlentities_custom(substr($lobbyLeaderName, 0, 17) . '...'));
                } else {
                    $lobbyLeaderName = strip_tags(htmlentities_custom($lobbyLeaderName));
                }
            } else {
                $lobbyLeaderName = 'Unknown User';
            }

            $lobbyName = htmlentitiesdecode_custom($value['lobby_name']);
            if (!empty($lobbyName)) {
                if (strlen($lobbyName) > 12) {
                    $lobbyName = strip_tags(htmlentities_custom(substr($lobbyName, 0, 9) . '...'));
                } else {
                    $lobbyName = strip_tags(htmlentities_custom($lobbyName));
                }
            } else {
                $lobbyName = 'Custom Game #' . $value['lobby_id'];
            }

            $lobbyRegion = !empty($value['region_code']) && !empty($value['region_name'])
                ? '<img width="16" height="16" src="' . $CDN_generic . '/images/misc/flags/regions/' . $value['region_code'] . '.png" title="' . $value['region_name'] . '" />'
                : '<img width="16" height="16" src="' . $CDN_generic . '/images/misc/flags/regions/_unknown.png" title="Unknown" />';

            echo '<tr>
                        <td class="vert-align">' . $lobbyRegion . ' <a class="nav-clickable" href="#d2mods__lobby?id=' . $value['lobby_id'] . '">' . $lobbyName . '</a></td>
                        <td class="vert-align"><a class="nav-clickable" href="#d2mods__profile?id=' . $value['lobby_leader'] . '"><span class="glyphicon glyphicon-search"></span></a> ' . $lobbyLeaderName . '</td>
                        <td class="vert-align"><a class="nav-clickable" href="#d2mods__stats?id=' . $value['mod_id'] . '">' . $value['mod_name'] . '</a> ' . $workshopLink . '</td>
                        <td class="text-center vert-align">' . $value['lobby_current_players'] . ' (' . $value['lobby_max_players'] . ') <a class="nav-clickable btn btn-success btn-sm" href="#d2mods__lobby?id=' . $value['lobby_id'] . '">JOIN</a></td>
                        <td class="text-right vert-align">' . relative_time_v3($value['lobby_date_recorded'], 0) . '</td>
                    </tr>';
        }

        echo '</table></div>';
    } else {
        echo bootstrapMessage('Oh Snap', 'No active lobbies! You should probably go make one.', 'danger');
    }

    echo '<div class="page-header"><h3>Recently Closed Lobbies</h3></div>';
    if (!empty($lobbyListDead)) {
        echo '<div class="table-responsive">
		        <table class="table table-striped table-hover">';

        echo '<tr>
                        <th class="text-center col-md-2">Lobby</th>
                        <th class="text-center col-md-3">Leader</th>
                        <th class="text-center col-md-3">Mod</th>
                        <th class="text-center col-md-2">Players <span class="glyphicon glyphicon-question-sign" title="Number of players in lobby (Maximum players allowed in lobby)"></span></th>
                        <th class="text-center col-md-2">Created <span class="glyphicon glyphicon-question-sign" title="When this lobby was created."></span></th>
                    </tr>';

        foreach ($lobbyListDead as $key => $value) {
            $workshopLink = !empty($value['mod_workshop_link'])
                ? '<a target="_blank" class="db_link" href="http://steamcommunity.com/sharedfiles/filedetails/?id=' . $value['mod_workshop_link'] . '">[WS]</a>'
                : '';

            $lobbyLeaderName = htmlentitiesdecode_custom($value['lobby_leader_name']);
            if (!empty($lobbyLeaderName)) {
                if (strlen($lobbyLeaderName) > 20) {
                    $lobbyLeaderName = strip_tags(htmlentities_custom(substr($lobbyLeaderName, 0, 17) . '...'));
                } else {
                    $lobbyLeaderName = strip_tags(htmlentities_custom($lobbyLeaderName));
                }
            } else {
                $lobbyLeaderName = 'Unknown User';
            }

            $lobbyName = htmlentitiesdecode_custom($value['lobby_name']);
            if (!empty($lobbyName)) {
                if (strlen($lobbyName) > 12) {
                    $lobbyName = strip_tags(htmlentities_custom(substr($lobbyName, 0, 9) . '...'));
                } else {
                    $lobbyName = strip_tags(htmlentities_custom($lobbyName));
                }
            } else {
                $lobbyName = 'Custom Game #' . $value['lobby_id'];
            }

            $lobbyRegion = !empty($value['region_code']) && !empty($value['region_name'])
                ? '<img width="16" height="16" src="' . $CDN_generic . '/images/misc/flags/regions/' . $value['region_code'] . '.png" title="' . $value['region_name'] . '" />'
                : '<img width="16" height="16" src="' . $CDN_generic . '/images/misc/flags/regions/_unknown.png" title="Unknown" />';

            echo '<tr>
                        <td class="vert-align">' . $lobbyRegion . ' <a class="nav-clickable" href="#d2mods__lobby?id=' . $value['lobby_id'] . '">' . $lobbyName . '</a></td>
                        <td class="vert-align"><a class="nav-clickable" href="#d2mods__profile?id=' . $value['lobby_leader'] . '"><span class="glyphicon glyphicon-search"></span></a> ' . $lobbyLeaderName . '</td>
                        <td class="vert-align"><a class="nav-clickable" href="#d2mods__stats?id=' . $value['mod_id'] . '">' . $value['mod_name'] . '</a> ' . $workshopLink . '</td>
                        <td class="text-center vert-align">' . $value['lobby_current_players'] . ' (' . $value['lobby_max_players'] . ')</td>
                        <td class="text-right vert-align">' . relative_time_v3($value['lobby_date_recorded'], 1) . '</td>
                    </tr>';
        }

        echo '</table></div>';
    } else {
        echo bootstrapMessage('Oh Snap', 'No lobbies have ever been made!.', 'danger');
    }

    echo '<span class="h4">&nbsp;</span>';

    echo '<div class="text-center">
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__lobby_list">Lobby List</a>
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__recent_games">Recent Games</a>
           </div>';

    echo '<span class="h4">&nbsp;</span>';

} catch (Exception $e) {
    echo formatExceptionHandling($e);
} finally {
    if (isset($memcache)) $memcache->close();
}

echo '
    <script>
        $(document).ready(function () {
            pageReloader = setTimeout(function () {
                if (document.getElementById("nav-refresh-holder").getAttribute("href") == "#d2mods__lobby_list") {
                    loadPage("#d2mods__lobby_list", 2);
                }
                else {
                    clearTimeout(pageReloader);
                }
            }, 10000);
        });
    </script>
    ';