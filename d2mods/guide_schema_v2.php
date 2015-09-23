<div class="page-header">
    <h2>Schema for stat-collection-v2
        <small>BETA</small>
    </h2>
</div>

<p>Below is the workflow in terms of data communicated between host and GDS through the various stages of game-play. If
    you wish to record additional information, or items and abilities, then you will need to get in contact with us.</p>

<p>If your mod is round based with progress completely wiped between rounds, then simply start this process from the
    start for each round, so that each round will be considered as a separate match. Otherwise, you need to figure out
    how to encapsulate your player data into rounds. The stat-collection-v2 modules will take a snapshot at the end of
    the game and assume it is a single round.</p>

<hr/>

<h3>Notes</h3>

<ul>
    <li>It is unknown whether players have a connectionState available in Lua before they connect</li>
    <li>It is unknown whether players have a slotID available in Lua before they connect</li>
</ul>

<h3>For Consideration</h3>

<ul>
    <li>Do we want each client to check in via a special endpoint, or even just the host report all of the clients? This
        would let us build a picture of where all of the users are playing from.
    </li>
</ul>

<hr/>

<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">

<!--
////////////////////////////////////////////////////
//Phase 1 - Client - Before Loaders
////////////////////////////////////////////////////
-->
<div class="panel panel-default">
    <div class="panel-heading" role="tab" id="headingOne">
        <h4 class="panel-title">
            <a class="h4 collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseOne"
               aria-expanded="false" aria-controls="collapseOne">
                Phase 1 - Client - Before Loaders
            </a>
        </h4>
    </div>
    <div id="collapseOne" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingOne">
        <div class="panel-body">

            <p>This is for catching all of the games that fail to start due to people not loading.</p>

            <p><strong>Endpoint:</strong> <code>POST http://getdotastats.com/s2/api/s2_phase_1.php || "payload" = <em>JSONschema</em></code>
            </p>

            <hr/>

            <div>
                <div class="row">
                    <div class="col-sm-3"><strong>Key</strong></div>
                    <div class="col-sm-2"><strong>Type</strong></div>
                    <div class="col-sm-3"><strong>Example</strong></div>
                    <div class="col-sm-4"><strong>Notes</strong></div>
                </div>
                <span class="h4">&nbsp;</span>

                <div class="row">
                    <div class="col-sm-3">modID</div>
                    <div class="col-sm-2">string</div>
                    <div class="col-sm-3">"7adfki234jlk23"</div>
                    <div class="col-sm-4">
                        <a class="nav-clickable" href="#d2mods__my_mods">Unique value assigned to your mod</a>
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-3">hostSteamID32</div>
                    <div class="col-sm-2">string</div>
                    <div class="col-sm-7">"2875155"</div>
                </div>

                <div class="row">
                    <div class="col-sm-3">isDedicated</div>
                    <div class="col-sm-2">integer</div>
                    <div class="col-sm-7">0</div>
                </div>

                <div class="row">
                    <div class="col-sm-3">mapName</div>
                    <div class="col-sm-2">string</div>
                    <div class="col-sm-7">"dota_pvp"</div>
                </div>

                <div class="row">
                    <div class="col-sm-3">numPlayers</div>
                    <div class="col-sm-2">integer</div>
                    <div class="col-sm-7">4</div>
                </div>

                <div class="row">
                    <div class="col-sm-3">schemaVersion</div>
                    <div class="col-sm-2">integer</div>
                    <div class="col-sm-7">1</div>
                </div>

                <span class="h4">&nbsp;</span>
            </div>

            <hr/>

            <h3>Example Schema</h3>

            <pre class="pre-scrollable">
{
    "modID": "7adfki234jlk23",
    "hostSteamID32": "2875155",
    "isDedicated": 1,
    "mapName": "dota_pvp",
    "numPlayers": 4,
    "schemaVersion": 1
}
            </pre>

        </div>
    </div>
</div>

<!--
////////////////////////////////////////////////////
//Phase 1 - Server - Before Loaders
////////////////////////////////////////////////////
-->
<div class="panel panel-default">
    <div class="panel-heading" role="tab" id="headingTwo">
        <h4 class="panel-title">
            <a class="h4 collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseTwo"
               aria-expanded="false" aria-controls="collapseTwo">
                Phase 1 - Server - Before Loaders
            </a>
        </h4>
    </div>
    <div id="collapseTwo" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingTwo">
        <div class="panel-body">
            <p>This is the response from the server after receiving the communication from the client for Phase 1. The
                authKey is required for the host to update the match details later, and prevents other clients from
                later changing the match details.</p>

            <pre class="pre-scrollable">
{
    "authKey": "asdfhkj324jklnfadssdafsd",
    "matchID": "21347923432",
    "modID": "7adfki234jlk23",
    "schemaVersion": 1
}
            </pre>
        </div>
    </div>
