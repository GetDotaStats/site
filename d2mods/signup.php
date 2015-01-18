<?php
require_once('../global_functions.php');
require_once('../connections/parameters.php');

if (!isset($_SESSION)) {
    session_start();
}

checkLogin_v2();

try {
    if (!empty($_SESSION['user_id64'])) {
        $db = new dbWrapper_v2($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site);
        $db->q('SET NAMES utf8;');

        if ($db) {
            ?>
            <div class="page-header">
                <h2>Add a new Mod for Stats
                    <small>BETA</small>
                </h2>
            </div>

            <p>This is a form that developers can use to add a mod to the list, and get access to the necessary code to
                implement stats for their mod. <strong>THIS IS NOT A PLACE TO ASK FOR A LOBBY!</strong></p>

            <div class="container">
                <div class="col-sm-6">
                    <form id="modSignup">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <tr>
                                    <th width="160">Name <span class="glyphicon glyphicon-question-sign"
                                                               title="The name of your mod, as listed in the workshop."></span>
                                    </th>
                                    <td><input name="mod_name" type="text" maxlength="35" size="55" required></td>
                                </tr>
                                <tr>
                                    <th>Description <span class="glyphicon glyphicon-question-sign"
                                                          title="A brief description of your mod. Site moderators may improve your description."></span>
                                    </th>
                                    <td><textarea name="mod_description" rows="4" cols="57" required></textarea></td>
                                </tr>
                                <tr>
                                    <th>Workshop Link <span class="glyphicon glyphicon-question-sign"
                                                            title="The full link to your mod in the workshop. This will allow users to subscribe to your mod."></span>
                                    </th>
                                    <td><input name="mod_workshop_link" type="text" maxlength="70" size="55" required value="http://steamcommunity.com/sharedfiles/filedetails/?id=XXXXXXXXX">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Steam Group <span class="glyphicon glyphicon-question-sign"
                                                          title="(OPTIONAL) The full link to your game group, should you wish to create a community around your mod."></span>
                                    </th>
                                    <td><input name="mod_steam_group" type="text" maxlength="70" size="55" value="http://steamcommunity.com/groups/XXXXX"></td>
                                </tr>
                                <tr>
                                    <th>Maps <span class="glyphicon glyphicon-question-sign"
                                                   title="Grab this from the lobby settings in-game. Failing to add this field will prevent users from playing the map via the Lobby Explorer!"></span>
                                        <br/><a target="_blank"
                                                href="//dota2.photography/images/misc/add_mod/map_name.png">EXAMPLE</a>
                                    </th>
                                    <td>
                                        <textarea name="mod_maps" rows="3" maxlength="255" cols="57" required>One map per line!</textarea>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2" align="center">
                                        <button id="sub">Signup</button>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </form>
                </div>
            </div>

            <!--
            [
                {type:"dropdown",label:"Game Mode",name:"gamemode",default="All Pick",options:[{label:"All Pick",data:"1"},{label:"Single Draft",data:"2"}, {label:"Mirror Draft",data:"3"}, {label:"All Random",data:"4"}]},
                {type:"dropdown",label:"Max Slots",name:"maxslots",default="6 slots",options:[{label:"4 Slots",data:"4"},{label:"5 Slots",data:"5"}, {label:"6 Slots",data:"6"}]},
                {type:"dropdown",label:"Max Skills",name:"maxskills",default="6 Skills",options:[{label:"No Regular Abilities",data:"0"},{label:"1 Regular Ability",data:"1"}, {label:"2 Regular Abilities",data:"2"}, {label:"3 Regular Abilities",data:"3"}, {label:"4 Regular Abilities",data:"4"}, {label:"5 Regular Abilities",data:"5"}, {label:"6 Regular Abilities",data:"6"}]},
                {type:"dropdown",label:"Max Ults",name:"maxults",default="2 Ultimate Abilities",options:[{label:"No Ultimate Abilities",data:"0"},{label:"1 Ultimate Skill",data:"1"}, {label:"2 Ultimate Abilities",data:"2"}, {label:"3 Ultimate Abilities",data:"3"}, {label:"4 Ultimate Abilities",data:"4"}, {label:"5 Ultimate Abilities",data:"5"}, {label:"6 Ultimate Abilities",data:"6"}]},
                {type:"dropdown",label:"Max Bans",name:"maxbans",default="5 Bans Each",options:[{label:"No Bans",data:"0"},{label:"1 Ban Each",data:"1"}, {label:"2 Bans Each",data:"2"}, {label:"3 Bans Each",data:"3"}, {label:"5 Bans Each",data:"5"}, {label:"10 Bans Each",data:"10"}, {label:"15 Bans Each",data:"15"}, {label:"20 Bans Each",data:"20"}, {label:"Host Banning",data:"-1"}]},
                {type:"checkbox",label:"Block Troll Combos",name:"blocktrollcombos",default:true},
                {type:"dropdown",label:"Starting Level",name:"startinglevel",default="Level 1",options:[{label:"Level 1",data:"1"},{label:"Level 6",data:"6"}, {label:"Level 11",data:"11"}, {label:"Level 16",data:"16"}, {label:"Level 25",data:"25"}]},
                {type:"checkbox",label:"Enable Easy Mode",name:"useeasymode",default:false},
                {type:"checkbox",label:"Hide Enemy Picks",name:"hideenemypicks",default:true},
                {type:"dropdown",label:"Bonus Starting Gold",name:"bonusstartinggold",default="None",options:[{label:"0g",data:"0"},{label:"250g",data:"250"}, {label:"500g",data:"500"}, {label:"1000g",data:"1000"}, {label:"2500g",data:"2500"}, {label:"5000g",data:"5000g"}, {label:"10000g",data:"10000"}, {label:"25000g",data:"25000"}, {label:"50000g",data:"50000"}, {label:"100000",data:"100000"}]},
                {type:"dropdown",label:"Unique Skills",name:"uniqueskills",default="Off",options:[{label:"Off",data:"0"},{label:"Unique Team Skills",data:"1"}, {label:"Unique Global Skills",data:"3"}]}
            ]
            -->

            <br/>

            <span id="modSignupResult" class="label label-danger"></span>

            <br/><br/>

            <div class="text-center">
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__my_mods">Browse my mods</a>
            </div>

            <script type="application/javascript">
                $("#modSignup").submit(function (event) {
                    event.preventDefault();

                    $.post("./d2mods/signup_insert.php", $("#modSignup").serialize(), function (data) {
                        $("#modSignup :input").each(function () {
                            $(this).val('');
                        });
                        $('#modSignupResult').html(data);
                    }, 'text');
                });
            </script>

        <?php
        } else {
            echo '<div class="page-header"><div class="alert alert-danger" role="alert"><strong>Oh Snap:</strong> No DB!</div></div>';
        }
    } else {
        echo '<div class="page-header"><div class="alert alert-danger" role="alert"><strong>Oh Snap:</strong> Not logged in!</div></div>';
    }
} catch (Exception $e) {
    echo '<div class="page-header"><div class="alert alert-danger" role="alert"><strong>Oh Snap:</strong> Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage() . '</div></div>';
}