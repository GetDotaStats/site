<?php
require_once('./functions.php');
require_once('../global_functions.php');
require_once('../connections/parameters.php');

if (!isset($_SESSION)) {
    session_start();
}

try {
    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    echo '<h2>Request a Mod be Accepted for Stats <small>BETA</small></h2>';

    echo '<p>This is a form that developers can use to add a mod to the list, and get access to the necessary code to implement stats for their mod. Only the
    developer (as listed in the workshop) of the submitted mod will be able to add it to the site.</p>';

    echo '<h3>Terms of Service</h3>';

    echo '<p>By submitting your mod for inclusion on our site, you must meet the following requirements:</p>';

    echo '<ul>
            <li>Your mod must be playable</li>
            <ul>
                <li>It should load and allow the player(s) to enter the game</li>
                <li>It should be as bug-free as possible. (e.g. there should be no fatal errors when performing normal actions in the mod)</li>
            </ul>
            <li>If more than 25% of the LUA files in your mod were not developed by you (as measured in terms of lines), then the original developer(s) must be
            listed as a contributors in your workshop. We will enforce this as fairly as possible, and evaluate it on a case-by-case basis. If your mod is
            found to be in breach of this rule, it will not be approved and may be de-listed until the situation is rectified. This rule does not apply to
            code libraries that are made with the intention to be shared.</li>
            <li>Your mod must include some custom code in the form of LUA. Mods that consist of only a map may not be accepted, but again will be approved on
            a case-by-case basis.</li>
        </ul>';

    checkLogin_v2();
    if (empty($_SESSION['user_id64'])) throw new Exception('Not logged in!');

    echo '<h3>Submission Form</h3>';

    echo '<div class="container"><div class="col-sm-6">';
    echo '<form id="modSignup">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <tr>
                                    <th>Workshop Link <span class="glyphicon glyphicon-question-sign"
                                                            title="The full link to your mod in the workshop. This will allow users to subscribe to your mod."></span>
                                    </th>
                                    <td><input name="mod_workshop_link" type="text" maxlength="70" size="55" placeholder="http://steamcommunity.com/sharedfiles/filedetails/?id=XXXXXXXXX" required>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Steam Group <span class="glyphicon glyphicon-question-sign"
                                                          title="(OPTIONAL) The full link to your game group, should you wish to create a community around your mod."></span>
                                    </th>
                                    <td><input name="mod_steam_group" type="text" maxlength="70" size="55" placeholder="http://steamcommunity.com/groups/XXXXX"></td>
                                </tr>
                                <tr>
                                    <th>Maps <span class="glyphicon glyphicon-question-sign"
                                                   title="Grab this from the lobby settings in-game. Failing to add this field will prevent users from playing the map via the Lobby Explorer!"></span>
                                        <br/><a target="_blank"
                                                href="//dota2.photography/images/misc/add_mod/map_name.png">EXAMPLE</a>
                                    </th>
                                    <td>
                                        <textarea name="mod_maps" rows="3" maxlength="255" cols="57" placeholder="One map per line!" required></textarea>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2" align="center">
                                        <button id="sub">Request</button>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </form>';
    echo '</div></div>';

    echo '<br/>';

    echo '<span id="modSignupResult" class="label label-danger"></span>';

    echo '<p>
                    <div class="text-center">
                        <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__directory">Mod Directory</a>
                        <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__my_mods">Browse my mods</a>
                   </div>
                </p>';

    echo '<script type="application/javascript">
                    $("#modSignup").submit(function (event) {
                        event.preventDefault();

                        $.post("./d2mods/mod_request_ajax.php", $("#modSignup").serialize(), function (data) {
                            $("#modSignup :input").each(function () {
                                $(this).val("");
                            });

                            if(data){
                                try {
                                    var response = JSON.parse(data);
                                    if(response && response.error){
                                        $("#modSignupResult").html(response.error);
                                    }
                                    else if(response && response.result){
                                        $("#modSignupResult").html(response.result);
                                    }
                                    else{
                                        $("#modSignupResult").html(data);
                                    }
                                }
                                catch(err) {
                                    $("#modSignupResult").html("Parsing Error: " + err.message + "<br />" + data);
                                }
                            }
                        }, "text");
                    });
                </script>';

    $memcache->close();
} catch (Exception $e) {
    echo formatExceptionHandling($e);
}