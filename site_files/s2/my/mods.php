<?php
require_once('../../global_functions.php');
require_once('../../connections/parameters.php');

try {
    if (!isset($_SESSION)) {
        session_start();
    }

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    checkLogin_v2();
    if (empty($_SESSION['user_id64'])) throw new Exception('Not logged in!');

    $userID64 = $_SESSION['user_id64'];

    $modWorkshopList = cached_query(
        's2_my_mods_' . $userID64,
        'SELECT
              ml.`mod_id`,
              ml.`steam_id64` AS developer_id64,
              ml.`mod_identifier`,
              ml.`mod_name`,
              ml.`mod_steam_group`,
              ml.`mod_workshop_link`,
              ml.`mod_active`,
              ml.`mod_rejected`,
              ml.`mod_rejected_reason`,
              ml.`mod_size`,
              ml.`workshop_updated`,
              ml.`date_recorded` AS mod_date_added,

              (SELECT
                    SUM(`gamesPlayed`)
                  FROM `cache_mod_matches` cmm
                  WHERE cmm.`modID` = ml.`mod_id` AND cmm.`gamePhase` = 3 AND cmm.`dateRecorded` >= now() - INTERVAL 7 DAY
              ) AS games_last_week,
              (SELECT
                    SUM(`gamesPlayed`)
                  FROM `cache_mod_matches` cmm
                  WHERE cmm.`modID` = ml.`mod_id` AND cmm.`gamePhase` = 3
              ) AS games_all_time,

              s2mcs.`schemaID`,
              s2mcs.`schemaAuth`,
              s2mcs.`schemaVersion`

            FROM `mod_list` ml
            LEFT JOIN (
                SELECT
                    s2mcs2.`schemaID`,
                    s2mcs2.`modID`,
                    s2mcs2.`schemaAuth`,
                    s2mcs2.`schemaVersion`
                  FROM `s2_mod_custom_schema` s2mcs2
                  WHERE
                    s2mcs2.`schemaID` IN (
                        SELECT
                                MAX(s2mcs3.`schemaID`)
                            FROM `s2_mod_custom_schema` s2mcs3
                            WHERE
                                s2mcs3.`schemaApproved` = 1
                            GROUP BY s2mcs3.`modID`
                    )
            ) as s2mcs ON s2mcs.`modID` = ml.`mod_id`
            WHERE ml.`steam_id64` = ?;',
        's',
        array($userID64),
        5
    );

    echo '<div class="page-header"><h2>My Mods</h2></div>';

    echo '<p>This is a list of all of your mods.</p>';

    try {
        if (empty($modWorkshopList)) {
            throw new Exception('You don\'t have any mods added yet!');
        }

        echo '<div class="row">
                    <div class="col-sm-1 text-center"><strong>Status</strong></div>
                    <div class="col-sm-4 text-center"><strong>Mod</strong></div>
                    <div class="col-sm-3 text-center"><strong>modID</strong></div>
                    <div class="col-sm-2 text-center"><strong>schemaID</strong></div>

                    <div class="col-sm-1 text-center"><strong>Week</strong></div>
                    <div class="col-sm-1 text-center"><strong>All</strong></div>
                </div>';

        echo '<span class="h3">&nbsp;</span>';


        foreach ($modWorkshopList as $key => $value) {
            $workshopLink = !empty($value['mod_workshop_link'])
                ? '<a target="_blank" class="db_link" href="http://steamcommunity.com/sharedfiles/filedetails/?id=' . $value['mod_workshop_link'] . '">WS</a>'
                : '<span class="db_link">WS</span>';

            $steamGroupLink = !empty($value['mod_steam_group'])
                ? '<a target="_blank" class="db_link" href="http://steamcommunity.com/groups/' . $value['mod_steam_group'] . '">SG</a>'
                : '<span class="db_link">SG</span>';

            $modStatus = $value['mod_rejected'] == 1
                ? 3
                : ($value['mod_active'] == 1
                    ? 2
                    : 1);

            switch ($modStatus) {
                case 1:
                    $modStatus = '<span class="glyphicon glyphicon-ok boldOrangeText" title="Mod awaiting approval"></span>';
                    break;
                case 2:
                    $modStatus = '<span class="glyphicon glyphicon-ok boldGreenText" title="Mod Approved"></span>';
                    break;
                case 3:
                    $modStatus = '<span class="glyphicon glyphicon-remove boldRedText" title="Mod rejected: ' . $value['mod_rejected_reason'] . '"></span>';
                    break;
                default:
                    $modStatus = '<span class="glyphicon glyphicon-ok boldOrangeText" title="Mod awaiting approval"></span>';
                    break;
            }

            $modLinks = $workshopLink . ' || ' . $steamGroupLink;

            $modThumb = is_file('../../images/mods/thumbs/' . $value['mod_id'] . '.png')
                ? $CDN_image . '/images/mods/thumbs/' . $value['mod_id'] . '.png'
                : $CDN_image . '/images/misc/steam/blank_avatar.jpg';

            $ModIdentifier = "<div><span class='db_link'>{$value['mod_identifier']}</span></div>";

            $schemaDetails = !empty($value['schemaAuth'])
                ? "<a class='nav-clickable' href='#s2__mod_schema?id={$value['schemaID']}'><div>v{$value['schemaVersion']} <span class='db_link'>{$value['schemaAuth']}</span></div></a>"
                : 'N/A';


            echo '<div class="row">
                    <div class="col-sm-1 text-center">' . $modStatus . '</div>
                    <div class="col-sm-4"><img width="25" height="25" src="' . $modThumb . '" /> <a class="nav-clickable" href="#s2__mod?id=' . $value['mod_id'] . '">' . $value['mod_name'] . '</a></div>
                    <div class="col-sm-3">' . $ModIdentifier . '</div>
                    <div class="col-sm-2">' . $schemaDetails . '</div>
                    <div class="col-sm-1 text-right">' . number_format($value['games_last_week']) . '</div>
                    <div class="col-sm-1 text-right">' . number_format($value['games_all_time']) . '</div>
                </div>';

            echo '<span class="h4">&nbsp;</span>';
        }

        echo '<hr />';
    } catch (Exception $e) {
        echo formatExceptionHandling($e);
    }

    echo '<span class="h4">&nbsp;</span>';

    echo '<div class="text-center">
            <a class="nav-clickable btn btn-default btn-lg" href="#s2__my__profile">My Profile</a>
            <a class="nav-clickable btn btn-default btn-lg" href="#s2__my__mod_request">Add a new mod</a>
            <a class="nav-clickable btn btn-default btn-lg" href="#s2__guide_stat_collection">Implementing Stats</a>
            <a class="nav-clickable btn btn-default btn-lg" href="#s2__my__mods_feedback">My Feedback</a>
        </div>';

    echo '<span class="h4">&nbsp;</span>';
} catch (Exception $e) {
    echo formatExceptionHandling($e);
} finally {
    if (isset($memcache)) $memcache->close();
}