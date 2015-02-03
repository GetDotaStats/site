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
                                        <input name="minigame_developer" type="text" maxlength="70" size="45" placeholder="http://steamcommunity.com/id/jimmydorry/">
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
                                    <td colspan="3" align="center">
                                        <button id="sub">Create</button>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </form>';
            echo '</div></div>';

            echo '<br/>';

            echo '<span id="minigameSignupResult" class="label label-danger"></span>';

            echo '<p>
                    <div class="text-center">
                        <a class="nav-clickable btn btn-default btn-lg" href="#admin/">Back to Admin Panel</a>
                   </div>
                </p>';

            echo '<script type="application/javascript">
                    $("#minigameSignup").submit(function (event) {
                        event.preventDefault();

                        $.post("./admin/minigame_create_ajax.php", $("#minigameSignup").serialize(), function (data) {
                            $("#minigameSignup :input").each(function () {
                                $(this).val("");
                            });

                            if(data){
                                try {
                                    var response = JSON.parse(data);
                                    if(response && response.error){
                                        $("#minigameSignupResult").html(response.error);
                                    }
                                    else if(response && response.result){
                                        $("#minigameSignupResult").html(response.result);
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
    } else {
        echo bootstrapMessage('Oh Snap', 'Not logged in or insufficient privilege!', 'danger');
    }
} catch (Exception $e) {
    $message = 'Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage();
    echo bootstrapMessage('Oh Snap', $message, 'danger');
}