</div>

<!--
////////////////////////////////////////////////////
//Client Check-In - Request
////////////////////////////////////////////////////
-->
<div class="panel panel-default">
    <div class="panel-heading" role="tab" id="headingOneB">
        <h4 class="panel-title">
            <a class="h4 collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseOneB"
               aria-expanded="false" aria-controls="collapseOneB">
                Client Check-In - Request
            </a>
        </h4>
    </div>
    <div id="collapseOneB" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingOneB">
        <div class="panel-body">

            <p>This is for building an idea of where clients are connecting from.</p>

            <p><strong>Endpoint:</strong> <code>POST http://getdotastats.com/s2/api/s2_check_in.php || "payload" = <em>JSONschema</em></code>
            </p>

            <hr/>

            <div>
                <div class="row">
                    <div class="col-sm-3"><strong>Key</strong></div>
                    <div class="col-sm-2"><strong>Type</strong></div>
                    <div class="col-sm-3"><strong>Example</strong></div>
                    <div class="col-sm-4"><strong>Notes</strong></div>
                </div>
                <span class="h4">&nbsp;</span>

                <div class="row">
                    <div class="col-sm-3">modID</div>
                    <div class="col-sm-2">string</div>
                    <div class="col-sm-3">"7adfki234jlk23"</div>
                    <div class="col-sm-4">
                        <a class="nav-clickable" href="#d2mods__my_mods">Unique value assigned to your mod</a>
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-3">steamID32</div>
                    <div class="col-sm-2">string</div>
                    <div class="col-sm-7">"2875155"</div>
                </div>

                <div class="row">
                    <div class="col-sm-3">matchID</div>
                    <div class="col-sm-2">string</div>
                    <div class="col-sm-7">"213123342"</div>
                </div>

                <span class="h4">&nbsp;</span>
            </div>

            <hr/>

            <h3>Example Schema</h3>

            <pre class="pre-scrollable">
{
    "modID": "7adfki234jlk23",
    "steamID32": "2875155",
    "matchID": "213123342"
}
            </pre>

        </div>
    </div>
</div>

<!--
////////////////////////////////////////////////////
//Client Check-In - Response
////////////////////////////////////////////////////
-->
<div class="panel panel-default">
    <div class="panel-heading" role="tab" id="headingTwoB">
        <h4 class="panel-title">
            <a class="h4 collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseTwoB"
               aria-expanded="false" aria-controls="collapseTwoB">
                Client Check-In - Response
            </a>
        </h4>
    </div>
    <div id="collapseTwoB" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingTwoB">
        <div class="panel-body">
            <p>This is the response from the server after receiving the communication from the client for the
                check-in.</p>

            <pre class="pre-scrollable">
{
    "result": 0,
    "error": "Unknown Error"
}
            </pre>
        </div>
    </div>
</div>

