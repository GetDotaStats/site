<?php
try {
    require_once('../global_functions.php');
    require_once('../connections/parameters.php');

    if (!isset($_SESSION)) {
        session_start();
    }

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    checkLogin_v2();
    if (empty($_SESSION['user_id64'])) throw new Exception('Not logged in!');

    $adminCheck = adminCheck($_SESSION['user_id64'], 'admin');
    if (empty($adminCheck)) throw new Exception('Not an admin!');

    echo '<h2>Custom Game Schemas <small>BETA</small></h2>';
    echo '<p>This is the admin section dedicated to the management of custom game schemas.</p>';

    echo '<div class="text-center">
                <a class="nav-clickable btn btn-default btn-lg" href="#admin__mod_schema_create">Create Schema</a>
                <a class="nav-clickable btn btn-default btn-lg" href="#admin__mod_schema_edit">Edit Schema</a>
           </div>';


    echo '<hr />';


    ///////////////////////
    // Unapproved Schemas
    ///////////////////////

    $modCustomSchemaList = cached_query(
        'admin_mod_custom_schema_list_unapproved',
        'SELECT
                s2mcs.`schemaID`,
                s2mcs.`modID`,
                s2mcs.`schemaAuth`,
                s2mcs.`schemaVersion`,
                s2mcs.`dateRecorded`,

                ml.`mod_id`,
                ml.`mod_name`,
                ml.`mod_workshop_link`,
                ml.`mod_active`,
                ml.`mod_rejected`,
                ml.`steam_id64`,

                gdsu.`user_name`,
                gdsu.`user_avatar`

            FROM `s2_mod_custom_schema` s2mcs
            INNER JOIN `mod_list` ml
                ON s2mcs.`modID` = ml.`mod_id`
            LEFT JOIN `gds_users` gdsu
                ON ml.`steam_id64` = gdsu.`user_id64`
            WHERE s2mcs.`schemaApproved` = 0 AND s2mcs.`schemaRejected` = 0
            ORDER BY s2mcs.`dateRecorded` DESC;',
        NULL,
        NULL,
        5
    );

    echo '<h2>Unapproved Mod Schemas</h2>';

    if (!empty($modCustomSchemaList)) {
        echo '<div class="row">
                <div class="col-md-4"><span class="h4">Mod</span></div>
                <div class="col-md-1 text-center"><span class="h4">Ver.</span></div>
                <div class="col-md-3"><span class="h4">Modder</span></div>
            </div>';

        echo '<span class="h5">&nbsp;</span>';

        foreach ($modCustomSchemaList as $key => $value) {
            $modThumb = is_file('../images/mods/thumbs/' . $value['mod_id'] . '.png')
                ? $CDN_image . '/images/mods/thumbs/' . $value['mod_id'] . '.png'
                : $CDN_image . '/images/misc/steam/blank_avatar.jpg';
            $modThumb = '<img width="25" height="25" src="' . $modThumb . '" />';
            $modNameLink = '<a target="_blank" href="http://steamcommunity.com/sharedfiles/filedetails/?id=' . $value['mod_workshop_link'] . '">' . $modThumb . '</a> <a class="nav-clickable" href="#d2mods__stats?id=' . $value['mod_id'] . '">' . $value['mod_name'] . '</a>';

            $developerAvatar = !empty($value['user_avatar'])
                ? $value['user_avatar']
                : $CDN_image . '/images/misc/steam/blank_avatar.jpg';
            $developerAvatar = '<img width="20" height="20" src="' . $developerAvatar . '" />';
            $developerLink = '<a target="_blank" href="http://steamcommunity.com/profiles/' . $value['steam_id64'] . '">' . $developerAvatar . '</a> <a class="nav-clickable" href="#d2mods__profile?id=' . $value['steam_id64'] . '">' . $value['user_name'] . '</a>';

            $editLink = '<a class="nav-clickable" href="#admin__mod_schema_edit?id=' . $value['schemaID'] . '">EDIT</a>';

            echo '<div class="row">
                <div class="col-md-4">' . $modNameLink . '</div>
                <div class="col-md-1 text-center">' . $value['schemaVersion'] . '</div>
                <div class="col-md-3">' . $developerLink . '</div>
                <div class="col-md-1">&nbsp;</div>
                <div class="col-md-1 text-center">' . $editLink . '</div>
                <div class="col-md-2 text-right">' . relative_time_v3($value['dateRecorded'], 1, NULL, false, true, true) . '</div>
            </div>';

            echo '<span class="h5">&nbsp;</span>';
        }
    } else {
        echo 'No schemas waiting for approval!';
    }


    echo '<hr />';


    ///////////////////////
    // Schemas Approved
    ///////////////////////

    $modCustomSchemaList = cached_query(
        'admin_mod_custom_schema_list_approved',
        'SELECT
                s2mcs.`schemaID`,
                s2mcs.`modID`,
                s2mcs.`schemaAuth`,
                s2mcs.`schemaVersion`,
                s2mcs.`dateRecorded`,

                ml.`mod_id`,
                ml.`mod_name`,
                ml.`mod_workshop_link`,
                ml.`mod_active`,
                ml.`mod_rejected`,
                ml.`steam_id64`,

                gdsu.`user_name`,
                gdsu.`user_avatar`

            FROM `s2_mod_custom_schema` s2mcs
            INNER JOIN `mod_list` ml
                ON s2mcs.`modID` = ml.`mod_id`
            LEFT JOIN `gds_users` gdsu
                ON ml.`steam_id64` = gdsu.`user_id64`
            WHERE s2mcs.`schemaApproved` = 1
            ORDER BY s2mcs.`dateRecorded` DESC;',
        NULL,
        NULL,
        5
    );

    echo '<h2>Approved Mod Schemas</h2>';

    if (!empty($modCustomSchemaList)) {
        echo '<div class="row">
                <div class="col-md-4"><span class="h4">Mod</span></div>
                <div class="col-md-1 text-center"><span class="h4">Ver.</span></div>
                <div class="col-md-3"><span class="h4">Modder</span></div>
            </div>';

        echo '<span class="h5">&nbsp;</span>';

        foreach ($modCustomSchemaList as $key => $value) {
            $modThumb = is_file('../images/mods/thumbs/' . $value['mod_id'] . '.png')
                ? $CDN_image . '/images/mods/thumbs/' . $value['mod_id'] . '.png'
                : $CDN_image . '/images/misc/steam/blank_avatar.jpg';
            $modThumb = '<img width="25" height="25" src="' . $modThumb . '" />';
            $modNameLink = '<a target="_blank" href="http://steamcommunity.com/sharedfiles/filedetails/?id=' . $value['mod_workshop_link'] . '">' . $modThumb . '</a> <a class="nav-clickable" href="#d2mods__stats?id=' . $value['mod_id'] . '">' . $value['mod_name'] . '</a>';

            $developerAvatar = !empty($value['user_avatar'])
                ? $value['user_avatar']
                : $CDN_image . '/images/misc/steam/blank_avatar.jpg';
            $developerAvatar = '<img width="20" height="20" src="' . $developerAvatar . '" />';
            $developerLink = '<a target="_blank" href="http://steamcommunity.com/profiles/' . $value['steam_id64'] . '">' . $developerAvatar . '</a> <a class="nav-clickable" href="#d2mods__profile?id=' . $value['steam_id64'] . '">' . $value['user_name'] . '</a>';

            $editLink = '<a class="nav-clickable" href="#admin__mod_schema_edit?id=' . $value['schemaID'] . '">EDIT</a>';

            echo '<div class="row">
                <div class="col-md-4">' . $modNameLink . '</div>
                <div class="col-md-1 text-center">' . $value['schemaVersion'] . '</div>
                <div class="col-md-3">' . $developerLink . '</div>
                <div class="col-md-1">&nbsp;</div>
                <div class="col-md-1 text-center">' . $editLink . '</div>
                <div class="col-md-2 text-right">' . relative_time_v3($value['dateRecorded'], 1, NULL, false, true, true) . '</div>
            </div>';

            echo '<span class="h5">&nbsp;</span>';
        }
    } else {
        echo 'No approved schemas!';
    }

    echo '<hr />';

    ///////////////////////
    // Schemas Rejected
    ///////////////////////

    $modCustomSchemaList = cached_query(
        'admin_mod_custom_schema_list_rejected',
        'SELECT
                s2mcs.`schemaID`,
                s2mcs.`modID`,
                s2mcs.`schemaAuth`,
                s2mcs.`schemaVersion`,
                s2mcs.`dateRecorded`,

                ml.`mod_id`,
                ml.`mod_name`,
                ml.`mod_workshop_link`,
                ml.`mod_active`,
                ml.`mod_rejected`,
                ml.`steam_id64`,

                gdsu.`user_name`,
                gdsu.`user_avatar`

            FROM `s2_mod_custom_schema` s2mcs
            INNER JOIN `mod_list` ml
                ON s2mcs.`modID` = ml.`mod_id`
            LEFT JOIN `gds_users` gdsu
                ON ml.`steam_id64` = gdsu.`user_id64`
            WHERE s2mcs.`schemaRejected` = 1
            ORDER BY s2mcs.`dateRecorded` DESC;',
        NULL,
        NULL,
        5
    );

    echo '<h2>Rejected Mod Schemas</h2>';

    if (!empty($modCustomSchemaList)) {
        echo '<div class="row">
                <div class="col-md-4"><span class="h4">Mod</span></div>
                <div class="col-md-1 text-center"><span class="h4">Ver.</span></div>
                <div class="col-md-3"><span class="h4">Modder</span></div>
            </div>';

        echo '<span class="h5">&nbsp;</span>';

        foreach ($modCustomSchemaList as $key => $value) {
            $modThumb = is_file('../images/mods/thumbs/' . $value['mod_id'] . '.png')
                ? $CDN_image . '/images/mods/thumbs/' . $value['mod_id'] . '.png'
                : $CDN_image . '/images/misc/steam/blank_avatar.jpg';
            $modThumb = '<img width="25" height="25" src="' . $modThumb . '" />';
            $modNameLink = '<a target="_blank" href="http://steamcommunity.com/sharedfiles/filedetails/?id=' . $value['mod_workshop_link'] . '">' . $modThumb . '</a> <a class="nav-clickable" href="#d2mods__stats?id=' . $value['mod_id'] . '">' . $value['mod_name'] . '</a>';

            $developerAvatar = !empty($value['user_avatar'])
                ? $value['user_avatar']
                : $CDN_image . '/images/misc/steam/blank_avatar.jpg';
            $developerAvatar = '<img width="20" height="20" src="' . $developerAvatar . '" />';
            $developerLink = '<a target="_blank" href="http://steamcommunity.com/profiles/' . $value['steam_id64'] . '">' . $developerAvatar . '</a> <a class="nav-clickable" href="#d2mods__profile?id=' . $value['steam_id64'] . '">' . $value['user_name'] . '</a>';

            $editLink = '<a class="nav-clickable" href="#admin__mod_schema_edit?id=' . $value['schemaID'] . '">EDIT</a>';

            echo '<div class="row">
                <div class="col-md-4">' . $modNameLink . '</div>
                <div class="col-md-1 text-center">' . $value['schemaVersion'] . '</div>
                <div class="col-md-3">' . $developerLink . '</div>
                <div class="col-md-1">&nbsp;</div>
                <div class="col-md-1 text-center">' . $editLink . '</div>
                <div class="col-md-2 text-right">' . relative_time_v3($value['dateRecorded'], 1, NULL, false, true, true) . '</div>
            </div>';

            echo '<span class="h5">&nbsp;</span>';
        }
    } else {
        echo 'No rejected schemas!';
    }


    echo '<hr />';


    echo '<span class="h4">&nbsp;</span>';

    echo '<div class="text-center">
                <a class="nav-clickable btn btn-default btn-lg" href="#admin__mod_schema_create">Create Schema</a>
                <a class="nav-clickable btn btn-default btn-lg" href="#admin__mod_schema_edit">Edit Schema</a>
           </div>';

    echo '<span class="h4">&nbsp;</span>';

} catch (Exception $e) {
    echo formatExceptionHandling($e);
} finally {
    if (isset($memcache)) $memcache->close();
}