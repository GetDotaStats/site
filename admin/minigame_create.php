<?php
require_once('../global_functions.php');
require_once('../connections/parameters.php');

if (!isset($_SESSION)) {
    session_start();
}

checkLogin_v2();

try {
    if (!empty($_SESSION['user_id64']) && !empty($_SESSION['isAdmin'])) {
        $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);

        $memcache = new Memcache;
        $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

        echo '<h2>Create a Mini Game <small>BETA</small></h2>';

        echo '<p>This is a form that admins can use to create a mini-game.</p>';

        if ($db) {
            echo '<div class="container"><div class="col-sm-6">';
            echo '<form id="minigameSignup">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <tr>
                                    <th>Mini Game Name</th>
                                    <td>
                                        <span class="glyphicon glyphicon-question-sign" title="The name of your mini-game"></span>
                                    </td>
                                    <td>
                                        <input name="minigame_name" type="text" maxlength="70" size="45" placeholder="Super Awesome Fun Time" required>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Developer</th>
                                    <td>
                                        <span class="glyphicon glyphicon-question-sign" title="The userID of the developer of the mini game. Can be the community URL, userID64 or userID32"></span>
                                    </td>
                                    <td>
                                        <input name="minigame_developer" type="text" maxlength="70" size="45" placeholder="http://steamcommunity.com/id/jimmydorry/" required>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Steam Group</th>
                                    <td>
                                        <span class="glyphicon glyphicon-question-sign" title="(OPTIONAL) The full link to your game group, should you wish to create a community around your minigame"></span>
                                    </td>
                                    <td>
                                        <input name="minigame_steam_group" type="text" maxlength="70" size="45" placeholder="http://steamcommunity.com/groups/XXXXX">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Description</th>
                                    <td>
                                        <span class="glyphicon glyphicon-question-sign" title="A description of the mini-game to be added"></span>
                                    </td>
                                    <td>
                                        <textarea name="minigame_description" cols="46" placeholder="A fun game where you do stuff" required></textarea>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3">&nbsp;</td>
                                </tr>
                                <tr>
                                    <th>Score Objective</th>
                                    <td>
                                        <span class="glyphicon glyphicon-question-sign" title="Are users to trying to maximise or minimise their score?"></span>
                                    </td>
                                    <td>
                                        <input type="radio" name="minigame_objective" value="max" checked>Maximise<br />
                                        <input type="radio" name="minigame_objective" value="min">Minimise<br />
                                    </td>
                                </tr>
                                <tr>
                                    <th>Score Operator</th>
                                    <td>
                                        <span class="glyphicon glyphicon-question-sign" title="How do we handle the score?"></span>
                                    </td>
                                    <td>
                                        <input type="radio" name="minigame_operator" value="multiply" checked>Multiply<br />
                                        <input type="radio" name="minigame_operator" value="divide">Divide
                                    </td>
                                </tr>
                                <tr>
                                    <th>Score Factor</th>
                                    <td>
                                        <span class="glyphicon glyphicon-question-sign" title="What factor are we using on the score?"></span>
                                    </td>
                                    <td>
                                        <input type="number" name="minigame_factor" maxlength="20" size="4" value="1" min="0" max="1000" required>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Score Decimals</th>
                                    <td>
                                        <span class="glyphicon glyphicon-question-sign" title="How many decimal points do we want on the leaderboard?"></span>
                                    </td>
                                    <td>
                                        <input type="number" name="minigame_decimals" maxlength="20" size="4" value="2" min="0" max="10" required>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3" align="center">
                                        <button id="sub">Create</button>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </form>';
            echo '</div></div>';

            echo '<span id="minigameSignupResult" class="label label-danger"></span>';

            echo '<hr />';

            $currentMG = cached_query(
                'mg_create_list1',
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
                    FROM `stat_highscore_minigames`;',
                NULL,
                NULL,
                15
            );

            if (!empty($currentMG)) {
                echo '<form id="minigameEdit">';
                foreach ($currentMG as $key => $value) {
                    $mgDescription = !empty($value['minigameDescription'])
                        ? $value['minigameDescription']
                        : 'No description recorded.';

                    $profileLink = !empty($value['minigameDeveloper']) && is_numeric($value['minigameDeveloper'])
                        ? '<a target="_blank" href="https://steamcommunity.com/profiles/' . $value['minigameDeveloper'] . '">' . $value['minigameDeveloper'] . '</a>'
                        : 'Unknown';

                    $steamGroup = !empty($value['minigameSteamGroup'])
                        ? '<a target="_blank" href="https://steamcommunity.com/groups/' . $value['minigameSteamGroup'] . '">' . $value['minigameSteamGroup'] . '</a>'
                        : 'No Group';

                    $dateRecorded = !empty($value['date_recorded'])
                        ? relative_time_v2($value['date_recorded'])
                        : 'No Group';

                    echo '<div class="row">
                            <div class="col-md-6">
                                <span class="h4">' . $value['minigameName'] . '</span>
                            </div>
                            <div class="col-md-2">
                                <span>' . $profileLink . '</span>
                            </div>
                            <div class="col-md-2">
                                <span>' . $steamGroup . '</span>
                            </div>
                            <div class="col-md-2">
                                <span>' . $dateRecorded . '</span>
                            </div>
                        </div>';

                    echo '<div class="row">
                            <div class="col-md-2">&nbsp;</div>
                            <div class="col-md-2">
                                <strong>Objective</strong> <span>' . $value['minigameObjective'] . '</span>
                            </div>
                            <div class="col-md-3">
                                <strong>Operator:</strong> <span>' . $value['minigameOperator'] . '</span>
                            </div>
                            <div class="col-md-3">
                                <strong>Factor:</strong> <span>' . $value['minigameFactor'] . '</span>
                            </div>
                            <div class="col-md-2">
                                <strong>Decimals:</strong> <span>' . $value['minigameDecimals'] . '</span>
                            </div>
                        </div>';

                    echo '<div class="row">
                            <div class="col-md-12">' . $mgDescription . '</div>
                        </div>';

                    echo '<span class="h3">&nbsp;</span>';
                    echo '<hr />';
                }
                echo '</form>';
            }
            else{
                echo '<hr />';
            }

            echo '<script type="application/javascript">
                    $("#minigameSignup").submit(function (event) {
                        event.preventDefault();

                        $.post("./admin/minigame_create_ajax.php", $("#minigameSignup").serialize(), function (data) {
                            if(data){
                                try {
                                    var response = JSON.parse(data);
                                    if(response && response.error){
                                        $("#minigameSignupResult").html(response.error);
                                    }
                                    else if(response && response.result){
                                        $("#minigameSignupResult").html(response.result);
                                        /*$("#minigameSignup :input").each(function () {
                                            $(this).val("");
                                        });*/
                                        loadPage("#admin__minigame_create",1);
                                    }
                                    else{
                                        $("#minigameSignupResult").html(data);
                                    }
                                }
                                catch(err) {
                                    $("#minigameSignupResult").html("Parsing Error: " + err.message + "<br />" + data);
                                }
                            }
                        }, "text");
                    });
                </script>';

        } else {
            echo bootstrapMessage('Oh Snap', 'No DB!', 'danger');
        }

        echo '<span class="h3">&nbsp;</span>';
        echo '<div class="text-center">
                        <a class="nav-clickable btn btn-default btn-lg" href="#admin/">Back to Admin Panel</a>
                   </div>';
        echo '<span class="h3">&nbsp;</span>';

        $memcache->close();
    } else {
        echo bootstrapMessage('Oh Snap', 'Not logged in or insufficient privilege!', 'danger');
    }
} catch (Exception $e) {
    $message = 'Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage();
    echo bootstrapMessage('Oh Snap', $message, 'danger');
}