<!--
////////////////////////////////////////////////////
//Phase 2 - Client - Pre Game
////////////////////////////////////////////////////
-->
<div class="panel panel-default">
    <div class="panel-heading" role="tab" id="headingThree">
        <h4 class="panel-title">
            <a class="h4 collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseThree"
               aria-expanded="false" aria-controls="collapseThree">
                Phase 2 - Client - Pre Game
            </a>
        </h4>
    </div>
    <div id="collapseThree" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingThree">
        <div class="panel-body">
            <p>This is for catching all of the games that crash, and understanding what heroes and game modes are
                played.</p>

            <p><strong>Endpoint:</strong> <code>POST http://getdotastats.com/s2/api/s2_phase_2.php || "payload" = <em>JSONschema</em></code>
            </p>

            <hr/>

            <div>
                <div class="row">
                    <div class="col-sm-3"><strong>Key</strong></div>
                    <div class="col-sm-2"><strong>Type</strong></div>
                    <div class="col-sm-3"><strong>Example</strong></div>
                    <div class="col-sm-4"><strong>Notes</strong></div>
                </div>
                <span class="h4">&nbsp;</span>

                <div class="row">
                    <div class="col-sm-3">authKey</div>
                    <div class="col-sm-2">string</div>
                    <div class="col-sm-3">"asdfhkj324jklnfadssdafsd"</div>
                    <div class="col-sm-4">Obtained by pre-match API</div>
                </div>

                <div class="row">
                    <div class="col-sm-3">matchID</div>
                    <div class="col-sm-2">string</div>
                    <div class="col-sm-3">"21347923432"</div>
                    <div class="col-sm-4">Obtained by pre-match API</div>
                </div>

                <div class="row">
                    <div class="col-sm-3">modID</div>
                    <div class="col-sm-2">string</div>
                    <div class="col-sm-7">"7adfki234jlk23"</div>
                </div>

                <div class="row">
                    <div class="col-sm-3">flags</div>
                    <div class="col-sm-2">array</div>
                    <div class="col-sm-3">{"mode": "ctf15", "winCondition": "kill50", "crazyCouriers": 1}</div>
                    <div class="col-sm-4">Un-structured and indicate lobby options</div>
                </div>

                <div class="row">
                    <div class="col-sm-3">schemaVersion</div>
                    <div class="col-sm-2">integer</div>
                    <div class="col-sm-7">1</div>
                </div>

                <!--players array-->

                <div class="row">
                    <div class="col-sm-3">players</div>
                    <div class="col-sm-2">key-value array</div>
                    <div class="col-sm-7">&nbsp;</div>
                </div>

                <div class="row">
                    <div class="col-sm-1">&nbsp;</div>
                    <div class="col-sm-2">playerName</div>
                    <div class="col-sm-2">string</div>
                    <div class="col-sm-7">"jimmydorry"</div>
                </div>

                <div class="row">
                    <div class="col-sm-1">&nbsp;</div>
                    <div class="col-sm-2">steamID32</div>
                    <div class="col-sm-2">string</div>
                    <div class="col-sm-7">"2875155"</div>
                </div>

                <div class="row">
                    <div class="col-sm-1">&nbsp;</div>
                    <div class="col-sm-2">teamID</div>
                    <div class="col-sm-2">integer</div>
                    <div class="col-sm-7">2</div>
                </div>

                <div class="row">
                    <div class="col-sm-1">&nbsp;</div>
                    <div class="col-sm-2">slotID</div>
                    <div class="col-sm-2">integer</div>
                    <div class="col-sm-7">1</div>
                </div>

                <div class="row">
                    <div class="col-sm-1">&nbsp;</div>
                    <div class="col-sm-2">heroID</div>
                    <div class="col-sm-2">integer</div>
                    <div class="col-sm-7">15</div>
                </div>

                <div class="row">
                    <div class="col-sm-1">&nbsp;</div>
                    <div class="col-sm-2">connectionState</div>
                    <div class="col-sm-2">integer</div>
                    <div class="col-sm-3">2</div>
                    <div class="col-sm-4">
                        0 = unknown<br/>
                        1 = not yet connected<br/>
                        2 = connected<br/>
                        3 = disconnected match<br/>
                        4 = abandoned<br/>
                        5 = loading<br/>
                        6 = failed<br/>
                        <a target="_blank"
                           href="https://github.com/SteamRE/SteamKit/blob/f6c0578506690d63a2b159340fe19835fe33564c/Resources/Protobufs/dota/dota_gcmessages_common.proto#L564">Refer
                            to enum DOTAConnectionState_t</a>
                    </div>
                </div>

                <span class="h4">&nbsp;</span>
            </div>

            <hr/>

            <h3>Example Schema</h3>

            <pre class="pre-scrollable">
{
    "authKey": "asdfhkj324jklnfadssdafsd",
    "matchID": "21347923432",
    "modID": "7adfki234jlk23",
    "flags": {
        "mode": "ctf15",
        "winCondition": "kill50",
        "crazyCouriers": 1
    },
    "schemaVersion": 1,
    "players": [
        {
            "playerName": "jimmydorry",
            "steamID32": "2875155",
            "teamID": 2,
            "slotID": 1,
            "heroID": 15,
            "connectionState": 2
        },
        {
            "playerName": "ash47",
            "steamID32": "2875156",
            "teamID": 3,
            "slotID": 2,
            "heroID": 22,
            "connectionState": 2
        },
        {
            "playerName": "BMD",
            "steamID32": "2875157",
            "teamID": 4,
            "slotID": 3,
            "heroID": 33,
            "connectionState": 2
        },
        {
            "playerName": "sinz",
            "steamID32": "2875158",
            "teamID": 5,
            "slotID": 4,
            "heroID": 2,
            "connectionState": 2
        }
    ]
}
            </pre>
        </div>
    </div>
