<?php
require_once('../global_functions.php');
require_once('../connections/parameters.php');

if (!isset($_SESSION)) {
    session_start();
}

try {
    if (empty($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception('Invalid modID! Bad type.');
    }

    $modID = $_GET['id'];

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    $modDetails = cached_query(
        's2_mod_page_details' . $modID,
        'SELECT
                ml.`mod_id`,
                ml.`steam_id64`,
                ml.`mod_identifier`,
                ml.`mod_name`,
                ml.`mod_description`,
                ml.`mod_workshop_link`,
                ml.`mod_steam_group`,
                ml.`mod_active`,
                ml.`mod_rejected`,
                ml.`mod_rejected_reason`,
                ml.`mod_maps`,
                ml.`date_recorded`,

                gu.`user_name`,

                (SELECT
                        COUNT(*)
                      FROM `s2_match` s2
                      WHERE s2.`modID` = ml.`mod_id`
                      LIMIT 0,1
                ) AS num_games_total,

                (SELECT
                        COUNT(*)
                      FROM `s2_match` s2
                      WHERE s2.`modID` = ml.`mod_id` AND s2.`dateRecorded` >= now() - INTERVAL 7 DAY
                      LIMIT 0,1
                ) AS num_games_last_week
            FROM `mod_list` ml
            JOIN `gds_users` gu ON ml.`steam_id64` = gu.`user_id64`
            WHERE ml.`mod_id` = ?
            LIMIT 0,1;',
        'i',
        $modID,
        15
    );

    if (empty($modDetails)) {
        throw new Exception('Invalid modID! Not recorded in database.');
    }

    //Tidy variables
    {
        //Mod name and thumb
        {
            $modThumb = is_file('../images/mods/thumbs/' . $modDetails[0]['mod_id'] . '.png')
                ? $CDN_image . '/images/mods/thumbs/' . $modDetails[0]['mod_id'] . '.png'
                : $CDN_image . '/images/misc/steam/blank_avatar.jpg';
            $modThumb = '<img width="24" height="24" src="' . $modThumb . '" alt="Mod thumbnail" />';
            $modThumb = '<a target="_blank" href="http://steamcommunity.com/sharedfiles/filedetails/?id=' . $modDetails[0]['mod_workshop_link'] . '">' . $modThumb . '</a>';

            $modNameLink = '';
            if (!empty($_SESSION['user_id64'])) {
                //if admin, show modIdentifier too
                $adminCheck = adminCheck($_SESSION['user_id64'], 'admin');
                if (!empty($adminCheck)) {
                    $modNameLink = ' <small>' . $modDetails[0]['mod_identifier'] . '</small>';
                }
            }
            $modNameLink = $modThumb . ' <a class="nav-clickable" href="#s2__mod?id=' . $modDetails[0]['mod_id'] . '">' . $modDetails[0]['mod_name'] . $modNameLink . '</a>';
        }

        //Mod external links
        {
            !empty($modDetails[0]['mod_workshop_link'])
                ? $links['steam_workshop'] = '<a href="http://steamcommunity.com/sharedfiles/filedetails/?id=' . $modDetails[0]['mod_workshop_link'] . '" target="_new"><span class="glyphicon glyphicon-new-window"></span> Workshop</a>'
                : NULL;
            !empty($modDetails[0]['mod_steam_group'])
                ? $links['steam_group'] = '<a href="http://steamcommunity.com/groups/' . $modDetails[0]['mod_steam_group'] . '" target="_new"><span class="glyphicon glyphicon-new-window"></span> Steam Group</a>'
                : NULL;
            $links = !empty($links)
                ? implode(' || ', $links)
                : 'None';
        }

        //Developer name and avatar
        {
            $developerAvatar = !empty($value['user_avatar'])
                ? $value['user_avatar']
                : $CDN_image . '/images/misc/steam/blank_avatar.jpg';
            $developerAvatar = '<img width="20" height="20" src="' . $developerAvatar . '" alt="Developer avatar" />';
            $developerLink = '<a target="_blank" href="http://steamcommunity.com/profiles/' . $modDetails[0]['steam_id64'] . '">' . $developerAvatar . '</a> <a class="nav-clickable" href="#s2__user?id=' . $modDetails[0]['steam_id64'] . '">' . $modDetails[0]['user_name'] . '</a>';
        }

        //Mod maps
        $modMaps = !empty($modDetails[0]['mod_maps'])
            ? implode(", ", json_decode($modDetails[0]['mod_maps'], 1))
            : 'unknown';

        //Status
        if (!empty($modDetails[0]['mod_rejected']) && !empty($modDetails[0]['mod_rejected_reason'])) {
            $modStatus = '<span class="boldRedText">Rejected:</span> ' . $modDetails[0]['mod_rejected_reason'];
        } else if ($modDetails[0]['mod_active'] == 1) {
            $modStatus = '<span class="boldGreenText">Accepted</span>';
        } else {
            $modStatus = '<span class="boldOrangeText">Pending Approval</span>';
        }
    }

    echo '<h2>' . $modNameLink . '</h2>';

    //FEATURE REQUEST
    echo '<div class="alert alert-danger"><strong>Help Wanted!</strong> We are re-designing every page. If there are features you would like to
        see on this page, please let us know by making a post per feature on this page\'s
        <a target="_blank" href="https://github.com/GetDotaStats/site/issues/162">issue</a>.</div>';

    //MOD INFO
    echo '<div class="container">';
    echo '<div class="col-sm-7">
                <div class="row mod_info_panel">
                    <div class="col-sm-12 text-center">
                        <button class="btn btn-sm" data-toggle="collapse" data-target="#mod_info">Mod Info</button>
                    </div>
                </div>
            </div>';

    echo '<div id="mod_info" class="collapse col-sm-7">
                <div class="row mod_info_panel">
                    <div class="col-sm-3"><strong>Status</strong></div>
                    <div class="col-sm-9">' . $modStatus . '</div>
                </div>
                <div class="row mod_info_panel">
                    <div class="col-sm-3"><strong>Links</strong></div>
                    <div class="col-sm-9">' . $links . '</div>
                </div>
                <div class="row mod_info_panel">
                    <div class="col-sm-3"><strong>Description</strong></div>
                    <div class="col-sm-9">' . $modDetails[0]['mod_description'] . '</div>
                </div>
                <div class="row mod_info_panel">
                    <div class="col-sm-3"><strong>Developer</strong></div>
                    <div class="col-sm-9">' . $developerLink . '</div>
                </div>
                <div class="row mod_info_panel">
                    <div class="col-sm-3"><strong>Maps</strong></div>
                    <div class="col-sm-9">' . $modMaps . '</div>
                </div>
                <div class="row mod_info_panel">
                    <div class="col-sm-3"><strong>Total Games</strong></div>
                    <div class="col-sm-9">' . number_format($modDetails[0]['num_games_total']) . '</div>
                </div>
                <div class="row mod_info_panel">
                    <div class="col-sm-3"><strong>Games (Last Week)</strong></div>
                    <div class="col-sm-9">' . number_format($modDetails[0]['num_games_last_week']) . '</div>
                </div>
                <div class="row mod_info_panel">
                    <div class="col-sm-3"><strong>Added</strong></div>
                    <div class="col-sm-9">' . relative_time_v3($modDetails[0]['date_recorded']) . '</div>
                </div>
           </div>';
    echo '</div>';

    echo '<span class="h4">&nbsp;</span>';

    echo '<hr />';

    echo 'PLACEHOLDER';

    echo '<hr />';


    echo '<span class="h4">&nbsp;</span>';

    echo '<div class="text-center">
                <a class="nav-clickable btn btn-default btn-lg" href="#s2__directory">Mod Directory</a>
                <a class="nav-clickable btn btn-default btn-lg" href="#s2__recent_games">Recent Games</a>
           </div>';

    echo '<span class="h4">&nbsp;</span>';
} catch (Exception $e) {
    echo formatExceptionHandling($e);
} finally {
    if (isset($memcache)) $memcache->close();
}