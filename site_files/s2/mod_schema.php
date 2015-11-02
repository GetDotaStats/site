<?php
require_once('../connections/parameters.php');
require_once('../global_functions.php');
require_once('./functions.php');

try {
    if (!isset($_SESSION)) {
        session_start();
    }

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    try {
        $exception = '';
        if (isset($_GET['mid'])) {
            $selectedModID = $_GET['mid'];
            if (empty($selectedModID) || !is_numeric($selectedModID)) throw new Exception('Selected modID is invalid type!');

            $modDetails = cached_query(
                's2_schema_modid_lookup' . $selectedModID,
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
                      ml.`date_recorded`
                    FROM `mod_list` ml
                    WHERE ml.`mod_id` = ?
                    LIMIT 0,1;',
                'i',
                $selectedModID,
                15
            );

            if (empty($modDetails)) {
                throw new Exception('Invalid modID! Not recorded in database.');
            }

            $modID = $modDetails[0]['mod_id'];

            $selectedSchemaIDFromModID = cached_query(
                's2_custom_schema_lookup_from_modid' . $modID,
                'SELECT
                        MAX(`schemaID`) AS `schemaID`
                    FROM `s2_mod_custom_schema`
                    WHERE `modID` = ?
                    LIMIT 0,1;',
                'i',
                $modID,
                1
            );

            if (!empty($selectedSchemaIDFromModID)) $_GET['id'] = $selectedSchemaIDFromModID[0]['schemaID'];
        }

        if (isset($_GET['id'])) {
            $selectedSchemaID = $_GET['id'];
            if (!is_numeric($selectedSchemaID)) {
                $selectedSchemaID = NULL;
                throw new Exception('Selected schemaID is invalid!');
            }

            $selectedSchemaIDLookup = cached_query(
                's2_custom_schema_lookup' . $selectedSchemaID,
                'SELECT
                        s2mcs.*,

                        ml.`mod_id`,
                        ml.`mod_name`,
                        ml.`steam_id64`,
                        ml.`mod_workshop_link`
                    FROM `s2_mod_custom_schema` s2mcs
                    INNER JOIN `mod_list` ml ON s2mcs.`modID` = ml.`mod_id`
                    WHERE s2mcs.`schemaID` = ?
                    LIMIT 0,1;',
                'i',
                $selectedSchemaID,
                1
            );

            if (empty($selectedSchemaIDLookup)) {
                $selectedSchemaID = NULL;
                throw new Exception('Selected schemaID does not exist!');
            }

            $modID = $selectedSchemaIDLookup[0]['mod_id'];

            $selectedSchemaGameFields = cached_query(
                'admin_custom_schema_game_fields' . $selectedSchemaID,
                'SELECT
                        s2mcsf.`fieldOrder`,
                        s2mcsf.`customValueObjective`,
                        s2mcsf.`isGroupable`,
                        s2mcsf.`noGraph`,
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
                        s2mcsf.`isGroupable`,
                        s2mcsf.`noGraph`,
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
    } catch (Exception $e) {
        if ($e == 'Invalid modID! Not recorded in database.') {
            throw new Exception($e);
        }
        $exception = formatExceptionHandling($e);
    }

    $SQL_ExtraStatement = '';
    $SQL_Declaration = '';
    $SQL_Values = array();
    if(!empty($modID)){
        $SQL_ExtraStatement .= ' AND ml.`mod_id` = ?';
        $SQL_Declaration .= 'i';
        $SQL_Values[] = $modID;
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
            WHERE s2mcs.`schemaApproved` = 1'.$SQL_ExtraStatement.'
            ORDER BY ml.`mod_name` ASC, s2mcs.`schemaVersion` DESC;',
        $SQL_Declaration,
        $SQL_Values,
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
            WHERE s2mcs.`schemaApproved` = 0 AND s2mcs.`schemaRejected` = 0'.$SQL_ExtraStatement.'
            ORDER BY ml.`mod_name` ASC, s2mcs.`schemaVersion` DESC;',
        $SQL_Declaration,
        $SQL_Values,
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
            WHERE s2mcs.`schemaRejected` = 1'.$SQL_ExtraStatement.'
            ORDER BY ml.`mod_name` ASC, s2mcs.`schemaVersion` DESC;',
        $SQL_Declaration,
        $SQL_Values,
        1
    );

    if (!empty($modID)) echo modPageHeader($modID, $CDN_image);

    echo '<h2>View Schema</h2>';
    echo '<p>This shows the schema for a custom game.</p>';

    echo $exception;

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
                    loadPage("#s2__mod_schema?id=" + op.value, 1);
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

    echo '<div class="row">
                    <div class="col-md-2">&nbsp;</div>
                    <div class="col-md-1 text-center"><span class="h4">Ver.</span></div>
                    <div class="col-md-4"><span class="h4">Mod</span></div>
                </div>';

    echo '<span class="h5">&nbsp;</span>';

    echo '<div class="row">
                    <div class="col-md-2"><span class="h4">Schema</span></div>
                    <div class="col-md-1 text-center">v' . $selectedSchemaIDLookup[0]['schemaVersion'] . '</div>
                    <div class="col-md-4">' . $modNameLink . '</div>
                </div>';

    echo '<span class="h5">&nbsp;</span>';

    //Approved Schemas
    if (!empty($selectedSchemaApprovedSchemas)) {
        echo '<div class="row">
                    <div class="col-md-2"><span class="h4">Schemas</span></div>
                    <div class="col-md-7"><strong>Approved:</strong> ';

        $i = 0;
        foreach ($selectedSchemaApprovedSchemas as $key => $value) {
            $i++;
            if ($i == 1) {
                echo '<a class="nav-clickable" href="#s2__mod_schema?id=' . $value['schemaID'] . '">' . ($selectedSchemaID == $value['schemaID'] ? '<strong>v' . $value['schemaVersion'] . '</strong>' : 'v' . $value['schemaVersion']) . '</a>';
            } else {
                echo ', <a class="nav-clickable" href="#s2__mod_schema?id=' . $value['schemaID'] . '">' . ($selectedSchemaID == $value['schemaID'] ? '<strong>v' . $value['schemaVersion'] . '</strong>' : 'v' . $value['schemaVersion']) . '</a>';
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
                echo '<a class="nav-clickable" href="#s2__mod_schema?id=' . $value['schemaID'] . '">' . ($selectedSchemaID == $value['schemaID'] ? '<strong>v' . $value['schemaVersion'] . '</strong>' : 'v' . $value['schemaVersion']) . '</a>';
            } else {
                echo ', <a class="nav-clickable" href="#s2__mod_schema?id=' . $value['schemaID'] . '">' . ($selectedSchemaID == $value['schemaID'] ? '<strong>v' . $value['schemaVersion'] . '</strong>' : 'v' . $value['schemaVersion']) . '</a>';
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
                echo '<a class="nav-clickable" href="#s2__mod_schema?id=' . $value['schemaID'] . '">' . ($selectedSchemaID == $value['schemaID'] ? '<strong>v' . $value['schemaVersion'] . '</strong>' : 'v' . $value['schemaVersion']) . '</a>';
            } else {
                echo ', <a class="nav-clickable" href="#s2__mod_schema?id=' . $value['schemaID'] . '">' . ($selectedSchemaID == $value['schemaID'] ? '<strong>v' . $value['schemaVersion'] . '</strong>' : 'v' . $value['schemaVersion']) . '</a>';
            }
        }

        echo '      </div>
                </div>';
    }

    echo '<hr />';

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
            $isGroupable = $selectedSchemaGameFields[$i - 1]['isGroupable'];
            $isGraph = $selectedSchemaGameFields[$i - 1]['noGraph'];

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
            $isGroupable = NULL;
            $isGraph = NULL;
        }

        echo '<div class="row">
                    <div class="col-md-1"><span class="h4">#' . $i . '</span></div>
                    <div class="col-md-1">Display</div>
                    <div class="col-md-6"><input class="formTextArea boxsizingBorder' . $modCustomDisplay_class . '" name="cgv_display' . $i . '" type="text" maxlength="70" size="45"' . $modCustomDisplay_value . ' disabled></div>
                </div>';
        echo '<div class="row">
                    <div class="col-md-1">&nbsp;</div>
                    <div class="col-md-1">Name</div>
                    <div class="col-md-6"><input class="formTextArea boxsizingBorder' . $modCustomName_class . '" name="cgv_name' . $i . '" type="text" maxlength="70" size="45"' . $modCustomName_value . ' disabled></div>
                </div>';
        echo '<div class="row">
                    <div class="col-md-1">&nbsp;</div>
                    <div class="col-md-1">Objective</div>
                    <div class="col-md-2">
                        <input type="radio" name="cgv_objective' . $i . '" value="1"' . ((!empty($modCustomObjective) && $modCustomObjective == 1) ? ' checked' : '') . ' disabled>Minimise<br />
                        <input type="radio" name="cgv_objective' . $i . '" value="2"' . ((!empty($modCustomObjective) && $modCustomObjective == 2) ? ' checked' : '') . ' disabled>Maximise<br />
                        <input type="radio" name="cgv_objective' . $i . '" value="3"' . ((empty($modCustomObjective) || $modCustomObjective == 3) ? ' checked' : '') . ' disabled>Info
                    </div>

                    <div class="col-md-1">Groupable<br /><span class="glyphicon glyphicon-question-sign" title="Select `yes` if the data is numeric (not decimal) and will contain many unique values (e.g. more than 50)"></span></div>
                    <div class="col-md-2">
                        <input type="radio" name="cgv_isgroupable' . $i . '" value="1"' . ((!empty($isGroupable) && $isGroupable == 1) ? ' checked' : '') . ' disabled>Yes<br />
                        <input type="radio" name="cgv_isgroupable' . $i . '" value="0"' . (empty($isGroupable) ? ' checked' : '') . ' disabled>No
                    </div>

                    <div class="col-md-1">Graph<br /><span class="glyphicon glyphicon-question-sign" title="Select `no` if there is going to be too many unique values"></span></div>
                    <div class="col-md-2">
                        <input type="radio" name="cgv_graph' . $i . '" value="0"' . (empty($isGraph) ? ' checked' : '') . ' disabled>Yes<br />
                        <input type="radio" name="cgv_graph' . $i . '" value="1"' . ((!empty($isGraph) && $isGraph == 1) ? ' checked' : '') . ' disabled>No
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

    //SteamID32 default
    {
        echo '<div class="row">
                    <div class="col-md-1"><span class="h4">#0</span></div>
                    <div class="col-md-1">Display</div>
                    <div class="col-md-6"><input class="formTextArea boxsizingBorder formBackgroundGreen" type="text" value="User ID" disabled></div>
                </div>
                <div class="row">
                    <div class="col-md-1">&nbsp;</div>
                    <div class="col-md-1">Name</div>
                    <div class="col-md-6"><input class="formTextArea boxsizingBorder formBackgroundGreen" type="text" value="steamID32" disabled></div>
                </div>
                <div class="row">
                    <div class="col-md-1">&nbsp;</div>
                    <div class="col-md-1">Objective</div>
                    <div class="col-md-2">
                        <input type="radio" value="1" disabled>Minimise<br />
                        <input type="radio" value="2" disabled>Maximise<br />
                        <input type="radio" value="3" checked disabled>Info
                    </div>

                    <div class="col-md-1">Groupable</div>
                    <div class="col-md-2">
                        <input type="radio" value="1" disabled>Yes<br />
                        <input type="radio" value="0" checked disabled>No
                    </div>

                    <div class="col-md-1">Graph</div>
                    <div class="col-md-2">
                        <input type="radio" value="1" disabled>Yes<br />
                        <input type="radio" value="0" checked disabled>No
                    </div>
                </div>';

        echo '<span class="h5">&nbsp;</span>';
    }

    $modSchemaEditPlayerFields = !empty($selectedSchemaPlayerFields) && count($selectedSchemaPlayerFields)
        ? count($selectedSchemaPlayerFields)
        : 1;
    for ($i = 1; $i <= $modSchemaEditPlayerFields; $i++) {
        if (!empty($selectedSchemaPlayerFields[$i - 1])) {
            $modCustomDisplay = $selectedSchemaPlayerFields[$i - 1]['customValueDisplay'];
            $modCustomName = $selectedSchemaPlayerFields[$i - 1]['customValueName'];
            $modCustomObjective = $selectedSchemaPlayerFields[$i - 1]['customValueObjective'];
            $isGroupable = $selectedSchemaPlayerFields[$i - 1]['isGroupable'];
            $isGraph = $selectedSchemaPlayerFields[$i - 1]['noGraph'];

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
            $isGroupable = NULL;
            $isGraph = NULL;
        }

        echo '<div class="row">
                    <div class="col-md-1"><span class="h4">#' . $i . '</span></div>
                    <div class="col-md-1">Display</div>
                    <div class="col-md-6"><input class="formTextArea boxsizingBorder' . $modCustomDisplay_class . '" name="cpv_display' . $i . '" type="text" maxlength="70" size="45"' . $modCustomDisplay_value . ' disabled></div>
                </div>';
        echo '<div class="row">
                    <div class="col-md-1">&nbsp;</div>
                    <div class="col-md-1">Name</div>
                    <div class="col-md-6"><input class="formTextArea boxsizingBorder' . $modCustomName_class . '" name="cpv_name' . $i . '" type="text" maxlength="70" size="45"' . $modCustomName_value . ' disabled></div>
                </div>';
        echo '<div class="row">
                    <div class="col-md-1">&nbsp;</div>
                    <div class="col-md-1">Objective</div>
                    <div class="col-md-2">
                        <input type="radio" name="cpv_objective' . $i . '" value="1"' . ((!empty($modCustomObjective) && $modCustomObjective == 1) ? ' checked' : '') . ' disabled>Minimise<br />
                        <input type="radio" name="cpv_objective' . $i . '" value="2"' . ((!empty($modCustomObjective) && $modCustomObjective == 2) ? ' checked' : '') . ' disabled>Maximise<br />
                        <input type="radio" name="cpv_objective' . $i . '" value="3"' . ((empty($modCustomObjective) || $modCustomObjective == 3) ? ' checked' : '') . ' disabled>Info
                    </div>

                    <div class="col-md-1">Groupable<br /><span class="glyphicon glyphicon-question-sign" title="Select `yes` if the data is numeric (not decimal) and will contain many unique values (e.g. more than 50)"></span></div>
                    <div class="col-md-2">
                        <input type="radio" name="cpv_isgroupable' . $i . '" value="1"' . ((!empty($isGroupable) && $isGroupable == 1) ? ' checked' : '') . ' disabled>Yes<br />
                        <input type="radio" name="cpv_isgroupable' . $i . '" value="0"' . (empty($isGroupable) ? ' checked' : '') . ' disabled>No
                    </div>

                    <div class="col-md-1">Graph<br /><span class="glyphicon glyphicon-question-sign" title="Select `no` if there is going to be too many unique values"></span></div>
                    <div class="col-md-2">
                        <input type="radio" name="cpv_graph' . $i . '" value="0"' . (empty($isGraph) ? ' checked' : '') . ' disabled>Yes<br />
                        <input type="radio" name="cpv_graph' . $i . '" value="1"' . ((!empty($isGraph) && $isGraph == 1) ? ' checked' : '') . ' disabled>No
                    </div>
                </div>';

        echo '<span class="h5">&nbsp;</span>';
    }

    echo '<span class="h4">&nbsp;</span>';


} catch (Exception $e) {
    echo formatExceptionHandling($e);
} finally {
    if (isset($memcache)) $memcache->close();
}