</div>

<!--
////////////////////////////////////////////////////
//Phase 2 - Server - Pre Game
////////////////////////////////////////////////////
-->
<div class="panel panel-default">
    <div class="panel-heading" role="tab" id="headingFour">
        <h4 class="panel-title">
            <a class="h4 collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseFour"
               aria-expanded="false" aria-controls="collapseFour">
                Phase 2 - Server - Pre Game
            </a>
        </h4>
    </div>
    <div id="collapseFour" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingFour">
        <div class="panel-body">
            <p>This is the response from the server after receiving the communication from the client for Phase 2. The
                result field will either be 0 or 1. A result of 0 indicates there was a failure. There may be an
                accompanying textual error, for debugging purposes.</p>

            <pre class="pre-scrollable">
{
    "result": 0,
    "error": "Bad JSON"
}
            </pre>
        </div>
    </div>
</div>

<!--
////////////////////////////////////////////////////
//Phase 3 - Client - End Game
////////////////////////////////////////////////////
-->
<div class="panel panel-default">
<div class="panel-heading" role="tab" id="headingFive">
    <h4 class="panel-title">
        <a class="h4 collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseFive"
           aria-expanded="false" aria-controls="collapseFive">
            Phase 3 - Client - End Game
        </a>
    </h4>
</div>
<div id="collapseFive" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingFive">
<div class="panel-body">
<p>This is for catching all of the games that properly end. The main difference here is that the resulting data can be
    broken down into rounds.</p>

<p><strong>Endpoint:</strong> <code>POST http://getdotastats.com/s2/api/s2_phase_3.php || "payload" =
        <em>JSONschema</em></code></p>

<hr/>

<div>
    <div class="row">
        <div class="col-sm-3"><strong>Key</strong></div>
        <div class="col-sm-2"><strong>Type</strong></div>
        <div class="col-sm-3"><strong>Example</strong></div>
        <div class="col-sm-4"><strong>Notes</strong></div>
    </div>
    <span class="h4">&nbsp;</span>

    <div class="row">
        <div class="col-sm-3">authKey</div>
        <div class="col-sm-2">string</div>
        <div class="col-sm-3">"asdfhkj324jklnfadssdafsd"</div>
        <div class="col-sm-4">Obtained by pre-match API</div>
    </div>

    <div class="row">
        <div class="col-sm-3">matchID</div>
        <div class="col-sm-2">string</div>
        <div class="col-sm-3">"21347923432"</div>
        <div class="col-sm-4">Obtained by pre-match API</div>
    </div>

    <div class="row">
        <div class="col-sm-3">modID</div>
        <div class="col-sm-2">string</div>
        <div class="col-sm-7">"7adfki234jlk23"</div>
    </div>

    <div class="row">
        <div class="col-sm-3">winningTeam</div>
        <div class="col-sm-2">integer</div>
        <div class="col-sm-7">5</div>
    </div>

    <div class="row">
        <div class="col-sm-3">gameDuration</div>
        <div class="col-sm-2">integer</div>
        <div class="col-sm-7">3954</div>
    </div>

    <div class="row">
        <div class="col-sm-3">schemaVersion</div>
        <div class="col-sm-2">integer</div>
        <div class="col-sm-7">1</div>
    </div>

    <!--players array-->

    <div class="row">
        <div class="col-sm-3">rounds</div>
        <div class="col-sm-2">array</div>
        <div class="col-sm-7">&nbsp;</div>
    </div>

    <div class="row">
        <div class="col-sm-1">&nbsp;</div>
        <div class="col-sm-3">players</div>
        <div class="col-sm-2">key-value array</div>
        <div class="col-sm-6">&nbsp;</div>
    </div>

    <div class="row">
        <div class="col-sm-2">&nbsp;</div>
        <div class="col-sm-2">playerName</div>
        <div class="col-sm-2">string</div>
        <div class="col-sm-6">"jimmydorry"</div>
    </div>

    <div class="row">
        <div class="col-sm-2">&nbsp;</div>
        <div class="col-sm-2">steamID32</div>
        <div class="col-sm-2">string</div>
        <div class="col-sm-6">"2875155"</div>
    </div>

    <div class="row">
        <div class="col-sm-2">&nbsp;</div>
        <div class="col-sm-2">teamID</div>
        <div class="col-sm-2">integer</div>
        <div class="col-sm-6">2</div>
    </div>

    <div class="row">
        <div class="col-sm-2">&nbsp;</div>
        <div class="col-sm-2">slotID</div>
        <div class="col-sm-2">integer</div>
        <div class="col-sm-6">1</div>
    </div>

    <div class="row">
        <div class="col-sm-2">&nbsp;</div>
        <div class="col-sm-2">heroID</div>
        <div class="col-sm-2">integer</div>
        <div class="col-sm-6">15</div>
    </div>

    <div class="row">
        <div class="col-sm-2">&nbsp;</div>
        <div class="col-sm-2">connectionState</div>
        <div class="col-sm-2">integer</div>
        <div class="col-sm-6">2</div>
    </div>

    <span class="h4">&nbsp;</span>
