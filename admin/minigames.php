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

    echo '<h2>Create a Mini Game <small>BETA</small></h2>';
    echo '<p>This form allows admins to create Mini Game entries.</p>';

    echo '<form id="minigameCreate">';

    echo '<div class="row">
                <div class="col-md-1"><span class="h4">Name</span></div>
                <div class="col-md-1 text-center"><span class="glyphicon glyphicon-question-sign" title="The name of your mini-game"></span></div>
                <div class="col-md-6"><input class="formTextArea boxsizingBorder"  name="minigame_name" type="text" maxlength="70" size="45" placeholder="Super Awesome Fun Time" required></div>

                <div class="col-md-1"><strong>Objective</strong></div>
                <div class="col-md-1 text-center">
                    <span class="glyphicon glyphicon-question-sign" title="Are users to trying to maximise or minimise their score?"></span>
                </div>
                <div class="col-md-2">
                    <input type="radio" name="minigame_objective" value="max" checked>Maximise<br />
                    <input type="radio" name="minigame_objective" value="min">Minimise<br />
                </div>
            </div>';

    echo '<span class="h5">&nbsp;</span>';

    echo '<div class="row">
                <div class="col-md-1"><span class="h4">Developer</span></div>
                <div class="col-md-1 text-center"><span class="glyphicon glyphicon-question-sign" title="The userID of the developer of the mini game. Can be the community URL, userID64 or userID32"></span></div>
                <div class="col-md-6"><input class="formTextArea boxsizingBorder"  name="minigame_developer" type="text" maxlength="70" size="45" placeholder="http://steamcommunity.com/id/jimmydorry/" required></div>

                <div class="col-md-1"><strong>Operator</strong></div>
                <div class="col-md-1 text-center">
                    <span class="glyphicon glyphicon-question-sign" title="How do we handle the score?"></span>
                </div>
                <div class="col-md-2">
                    <input type="radio" name="minigame_operator" value="multiply" checked>Multiply<br />
                    <input type="radio" name="minigame_operator" value="divide">Divide
                </div>
            </div>';

    echo '<span class="h5">&nbsp;</span>';

    echo '<div class="row">
                <div class="col-md-1"><span class="h4">Group</span></div>
                <div class="col-md-1 text-center"><span class="glyphicon glyphicon-question-sign" title="(OPTIONAL) The full link to your game group, should you wish to create a community around your minigame"></span></div>
                <div class="col-md-6"><input class="formTextArea boxsizingBorder" name="minigame_steam_group" type="text" maxlength="70" placeholder="http://steamcommunity.com/groups/XXXXX"></div>

                <div class="col-md-1"><strong>Factor</strong></div>
                <div class="col-md-1 text-center">
                    <span class="glyphicon glyphicon-question-sign" title="What factor are we using on the score?"></span>
                </div>
                <div class="col-md-2">
                    <input class="formTextArea boxsizingBorder" type="number" name="minigame_factor" maxlength="20" value="1" min="0" max="1000" required>
                </div>
            </div>';

    echo '<span class="h5">&nbsp;</span>';

    echo '<div class="row">
                <div class="col-md-1"><span class="h4">Desc.</span></div>
                <div class="col-md-1 text-center"><span class="glyphicon glyphicon-question-sign" title="A description of the mini-game to be added"></span></div>
                <div class="col-md-6"><textarea class="formTextArea boxsizingBorder" name="minigame_description" placeholder="A fun game where you do stuff" required></textarea></div>

                <div class="col-md-1"><strong>Decimals</strong></div>
                <div class="col-md-1 text-center">
                    <span class="glyphicon glyphicon-question-sign" title="How many decimal points do we want on the leaderboard?"></span>
                </div>
                <div class="col-md-2">
                    <input class="formTextArea boxsizingBorder" type="number" name="minigame_decimals" maxlength="20" value="2" min="0" max="10" required>
                </div>
            </div>';

    echo '<span class="h5">&nbsp;</span>';

    echo '<div class="row">
                <div class="col-md-12 text-center">
                    <button id="sub">Create</button>
                </div>
            </div>';

    echo '<span class="h5">&nbsp;</span>';

    echo '</form>';

    echo '<span id="minigameAJAXResult" class="labelWarnings label label-danger"></span>';

    echo '<script type="application/javascript">
                    $("#minigameCreate").submit(function (event) {
                        event.preventDefault();

                        $.post("./admin/minigame_create_ajax.php", $("#minigameCreate").serialize(), function (data) {
                            try {
                                if(data){
                                    var response = JSON.parse(data);
                                    if(response && response.error){
                                        $("#minigameAJAXResult").html(response.error);
                                    }
                                    else if(response && response.result){
                                        $("#minigameAJAXResult").html(response.result);
                                        /*$("#minigameCreate :input").each(function () {
                                            $(this).val("");
                                        });*/
                                        loadPage("#admin__minigames",1);
                                    }
                                    else{
                                        $("#minigameAJAXResult").html(data);
                                    }
                                }
                            }
                            catch(err) {
                                $("#minigameAJAXResult").html("Parsing Error: " + err.message + "<br />" + data);
                            }
                        }, "text");
                    });
                </script>';

    echo '<hr />';

    echo '<h2>Manage Mini Games</h2>';
    echo '<p>These forms allow admins to edit existing mini games.</p>';
    echo '<hr />';

    $currentMG = $db->q(
        'SELECT
                `minigameID`,
                `minigameIdentifier`,
                `minigameName`,
                `minigameDeveloper`,
                `minigameDescription`,
                `minigameSteamGroup`,
                `minigameActive`,
                `minigameObjective`,
                `minigameOperator`,
                `minigameFactor`,
                `minigameDecimals`,
                `date_recorded`
            FROM `stat_highscore_minigames`;'
    );

    if (empty($currentMG)) {
        throw new Exception('No mini games currently created!');
    }

    foreach ($currentMG as $key => $value) {
        echo '<form id="minigameEdit' . $key . '">';
        $mgDescription = !empty($value['minigameDescription'])
            ? '<textarea class="formTextArea boxsizingBorder" name="minigameDescription" rows="3">' . $value['minigameDescription'] . '</textarea>'
            : '<textarea class="formTextArea boxsizingBorder" name="minigameDescription" rows="3">No description recorded.</textarea>';

        $profileLink = !empty($value['minigameDeveloper']) && is_numeric($value['minigameDeveloper'])
            ? '<a target="_blank" href="https://steamcommunity.com/profiles/' . $value['minigameDeveloper'] . '">' . $value['minigameDeveloper'] . '</a>'
            : 'Unknown';

        $steamGroup = !empty($value['minigameSteamGroup'])
            ? '<a target="_blank" href="https://steamcommunity.com/groups/' . $value['minigameSteamGroup'] . '">' . $value['minigameSteamGroup'] . '</a>'
            : 'No Group';

        $dateRecorded = !empty($value['date_recorded'])
            ? relative_time_v2($value['date_recorded'])
            : 'No Group';

        $mgObjective = !empty($value['minigameObjective']) && $value['minigameObjective'] == 'max'
            ? '<select name="minigameObjective" class="formTextArea boxsizingBorder">
                        <option value="min">Minimise</option>
                        <option value="max" selected>Maximise</option>
                    </select>'
            : '<select name="minigameObjective" class="formTextArea boxsizingBorder">
                        <option value="min" selected>Minimise</option>
                        <option value="max">Maximise</option>
                    </select>';

        $mgOperator = !empty($value['minigameOperator']) && $value['minigameOperator'] == 'divide'
            ? '<select name="minigameOperator" class="formTextArea boxsizingBorder">
                        <option value="multiply">Multiply</option>
                        <option value="divide" selected>Divide</option>
                    </select>'
            : '<select name="minigameOperator" class="formTextArea boxsizingBorder">
                        <option value="multiply" selected>Multiply</option>
                        <option value="divide">Divide</option>
                    </select>';

        $mgActive = isset($value['minigameActive']) && $value['minigameActive'] == 1
            ? '<input type="radio" name="minigameActive" value="0">No<br />
                    <input type="radio" name="minigameActive" value="1" checked>Yes'
            : '<input type="radio" name="minigameActive" value="0" checked>No<br />
                    <input type="radio" name="minigameActive" value="1">Yes';

        $mgFactor = !empty($value['minigameFactor']) && is_numeric($value['minigameFactor'])
            ? '<input class="formTextArea boxsizingBorder" type="number" name="minigameFactor" maxlength="20" size="4" value="' . floatval($value['minigameFactor']) . '" min="0" max="1000">'
            : '<input class="formTextArea boxsizingBorder" type="number" name="minigameFactor" maxlength="20" size="4" value="1" min="0" max="1000">';

        $mgDecimals = !empty($value['minigameDecimals']) && is_numeric($value['minigameDecimals'])
            ? '<input class="formTextArea boxsizingBorder" type="number" name="minigameDecimals" maxlength="20" size="4" value="' . $value['minigameDecimals'] . '" min="0" max="10">'
            : '<input class="formTextArea boxsizingBorder" type="number" name="minigameDecimals" maxlength="20" size="4" value="2" min="0" max="10">';

        echo '<div class="row">
                <div class="col-md-6">
                    <span class="h4">' . $value['minigameName'] . '</span>
                </div>
                <div class="col-md-2">' . $profileLink . '</div>
                <div class="col-md-2">' . $steamGroup . '</div>
                <div class="col-md-2">' . $dateRecorded . '</div>
            </div>';

        echo '<span class="h5">&nbsp;</span>';

        echo '<div class="row">
                <div class="col-md-1">&nbsp;</div>
                <div class="col-md-1"><strong>ID</strong></div>
                <div class="col-md-3">' . $value['minigameID'] . '</div>

                <div class="col-md-1">&nbsp;</div>
                <div class="col-md-2"><a target="_blank" href="#d2mods__minigame_leaderboard?lid=' . $value['minigameIdentifier'] . '">LEADERBOARD</a></div>
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
                </div>';

        echo '<span class="h5">&nbsp;</span>';

        echo '<div class="row">
                    <div class="col-md-12">' . $mgDescription . '</div>
                </div>';

        echo '<span class="h5">&nbsp;</span>';

        echo '<div class="row">
                    <div class="col-md-12 text-center">
                        <input type="submit" value="Approve / Edit"></div>
                </div>';

        echo '<input type="hidden" name="minigameID" value="' . $value['minigameID'] . '">';

        echo '</form>';

        echo '<script type="application/javascript">
                    function htmlEntities(str) {
                        return String(str).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
                    }

                    $("#minigameEdit' . $key . '").submit(function (event) {
                        event.preventDefault();

                        $.post("./admin/minigame_edit_ajax.php", $("#minigameEdit' . $key . '").serialize(), function (data) {
                            try {
                                if(data){
                                    var response = JSON.parse(data);
                                    if(response && response.error){
                                        $("#minigameAJAXResult").html(response.error);
                                    }
                                    else if(response && response.result){
                                        $("#minigameAJAXResult").html(response.result);
                                        loadPage("#admin__minigames",1);
                                    }
                                    else{
                                        $("#minigameAJAXResult").html(htmlEntities(data));
                                    }
                                }
                            }
                            catch(err) {
                                $("#minigameAJAXResult").html("Parsing Error: " + err.message + "<br />" + htmlEntities(data));
                            }
                        }, "text");
                    });
                </script>';

        echo '<hr />';
    }

    $memcache->close();
} catch (Exception $e) {
    $message = 'Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage();
    echo bootstrapMessage('Oh Snap', $message, 'danger');
}