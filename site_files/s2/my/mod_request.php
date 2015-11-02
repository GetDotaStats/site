<?php
require_once('../../global_functions.php');
require_once('../../connections/parameters.php');

if (!isset($_SESSION)) {
    session_start();
}

try {
    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    echo '<div class="page-header"><h2>Request a Mod be Accepted for Stats</h2></div>';

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
            <li>If more than 25% of the Lua files in your mod were not developed by you (as measured in terms of lines), then the original developer(s) must be
            listed as a contributors in your workshop. We will enforce this as fairly as possible, and evaluate it on a case-by-case basis. If your mod is
            found to be in breach of this rule, it will not be approved and may be de-listed until the situation is rectified. This rule does not apply to
            code libraries that are made with the intention to be shared.</li>
            <li>Your mod must include some custom code in the form of Lua. Mods that consist of only a map may not be accepted, but again will be approved on
            a case-by-case basis.</li>
        </ul>';

    checkLogin_v2();
    if (empty($_SESSION['user_id64'])) throw new Exception('Not logged in!');

    echo '<h3>Submission Form</h3>';

    $workshop_help = '<span class="glyphicon glyphicon-question-sign" title="The full link to your mod in the workshop. This will allow users to subscribe to your mod."></span>';
    $steamgroup_help = '<span class="glyphicon glyphicon-question-sign" title="(OPTIONAL) The full link to your game group, should you wish to create a community around your mod."></span>';
    $contact_help = '<span class="glyphicon glyphicon-question-sign" title="A contact email address for when there are critical announcements. We will not sell this address and promise not to spam it!"></span>';

    echo "<form id='modSignup'>";

    echo "<div class='row'>
                <div class='col-md-3'>$workshop_help <strong>Workshop Link</strong></div>
                <div class='col-md-6'>
                    <input class='formTextArea boxsizingBorder' name='mod_workshop_link' type='text' maxlength='70' placeholder='http://steamcommunity.com/sharedfiles/filedetails/?id=XXXXXXXXX' required>
                </div>
            </div>";

    echo "<span class='h4'>&nbsp;</span>";


    echo "<div class='row'>
                <div class='col-md-3'>$steamgroup_help <strong>Steam Group</strong></div>
                <div class='col-md-6'>
                    <input class='formTextArea boxsizingBorder' name='mod_steam_group' type='text' maxlength='70' placeholder='http://steamcommunity.com/groups/XXXXX'>
                </div>
            </div>";

    echo "<span class='h4'>&nbsp;</span>";

    echo "<div class='row'>
                <div class='col-md-3'>$contact_help <strong>Contact eMail</strong></div>
                <div class='col-md-6'>
                    <input class='formTextArea boxsizingBorder' name='mod_contact_address' type='text' maxlength='70' placeholder='developer123@gmail.com' required>
                </div>
            </div>";

    echo "<span class='h4'>&nbsp;</span>";


    echo "<div class='row'>
                <div class='col-md-9 text-center'><button id='sub'>Request</button></div>
            </div>";

    echo "<span class='h4'>&nbsp;</span>";

    echo "</form>";

    echo '<span id="modSignupResult" class="label label-danger"></span>';

    echo '<script type="application/javascript">
                    $("#modSignup").submit(function (event) {
                        event.preventDefault();

                        $.post("./s2/my/mod_request_ajax.php", $("#modSignup").serialize(), function (data) {
                            if(data){
                                try {
                                    var response = JSON.parse(data);
                                    if(response && response.error){
                                        $("#modSignupResult").html(response.error);
                                    }
                                    else if(response && response.result){
                                        $("#modSignup :input").each(function () {
                                            $(this).val("");
                                        });

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

    echo '<hr />';

    echo '<span class="h4">&nbsp;</span>';

    echo '<div class="text-center">
            <a class="nav-clickable btn btn-default btn-lg" href="#s2__directory">Mod Directory</a>
            <a class="nav-clickable btn btn-default btn-lg" href="#s2__my__mods">My Mods</a>
        </div>';

    echo '<span class="h4">&nbsp;</span>';

} catch (Exception $e) {
    echo formatExceptionHandling($e);
} finally {
    if (isset($memcache)) $memcache->close();
}