</div>

<hr/>

<h3>Example Schema</h3>

            <pre class="pre-scrollable">
{
    "authKey": "asdfhkj324jklnfadssdafsd",
    "matchID": "21347923432",
    "modID": "7adfki234jlk23",
    "winningTeam": 5,
    "gameDuration": 3954,
    "schemaVersion": 1,
    "rounds": [
        {
            "players": [
                {
                    "playerName": "jimmydorry",
                    "steamID32": "2875155",
                    "teamID": 2,
                    "slotID": 1,
                    "heroID": 15,
                    "connectionState": 2
                },
                {
                    "playerName": "ash47",
                    "steamID32": "2875156",
                    "teamID": 3,
                    "slotID": 2,
                    "heroID": 22,
                    "connectionState": 2
                },
                {
                    "playerName": "BMD",
                    "steamID32": "2875157",
                    "teamID": 4,
                    "slotID": 3,
                    "heroID": 33,
                    "connectionState": 2
                },
                {
                    "playerName": "sinz",
                    "steamID32": "2875158",
                    "teamID": 5,
                    "slotID": 4,
                    "heroID": 2,
                    "connectionState": 2
                }
            ]
        },
        {
            "players": [
                {
                    "playerName": "jimmydorry",
                    "steamID32": "2875155",
                    "teamID": 2,
                    "slotID": 1,
                    "heroID": 15,
                    "connectionState": 2
                },
                {
                    "playerName": "ash47",
                    "steamID32": "2875156",
                    "teamID": 3,
                    "slotID": 2,
                    "heroID": 22,
                    "connectionState": 2
                },
                {
                    "playerName": "BMD",
                    "steamID32": "2875157",
                    "teamID": 4,
                    "slotID": 3,
                    "heroID": 33,
                    "connectionState": 2
                },
                {
                    "playerName": "sinz",
                    "steamID32": "2875158",
                    "teamID": 5,
                    "slotID": 4,
                    "heroID": 2,
                    "connectionState": 2
                }
            ]
        }
    ]
}
            </pre>
</div>
</div>
</div>

<!--
////////////////////////////////////////////////////
//Phase 3 - Server - End Game
////////////////////////////////////////////////////
-->
<div class="panel panel-default">
    <div class="panel-heading" role="tab" id="headingSix">
        <h4 class="panel-title">
            <a class="h4 collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseSix"
               aria-expanded="false" aria-controls="collapseSix">
                Phase 3 - Server - End Game
            </a>
        </h4>
    </div>
    <div id="collapseSix" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingSix">
        <div class="panel-body">
            <p>This is the response from the server after receiving the communication from the client for Phase 3. The
                result field will either be 0 or 1. A result of 0 indicates there was a failure. There may be an
                accompanying textual error, for debugging purposes.</p>

            <pre class="pre-scrollable">
{
    "result": 0,
    "error" : "Bad JSON"
}
            </pre>
        </div>
    </div>
</div>

