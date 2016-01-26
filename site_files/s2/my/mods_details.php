<?php
require_once('../../global_functions.php');
require_once('../../connections/parameters.php');

try {
    if (!isset($_SESSION)) {
        session_start();
    }

    if (empty($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception('Invalid modID! Bad type.');
    }

    $modID = $_GET['id'];

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    checkLogin_v2();
    if (empty($_SESSION['user_id64'])) throw new Exception('Not logged in!');

    $modDetails = cached_query(
        's2_my_mods_details' . $modID,
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
              ml.`date_recorded` AS mod_date_added

            FROM `mod_list` ml
            WHERE ml.`mod_id` = ?;',
        'i',
        array($modID),
        5
    );

    try {
        if (empty($modDetails)) {
            throw new Exception('No mods matching ID!');
        }

        echo "<div class='page-header'><h2>{$modDetails[0]['mod_name']}</h2></div>";

        //Check if logged in user is on team
        $modDetailsAuthorisation = $db->q(
            'SELECT
                `mod_id`
              FROM mod_list_owners
              WHERE
                `mod_id` = ? AND
                `steam_id64` = ?
              LIMIT 0,1;',
            'is',
            array($modID, $_SESSION['user_id64'])
        );

        //Check if logged in user is an admin
        $adminCheck = adminCheck($_SESSION['user_id64'], 'admin');

        if (empty($modDetailsAuthorisation) && !$adminCheck) {
            throw new Exception('Not authorised to modify this mod!');
        }

        $teamMembers = cached_query(
            's2_my_mods_team' . $modID,
            'SELECT
                  mlo.`steam_id64`,
                  mlo.`date_recorded`,

                  gdsu.`user_name`,
                  gdsu.`user_avatar`

              FROM `mod_list_owners`  mlo
              LEFT JOIN `gds_users` gdsu
                ON mlo.`steam_id64` = gdsu.`user_id64`
              WHERE `mod_id` = ?
              ORDER BY `date_recorded` DESC;',
            'i',
            array($modID),
            1
        );

        echo '<span id="modDetailsDelAJAXResult" class="labelWarnings label label-danger"></span>';

        foreach ($teamMembers as $key => $value) {
            $developerAvatar = !empty($value['user_avatar'])
                ? $value['user_avatar']
                : $CDN_image . '/images/misc/steam/blank_avatar.jpg';
            $developerAvatar = '<img width="20" height="20" src="' . $developerAvatar . '" />';
            $developerUsername = !empty($value['user_name'])
                ? $value['user_name']
                : '???';
            $developerLink = '<a target="_blank" href="http://steamcommunity.com/profiles/' . $value['steam_id64'] . '">' . $developerAvatar . '</a> <a class="nav-clickable" href="#s2__user?id=' . $value['steam_id64'] . '">' . $developerUsername . '</a>';

            $removeButton = $modDetails[0]['developer_id64'] != $value['steam_id64']
                ? '<div class="col-md-2">
                    <form id="modTeamMemberRemove' . $value['steam_id64'] . '">
                        <input name="mod_id" type="hidden" value="' . $modID . '">
                        <input name="team_member" type="hidden" value="' . $value['steam_id64'] . '">
                        <button id="sub" class="btn btn-sm btn-danger">Remove</button>
                    </form>
                </div>'
                : '';

            echo '<div class="row">
                <div class="col-md-3">' . $developerLink . '</div>
                <div class="col-md-2 text-right">' . relative_time_v3($value['date_recorded'], 1, NULL, false, true, true) . '</div>
                ' . $removeButton . '
            </div>';

            echo '<span class="h5">&nbsp;</span>';

            if ($modDetails[0]['developer_id64'] != $value['steam_id64']) {
                echo '<script type="application/javascript">
                    $("#modTeamMemberRemove' . $value['steam_id64'] . '").submit(function (event) {
                        event.preventDefault();

                        $.post("./s2/my/mods_details_del_tm_ajax.php", $("#modTeamMemberRemove' . $value['steam_id64'] . '").serialize(), function (data) {
                            try {
                                if(data){
                                    var response = JSON.parse(data);
                                    if(response && response.error){
                                        $("#modDetailsDelAJAXResult").html(response.error);
                                    }
                                    else if(response && response.result){
                                        loadPage("#s2__my__mods_details?id=' . $modID . '",1);
                                    }
                                    else{
                                        $("#modDetailsDelAJAXResult").html(data);
                                    }
                                }
                            }
                            catch(err) {
                                $("#modDetailsDelAJAXResult").html("Parsing Error: " + err.message + "<br />" + data);
                            }
                        }, "text");
                    });
                </script>';
            }
        }

        echo '<h4>Add Team Member</h4>';

        echo '<form id="modTeamMemberAdd">';
        echo '<input name="mod_id" type="hidden" value="' . $modID . '">';
        echo '<div class="row">
                    <div class="col-md-5"><input class="formTextArea boxsizingBorder" name="team_member" type="text" maxlength="100" placeholder="URL or steamID64 or steamID32"></div>
                    <div class="col-md-2"><button id="sub" class="btn btn-success">Add</button></div>
                </div>';
        echo '</form>';

        echo '<span id="modDetailsAddAJAXResult" class="labelWarnings label label-danger"></span>';

        echo '<script type="application/javascript">
            $("#modTeamMemberAdd").submit(function (event) {
                event.preventDefault();

                $.post("./s2/my/mods_details_add_tm_ajax.php", $("#modTeamMemberAdd").serialize(), function (data) {
                    try {
                        if(data){
                            var response = JSON.parse(data);
                            if(response && response.error){
                                $("#modDetailsAddAJAXResult").html(response.error);
                            }
                            else if(response && response.result){
                                loadPage("#s2__my__mods_details?id=' . $modID . '",1);
                            }
                            else{
                                $("#modDetailsAddAJAXResult").html(data);
                            }
                        }
                    }
                    catch(err) {
                        $("#modDetailsAddAJAXResult").html("Parsing Error: " + err.message + "<br />" + data);
                    }
                }, "text");
            });
        </script>';


        echo '<hr />';
    } catch (Exception $e) {
        echo formatExceptionHandling($e);
    }

    echo '<span class="h4">&nbsp;</span>';

    echo '<div class="text-center">
            <a class="nav-clickable btn btn-default btn-lg" href="#s2__my__mods">My Mods</a>
            <a class="nav-clickable btn btn-default btn-lg" href="#s2__guide_stat_collection">Implementing Stats</a>
        </div>';

    echo '<span class="h4">&nbsp;</span>';
} catch (Exception $e) {
    echo formatExceptionHandling($e);
} finally {
    if (isset($memcache)) $memcache->close();
}