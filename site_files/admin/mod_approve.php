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

    echo '<h2>Approve Mods</h2>';
    echo '<p>This form allows admins to edit and approve custom games submitted to the site.</p>';
    echo '<hr />';

    $modList = $db->q(
        'SELECT
                ml.*,
                gu.`user_name`,
                gu.`user_avatar`,

                (SELECT SUM(`gamesPlayed`) FROM `cache_mod_matches` WHERE `modID` = ml.`mod_id` AND `gamePhase` = 3 AND `dateRecorded` >= NOW() - INTERVAL 7 DAY) AS `games_last_week`
            FROM `mod_list` ml
            LEFT JOIN `gds_users` gu ON ml.`steam_id64` = gu.`user_id64`
            WHERE ml.`mod_active` <> 1 AND ml.`mod_rejected` <> 1
            ORDER BY `games_last_week` DESC, ml.date_recorded DESC;'
    );

    if (empty($modList)) {
        throw new Exception('No mods to approve!');
    }

    foreach ($modList as $key => $value) {
        echo '<form id="modApprove' . $key . '">';

        $modIDlink = "<a class='nav-clickable' href='#s2__mod?id={$value['mod_id']}'>{$value['mod_identifier']}</a>";

        $modGroup = !empty($value['mod_steam_group'])
            ? '<input class="formTextArea boxsizingBorder" name="modGroup" type="text" maxlength="70" value="' . $value['mod_steam_group'] . '">'
            : '<input class="formTextArea boxsizingBorder" name="modGroup" type="text" maxlength="70" placeholder="http://steamcommunity.com/groups/XXXXX">';

        $workshopLink = !empty($value['mod_workshop_link'])
            ? '<a target="_blank" href="http://steamcommunity.com/sharedfiles/filedetails/?id=' . $value['mod_workshop_link'] . '">WS</a>'
            : '<span>WS</span>';

        $steamGroupLink = !empty($value['mod_steam_group'])
            ? '<a target="_blank" href="http://steamcommunity.com/groups/' . $value['mod_steam_group'] . '">SG</a>'
            : '<span>SG</span>';

        $modDescription = !empty($value['mod_description'])
            ? '<textarea class="formTextArea boxsizingBorder" name="modDescription" rows="3" required>' . $value['mod_description'] . '</textarea>'
            : '<textarea class="formTextArea boxsizingBorder" name="modDescription" rows="3" placeholder="Awesome description of custom game" required></textarea>';

        $modRejectedReason = '<textarea class="formTextArea boxsizingBorder" name="modRejectedReason" rows="1" placeholder="Reason for rejecting this mod"></textarea>';

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

        echo '<input type="hidden" value="' . $value['mod_identifier'] . '">';

        echo '<div class="row">
                <div class="col-md-1"><span class="h4">ID</span></div>
                <div class="col-md-1 text-center"><span class="glyphicon glyphicon-question-sign" title="The identifier for this custom game"></span></div>
                <div class="col-md-4">' . $modIDlink . '</div>
                <div class="col-md-2"><strong>Games (LW):</strong> ' . number_format($value['games_last_week']) . '</div>

                <div class="col-md-1"><span class="h4">Links</span></div>
                <div class="col-md-3">' . $workshopLink . ' || ' . $steamGroupLink . '</div>
            </div>';

        echo '<span class="h5">&nbsp;</span>';

        echo '<div class="row">
                <div class="col-md-1"><span class="h4">Name</span></div>
                <div class="col-md-1 text-center"><span class="glyphicon glyphicon-question-sign" title="The name of the custom game"></span></div>
                <div class="col-md-6"><input class="formTextArea boxsizingBorder" name="modName" type="text" maxlength="70" value="' . $value['mod_name'] . '" required></div>

                <div class="col-md-1"><span class="h4">Added</span></div>
                <div class="col-md-3">' . relative_time_v3($value['date_recorded']) . '</div>
            </div>';

        echo '<span class="h5">&nbsp;</span>';

        echo '<div class="row">
                <div class="col-md-1"><span class="h4">Group</span></div>
                <div class="col-md-1 text-center"><span class="glyphicon glyphicon-question-sign" title="The steam group for this custom game, if applicable"></span></div>
                <div class="col-md-6">' . $modGroup . '</div>
                <div class="col-md-4">' . $modDeveloperAvatar . ' ' . $modDeveloper . '</div>
            </div>';

        echo '<span class="h5">&nbsp;</span>';

        echo '<div class="row">
                <div class="col-md-1"><span class="h4">Desc.</span></div>
                <div class="col-md-1 text-center"><span class="glyphicon glyphicon-question-sign" title="The description for this custom game"></span></div>
                <div class="col-md-10">' . $modDescription . '</div>
            </div>';

        echo '<span class="h5">&nbsp;</span>';

        echo '<div class="row">
                <div class="col-md-2">&nbsp;</div>
                <div class="col-md-2 text-right"><strong>Rejection:</strong></div>
                <div class="col-md-8">' . $modRejectedReason . '</div>
            </div>';

        echo '<span class="h5">&nbsp;</span>';

        echo '<div class="row">
                <div class="col-md-12 text-center"><span id="modAJAXResult' . $key . '" class="labelWarnings label label-danger"></span></div>
            </div>';

        echo '<span class="h5">&nbsp;</span>';

        echo '<div class="row">
                <div class="col-md-4">&nbsp;</div>
                <div class="col-md-4 text-center">
                    <input name="submit" class="btn btn-success" type="submit" value="Approve" onclick="this.form.m_submit.value = this.value;">
                    <input name="submit" class="btn btn-danger" type="submit" value="Reject" onclick="this.form.m_submit.value = this.value;">
                </div>
                <div class="col-md-4">&nbsp;</div>
            </div>';

        echo '<input type="hidden" name="modID" value="' . $value['mod_id'] . '">';

        echo '<input type="hidden" name="m_submit" value=""/>';

        echo '<span class="h5">&nbsp;</span>';

        echo '</form>';

        echo '<hr />';

        echo '<script type="application/javascript">
                    function htmlEntities(str) {
                        return String(str).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
                    }

                    $("#modApprove' . $key . '").submit(function (event) {
                        event.preventDefault();

                        $.post("./admin/mod_approve_ajax.php", $("#modApprove' . $key . '").serialize(), function (data) {
                            try {
                                if(data){
                                    var response = JSON.parse(data);
                                    if(response && response.error){
                                        $("#modAJAXResult' . $key . '").html(response.error);
                                    }
                                    else if(response && response.result){
                                        $("#modAJAXResult' . $key . '").html(response.result);
                                        loadPage("#admin__mod_approve",1);
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
    }

    echo '<span class="h5">&nbsp;</span>';

} catch (Exception $e) {
    echo formatExceptionHandling($e);
} finally {
    if (isset($memcache)) $memcache->close();
}