<!--
////////////////////////////////////////////////////
//Client - CUSTOM
////////////////////////////////////////////////////
-->
<div class="panel panel-default">
    <div class="panel-heading" role="tab" id="headingSeven">
        <h4 class="panel-title">
            <a class="h4 collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseSeven"
               aria-expanded="false" aria-controls="collapseSeven">
                Client - CUSTOM DATA
            </a>
        </h4>
    </div>
    <div id="collapseSeven" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingSeven">
        <div class="panel-body">
            <p>If your mod wishes to capture additional data (such as scoring, items or abilities), you will need to
                create a schema
                and submit it to the site for discussion. Each implementation will be unique.</p>

            <p><strong>Endpoint:</strong> <code>N/A</code></p>

            <hr/>

            <div>
                <div class="row">
                    <div class="col-sm-3"><strong>Key</strong></div>
                    <div class="col-sm-2"><strong>Type</strong></div>
                    <div class="col-sm-3"><strong>Example</strong></div>
                    <div class="col-sm-4"><strong>Notes</strong></div>
                </div>
                <span class="h4">&nbsp;</span>

                <div class="row">
                    <div class="col-sm-3">authKey</div>
                    <div class="col-sm-2">string</div>
                    <div class="col-sm-3">"asdfhkj324jklnfadssdafsd"</div>
                    <div class="col-sm-4">Obtained by pre-match API</div>
                </div>

                <div class="row">
                    <div class="col-sm-3">matchID</div>
                    <div class="col-sm-2">string</div>
                    <div class="col-sm-3">"21347923432"</div>
                    <div class="col-sm-4">Obtained by pre-match API</div>
                </div>

                <div class="row">
                    <div class="col-sm-3">modID</div>
                    <div class="col-sm-2">string</div>
                    <div class="col-sm-7">"7adfki234jlk23"</div>
                </div>

                <div class="row">
                    <div class="col-sm-3">schemaAuthKey</div>
                    <div class="col-sm-2">string</div>
                    <div class="col-sm-3">"jh345235ljhfads"</div>
                    <div class="col-sm-4">Obtained by schema obtaining approval</div>
                </div>

                <div class="row">
                    <div class="col-sm-3">schemaVersion</div>
                    <div class="col-sm-2">integer</div>
                    <div class="col-sm-7">1</div>
                </div>

                <!--players array-->

                <div class="row">
                    <div class="col-sm-3">rounds</div>
                    <div class="col-sm-2">array</div>
                    <div class="col-sm-7">&nbsp;</div>
                </div>

                <div class="row">
                    <div class="col-sm-1">&nbsp;</div>
                    <div class="col-sm-3">players</div>
                    <div class="col-sm-2">key-value array</div>
                    <div class="col-sm-6">&nbsp;</div>
                </div>

                <div class="row">
                    <div class="col-sm-2">&nbsp;</div>
                    <div class="col-sm-2">steamID32</div>
                    <div class="col-sm-2">string</div>
                    <div class="col-sm-6">"2875155"</div>
                </div>

                <div class="row">
                    <div class="col-sm-2">&nbsp;</div>
                    <div class="col-sm-2">customValue1</div>
                    <div class="col-sm-2">???</div>
                    <div class="col-sm-6">XXXX</div>
                </div>

                <div class="row">
                    <div class="col-sm-2">&nbsp;</div>
                    <div class="col-sm-2">customValue2</div>
                    <div class="col-sm-2">???</div>
                    <div class="col-sm-6">YYYY</div>
                </div>

                <span class="h4">&nbsp;</span>
            </div>

            <hr/>

            <h3>Example Schema</h3>

            <pre class="pre-scrollable">
{
    "authKey": "asdfhkj324jklnfadssdafsd",
    "matchID": "21347923432",
    "modID": "7adfki234jlk23",
    "schemaAuthKey": "jh345235ljhfads",
    "schemaVersion": 1,
    "rounds": [
        {
            "players": [
                {
                    "steamID32": "2875155",
                    "score": 242,
                    "customValue1": "XXXX",
                    "customValue2": "XXXX"
                },
                {
                    "steamID32": "2875156",
                    "score": 123,
                    "customValue1": "XXXX",
                    "customValue2": "XXXX"
                },
                {
                    "steamID32": "2875157",
                    "score": 453,
                    "customValue1": "XXXX",
                    "customValue2": "XXXX"
                },
                {
                    "steamID32": "2875158",
                    "score": 3,
                    "customValue1": "XXXX",
                    "customValue2": "XXXX"
                }
            ]
        },
        {
            "players": [
                {
                    "steamID32": "2875155",
                    "score": 546,
                    "customValue1": "XXXX",
                    "customValue2": "XXXX"
                },
                {
                    "steamID32": "2875156",
                    "score": 432,
                    "customValue1": "XXXX",
                    "customValue2": "XXXX"
                },
                {
                    "steamID32": "2875157",
                    "score": 7,
                    "customValue1": "XXXX",
                    "customValue2": "XXXX"
                },
                {
                    "steamID32": "2875158",
                    "score": 97,
                    "customValue1": "XXXX",
                    "customValue2": "XXXX"
                }
            ]
        }
    ]
}
            </pre>

        </div>
    </div>
