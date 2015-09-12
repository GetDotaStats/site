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

    echo '<div class="row">
                <div class="col-md-4"><span class="h4">Custom Game Values</span> <span class="glyphicon glyphicon-question-sign" title="This is where all of your schema fields that relate to the entire game are defined"></span></div>
            </div>';

    echo '<span class="h5">&nbsp;</span>';

    $modSchemaCreateFields_CustomGame_examples = array(
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
        array('display' => 'Game Duration', 'name' => 'game_duration'),
        array('display' => 'Hero Picks', 'name' => 'game_hero_picks_allowed'),
    );
    $modSchemaCreateFields_CustomGame = 5;
    for ($i = 1; $i <= $modSchemaCreateFields_CustomGame; $i++) {
        $randomExample = rand(0, (count($modSchemaCreateFields_CustomGame_examples) - 1));
        echo '<div class="row">
                <div class="col-md-1"><span class="h4">#' . $i . '</span></div>
                <div class="col-md-1">Display</div>
                <div class="col-md-6"><input class="formTextArea boxsizingBorder" name="cgv' . $i . '_display" type="text" maxlength="70" size="45" placeholder="' . $modSchemaCreateFields_CustomGame_examples[$randomExample]['display'] . '"></div>
            </div>';
        echo '<div class="row">
                <div class="col-md-1">&nbsp;</div>
                <div class="col-md-1">Name</div>
                <div class="col-md-6"><input class="formTextArea boxsizingBorder" name="cgv' . $i . '_name" type="text" maxlength="70" size="45" placeholder="' . $modSchemaCreateFields_CustomGame_examples[$randomExample]['name'] . '"></div>
            </div>';
        echo '<div class="row">
                <div class="col-md-1">&nbsp;</div>
                <div class="col-md-1">Objective</div>
                <div class="col-md-2">
                    <input type="radio" name="cgv' . $i . '_objective" value="1">Minimise<br />
                    <input type="radio" name="cgv' . $i . '_objective" value="2">Maximise<br />
                    <input type="radio" name="cgv' . $i . '_objective" value="3" checked>Info
                </div>
            </div>';

        echo '<span class="h5">&nbsp;</span>';
    }

    echo '<div class="row">
                <div class="col-md-4"><span class="h4">Custom Player Values</span> <span class="glyphicon glyphicon-question-sign" title="This is where all of your schema fields that relate to individual players are defined"></span></div>
            </div>';

    echo '<span class="h5">&nbsp;</span>';

    $modSchemaCreateFields_CustomPlayer_examples = array(
        array('display' => 'Hero', 'name' => 'player_hero_id'),
        array('display' => 'Level', 'name' => 'player_hero_level'),
        array('display' => 'Deaths', 'name' => 'player_deaths'),
        array('display' => 'Assists', 'name' => 'player_assists'),
        array('display' => 'Denies', 'name' => 'player_denies'),
        array('display' => 'Skill 1', 'name' => 'player_skill_1'),
        array('display' => 'Item 1', 'name' => 'player_item_1'),
        array('display' => 'Roshan Kills', 'name' => 'player_roshan_kills'),
        array('display' => 'Pickup Time - Item Slot #1', 'name' => 'player_item1_pickup_time'),
        array('display' => 'Damage Upgrades Purchased', 'name' => 'player_upgrades_dmg_count'),
        array('display' => 'Selected Skills', 'name' => 'player_skills_selected_hash'),
        array('display' => 'Items at 5mins', 'name' => 'player_items_5mins_hash'),
    );
    $modSchemaCreateFields_CustomPlayer = 15;
    for ($i = 1; $i <= $modSchemaCreateFields_CustomPlayer; $i++) {
        $randomExample = rand(0, (count($modSchemaCreateFields_CustomPlayer_examples) - 1));
        echo '<div class="row">
                <div class="col-md-1"><span class="h4">#' . $i . '</span></div>
                <div class="col-md-1">Display</div>
                <div class="col-md-6"><input class="formTextArea boxsizingBorder" name="cpv' . $i . '_display" type="text" maxlength="70" size="45" placeholder="' . $modSchemaCreateFields_CustomPlayer_examples[$randomExample]['display'] . '"></div>
            </div>';
        echo '<div class="row">
                <div class="col-md-1">&nbsp;</div>
                <div class="col-md-1">Name</div>
                <div class="col-md-6"><input class="formTextArea boxsizingBorder" name="cpv' . $i . '_name" type="text" maxlength="70" size="45" placeholder="' . $modSchemaCreateFields_CustomPlayer_examples[$randomExample]['name'] . '"></div>
            </div>';
        echo '<div class="row">
                <div class="col-md-1">&nbsp;</div>
                <div class="col-md-1">Objective</div>
                <div class="col-md-2">
                    <input type="radio" name="cpv' . $i . '_objective" value="1">Minimise<br />
                    <input type="radio" name="cpv' . $i . '_objective" value="2">Maximise<br />
                    <input type="radio" name="cpv' . $i . '_objective" value="3" checked>Info
                </div>
            </div>';

        echo '<span class="h5">&nbsp;</span>';
    }

    echo '<div class="row">
                <div class="col-md-8 text-center">
                    <button id="sub">Create</button>
                </div>
            </div>';

    echo '<span class="h5">&nbsp;</span>';

    echo '</form>';

    echo '<span id="schemaCustomAJAXResult" class="labelWarnings label label-danger"></span>';

    echo '<script type="application/javascript">
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