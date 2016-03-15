<?php
try {
    require_once('../global_functions.php');
    require_once('../connections/parameters.php');

    if (!isset($_SESSION)) {
        session_start();
    }

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcached = new Cache(NULL, NULL, $localDev);

    checkLogin_v2();
    if (empty($_SESSION['user_id64'])) throw new Exception('Not logged in!');

    $adminCheck = adminCheck($_SESSION['user_id64'], 'admin');
    if (empty($adminCheck)) throw new Exception('Not an admin!');

    echo '<h2>Create a Highscore Type <small>BETA</small></h2>';
    echo '<p>This form allows admins to create highscore types for mods.</p>';

    $modSelectInput = cached_query(
        'admin_mod_select_input',
        'SELECT `mod_id`, `mod_identifier`, `mod_name` FROM `mod_list` WHERE `mod_active` = 1 ORDER BY `mod_name`;',
        NULL,
        NULL,
        30
    );

    echo '<form id="highscoreCreate">';

    echo '<div class="row">
                <div class="col-md-1"><span class="h4">Name</span></div>
                <div class="col-md-1 text-center"><span class="glyphicon glyphicon-question-sign" title="The name of your highscore type"></span></div>
                <div class="col-md-6"><input class="formTextArea boxsizingBorder"  name="highscore_name" type="text" maxlength="70" size="45" placeholder="Kills" required></div>
            </div>';

    echo '<span class="h5">&nbsp;</span>';

    echo '<div class="row">
                <div class="col-md-1"><span class="h4">Mod</span></div>
                <div class="col-md-1 text-center"><span class="glyphicon glyphicon-question-sign" title="The mod associated with this highscore type"></span></div>
                <div class="col-md-6">
                    <select name="highscore_modid" class="formTextArea boxsizingBorder" required>
                        <option selected value="--">--</option>
                        ';

    if (!empty($modSelectInput)) {
        foreach ($modSelectInput as $key => $value) {
            echo '<option value="' . $value['mod_id'] . '">' . $value['mod_name'] . '</option>';
        }
    }

    echo '
                    </select>
                </div>
            </div>';

    echo '<span class="h5">&nbsp;</span>';

    echo '<div class="row">
                <div class="col-md-1"><span class="h4">Desc.</span></div>
                <div class="col-md-1 text-center"><span class="glyphicon glyphicon-question-sign" title="A description of the mini-game to be added"></span></div>
                <div class="col-md-6"><textarea class="formTextArea boxsizingBorder" name="highscore_description" placeholder="Only the best can secure those kills. Are you one of them?" rows="4" required></textarea></div>
            </div>';

    echo '<span class="h5">&nbsp;</span>';

    echo '<div class="row">
                <div class="col-md-1"><strong>Objective</strong></div>
                <div class="col-md-1 text-center">
                    <span class="glyphicon glyphicon-question-sign" title="Are users to trying to maximise or minimise their score?"></span>
                </div>
                <div class="col-md-2">
                    <input type="radio" name="highscore_objective" value="max" checked>Maximise<br />
                    <input type="radio" name="highscore_objective" value="min">Minimise<br />
                </div>

                <div class="col-md-1"><strong>Factor</strong></div>
                <div class="col-md-1 text-center">
                    <span class="glyphicon glyphicon-question-sign" title="What factor are we using on the score?"></span>
                </div>
                <div class="col-md-2">
                    <input class="formTextArea boxsizingBorder" type="number" name="highscore_factor" maxlength="20" value="1" min="0" max="1000" required>
                </div>
            </div>';

    echo '<span class="h5">&nbsp;</span>';

    echo '<div class="row">
                <div class="col-md-1"><strong>Operator</strong></div>
                <div class="col-md-1 text-center">
                    <span class="glyphicon glyphicon-question-sign" title="How do we handle the score?"></span>
                </div>
                <div class="col-md-2">
                    <input type="radio" name="highscore_operator" value="multiply" checked>Multiply<br />
                    <input type="radio" name="highscore_operator" value="divide">Divide
                </div>

                <div class="col-md-1"><strong>Decimals</strong></div>
                <div class="col-md-1 text-center">
                    <span class="glyphicon glyphicon-question-sign" title="How many decimal points do we want on the leaderboard?"></span>
                </div>
                <div class="col-md-2">
                    <input class="formTextArea boxsizingBorder" type="number" name="highscore_decimals" maxlength="20" value="2" min="0" max="10" required>
                </div>
            </div>';

    echo '<span class="h5">&nbsp;</span>';

    echo '<div class="row">
                <div class="col-md-8 text-center">
                    <button id="sub">Create</button>
                </div>
            </div>';

    echo '<span class="h5">&nbsp;</span>';

    echo '</form>';

    echo '<span id="highscoreAJAXResult" class="labelWarnings label label-danger"></span>';

    echo '<script type="application/javascript">
                    $("#highscoreCreate").submit(function (event) {
                        event.preventDefault();

                        $.post("./admin/hs_mod_create_ajax.php", $("#highscoreCreate").serialize(), function (data) {
                            try {
                                if(data){
                                    var response = JSON.parse(data);
                                    if(response && response.error){
                                        $("#highscoreAJAXResult").html(response.error);
                                    }
                                    else if(response && response.result){
                                        loadPage("#admin__hs_mod",1);
                                    }
                                    else{
                                        $("#highscoreAJAXResult").html(data);
                                    }
                                }
                            }
                            catch(err) {
                                $("#highscoreAJAXResult").html("Parsing Error: " + err.message + "<br />" + data);
                            }
                        }, "text");
                    });
                </script>';

    echo '<hr />';

    echo '<h2>Manage Highscore Types</h2>';
    echo '<p>These forms allow admins to edit existing highscore types.</p>';
    echo '<hr />';

    $currentHS = cached_query(
        'admin_hs_mod_types',
        'SELECT
                shms.`highscoreID`,
                shms.`highscoreIdentifier`,
                shms.`modID`,
                shms.`modIdentifier`,
                shms.`secureWithAuth`,
                shms.`highscoreIdentifier`,
                shms.`highscoreName`,
                shms.`highscoreDescription`,
                shms.`highscoreActive`,
                shms.`highscoreObjective`,
                shms.`highscoreOperator`,
                shms.`highscoreFactor`,
                shms.`highscoreDecimals`,
                shms.`date_recorded`,

                ml.`mod_name`
            FROM `stat_highscore_mods_schema` shms
            JOIN `mod_list` ml ON shms.`modID` = ml.`mod_id`;',
        NULL,
        NULL,
        1
    );

    if (empty($currentHS)) {
        throw new Exception('No highscore types currently created!');
    }

    foreach ($currentHS as $key => $value) {
        echo '<form id="highscoreEdit' . $key . '">';
        $mgDescription = !empty($value['highscoreDescription'])
            ? '<textarea class="formTextArea boxsizingBorder" name="highscore_description" rows="3">' . $value['highscoreDescription'] . '</textarea>'
            : '<textarea class="formTextArea boxsizingBorder" name="highscore_description" rows="3">No description recorded.</textarea>';

        $dateRecorded = !empty($value['date_recorded'])
            ? relative_time_v3($value['date_recorded'])
            : 'No Group';

        $mgObjective = !empty($value['highscoreObjective']) && $value['highscoreObjective'] == 'max'
            ? '<select name="highscore_objective" class="formTextArea boxsizingBorder">
                        <option value="min">Minimise</option>
                        <option value="max" selected>Maximise</option>
                    </select>'
            : '<select name="highscore_objective" class="formTextArea boxsizingBorder">
                        <option value="min" selected>Minimise</option>
                        <option value="max">Maximise</option>
                    </select>';

        $mgOperator = !empty($value['highscoreOperator']) && $value['highscoreOperator'] == 'divide'
            ? '<select name="highscore_operator" class="formTextArea boxsizingBorder">
                        <option value="multiply">Multiply</option>
                        <option value="divide" selected>Divide</option>
                    </select>'
            : '<select name="highscore_operator" class="formTextArea boxsizingBorder">
                        <option value="multiply" selected>Multiply</option>
                        <option value="divide">Divide</option>
                    </select>';

        $mgActive = isset($value['highscoreActive']) && $value['highscoreActive'] == 1
            ? '<input type="radio" name="highscore_active" value="0">No<br />
                    <input type="radio" name="highscore_active" value="1" checked>Yes'
            : '<input type="radio" name="highscore_active" value="0" checked>No<br />
                    <input type="radio" name="highscore_active" value="1">Yes';

        $secureWithAuth = isset($value['secureWithAuth']) && $value['secureWithAuth'] == 1
            ? '<input type="radio" name="highscore_secure" value="0">No<br />
                    <input type="radio" name="highscore_secure" value="1" checked>Yes'
            : '<input type="radio" name="highscore_secure" value="0" checked>No<br />
                    <input type="radio" name="highscore_secure" value="1">Yes';

        $mgFactor = isset($value['highscoreFactor']) && is_numeric($value['highscoreFactor'])
            ? '<input class="formTextArea boxsizingBorder" type="number" name="highscore_factor" maxlength="20" size="4" value="' . floatval($value['highscoreFactor']) . '" min="0" max="1000">'
            : '<input class="formTextArea boxsizingBorder" type="number" name="highscore_factor" maxlength="20" size="4" value="1" min="0" max="1000">';

        $mgDecimals = isset($value['highscoreDecimals']) && is_numeric($value['highscoreDecimals'])
            ? '<input class="formTextArea boxsizingBorder" type="number" name="highscore_decimals" maxlength="20" size="4" value="' . $value['highscoreDecimals'] . '" min="0" max="10">'
            : '<input class="formTextArea boxsizingBorder" type="number" name="highscore_decimals" maxlength="20" size="4" value="2" min="0" max="10">';

        $hsModLBlink = '<a href="#d2mods__mod_leaderboard?lid=' . $value['highscoreIdentifier'] . '" target="_new"><span class="glyphicon glyphicon-new-window"></span></a>';

        echo '<div class="row">
                <div class="col-md-1 text-center">' . $hsModLBlink . '</div>
                <div class="col-md-5">
                    <span class="h4">' . $value['highscoreName'] . '</span>
                </div>
                <div class="col-md-2">' . $dateRecorded . '</div>
            </div>';

        echo '<span class="h5">&nbsp;</span>';

        echo '<div class="row">
                <div class="col-md-1">&nbsp;</div>
                <div class="col-md-1"><strong>Mod</strong></div>
                <div class="col-md-5">' . $value['mod_name'] . '</div>
            </div>';

        echo '<div class="row">
                <div class="col-md-1">&nbsp;</div>
                <div class="col-md-1"><strong>Mod ID</strong></div>
                <div class="col-md-5">' . $value['modIdentifier'] . '</div>
            </div>';

        echo '<div class="row">
                <div class="col-md-1">&nbsp;</div>
                <div class="col-md-1"><strong>HS ID</strong></div>
                <div class="col-md-5">' . $value['highscoreIdentifier'] . '</div>
            </div>';

        echo '<span class="h5">&nbsp;</span>';

        echo '<div class="row">
                    <div class="col-md-1">&nbsp;</div>
                    <div class="col-md-1"><strong>Objective</strong></div>
                    <div class="col-md-2">' . $mgObjective . '</div>

                    <div class="col-md-1"><strong>Operator</strong></div>
                    <div class="col-md-2">' . $mgOperator . '</div>

                    <div class="col-md-1"><strong>Active</strong></div>
                    <div class="col-md-2">' . $mgActive . '</div>
                </div>';

        echo '<span class="h5">&nbsp;</span>';

        echo '<div class="row">
                    <div class="col-md-1">&nbsp;</div>
                    <div class="col-md-1"><strong>Factor</strong></div>
                    <div class="col-md-2">' . $mgFactor . '</div>

                    <div class="col-md-1"><strong>Decimals</strong></div>
                    <div class="col-md-2">' . $mgDecimals . '</div>

                    <div class="col-md-1"><strong>Secure</strong> <span class="glyphicon glyphicon-exclamation-sign" title="Whether the mod retains and sends an auth code to replace existing scores"></span></div>
                    <div class="col-md-2">' . $secureWithAuth . '</div>
                </div>';

        echo '<span class="h5">&nbsp;</span>';

        echo '<div class="row">
                    <div class="col-md-1">&nbsp;</div>
                    <div class="col-md-8">' . $mgDescription . '</div>
                </div>';

        echo '<span class="h5">&nbsp;</span>';

        echo '<div class="row">
                    <div class="col-md-1">&nbsp;</div>
                    <div class="col-md-8 text-center">
                        <input type="submit" value="Approve / Edit"></div>
                </div>';

        echo '<input type="hidden" name="highscore_ID" value="' . $value['highscoreID'] . '">';
        echo '<input type="hidden" name="mod_ID" value="' . $value['modID'] . '">';
        echo '<input type="hidden" name="highscore_name" value="' . $value['highscoreName'] . '">';

        echo '</form>';

        echo '<span id="highscoreAJAXResult' . $value['highscoreID'] . '" class="labelWarnings label label-danger"></span>';

        echo '<script type="application/javascript">
                    function htmlEntities(str) {
                        return String(str).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
                    }

                    $("#highscoreEdit' . $key . '").submit(function (event) {
                        event.preventDefault();

                        $.post("./admin/hs_mod_edit_ajax.php", $("#highscoreEdit' . $key . '").serialize(), function (data) {
                            try {
                                if(data){
                                    var response = JSON.parse(data);
                                    if(response && response.error){
                                        $("#highscoreAJAXResult' . $value['highscoreID'] . '").html(response.error);
                                    }
                                    else if(response && response.result){
                                        loadPage("#admin__hs_mod",1);
                                    }
                                    else{
                                        $("#highscoreAJAXResult' . $value['highscoreID'] . '").html(htmlEntities(data));
                                    }
                                }
                            }
                            catch(err) {
                                $("#highscoreAJAXResult' . $value['highscoreID'] . '").html("Parsing Error: " + err.message + "<br />" + htmlEntities(data));
                            }
                        }, "text");
                    });
                </script>';

        echo '<hr />';
    }

} catch (Exception $e) {
    echo formatExceptionHandling($e);
} finally {
    if (isset($memcached)) $memcached->close();
}