</div>


<!--
////////////////////////////////////////////////////
//Server - CUSTOM
////////////////////////////////////////////////////
-->
<div class="panel panel-default">
    <div class="panel-heading" role="tab" id="headingEight">
        <h4 class="panel-title">
            <a class="h4 collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseEight"
               aria-expanded="false" aria-controls="collapseEight">
                Server - CUSTOM DATA
            </a>
        </h4>
    </div>
    <div id="collapseEight" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingEight">
        <div class="panel-body">
            <p>This is the response from the server after receiving the communication from the client for CUSTOM DATA.
                The
                result field will either be 0 or 1. A result of 0 indicates there was a failure. There may be an
                accompanying textual error, for debugging purposes.</p>

            <pre class="pre-scrollable">
{
    "result": 0,
    "error": "Bad JSON"
}
            </pre>
        </div>
    </div>
</div>


</div>

<hr/>


<h3>Latest Data</h3>
<p>Below is a simple table showing the five latest matches recorded. It will serve as a debugging tool while we setup a
    more sophisticated testing environment.</p>

<?php
require_once('../global_functions.php');
require_once('../connections/parameters.php');

try {
    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    $latestData = cached_query('s2_latest_data',
        'SELECT *
            FROM `s2_match`
            ORDER BY `dateUpdated` DESC, `matchID` DESC
            LIMIT 0,5;',
        NULL,
        NULL,
        5
    );

    if (!empty($latestData)) {
        foreach ($latestData as $key => $value) {
            echo '<div class="row">
                    <div class="col-sm-1"><strong>matchID</strong></div>
                    <div class="col-sm-3"><strong>modID</strong></div>
                    <div class="col-sm-4">
                        <div class="row">
                            <div class="col-sm-3"><strong>Phase</strong></div>
                            <div class="col-sm-3"><strong>Players</strong></div>
                            <div class="col-sm-3"><strong>Rounds</strong></div>
                            <div class="col-sm-3"><strong>Duration</strong></div>
                        </div>
                    </div>
                    <div class="col-sm-1">&nbsp;</div>
                    <div class="col-sm-3"><strong>Recorded</strong></div>
                </div>
                <span class="h4">&nbsp;</span>
                ';

            $numPlayers = !empty($value['numPlayers']) && is_numeric($value['numPlayers'])
                ? $value['numPlayers']
                : 0;

            $numRounds = !empty($value['numRounds']) && is_numeric($value['numRounds'])
                ? $value['numRounds']
                : 1;

            $duration = !empty($value['matchDuration']) && is_numeric($value['matchDuration'])
                ? $value['matchDuration']
                : '??';

            $relativeDateRaw = relative_time_v3($value['dateUpdated'], 'hour', true);

            $timeColour = $relativeDateRaw['number'] <= 2
                ? ' hs_lb_recent_score'
                : '';

            $newBadge = $relativeDateRaw['number'] <= 2
                ? ' <span class="badge">!</span>'
                : '';

            echo '<div class="row">
                <div class="col-sm-1">' . $value['matchID'] . '</div>
                <div class="col-sm-3"><span class="db_link">' . $value['modID'] . '</span></div>
                <div class="col-sm-4">
                    <div class="row">
                        <div class="col-sm-3">' . $value['matchPhaseID'] . '</div>
                        <div class="col-sm-3">' . $numPlayers . '</div>
                        <div class="col-sm-3">' . $numRounds . '</div>
                        <div class="col-sm-3">' . $duration . '</div>
                    </div>
                </div>
                <div class="col-sm-1">&nbsp;</div>
                <div class="col-sm-3"><span class="' . $timeColour . '">' . relative_time_v3($value['dateUpdated'], 1) . $newBadge . '</span></div>
            </div>
            ';

            echo '<span class="h4">&nbsp;</span>';

            /////////////////////////////
            //Match Full Details
            /////////////////////////////

            $fullDetailsTemp = $value;
            unset($fullDetailsTemp['matchID']);
            unset($fullDetailsTemp['modID']);
            unset($fullDetailsTemp['matchPhaseID']);
            unset($fullDetailsTemp['numPlayers']);
            unset($fullDetailsTemp['numRounds']);
            unset($fullDetailsTemp['matchDuration']);
            unset($fullDetailsTemp['isDedicated']);
            unset($fullDetailsTemp['dateUpdated']);
            unset($fullDetailsTemp['dateRecorded']);

            echo '<div class="row">
                    <div class="col-sm-1">&nbsp;</div>
                    <div class="col-sm-1"><strong>Match</strong></div>
                    <div class="col-sm-10">' . json_encode($fullDetailsTemp) . '</div>
                </div>';

            /////////////////////////////
            //FLAG LIST
            /////////////////////////////
            $latestFlagData = cached_query('s2_latest_flag_data' . $value['matchID'],
                'SELECT
                      `matchID`,
                      `modID`,
                      `flagName`,
                      `flagValue`,
                      `dateRecorded`
                    FROM `s2_match_flags`
                    WHERE `matchID` = ?
                    ORDER BY `dateRecorded` DESC;',
                's',
                array(
                    $value['matchID']
                ),
                5
            );

            if (!empty($latestFlagData)) {
                $flagList = array();

                foreach ($latestFlagData as $key2 => $value2) {
                    $flagList[$value2['flagName']] = $value2['flagValue'];
                }

                echo '<div class="row">
                        <div class="col-sm-1">&nbsp;</div>
                        <div class="col-sm-1"><strong>Flags</strong></div>
                        <div class="col-sm-10">' . json_encode($flagList) . '</div>
                    </div>';
            } else {
                echo '<div class="row">
                        <div class="col-sm-1">&nbsp;</div>
                        <div class="col-sm-1"><strong>Flags</strong></div>
                        <div class="col-sm-10">????</div>
                    </div>';
            }

            /////////////////////////////
            //Client LIST
            /////////////////////////////
            $latestClientData = cached_query('s2_latest_client_data' . $value['matchID'],
                'SELECT
                      `matchID`,
                      `modID`,
                      `steamID32`,
                      `steamID64`,
                      `clientIP`,
                      `isHost`,
                      `dateRecorded`
                    FROM `s2_match_client_details`
                    WHERE `matchID` = ?
                    ORDER BY `dateRecorded` DESC;',
                's',
                array(
                    $value['matchID']
                ),
                5
            );

            if (!empty($latestClientData)) {
                $clientList = array();

                foreach ($latestClientData as $key2 => $value2) {
                    $clientList[] = $value2['clientIP'];
                }

                echo '<div class="row">
                        <div class="col-sm-1">&nbsp;</div>
                        <div class="col-sm-1"><strong>Clients</strong></div>
                        <div class="col-sm-10">' . json_encode($clientList) . '</div>
                    </div>';
            } else {
                echo '<div class="row">
                        <div class="col-sm-1">&nbsp;</div>
                        <div class="col-sm-1"><strong>Clients</strong></div>
                        <div class="col-sm-10">????</div>
                    </div>';
            }


            /////////////////////////////
            //Players LIST
            /////////////////////////////
            $latestPlayerData = cached_query('s2_latest_player_data' . $value['matchID'],
                'SELECT *
                    FROM `s2_match_players`
                    WHERE `matchID` = ?
                    ORDER BY `dateRecorded` DESC;',
                's',
                array(
                    $value['matchID']
                ),
                5
            );

            if (!empty($latestPlayerData)) {
                $playerList = array();

                foreach ($latestPlayerData as $key2 => $value2) {
                    unset($value2['matchID']);
                    unset($value2['modID']);
                    unset($value2['steamID64']);
                    unset($value2['isWinner']);
                    unset($value2['dateRecorded']);
                    $playerList[] = $value2;
                }

                echo '<div class="row">
                        <div class="col-sm-1">&nbsp;</div>
                        <div class="col-sm-1"><strong>Players</strong></div>
                        <div class="col-sm-10">' . json_encode($playerList) . '</div>
                    </div>';
            } else {
                echo '<div class="row">
                        <div class="col-sm-1">&nbsp;</div>
                        <div class="col-sm-1"><strong>Players</strong></div>
                        <div class="col-sm-10">????</div>
                    </div>';
            }


            echo '<hr />';

            echo '<span class="h4">&nbsp;</span>';
        }
    } else {
        echo bootstrapMessage('Oh Snap', 'No data recorded yet!.', 'danger');
    }

    echo '<span class="h4">&nbsp;</span>';

    echo '<div class="text-center">
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__guide">Developer Guide</a>
           </div>';

    echo '<span class="h4">&nbsp;</span>';

} catch (Exception $e) {
    echo formatExceptionHandling($e);
} finally {
    if (isset($memcache)) $memcache->close();
}