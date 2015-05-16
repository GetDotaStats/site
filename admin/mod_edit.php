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

    echo '<h2>Edit Mods</h2>';
    echo '<p>This form allows admins to edit custom games that have already been approved.</p>';
    echo '<hr />';

    $modList = $db->q(
        'SELECT
                ml.*,
                gu.`user_name`,
                gu.`user_avatar`,
                (SELECT COUNT(*) FROM `mod_match_overview` WHERE `mod_id` = ml.`mod_identifier`) as games_recorded
            FROM `mod_list` ml
            LEFT JOIN `gds_users` gu ON ml.`steam_id64` = gu.`user_id64`
            WHERE ml.`mod_active` = 1
            ORDER BY games_recorded ASC, ml.date_recorded DESC;'
    );

    if (empty($modList)) {
        throw new Exception('No mods to edit!');
    }

    foreach ($modList as $key => $value) {
        echo '<form id="modApprove' . $key . '">';

        $modID = !empty($value['mod_identifier'])
            ? '<input class="formTextArea boxsizingBorder" type="text" value="' . $value['mod_identifier'] . '" disabled>'
            : '<input class="formTextArea boxsizingBorder" type="text" value="UNKNOWN" disabled>';

        $modGroup = !empty($value['mod_steam_group'])
            ? '<input class="formTextArea boxsizingBorder" name="modGroup" type="text" maxlength="70" value="' . $value['mod_steam_group'] . '">'
            : '<input class="formTextArea boxsizingBorder" name="modGroup" type="text" maxlength="70" placeholder="http://steamcommunity.com/groups/XXXXX">';

        $modWorkshop = !empty($value['mod_workshop_link'])
            ? '<input class="formTextArea boxsizingBorder" name="modWorkshop" type="text" maxlength="70" value="' . $value['mod_workshop_link'] . '">'
            : '<input class="formTextArea boxsizingBorder" name="modWorkshop" type="text" maxlength="70" placeholder="XXXXX">';

        $modGroupLink = !empty($value['mod_steam_group'])
            ? '</div><div class="col-md-2"><span class="h4">SG</span> <a href="http://steamcommunity.com/groups/' . $value['mod_steam_group'] . '" target="_new"><span class="glyphicon glyphicon-new-window"></span></a>'
            : '';

        $modWorkshopLink = !empty($value['mod_workshop_link'])
            ? '<a href="http://steamcommunity.com/sharedfiles/filedetails/?id=' . $value['mod_workshop_link'] . '" target="_new"><span class="glyphicon glyphicon-new-window"></span></a>'
            : '<span class="glyphicon glyphicon-new-window"></span>';

        $modMaps = !empty($value['mod_maps']) && $value['mod_maps'] != 'One map per line!'
            ? '<textarea class="formTextArea boxsizingBorder" name="modMaps" rows="2" required>' . implode("\n", json_decode($value['mod_maps'], 1)) . '</textarea>'
            : '<textarea class="formTextArea boxsizingBorder" name="modMaps" rows="2" placeholder="One map per line!" required></textarea>';

        $modDescription = !empty($value['mod_description'])
            ? '<textarea class="formTextArea boxsizingBorder" name="modDescription" rows="3" required>' . $value['mod_description'] . '</textarea>'
            : '<textarea class="formTextArea boxsizingBorder" name="modDescription" rows="3" placeholder="Awesome description of custom game" required></textarea>';

        $modOptions = !empty($value['mod_options'])
            ? '<textarea class="formTextArea boxsizingBorder" name="modOptions" rows="4">' . $value['mod_options'] . '</textarea>'
            : '<textarea class="formTextArea boxsizingBorder" name="modOptions" rows="1" placeholder="Options for the mod"></textarea>';

        $modActive = isset($value['mod_active']) && $value['mod_active'] == 1
            ? '<input type="radio" name="modActive" value="0">No<br />
                    <input type="radio" name="modActive" value="1" checked>Yes'
            : '<input type="radio" name="modActive" value="0" checked>No<br />
                    <input type="radio" name="modActive" value="1">Yes';

        $modOptionsActive = isset($value['mod_options_enabled']) && $value['mod_options_enabled'] == 1
            ? '<input type="radio" name="modOptionsActive" value="0">No<br />
                    <input type="radio" name="modOptionsActive" value="1" checked>Yes'
            : '<input type="radio" name="modOptionsActive" value="0" checked>No<br />
                    <input type="radio" name="modOptionsActive" value="1">Yes';

        $modMaxPlayers = '<select name="modMaxPlayers" class="formTextArea boxsizingBorder">';
        $modMaxPlayers .= !isset($value['mod_max_players']) || !is_numeric($value['mod_max_players'])
            ? '<option value="-" selected>-</option>'
            : '<option value="-">-</option>';
        for ($i = 1; $i <= 10; $i++) {
            $selected = '';
            if (isset($value['mod_max_players']) && is_numeric($value['mod_max_players']) && $value['mod_max_players'] == $i) {
                $selected = ' selected';
            }
            $modMaxPlayers .= '<option value="' . $i . '"' . $selected . '>' . $i . '</option>';
        }
        $modMaxPlayers .= '</select>';


        if (!empty($value['user_name'])) {
            $modDeveloper = !empty($value['steam_id64'])
                ? '<a class="nav-clickable" href="#d2mods__profile?id=' . $value['steam_id64'] . '">' . $value['user_name'] . '</a>'
                : $value['user_name'];

            $modDeveloperAvatar = !empty($value['user_avatar'])
                ? '<a href="https://steamcommunity.com/profiles/' . $value['steam_id64'] . '" target="_blank"><img width="20" height="20" src="' . $value['user_avatar'] . '"/></a>'
                : '<a href="https://steamcommunity.com/profiles/' . $value['steam_id64'] . '" target="_blank"><img width="20" height="20" src="' . $CDN_image . '/images/misc/steam/blank_avatar.jpg"/></a>';
        } else {
            $modDeveloper = 'Unknown';
            $modDeveloperAvatar = '<img width="20" height="20" src="' . $CDN_image . '/images/misc/steam/blank_avatar.jpg"/>';
        }

        $modGames = '<a class="nav-clickable" href="#d2mods__stats?id=' . $value['mod_id'] . '">' . number_format($value['games_recorded']) . '</a>';

        echo '<div class="row">
                <div class="col-md-1"><span class="h4">Name</span></div>
                <div class="col-md-1 text-center"><span class="glyphicon glyphicon-question-sign" title="The name of the custom game"></span></div>
                <div class="col-md-6"><input class="formTextArea boxsizingBorder" name="modName" type="text" maxlength="70" value="' . $value['mod_name'] . '" required></div>

                <div class="col-md-1"><span class="h4">Games</span></div>
                <div class="col-md-3">' . $modGames . '</div>
            </div>';

        echo '<span class="h5">&nbsp;</span>';

        echo '<div class="row">
                <div class="col-md-1"><span class="h4">ID</span></div>
                <div class="col-md-1 text-center"><span class="glyphicon glyphicon-question-sign" title="The identifier for this custom game"></span></div>
                <div class="col-md-6">' . $modID . '</div>

                <div class="col-md-1"><span class="h4">Added</span></div>
                <div class="col-md-3">' . relative_time_v3($value['date_recorded']) . '</div>
            </div>';

        echo '<span class="h5">&nbsp;</span>';

        echo '<div class="row">
                <div class="col-md-1"><span class="h4">Maps</span></div>
                <div class="col-md-1 text-center"><span class="glyphicon glyphicon-question-sign" title="The maps in this custom game"></span></div>
                <div class="col-md-6">' . $modMaps . '</div>
                <div class="col-md-4">' . $modDeveloperAvatar . ' ' . $modDeveloper . '</div>
            </div>';

        echo '<span class="h5">&nbsp;</span>';

        echo '<div class="row">
                <div class="col-md-1"><span class="h4">WS.ID</span></div>
                <div class="col-md-1 text-center"><span class="glyphicon glyphicon-question-sign" title="The workshop ID for this custom game"></span></div>
                <div class="col-md-6">' . $modWorkshop . '</div>
                <div class="col-md-2"><span class="h4">WS</span> ' . $modWorkshopLink . $modGroupLink . '</div>
            </div>';

        echo '<span class="h5">&nbsp;</span>';

        echo '<div class="row">
                <div class="col-md-1"><span class="h4">Group</span></div>
                <div class="col-md-1 text-center"><span class="glyphicon glyphicon-question-sign" title="The steam group for this custom game, if applicable"></span></div>
                <div class="col-md-6">' . $modGroup . '</div>
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

                <div class="col-md-1"><span class="h4">Active</span></div>
                <div class="col-md-1">' . $modActive . '</div>

                <div class="col-md-1"><span class="h4">Options</span></div>
                <div class="col-md-1">' . $modOptionsActive . '</div>

                <div class="col-md-1"><span class="h4">Players</span></div>
                <div class="col-md-2">' . $modMaxPlayers . '</div>
            </div>';

        echo '<span class="h5">&nbsp;</span>';

        echo '<div class="row">
                <div class="col-md-1"><span class="h4">Options</span></div>
                <div class="col-md-1 text-center"><span class="glyphicon glyphicon-question-sign" title="The options for this custom game"></span></div>
                <div class="col-md-10">' . $modOptions . '</div>
            </div>';

        echo '<span class="h5">&nbsp;</span>';

        echo '<div class="row">
                <div class="col-md-12 text-center"><span id="modAJAXResult' . $key . '" class="labelWarnings label label-danger"></span></div>
            </div>';

        echo '<span class="h5">&nbsp;</span>';

        echo '<div class="row">
                <div class="col-md-12 text-center"><input type="submit" value="Edit"></div>
            </div>';

        echo '<input type="hidden" name="modID" value="' . $value['mod_identifier'] . '">';

        echo '<span class="h5">&nbsp;</span>';

        echo '</form>';

        echo '<hr />';

        echo '<script type="application/javascript">
                    function htmlEntities(str) {
                        return String(str).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
                    }

                    $("#modApprove' . $key . '").submit(function (event) {
                        event.preventDefault();

                        $.post("./admin/mod_edit_ajax.php", $("#modApprove' . $key . '").serialize(), function (data) {
                            try {
                                if(data){
                                    var response = JSON.parse(data);
                                    if(response && response.error){
                                        $("#modAJAXResult' . $key . '").html(response.error);
                                    }
                                    else if(response && response.result){
                                        $("#modAJAXResult' . $key . '").html(response.result);
                                        loadPage("#admin__mod_edit",1);
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