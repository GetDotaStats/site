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
                    gdsu2.`user_avatar` AS submitter_user_avatar,

                    gdsu3.`user_id64` AS approver_userid64,
                    gdsu3.`user_name` AS approver_user_name,
                    gdsu3.`user_avatar` AS approver_user_avatar

                FROM `s2_mod_custom_schema` s2mcs
                INNER JOIN `mod_list` ml ON s2mcs.`modID` = ml.`mod_id`
                LEFT JOIN `gds_users` gdsu
                  ON ml.`steam_id64` = gdsu.`user_id64`
                LEFT JOIN `gds_users` gdsu2
                  ON s2mcs.`schemaSubmitterUserID64` = gdsu2.`user_id64`
                LEFT JOIN `gds_users` gdsu3
                  ON s2mcs.`schemaApproverUserID64` = gdsu3.`user_id64`
                WHERE s2mcs.`schemaID` = ?
                ORDER BY ml.`mod_name`
                LIMIT 0,1;',
            'i',
            $selectedSchemaID,
            1
        );

        if (empty($selectedSchemaIDLookup)) throw new Exception('Selected schemaID does not exist!');

        $selectedSchemaGameFields = cached_query(
            'admin_custom_schema_game_fields' . $selectedSchemaID,
            'SELECT
                    s2mcsf.`fieldOrder`,
                    s2mcsf.`customValueObjective`,
                    s2mcsf.`customValueDisplay`,
                    s2mcsf.`customValueName`
                FROM `s2_mod_custom_schema_fields` s2mcsf
                WHERE s2mcsf.`schemaID` = ? AND s2mcsf.`fieldType` = 1
                ORDER BY s2mcsf.`fieldOrder` ASC;',
            'i',
            $selectedSchemaID,
            1
        );

        $selectedSchemaPlayerFields = cached_query(
            'admin_custom_schema_player_fields' . $selectedSchemaID,
            'SELECT
                    s2mcsf.`fieldOrder`,
                    s2mcsf.`customValueObjective`,
                    s2mcsf.`customValueDisplay`,
                    s2mcsf.`customValueName`
                FROM `s2_mod_custom_schema_fields` s2mcsf
                WHERE s2mcsf.`schemaID` = ? AND s2mcsf.`fieldType` = 2
                ORDER BY s2mcsf.`fieldOrder` ASC;',
            'i',
            $selectedSchemaID,
            1
        );

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

        //Un-approved schemas for mod
        $selectedSchemaUnapprovedSchemas = cached_query(
            'admin_custom_schema_lookup_schemas_unapproved',
            'SELECT
                    s2mcs.`schemaID`,
                    s2mcs.`schemaVersion`
                FROM `s2_mod_custom_schema` s2mcs
                WHERE s2mcs.`schemaApproved` = 0 AND s2mcs.`schemaRejected` = 0 AND s2mcs.`modID` = ?;',
            'i',
            $selectedSchemaIDLookup[0]['modID'],
            1
        );

        //Rejected schemas for mod
        $selectedSchemaRejectedSchemas = cached_query(
            'admin_custom_schema_lookup_schemas_rejected',
            'SELECT
                    s2mcs.`schemaID`,
                    s2mcs.`schemaVersion`
                FROM `s2_mod_custom_schema` s2mcs
                WHERE s2mcs.`schemaRejected` = 1 AND s2mcs.`modID` = ?;',
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
            WHERE s2mcs.`schemaApproved` = 0 AND s2mcs.`schemaRejected` = 0
            ORDER BY ml.`mod_name` ASC, s2mcs.`schemaVersion` DESC;',
        NULL,
        NULL,
        1
    );

    //Lookup all of the rejected schemas
    $schemaSelectInputRejected = cached_query(
        'admin_custom_schema_unapproved_select_input_rejected',
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
            WHERE s2mcs.`schemaRejected` = 1
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
                            ' . ((empty($selectedSchemaID) || $selectedSchemaIDLookup[0]['schemaApproved'] != 1) ? '<option selected value="--">--</option>' : '<option value="--">--</option>');

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
                            ' . ((empty($selectedSchemaID) || ($selectedSchemaIDLookup[0]['schemaApproved'] == 0 && $selectedSchemaIDLookup[0]['schemaRejected'] == 0)) ? '<option selected value="--">--</option>' : '<option value="--">--</option>');

        if (!empty($schemaSelectInputUnapproved)) {
            foreach ($schemaSelectInputUnapproved as $key => $value) {
                if ($value['schemaApproved'] == 0) {
                    echo '<option' . ((!empty($selectedSchemaID) && $value['schemaID'] == $selectedSchemaID) ? ' selected' : '') . ' value="' . $value['schemaID'] . '">v' . $value['schemaVersion'] . ' -- ' . $value['mod_name'] . ' (ID #' . $value['schemaID'] . ')' . '</option>';
                }
            }
        }

        echo '          </select>
                    </div>
                </div>';
        echo '</form>';

        //Schemas Rejected
        echo '<form id="modSchemaSearchRejected">';
        echo '<div class="row">
                    <div class="col-md-3"><span class="h4">Schemas <small> - Rejected</small></span></div>
                    <div class="col-md-5">
                        <select id="modSchemaSearch_field_unapproved" class="formTextArea boxsizingBorder" onChange="jumpMenu(this)" required>
                            ' . ((empty($selectedSchemaID) || $selectedSchemaIDLookup[0]['schemaRejected'] != 1) ? '<option selected value="--">--</option>' : '<option value="--">--</option>');

        if (!empty($schemaSelectInputRejected)) {
            foreach ($schemaSelectInputRejected as $key => $value) {
                if ($value['schemaApproved'] == 0) {
                    echo '<option' . ((!empty($selectedSchemaID) && $value['schemaID'] == $selectedSchemaID) ? ' selected' : '') . ' value="' . $value['schemaID'] . '">v' . $value['schemaVersion'] . ' -- ' . $value['mod_name'] . ' (ID #' . $value['schemaID'] . ')' . '</option>';
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

    if (empty($selectedSchemaID) || empty($selectedSchemaIDLookup)) {
        throw new Exception('No selected schema!');
    }

    $modThumb = is_file('../images/mods/thumbs/' . $selectedSchemaIDLookup[0]['mod_id'] . '.png')
        ? $CDN_image . '/images/mods/thumbs/' . $selectedSchemaIDLookup[0]['mod_id'] . '.png'
        : $CDN_image . '/images/misc/steam/blank_avatar.jpg';
    $modThumb = '<img width="25" height="25" src="' . $modThumb . '" />';
    $modNameLink = '<a target="_blank" href="http://steamcommunity.com/sharedfiles/filedetails/?id=' . $selectedSchemaIDLookup[0]['mod_workshop_link'] . '">' . $modThumb . '</a> <a class="nav-clickable" href="#s2__mod?id=' . $selectedSchemaIDLookup[0]['mod_id'] . '">' . $selectedSchemaIDLookup[0]['mod_name'] . '</a>';

    $ownerAvatar = !empty($selectedSchemaIDLookup[0]['owner_user_avatar'])
        ? $selectedSchemaIDLookup[0]['owner_user_avatar']
        : $CDN_image . '/images/misc/steam/blank_avatar.jpg';
    $ownerAvatar = '<img width="20" height="20" src="' . $ownerAvatar . '" />';
    $ownerLink = '<a target="_blank" href="http://steamcommunity.com/profiles/' . $selectedSchemaIDLookup[0]['owner_userid64'] . '">' . $ownerAvatar . '</a> <a class="nav-clickable" href="#s2__user?id=' . $selectedSchemaIDLookup[0]['owner_userid64'] . '">' . $selectedSchemaIDLookup[0]['owner_user_name'] . '</a>';

    $submitterAvatar = !empty($selectedSchemaIDLookup[0]['submitter_user_avatar'])
        ? $selectedSchemaIDLookup[0]['submitter_user_avatar']
        : $CDN_image . '/images/misc/steam/blank_avatar.jpg';
    $submitterAvatar = '<img width="20" height="20" src="' . $submitterAvatar . '" />';
    $submitterLink = '<a target="_blank" href="http://steamcommunity.com/profiles/' . $selectedSchemaIDLookup[0]['submitter_userid64'] . '">' . $submitterAvatar . '</a> <a class="nav-clickable" href="#s2__user?id=' . $selectedSchemaIDLookup[0]['submitter_userid64'] . '">' . $selectedSchemaIDLookup[0]['submitter_user_name'] . '</a>';

    if (!empty($selectedSchemaIDLookup[0]['approver_userid64'])) {
        $approverAvatar = !empty($selectedSchemaIDLookup[0]['approver_user_avatar'])
            ? $selectedSchemaIDLookup[0]['approver_user_avatar']
            : $CDN_image . '/images/misc/steam/blank_avatar.jpg';
        $approverAvatar = '<img width="20" height="20" src="' . $approverAvatar . '" />';
        $approverLink = '<a target="_blank" href="http://steamcommunity.com/profiles/' . $selectedSchemaIDLookup[0]['approver_userid64'] . '">' . $approverAvatar . '</a> <a class="nav-clickable" href="#s2__user?id=' . $selectedSchemaIDLookup[0]['approver_userid64'] . '">' . $selectedSchemaIDLookup[0]['approver_user_name'] . '</a>';
    }

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

    //Approver
    if (!empty($selectedSchemaIDLookup[0]['approver_userid64'])) {
        echo '<div class="row">
                    <div class="col-md-2"><span class="h4">Approver</span></div>
                    <div class="col-md-5">' . $approverLink . '</div>
                </div>';

        echo '<span class="h5">&nbsp;</span>';
    }

    //Approved Schemas
    if (!empty($selectedSchemaApprovedSchemas)) {
        echo '<div class="row">
                    <div class="col-md-2"><span class="h4">Schemas</span></div>
                    <div class="col-md-7"><strong>Approved:</strong> ';

        $i = 0;
        foreach ($selectedSchemaApprovedSchemas as $key => $value) {
            $i++;
            if ($i == 1) {
                echo '<a class="nav-clickable" href="#admin__mod_schema_edit?id=' . $value['schemaID'] . '">' . ($selectedSchemaID == $value['schemaID'] ? '<strong>v' . $value['schemaVersion'] . '</strong>' : 'v' . $value['schemaVersion']) . '</a>';
            } else {
                echo ', <a class="nav-clickable" href="#admin__mod_schema_edit?id=' . $value['schemaID'] . '">' . ($selectedSchemaID == $value['schemaID'] ? '<strong>v' . $value['schemaVersion'] . '</strong>' : 'v' . $value['schemaVersion']) . '</a>';
            }
        }

        echo '      </div>
                </div>';
    }

    //Un-approved schemas
    if (!empty($selectedSchemaUnapprovedSchemas)) {
        echo '<div class="row">
                    <div class="col-md-2">&nbsp;</div>
                    <div class="col-md-7"><strong>Un-approved:</strong> ';

        $i = 0;
        foreach ($selectedSchemaUnapprovedSchemas as $key => $value) {
            $i++;
            if ($i == 1) {
                echo '<a class="nav-clickable" href="#admin__mod_schema_edit?id=' . $value['schemaID'] . '">' . ($selectedSchemaID == $value['schemaID'] ? '<strong>v' . $value['schemaVersion'] . '</strong>' : 'v' . $value['schemaVersion']) . '</a>';
            } else {
                echo ', <a class="nav-clickable" href="#admin__mod_schema_edit?id=' . $value['schemaID'] . '">' . ($selectedSchemaID == $value['schemaID'] ? '<strong>v' . $value['schemaVersion'] . '</strong>' : 'v' . $value['schemaVersion']) . '</a>';
            }
        }

        echo '      </div>
                </div>';
    }

    //Rejected schemas
    if (!empty($selectedSchemaRejectedSchemas)) {
        echo '<div class="row">
                    <div class="col-md-2">&nbsp;</div>
                    <div class="col-md-7"><strong>Rejected:</strong> ';

        $i = 0;
        foreach ($selectedSchemaRejectedSchemas as $key => $value) {
            $i++;
            if ($i == 1) {
                echo '<a class="nav-clickable" href="#admin__mod_schema_edit?id=' . $value['schemaID'] . '">' . ($selectedSchemaID == $value['schemaID'] ? '<strong>v' . $value['schemaVersion'] . '</strong>' : 'v' . $value['schemaVersion']) . '</a>';
            } else {
                echo ', <a class="nav-clickable" href="#admin__mod_schema_edit?id=' . $value['schemaID'] . '">' . ($selectedSchemaID == $value['schemaID'] ? '<strong>v' . $value['schemaVersion'] . '</strong>' : 'v' . $value['schemaVersion']) . '</a>';
            }
        }

        echo '      </div>
                </div>';
    }

    if (!empty($selectedSchemaApprovedSchemas) || !empty($selectedSchemaUnapprovedSchemas)) {
        echo '<span class="h5">&nbsp;</span>';

        echo '<div class="row">
                    <div class="col-md-2">&nbsp;</div>
                    <div class="col-md-7 text-center">
                        <div class="row">
                            <div class="col-md-2">&nbsp;</div>
                            <div class="col-md-4 text-center">
                                <form id="modSchemaDeactivate">
                                    <input name="schema_id" type="hidden" value="' . $selectedSchemaID . '">
                                    <input name="schema_mod_id" type="hidden" value="' . $selectedSchemaIDLookup[0]['modID'] . '">
                                    <button>De-activate</button> <span class="glyphicon glyphicon-question-sign" title="Do you want to un-approve all but the current version of the schema for this mod?"></span>
                                </form>
                            </div>
                            <div class="col-md-4">
                                    <form id="modSchemaReject">
                                        <input name="schema_id" type="hidden" value="' . $selectedSchemaID . '">
                                        <input name="schema_mod_id" type="hidden" value="' . $selectedSchemaIDLookup[0]['modID'] . '">
                                        <input name="schema_reason_rejected" type="hidden" value="Admin has cleaned up the unapproved">
                                        <button>Reject</button> <span class="glyphicon glyphicon-question-sign" title="Do you want to reject all but the current version of the schema for this mod?"></span>
                                    </form>
                            </div>
                        </div>
                    </div>
                </div>';

        echo '<span id="schemaCustomDeactivateAJAX_space" class="h5 hidden">&nbsp;</span>';

        echo '<div id="schemaCustomDeactivateAJAX_container" class="row hidden">
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
                                            $("#schemaCustomDeactivateAJAX_space").removeClass("hidden");
                                            $("#schemaCustomDeactivateAJAX_container").removeClass("hidden");

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

                        $("#modSchemaReject").submit(function (event) {
                            event.preventDefault();

                            $.post("./admin/mod_schema_edit_reject_ajax.php", $("#modSchemaReject").serialize(), function (data) {
                                try {
                                    if(data){
                                        var response = JSON.parse(data);
                                        if(response && response.error){
                                            $("#schemaCustomDeactivateAJAX_space").removeClass("hidden");
                                            $("#schemaCustomDeactivateAJAX_container").removeClass("hidden");

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
    }

    echo '<hr />';

    ///////////////////////////////////////////////////
    // Form for approving and rejecting the schema
    ///////////////////////////////////////////////////

    echo '<form id="modSchemaApproveReject">';
    echo '<input name="schema_id" type="hidden" value="' . $selectedSchemaIDLookup[0]['schemaID'] . '">';

    //Approved & Rejected Radios
    echo '<div class="row">
                    <div class="col-md-2"><span class="h4">Approved</span></div>
                    <div class="col-md-3' . ($selectedSchemaIDLookup[0]['schemaApproved'] == 1 ? ' formBackgroundGreen' : ($selectedSchemaIDLookup[0]['schemaRejected'] != 1 ? ' formBackgroundYellow' : '')) . '">
                        <input type="radio" name="schema_approved" value="1"' . (($selectedSchemaIDLookup[0]['schemaApproved'] == 1) ? ' checked' : '') . '>Approved<br />
                        <input type="radio" name="schema_approved" value="0"' . (($selectedSchemaIDLookup[0]['schemaApproved'] == 0) ? ' checked' : '') . '>Not Approved
                    </div>

                    <div class="col-md-2"><span class="h4">Rejected</span></div>
                    <div class="col-md-3' . ($selectedSchemaIDLookup[0]['schemaRejected'] == 1 ? ' formBackgroundRed' : '') . '">
                        <input type="radio" name="schema_rejected" value="1"' . (($selectedSchemaIDLookup[0]['schemaRejected'] == 1) ? ' checked' : '') . '>Rejected<br />
                        <input type="radio" name="schema_rejected" value="0"' . (($selectedSchemaIDLookup[0]['schemaRejected'] == 0) ? ' checked' : '') . '>Not Rejected
                    </div>
                </div>';

    echo '<span class="h5">&nbsp;</span>';

    echo '<div class="row">
                    <div class="col-md-2"><span class="h4">Rejected Reason</span></div>
                    <div class="col-md-7"><textarea class="formTextArea boxsizingBorder" name="schema_rejected_reason" rows="3" placeholder="Reason for rejecting the schema">' . ((!empty($selectedSchemaIDLookup[0]['schemaRejectedReason'])) ? $selectedSchemaIDLookup[0]['schemaRejectedReason'] : '') . '</textarea></div>
                </div>';

    echo '<span class="h5">&nbsp;</span>';

    echo '<div class="row">
                    <div class="col-md-2">&nbsp;</div>
                    <div class="col-md-7 text-center">
                        <button>Approve / Reject</button> <span class="glyphicon glyphicon-question-sign" title="Do you want to approve / reject the current version of the schema for this mod?"></span>
                    </div>
                </div>';

    echo '</form>';

    echo '<span id="schemaCustomApproveRejectAJAX_space" class="h5 hidden">&nbsp;</span>';

    echo '<div id="schemaCustomApproveRejectAJAX_container" class="row hidden">
                        <div class="col-md-2">&nbsp;</div>
                        <div class="col-md-7"><span id="schemaCustomApproveRejectAJAXResult" class="labelWarnings label label-danger"></span></div>
                    </div>';

    echo '<script type="application/javascript">
                        $("#modSchemaApproveReject").submit(function (event) {
                            event.preventDefault();

                            $.post("./admin/mod_schema_edit_approve_reject_ajax.php", $("#modSchemaApproveReject").serialize(), function (data) {
                                try {
                                    if(data){
                                        var response = JSON.parse(data);
                                        if(response && response.error){
                                            $("#schemaCustomApproveRejectAJAX_space").removeClass("hidden");
                                            $("#schemaCustomApproveRejectAJAX_container").removeClass("hidden");

                                            $("#schemaCustomApproveRejectAJAXResult").html(response.error);
                                        }
                                        else if(response && response.result){
                                            loadPage("#admin__mod_schema_edit?id=' . $selectedSchemaID . '",1);
                                        }
                                        else{
                                            $("#schemaCustomApproveRejectAJAXResult").html(data);
                                        }
                                    }
                                }
                                catch(err) {
                                    $("#schemaCustomApproveRejectAJAXResult").html("Parsing Error: " + err.message + "<br />" + data);
                                }
                            }, "text");
                        });
        </script>';


    echo '<hr />';

    ///////////////////////////////////////////////////
    // Form for editing the schema
    ///////////////////////////////////////////////////


    echo '<div id="custom_game_master" style="display: none">';
    {
        echo '<div class="row">
                    <div class="col-md-1"><span id="customValueIdentifierName" class="h4">#</span></div>
                    <div class="col-md-1">Display</div>
                    <div class="col-md-6"><input class="formTextArea boxsizingBorder" name="cgv_display" type="text" maxlength="70" size="45"></div>
                </div>';
        echo '<div class="row">
                    <div class="col-md-1">&nbsp;</div>
                    <div class="col-md-1">Name</div>
                    <div class="col-md-6"><input class="formTextArea boxsizingBorder" name="cgv_name" type="text" maxlength="70" size="45"></div>
                </div>';
        echo '<div class="row">
                    <div class="col-md-1">&nbsp;</div>
                    <div class="col-md-1">Objective</div>
                    <div class="col-md-2">
                        <input type="radio" name="cgv_objective" value="1">Minimise<br />
                        <input type="radio" name="cgv_objective" value="2">Maximise<br />
                        <input type="radio" name="cgv_objective" value="3" checked>Info
                    </div>
                </div>';
    }
    echo '</div>';


    echo '<div id="custom_player_master" style="display: none">';
    {
        echo '<div class="row">
                    <div class="col-md-1"><span id="customValueIdentifierName" class="h4">#</span></div>
                    <div class="col-md-1">Display</div>
                    <div class="col-md-6"><input class="formTextArea boxsizingBorder" name="cpv_display" type="text" maxlength="70" size="45"></div>
                </div>';
        echo '<div class="row">
                    <div class="col-md-1">&nbsp;</div>
                    <div class="col-md-1">Name</div>
                    <div class="col-md-6"><input class="formTextArea boxsizingBorder" name="cpv_name" type="text" maxlength="70" size="45"></div>
                </div>';
        echo '<div class="row">
                    <div class="col-md-1">&nbsp;</div>
                    <div class="col-md-1">Objective</div>
                    <div class="col-md-2">
                        <input type="radio" name="cpv_objective" value="1">Minimise<br />
                        <input type="radio" name="cpv_objective" value="2">Maximise<br />
                        <input type="radio" name="cpv_objective" value="3" checked>Info
                    </div>
                </div>';
    }
    echo '</div>';


    echo '<form id="modSchemaEdit">';
    echo '<input name="schema_id" type="hidden" value="' . $selectedSchemaIDLookup[0]['schemaID'] . '">';

    /////////////////////////
    // Custom Game Values
    /////////////////////////

    echo '<div class="row">
                    <div class="col-md-4"><span class="h4">Custom Game Values</span> <span class="glyphicon glyphicon-question-sign" title="This is where all of your schema fields that relate to the entire game are defined"></span></div>
                </div>';

    echo '<span class="h5">&nbsp;</span>';

    $modSchemaEditGameFields = !empty($selectedSchemaGameFields) && count($selectedSchemaGameFields)
        ? count($selectedSchemaGameFields)
        : 1;
    for ($i = 1; $i <= $modSchemaEditGameFields; $i++) {
        if (!empty($selectedSchemaGameFields[$i - 1])) {
            $modCustomDisplay = $selectedSchemaGameFields[$i - 1]['customValueDisplay'];
            $modCustomName = $selectedSchemaGameFields[$i - 1]['customValueName'];
            $modCustomObjective = $selectedSchemaGameFields[$i - 1]['customValueObjective'];

            $modCustomDisplay_value = ' value="' . $modCustomDisplay . '"';
            $modCustomDisplay_class = ' formBackgroundGreen';
            $modCustomName_value = ' value="' . $modCustomName . '"';
            $modCustomName_class = ' formBackgroundGreen';
        } else {
            $modCustomDisplay_value = '';
            $modCustomDisplay_class = '';
            $modCustomName_value = '';
            $modCustomName_class = '';
            $modCustomObjective = NULL;
        }

        echo '<div class="row">
                    <div class="col-md-1"><span class="h4">#' . $i . '</span></div>
                    <div class="col-md-1">Display</div>
                    <div class="col-md-6"><input class="formTextArea boxsizingBorder' . $modCustomDisplay_class . '" name="cgv_display' . $i . '" type="text" maxlength="70" size="45"' . $modCustomDisplay_value . '></div>
                </div>';
        echo '<div class="row">
                    <div class="col-md-1">&nbsp;</div>
                    <div class="col-md-1">Name</div>
                    <div class="col-md-6"><input class="formTextArea boxsizingBorder' . $modCustomName_class . '" name="cgv_name' . $i . '" type="text" maxlength="70" size="45"' . $modCustomName_value . '></div>
                </div>';
        echo '<div class="row">
                    <div class="col-md-1">&nbsp;</div>
                    <div class="col-md-1">Objective</div>
                    <div class="col-md-2">
                        <input type="radio" name="cgv_objective' . $i . '" value="1"' . ((!empty($modCustomObjective) && $modCustomObjective == 1) ? ' checked' : '') . '>Minimise<br />
                        <input type="radio" name="cgv_objective' . $i . '" value="2"' . ((!empty($modCustomObjective) && $modCustomObjective == 2) ? ' checked' : '') . '>Maximise<br />
                        <input type="radio" name="cgv_objective' . $i . '" value="3"' . ((empty($modCustomObjective) || $modCustomObjective == 3) ? ' checked' : '') . '>Info
                    </div>
                </div>';

        echo '<span class="h5">&nbsp;</span>';
    }

    echo '<span id="customGameValuesPlaceholder"></span>';

    echo '<div class="row">
                <div class="col-md-8 text-center">
                    <button id="moreGameFields" class="btn btn-warning">moreFields</button>
                </div>
            </div>';

    echo '<span class="h5">&nbsp;</span>';


    /////////////////////////
    // Custom Player Values
    /////////////////////////

    echo '<div class="row">
                    <div class="col-md-4"><span class="h4">Custom Player Values</span> <span class="glyphicon glyphicon-question-sign" title="This is where all of your schema fields that relate to individual players are defined"></span></div>
                </div>';

    echo '<span class="h5">&nbsp;</span>';

    $modSchemaEditPlayerFields = !empty($selectedSchemaPlayerFields) && count($selectedSchemaPlayerFields)
        ? count($selectedSchemaPlayerFields)
        : 1;
    for ($i = 1; $i <= $modSchemaEditPlayerFields; $i++) {
        if (!empty($selectedSchemaPlayerFields[$i - 1])) {
            $modCustomDisplay = $selectedSchemaPlayerFields[$i - 1]['customValueDisplay'];
            $modCustomName = $selectedSchemaPlayerFields[$i - 1]['customValueName'];
            $modCustomObjective = $selectedSchemaPlayerFields[$i - 1]['customValueObjective'];

            $modCustomDisplay_value = ' value="' . $modCustomDisplay . '"';
            $modCustomDisplay_class = ' formBackgroundGreen';
            $modCustomName_value = ' value="' . $modCustomName . '"';
            $modCustomName_class = ' formBackgroundGreen';
        } else {
            $modCustomDisplay_value = '';
            $modCustomDisplay_class = '';
            $modCustomName_value = '';
            $modCustomName_class = '';
            $modCustomObjective = NULL;
        }

        echo '<div class="row">
                    <div class="col-md-1"><span class="h4">#' . $i . '</span></div>
                    <div class="col-md-1">Display</div>
                    <div class="col-md-6"><input class="formTextArea boxsizingBorder' . $modCustomDisplay_class . '" name="cpv_display' . $i . '" type="text" maxlength="70" size="45"' . $modCustomDisplay_value . '></div>
                </div>';
        echo '<div class="row">
                    <div class="col-md-1">&nbsp;</div>
                    <div class="col-md-1">Name</div>
                    <div class="col-md-6"><input class="formTextArea boxsizingBorder' . $modCustomName_class . '" name="cpv_name' . $i . '" type="text" maxlength="70" size="45"' . $modCustomName_value . '></div>
                </div>';
        echo '<div class="row">
                    <div class="col-md-1">&nbsp;</div>
                    <div class="col-md-1">Objective</div>
                    <div class="col-md-2">
                        <input type="radio" name="cpv_objective' . $i . '" value="1"' . ((!empty($modCustomObjective) && $modCustomObjective == 1) ? ' checked' : '') . '>Minimise<br />
                        <input type="radio" name="cpv_objective' . $i . '" value="2"' . ((!empty($modCustomObjective) && $modCustomObjective == 2) ? ' checked' : '') . '>Maximise<br />
                        <input type="radio" name="cpv_objective' . $i . '" value="3"' . ((empty($modCustomObjective) || $modCustomObjective == 3) ? ' checked' : '') . '>Info
                    </div>
                </div>';

        echo '<span class="h5">&nbsp;</span>';
    }

    echo '<span id="customPlayerValuesPlaceholder"></span>';

    echo '<div class="row">
                <div class="col-md-8 text-center">
                    <button id="morePlayerFields" class="btn btn-warning">moreFields</button>
                </div>
            </div>';

    echo '<span class="h5">&nbsp;</span>';


    echo '<div class="row">
                    <div class="col-md-8 text-center">
                        <button id="sub" class="btn btn-success">Edit</button>
                    </div>
                </div>';

    echo '<span class="h5">&nbsp;</span>';

    echo '</form>';

    echo '<span id="schemaCustomAJAXResult" class="labelWarnings label label-danger"></span>';

    echo '<script type="application/javascript">
            var counterGame = ' . $modSchemaEditGameFields . ';
            var counterPlayer = ' . $modSchemaEditPlayerFields . ';

            $("#moreGameFields").click(event, function(){
                event.preventDefault();
                addMoreFields("game");
            });

            $("#morePlayerFields").click(event, function(){
                event.preventDefault();
                addMoreFields("player");
            });

            function addMoreFields(type){
                var tempClone = null,
                    counter = null;

                if(type == "game"){
                    counterGame++;
                    counter = counterGame;
                    tempClone = $("#custom_game_master").clone();
                } else if(type == "player"){
                    counterPlayer++;
                    counter = counterPlayer;
                    tempClone = $("#custom_player_master").clone();
                }

                tempClone.attr("id", function(i,val){ return val + counter; }).removeAttr("style");

                $("input", tempClone).each(function () {
                    $(this).attr("name", function(i,val){ return val + counter; });
                });

                $("#customValueIdentifierName", tempClone).each(function(){
                    $(this).html("#" + counter);
                });

                if(type == "game"){
                    tempClone.appendTo("#customGameValuesPlaceholder");
                } else if(type == "player"){
                    tempClone.appendTo("#customPlayerValuesPlaceholder");
                }
            }


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


} catch (Exception $e) {
    echo formatExceptionHandling($e);
} finally {
    if (isset($memcache)) $memcache->close();
}