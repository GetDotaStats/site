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

    echo '<h2>Edit a Custom Game Schema <small>BETA</small></h2>';
    echo '<p>This form allows admins to: edit, approve, and reject schemas for mods.</p>';

    echo '<div class="text-center">
                <a class="nav-clickable btn btn-default btn-lg" href="#admin__mod_schema">Schema List</a>
                <a class="nav-clickable btn btn-default btn-lg" href="#admin__mod_schema_create">Create Schema</a>
                <a class="nav-clickable btn btn-default btn-lg" href="#admin__mod_schema_edit">Edit Schema</a>
           </div>';

    echo '<hr />';

    if (isset($_GET['id'])) {
        $selectedSchemaID = $_GET['id'];
        if (!is_numeric($selectedSchemaID)) throw new Exception('Selected schemaID is invalid!');

        $selectedSchemaIDLookup = cached_query(
            'admin_custom_schema_lookup' . $selectedSchemaID,
            'SELECT
                    s2mcs.*,

                    ml.`mod_id`,
                    ml.`mod_name`,
                    ml.`steam_id64`,
                    ml.`mod_workshop_link`,

                    gdsu.`user_id64` AS owner_userid64,
                    gdsu.`user_name` AS owner_user_name,
                    gdsu.`user_avatar` AS owner_user_avatar,

                    gdsu2.`user_id64` AS submitter_userid64,
                    gdsu2.`user_name` AS submitter_user_name,
                    gdsu2.`user_avatar` AS submitter_user_avatar

                FROM `s2_mod_custom_schema` s2mcs
                INNER JOIN `mod_list` ml ON s2mcs.`modID` = ml.`mod_id`
                LEFT JOIN `gds_users` gdsu
                  ON ml.`steam_id64` = gdsu.`user_id64`
                LEFT JOIN `gds_users` gdsu2
                  ON s2mcs.`schemaSubmitterUserID64` = gdsu2.`user_id64`
                WHERE s2mcs.`schemaID` = ?
                ORDER BY ml.`mod_name`
                LIMIT 0,1;',
            'i',
            $selectedSchemaID,
            1
        );

        if (empty($selectedSchemaIDLookup)) throw new Exception('Selected schemaID does not exist!');

        //Approved schemas for mod
        $selectedSchemaApprovedSchemas = cached_query(
            'admin_custom_schema_lookup_schemas_approved',
            'SELECT
                    s2mcs.`schemaID`,
                    s2mcs.`schemaVersion`
                FROM `s2_mod_custom_schema` s2mcs
                WHERE s2mcs.`schemaApproved` = 1 AND s2mcs.`modID` = ?;',
            'i',
            $selectedSchemaIDLookup[0]['modID'],
            1
        );
    } else {
        $selectedSchemaID = NULL;
    }

    //Lookup all of the approved schemas
    $schemaSelectInputApproved = cached_query(
        'admin_custom_schema_unapproved_select_input_approved',
        'SELECT
                s2mcs.`schemaID`,
                s2mcs.`modID`,
                s2mcs.`schemaAuth`,
                s2mcs.`schemaVersion`,
                s2mcs.`schemaApproved`,
                s2mcs.`schemaRejected`,
                s2mcs.`schemaRejectedReason`,
                s2mcs.`schemaSubmitterUserID64`,
                s2mcs.`dateRecorded`,

                ml.`mod_name`

            FROM `s2_mod_custom_schema` s2mcs
            INNER JOIN `mod_list` ml ON s2mcs.`modID` = ml.`mod_id`
            WHERE s2mcs.`schemaApproved` = 1
            ORDER BY ml.`mod_name` ASC, s2mcs.`schemaVersion` DESC;',
        NULL,
        NULL,
        1
    );

    //Lookup all of the un-approved schemas
    $schemaSelectInputUnapproved = cached_query(
        'admin_custom_schema_unapproved_select_input_unapproved',
        'SELECT
                s2mcs.`schemaID`,
                s2mcs.`modID`,
                s2mcs.`schemaAuth`,
                s2mcs.`schemaVersion`,
                s2mcs.`schemaApproved`,
                s2mcs.`schemaRejected`,
                s2mcs.`schemaRejectedReason`,
                s2mcs.`schemaSubmitterUserID64`,
                s2mcs.`dateRecorded`,

                ml.`mod_name`

            FROM `s2_mod_custom_schema` s2mcs
            INNER JOIN `mod_list` ml ON s2mcs.`modID` = ml.`mod_id`
            WHERE s2mcs.`schemaApproved` = 0
            ORDER BY ml.`mod_name` ASC, s2mcs.`schemaVersion` DESC;',
        NULL,
        NULL,
        1
    );

    //Schema Search Form
    {

        //Schemas Approved
        echo '<form id="modSchemaSearchApproved">';
        echo '<div class="row">
                    <div class="col-md-3"><span class="h4">Schemas <small> - Approved</small></span></div>
                    <div class="col-md-5">
                        <select id="modSchemaSearch_field_approved" class="formTextArea boxsizingBorder" onChange="jumpMenu(this)" required>
                            ' . ((empty($selectedSchemaID) || $selectedSchemaIDLookup[0]['schemaApproved'] == 0) ? '<option selected value="--">--</option>' : '');

        if (!empty($schemaSelectInputApproved)) {
            foreach ($schemaSelectInputApproved as $key => $value) {
                if ($value['schemaApproved'] == 1) {
                    echo '<option' . ((!empty($selectedSchemaID) && $value['schemaID'] == $selectedSchemaID) ? ' selected' : '') . ' value="' . $value['schemaID'] . '">v' . $value['schemaVersion'] . ' -- ' . $value['mod_name'] . ' (ID #' . $value['schemaID'] . ')' . '</option>';
                }
            }
        }

        echo '          </select>
                    </div>
                </div>';
        echo '</form>';

        //Schemas Un-approved
        echo '<form id="modSchemaSearchUnapproved">';
        echo '<div class="row">
                    <div class="col-md-3"><span class="h4">Schemas <small> - Un-approved</small></span></div>
                    <div class="col-md-5">
                        <select id="modSchemaSearch_field_unapproved" class="formTextArea boxsizingBorder" onChange="jumpMenu(this)" required>
                            ' . ((empty($selectedSchemaID) || $selectedSchemaIDLookup[0]['schemaApproved'] == 1) ? '<option selected value="--">--</option>' : '');

        if (!empty($schemaSelectInputUnapproved)) {
            foreach ($schemaSelectInputUnapproved as $key => $value) {
                if ($value['schemaApproved'] == 0) {
                    echo '<option' . ((!empty($selectedSchemaID) && $value['schemaID'] == $selectedSchemaID) ? ' selected' : '') . ' value="' . $value['schemaID'] . '">v' . $value['schemaVersion'] . ' -- ' . $value['mod_name'] . ' (ID #' . $value['schemaID'] . ')' . ($value['schemaRejected'] == 1 ? ' -- REJECTED' : '') . '</option>';
                }
            }
        }

        echo '          </select>
                    </div>
                </div>';
        echo '</form>';

        echo '<span class="h5">&nbsp;</span>';

        echo '<script type="application/javascript">
                function jumpMenu(selObj) {
                    var op = selObj.options[selObj.selectedIndex];
                    loadPage("#admin__mod_schema_edit?id=" + op.value, 1);
                }
            </script>';
    }

    echo '<hr />';

    if (!empty($selectedSchemaID) && !empty($selectedSchemaIDLookup)) {
        $modThumb = is_file('../images/mods/thumbs/' . $selectedSchemaIDLookup[0]['mod_id'] . '.png')
            ? $CDN_image . '/images/mods/thumbs/' . $selectedSchemaIDLookup[0]['mod_id'] . '.png'
            : $CDN_image . '/images/misc/steam/blank_avatar.jpg';
        $modThumb = '<img width="25" height="25" src="' . $modThumb . '" />';
        $modNameLink = '<a target="_blank" href="http://steamcommunity.com/sharedfiles/filedetails/?id=' . $selectedSchemaIDLookup[0]['mod_workshop_link'] . '">' . $modThumb . '</a> <a class="nav-clickable" href="#d2mods__stats?id=' . $selectedSchemaIDLookup[0]['mod_id'] . '">' . $selectedSchemaIDLookup[0]['mod_name'] . '</a>';

        $ownerAvatar = !empty($selectedSchemaIDLookup[0]['owner_user_avatar'])
            ? $selectedSchemaIDLookup[0]['owner_user_avatar']
            : $CDN_image . '/images/misc/steam/blank_avatar.jpg';
        $ownerAvatar = '<img width="20" height="20" src="' . $ownerAvatar . '" />';
        $ownerLink = '<a target="_blank" href="http://steamcommunity.com/profiles/' . $selectedSchemaIDLookup[0]['owner_userid64'] . '">' . $ownerAvatar . '</a> <a class="nav-clickable" href="#d2mods__profile?id=' . $selectedSchemaIDLookup[0]['owner_userid64'] . '">' . $selectedSchemaIDLookup[0]['owner_user_name'] . '</a>';

        $submitterAvatar = !empty($selectedSchemaIDLookup[0]['submitter_user_avatar'])
            ? $selectedSchemaIDLookup[0]['submitter_user_avatar']
            : $CDN_image . '/images/misc/steam/blank_avatar.jpg';
        $submitterAvatar = '<img width="20" height="20" src="' . $submitterAvatar . '" />';
        $submitterLink = '<a target="_blank" href="http://steamcommunity.com/profiles/' . $selectedSchemaIDLookup[0]['submitter_userid64'] . '">' . $submitterAvatar . '</a> <a class="nav-clickable" href="#d2mods__profile?id=' . $selectedSchemaIDLookup[0]['submitter_userid64'] . '">' . $selectedSchemaIDLookup[0]['submitter_user_name'] . '</a>';

        echo '<div class="row">
                    <div class="col-md-2">&nbsp;</div>
                    <div class="col-md-1 text-center"><span class="h4">Ver.</span></div>
                    <div class="col-md-4"><span class="h4">Mod</span></div>
                    <div class="col-md-5"><span class="h4">Owner</span></div>
                </div>';

        echo '<span class="h5">&nbsp;</span>';

        echo '<div class="row">
                    <div class="col-md-2"><span class="h4">Schema</span></div>
                    <div class="col-md-1 text-center">v' . $selectedSchemaIDLookup[0]['schemaVersion'] . '</div>
                    <div class="col-md-4">' . $modNameLink . '</div>
                    <div class="col-md-5">' . $ownerLink . '</div>
                </div>';

        echo '<span class="h5">&nbsp;</span>';

        //Submitter
        echo '<div class="row">
                    <div class="col-md-2"><span class="h4">Submitter</span></div>
                    <div class="col-md-5">' . $submitterLink . '</div>
                    <div class="col-md-5">' . relative_time_v3($selectedSchemaIDLookup[0]['dateRecorded'], 1, NULL, false, true, true) . '</div>
                </div>';

        echo '<span class="h5">&nbsp;</span>';

        //Approved Schemas
        if (!empty($selectedSchemaApprovedSchemas)) {
            echo '<form id="modSchemaDeactivate">';
            echo '<input name="schema_id" type="hidden" value="' . $selectedSchemaID . '">';
            echo '<input name="schema_mod_id" type="hidden" value="' . $selectedSchemaIDLookup[0]['modID'] . '">';

            echo '<div class="row">
                    <div class="col-md-2"><span class="h4">Active Schemas</span></div>
                    <div class="col-md-5">';

            $i = 0;
            foreach ($selectedSchemaApprovedSchemas as $key => $value) {
                $i++;
                if ($i == 1) {
                    echo '<a class="nav-clickable" href="#admin__mod_schema_edit?id=' . $value['schemaID'] . '">' . ($selectedSchemaID == $value['schemaID'] ? '<strong>v' . $value['schemaVersion'] . '</strong>' : 'v' . $value['schemaVersion']) . '</a>';
                } else {
                    echo ', <a class="nav-clickable" href="#admin__mod_schema_edit?id=' . $value['schemaID'] . '">' . ($selectedSchemaID == $value['schemaID'] ? '<strong>v' . $value['schemaVersion'] . '</strong>' : 'v' . $value['schemaVersion']) . '</a>';
                }
            }

            echo '  </div>
                    <div class="col-md-2"><button>De-activate</button> <span class="glyphicon glyphicon-question-sign" title="Do you want to un-approve all but the current version of the schema for this mod?"></span></div>
                </div>';

            echo '</form>';

            echo '<div class="row">
                        <div class="col-md-2">&nbsp;</div>
                        <div class="col-md-7"><span id="schemaCustomDeactivateAJAXResult" class="labelWarnings label label-danger"></span></div>
                    </div>';

            echo '<script type="application/javascript">
                        $("#modSchemaDeactivate").submit(function (event) {
                            event.preventDefault();

                            $.post("./admin/mod_schema_edit_deactivate_ajax.php", $("#modSchemaDeactivate").serialize(), function (data) {
                                try {
                                    if(data){
                                        var response = JSON.parse(data);
                                        if(response && response.error){
                                            $("#schemaCustomDeactivateAJAXResult").html(response.error);
                                        }
                                        else if(response && response.result){
                                            loadPage("#admin__mod_schema_edit?id=' . $selectedSchemaID . '",1);
                                        }
                                        else{
                                            $("#schemaCustomDeactivateAJAXResult").html(data);
                                        }
                                    }
                                }
                                catch(err) {
                                    $("#schemaCustomDeactivateAJAXResult").html("Parsing Error: " + err.message + "<br />" + data);
                                }
                            }, "text");
                        });
                    </script>';

            echo '<span class="h5">&nbsp;</span>';
        }

        ///////////////////////////////////////////////////
        // Form for editing the schema
        ///////////////////////////////////////////////////

        echo '<form id="modSchemaEdit">';
        echo '<input name="schema_id" type="hidden" value="' . $selectedSchemaIDLookup[0]['schemaID'] . '">';

        //Approved
        echo '<div class="row">
                    <div class="col-md-2"><span class="h4">Approved</span> <span class="glyphicon glyphicon-question-sign" title="Do you want to approve this schema and allow it to go live?"></span></div>
                    <div class="col-md-2">
                        <input type="radio" name="schema_approved" value="1"' . (($selectedSchemaIDLookup[0]['schemaApproved'] == 1) ? ' checked' : '') . '>Approved<br />
                        <input type="radio" name="schema_approved" value="0"' . (($selectedSchemaIDLookup[0]['schemaApproved'] == 0) ? ' checked' : '') . '>Not Approved
                    </div>
                </div>';

        echo '<span class="h5">&nbsp;</span>';

        //Rejected
        echo '<div class="row">
                    <div class="col-md-2"><span class="h4">Rejected</span> <span class="glyphicon glyphicon-question-sign" title="Is this version not acceptable?"></span></div>
                    <div class="col-md-2">
                        <input type="radio" name="schema_rejected" value="1"' . (($selectedSchemaIDLookup[0]['schemaRejected'] == 1) ? ' checked' : '') . '>Rejected<br />
                        <input type="radio" name="schema_rejected" value="0"' . (($selectedSchemaIDLookup[0]['schemaRejected'] == 0) ? ' checked' : '') . '>Not Rejected
                    </div>
                </div>';

        echo '<span class="h5">&nbsp;</span>';

        echo '<div class="row">
                    <div class="col-md-2"><span class="h4">Reason</span></div>
                    <div class="col-md-6"><textarea class="formTextArea boxsizingBorder" name="schema_rejected_reason" rows="3" placeholder="Reason for rejecting the schema">' . ((!empty($selectedSchemaIDLookup[0]['schemaRejectedReason'])) ? $selectedSchemaIDLookup[0]['schemaRejectedReason'] : '') . '</textarea></div>
                </div>';

        echo '<span class="h5">&nbsp;</span>';

        /////////////////////////
        // Custom Game Values
        /////////////////////////

        echo '<div class="row">
                    <div class="col-md-4"><span class="h4">Custom Game Values</span> <span class="glyphicon glyphicon-question-sign" title="This is where all of your schema fields that relate to the entire game are defined"></span></div>
                </div>';

        echo '<span class="h5">&nbsp;</span>';

        $modSchemaEditFields_CustomGame_examples = array(
            array('display' => 'Roshan Attempts', 'name' => 'game_roshan_attempts'),
            array('display' => 'Radiant Creeps Trained', 'name' => 'game_team1_creeps_levelups'),
            array('display' => 'First Life Lost', 'name' => 'game_time_firstblood'),
            array('display' => 'Heroes Banned', 'name' => 'game_banned_heroes_hash'),
            array('display' => 'Tower1 Lifetime', 'name' => 'game_tower1_fall_time'),
            array('display' => 'Tower2 Lifetime', 'name' => 'game_tower2_fall_time'),
            array('display' => 'Highest Wave Beaten', 'name' => 'game_highest_wave_beaten'),
            array('display' => 'Lives Lost Team1', 'name' => 'game_lives_lost_team1'),
            array('display' => 'Betting Rounds', 'name' => 'game_betting_rounds'),
            array('display' => 'Zeny Investment', 'name' => 'game_investment_zeny_total'),
        );
        $modSchemaEditFields_CustomGame = 5;
        for ($i = 1; $i <= $modSchemaEditFields_CustomGame; $i++) {
            $randomExample = rand(0, (count($modSchemaEditFields_CustomGame_examples) - 1));
            echo '<div class="row">
                    <div class="col-md-1"><span class="h4">#' . $i . '</span></div>
                    <div class="col-md-1">Display</div>
                    <div class="col-md-6"><input class="formTextArea boxsizingBorder" name="cgv' . $i . '_display" type="text" maxlength="70" size="45" placeholder="' . $modSchemaEditFields_CustomGame_examples[$randomExample]['display'] . '"' . (!empty($selectedSchemaIDLookup[0]['customGameValue' . $i . '_display']) ? ' value="' . $selectedSchemaIDLookup[0]['customGameValue' . $i . '_display'] . '"' : '') . '></div>
                </div>';
            echo '<div class="row">
                    <div class="col-md-1">&nbsp;</div>
                    <div class="col-md-1">Name</div>
                    <div class="col-md-6"><input class="formTextArea boxsizingBorder" name="cgv' . $i . '_name" type="text" maxlength="70" size="45" placeholder="' . $modSchemaEditFields_CustomGame_examples[$randomExample]['name'] . '"' . (!empty($selectedSchemaIDLookup[0]['customGameValue' . $i . '_name']) ? ' value="' . $selectedSchemaIDLookup[0]['customGameValue' . $i . '_name'] . '"' : '') . '></div>
                </div>';
            echo '<div class="row">
                    <div class="col-md-1">&nbsp;</div>
                    <div class="col-md-1">Objective</div>
                    <div class="col-md-2">
                        <input type="radio" name="cgv' . $i . '_objective" value="1"' . (($selectedSchemaIDLookup[0]['customGameValue' . $i . '_objective'] == 1) ? ' checked' : '') . '>Minimise<br />
                        <input type="radio" name="cgv' . $i . '_objective" value="2"' . (($selectedSchemaIDLookup[0]['customGameValue' . $i . '_objective'] == 2) ? ' checked' : '') . '>Maximise<br />
                        <input type="radio" name="cgv' . $i . '_objective" value="3"' . (($selectedSchemaIDLookup[0]['customGameValue' . $i . '_objective'] == 3 || $selectedSchemaIDLookup[0]['customGameValue' . $i . '_objective'] == NULL) ? ' checked' : '') . '>Info
                    </div>
                </div>';

            echo '<span class="h5">&nbsp;</span>';
        }


        /////////////////////////
        // Custom Player Values
        /////////////////////////

        echo '<div class="row">
                    <div class="col-md-4"><span class="h4">Custom Player Values</span> <span class="glyphicon glyphicon-question-sign" title="This is where all of your schema fields that relate to individual players are defined"></span></div>
                </div>';

        echo '<span class="h5">&nbsp;</span>';

        $modSchemaEditFields_CustomPlayer_examples = array(
            array('display' => 'Roshan Kills', 'name' => 'player_roshan_kills'),
            array('display' => 'Pickup Time - Item Slot #1', 'name' => 'player_item1_pickup_time'),
            array('display' => 'Damage Upgrades Purchased', 'name' => 'player_upgrades_dmg_count'),
            array('display' => 'Selected Skills', 'name' => 'player_skills_selected_hash'),
            array('display' => 'Items at 5mins', 'name' => 'player_items_5mins_hash'),
            array('display' => 'Items at 10mins', 'name' => 'player_items_10mins_hash'),
            array('display' => 'Items at 15mins', 'name' => 'player_items_15mins_hash'),
        );
        $modSchemaEditFields_CustomPlayer = 15;
        for ($i = 1; $i <= $modSchemaEditFields_CustomPlayer; $i++) {
            $randomExample = rand(0, (count($modSchemaEditFields_CustomPlayer_examples) - 1));
            echo '<div class="row">
                    <div class="col-md-1"><span class="h4">#' . $i . '</span></div>
                    <div class="col-md-1">Display</div>
                    <div class="col-md-6"><input class="formTextArea boxsizingBorder" name="cpv' . $i . '_display" type="text" maxlength="70" size="45" placeholder="' . $modSchemaEditFields_CustomPlayer_examples[$randomExample]['display'] . '"' . (!empty($selectedSchemaIDLookup[0]['customPlayerValue' . $i . '_display']) ? ' value="' . $selectedSchemaIDLookup[0]['customPlayerValue' . $i . '_display'] . '"' : '') . '></div>
                </div>';
            echo '<div class="row">
                    <div class="col-md-1">&nbsp;</div>
                    <div class="col-md-1">Name</div>
                    <div class="col-md-6"><input class="formTextArea boxsizingBorder" name="cpv' . $i . '_name" type="text" maxlength="70" size="45" placeholder="' . $modSchemaEditFields_CustomPlayer_examples[$randomExample]['name'] . '"' . (!empty($selectedSchemaIDLookup[0]['customPlayerValue' . $i . '_name']) ? ' value="' . $selectedSchemaIDLookup[0]['customPlayerValue' . $i . '_name'] . '"' : '') . '></div>
                </div>';
            echo '<div class="row">
                    <div class="col-md-1">&nbsp;</div>
                    <div class="col-md-1">Objective</div>
                    <div class="col-md-2">
                        <input type="radio" name="cpv' . $i . '_objective" value="1"' . (($selectedSchemaIDLookup[0]['customPlayerValue' . $i . '_objective'] == 1) ? ' checked' : '') . '>Minimise<br />
                        <input type="radio" name="cpv' . $i . '_objective" value="2"' . (($selectedSchemaIDLookup[0]['customPlayerValue' . $i . '_objective'] == 2) ? ' checked' : '') . '>Maximise<br />
                        <input type="radio" name="cpv' . $i . '_objective" value="3"' . (($selectedSchemaIDLookup[0]['customPlayerValue' . $i . '_objective'] == 3 || $selectedSchemaIDLookup[0]['customPlayerValue' . $i . '_objective'] == NULL) ? ' checked' : '') . '>Info
                    </div>
                </div>';

            echo '<span class="h5">&nbsp;</span>';
        }

        echo '<div class="row">
                    <div class="col-md-8 text-center">
                        <button>Edit</button>
                    </div>
                </div>';

        echo '<span class="h5">&nbsp;</span>';

        echo '</form>';

        echo '<span id="schemaCustomAJAXResult" class="labelWarnings label label-danger"></span>';

        echo '<script type="application/javascript">
                        $("#modSchemaEdit").submit(function (event) {
                            event.preventDefault();

                            $.post("./admin/mod_schema_edit_ajax.php", $("#modSchemaEdit").serialize(), function (data) {
                                try {
                                    if(data){
                                        var response = JSON.parse(data);
                                        if(response && response.error){
                                            $("#schemaCustomAJAXResult").html(response.error);
                                        }
                                        else if(response && response.schemaID){
                                            loadPage("#admin__mod_schema_edit?id="+response.schemaID,0);
                                        }
                                        else if(response && response.result){
                                            $("#schemaCustomAJAXResult").html(response.result);
                                        }
                                        else{
                                            $("#schemaCustomAJAXResult").html(data);
                                        }
                                    }
                                }
                                catch(err) {
                                    $("#schemaCustomAJAXResult").html("Parsing Error: " + err.message + "<br />" + data);
                                }
                            }, "text");
                        });
                    </script>';

        echo '<span class="h4">&nbsp;</span>';
    }

} catch (Exception $e) {
    echo formatExceptionHandling($e);
} finally {
    if (isset($memcache)) $memcache->close();
}