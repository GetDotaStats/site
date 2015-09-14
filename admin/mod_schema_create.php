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

    echo '<h2>Create a Custom Game Schema <small>BETA</small></h2>';
    echo '<p>This form allows admins to create schemas for mods.</p>';

    echo '<div class="text-center">
                <a class="nav-clickable btn btn-default btn-lg" href="#admin__mod_schema">Schema List</a>
                <a class="nav-clickable btn btn-default btn-lg" href="#admin__mod_schema_edit">Edit Schema</a>
           </div>';

    echo '<hr />';

    $modSelectInput = cached_query(
        'admin_mod_select_input',
        'SELECT `mod_id`, `mod_name` FROM `mod_list` WHERE `mod_active` = 1 ORDER BY `mod_name`;',
        NULL,
        NULL,
        30
    );

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
        echo '<span class="h5">&nbsp;</span>';
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


    echo '<form id="modSchemaCreate">';

    echo '<div class="row">
                <div class="col-md-1"><span class="h4">Mod</span></div>
                <div class="col-md-1 text-center"><span class="glyphicon glyphicon-question-sign" title="The mod associated with this schema"></span></div>
                <div class="col-md-6">
                    <select name="schema_mod_id" class="formTextArea boxsizingBorder" required>
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

    ////////////////////////////////////
    //CUSTOM GAME FIELDS
    ////////////////////////////////////
    echo '<div class="row">
                <div class="col-md-4"><span class="h4">Custom Game Values</span> <span class="glyphicon glyphicon-question-sign" title="This is where all of your schema fields that relate to the entire game are defined"></span></div>
            </div>';

    echo '<span class="h5">&nbsp;</span>';

    echo '<span id="customGameValuesPlaceholder"></span>';

    echo '<div class="row">
                <div class="col-md-8 text-center">
                    <button id="moreGameFields" class="btn btn-warning">moreFields</button>
                </div>
            </div>';

    echo '<span class="h5">&nbsp;</span>';

    ////////////////////////////////////
    //CUSTOM PLAYER FIELDS
    ////////////////////////////////////

    echo '<div class="row">
                <div class="col-md-4"><span class="h4">Custom Player Values</span> <span class="glyphicon glyphicon-question-sign" title="This is where all of your schema fields that relate to individual players are defined"></span></div>
            </div>';

    echo '<span class="h5">&nbsp;</span>';

    echo '<span id="customPlayerValuesPlaceholder"></span>';

    echo '<div class="row">
                <div class="col-md-8 text-center">
                    <button id="morePlayerFields" class="btn btn-warning">moreFields</button>
                </div>
            </div>';

    echo '<span class="h5">&nbsp;</span>';


    ///////////////////////////////////

    echo '<div class="row">
                <div class="col-md-8 text-center">
                    <button id="sub" class="btn btn-success">Create</button>
                </div>
            </div>';

    echo '<span class="h5">&nbsp;</span>';

    echo '</form>';

    echo '<span id="schemaCustomAJAXResult" class="labelWarnings label label-danger"></span>';

    echo '<script type="application/javascript">
            var counterGame = 0;
            var counterPlayer = 0;

            addMoreFields("game");
            addMoreFields("player");

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


            $("#modSchemaCreate").submit(function (event) {
                event.preventDefault();

                $.post("./admin/mod_schema_create_ajax.php", $("#modSchemaCreate").serialize(), function (data) {
                    try {
                        if(data){
                            var response = JSON.parse(data);
                            if(response && response.error){
                                $("#schemaCustomAJAXResult").html(response.error);
                            }
                            else if(response && response.result){
                                loadPage("#admin__mod_schema",1);
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