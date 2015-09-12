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

    echo '<h2>Mods Rejected</h2>';
    echo '<p>This page shows admins which custom games have been rejected.</p>';

    echo '<span class="h5">&nbsp;</span>';

    echo '<hr />';

    $modList = $db->q(
        'SELECT
                ml.*,
                gu.`user_name`,
                gu.`user_avatar`
            FROM `mod_list` ml
            LEFT JOIN `gds_users` gu ON ml.`steam_id64` = gu.`user_id64`
            WHERE ml.`mod_active` <> 1 AND ml.`mod_rejected` = 1
            ORDER BY ml.date_recorded DESC;'
    );

    if (empty($modList)) {
        throw new Exception('No mods have been rejected!');
    }

    foreach ($modList as $key => $value) {
        $modWorkshopLink = !empty($value['mod_workshop_link'])
            ? '<a href="http://steamcommunity.com/sharedfiles/filedetails/?id=' . $value['mod_workshop_link'] . '" target="_new"><span class="glyphicon glyphicon-new-window"></span></a>'
            : '<span class="glyphicon glyphicon-new-window"></span>';

        $modName = !empty($value['mod_name'])
            ? '<strong>' . $value['mod_name'] . '</strong>'
            : '<strong>' . 'No mod name provided' . '</strong>';

        $modDescription = !empty($value['mod_description'])
            ? $value['mod_description']
            : 'No mod description provided';

        $modRejectedReason = !empty($value['mod_rejected_reason'])
            ? $value['mod_rejected_reason']
            : 'No reason given';

        if (!empty($value['user_name'])) {
            $modDeveloper = !empty($value['steam_id64'])
                ? '<a href="https://steamcommunity.com/profiles/' . $value['steam_id64'] . '" target="_blank">' . $value['user_name'] . '</a>'
                : $value['user_name'];

            $modDeveloperAvatar = !empty($value['user_avatar'])
                ? '<img width="20" height="20" src="' . $value['user_avatar'] . '"/>'
                : '<img width="20" height="20" src="' . $CDN_image . '/images/misc/steam/blank_avatar.jpg"/>';
        } else {
            $modDeveloper = 'Unknown';
            $modDeveloperAvatar = '<img width="20" height="20" src="' . $CDN_image . '/images/misc/steam/blank_avatar.jpg"/>';
        }

        echo '<form id="modApprove' . $key . '">';

        echo '<div class="row">
                <div class="col-sm-5">' . $modName . '</div>
                <div class="col-sm-4">' . $modDeveloperAvatar . ' ' . $modDeveloper . '</div>
                <div class="col-sm-1 text-center"><strong>WS</strong> ' . $modWorkshopLink . '</div>
                <div class="col-sm-2 text-right">' . relative_time_v3($value['date_recorded']) . '</div>
            </div>';

        echo '<span class="h5">&nbsp;</span>';

        echo '<div class="row">
                <div class="col-sm-2">&nbsp;</div>
                <div class="col-sm-10">' . $modDescription . '</div>
            </div>';

        echo '<span class="h5">&nbsp;</span>';

        echo '<div class="row">
                <div class="col-sm-1">&nbsp;</div>
                <div class="col-sm-1"><strong>Rejected:</strong></div>
                <div class="col-sm-10">
                    <div class="alert alert-danger" role="alert">
                        <p>' . $modRejectedReason . '</p>
                    </div>
                </div>
            </div>';

        echo '<div class="row">
                <div class="col-md-12 text-center"><span id="modAJAXResult' . $key . '" class="labelWarnings label label-danger"></span></div>
            </div>';

        echo '<span class="h5">&nbsp;</span>';

        echo '<div class="row">
                <div class="col-md-4">&nbsp;</div>
                <div class="col-md-4 text-center">
                    <input name="submit" class="btn btn-danger" type="submit" value="Re-Queue">
                </div>
                <div class="col-md-4">&nbsp;</div>
            </div>';

        echo '<input type="hidden" name="modID" value="' . $value['mod_id'] . '">';

        echo '</form>';

        echo '<script type="application/javascript">
                    function htmlEntities(str) {
                        return String(str).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
                    }

                    $("#modApprove' . $key . '").submit(function (event) {
                        event.preventDefault();

                        $.post("./admin/mod_rejected_ajax.php", $("#modApprove' . $key . '").serialize(), function (data) {
                            try {
                                if(data){
                                    var response = JSON.parse(data);
                                    if(response && response.error){
                                        $("#modAJAXResult' . $key . '").html(response.error);
                                    }
                                    else if(response && response.result){
                                        $("#modAJAXResult' . $key . '").html(response.result);
                                        loadPage("#admin__mod_rejected",1);
                                    }
                                    else{
                                        $("#modAJAXResult' . $key . '").html(htmlEntities(data));
                                    }
                                }
                            }
                            catch(err) {
                                $("#modAJAXResult' . $key . '").html("Parsing Error: " + err.message + "<br />" + htmlEntities(data));
                            }
                        }, "text");
                    });
                </script>';

        echo '<hr />';
    }

    echo '<span class="h5">&nbsp;</span>';
} catch (Exception $e) {
    echo formatExceptionHandling($e);
} finally {
    if (isset($memcache)) $memcache->close();
}