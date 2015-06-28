<div class="page-header">
    <h2>Schema for stat-collection Library v2
        <small>BETA</small>
    </h2>
</div>

<p>Below is the workflow in terms of data communicated between host and GDS through the various stages of game-play. If
    you wish to record additional information, or items and abilities, then you will need to get in contact with us.</p>

<p>If your mod is round based with progress completely wiped between rounds, then simply start this process from the
    start for each round. Each round will be considered as a separate match.</p>

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
                    <div class="col-sm-3">flags</div>
                    <div class="col-sm-2">array</div>
                    <div class="col-sm-3">["ctf15", "kill50"]</div>
                    <div class="col-sm-4">Un-structured and indicate lobby options</div>
                </div>

                <div class="row">
                    <div class="col-sm-3">gamePhase</div>
                    <div class="col-sm-2">integer</div>
                    <div class="col-sm-3">1</div>
                    <div class="col-sm-4">Must have value of 1 in this phase.</div>
                </div>

                <div class="row">
                    <div class="col-sm-3">isDedicated</div>
                    <div class="col-sm-2">integer</div>
                    <div class="col-sm-7">0</div>
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

            <!--<pre class="pre-scrollable">
            [
                {
                    "authKey": "asdfhkj324jklnfadssdafsd",
                    "matchID": "21347923432",
                    "modID": "7adfki234jlk23",
                    "hostSteamID32": "2875155",
                    "flags": [
                        "ctf15",
                        "kill50"
                    ],
                    "gameStarted": 1,
                    "isDedicated": 1,
                    "winningTeam": 5,
                    "gameDuration": 3954,
                    "numPlayers": 4,
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
            ]
            </pre>-->

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
[
    {
        "authKey": "asdfhkj324jklnfadssdafsd",
        "matchID": "21347923432",
        "modID": "7adfki234jlk23",
        "schemaVersion": 1
    }
]
</pre>
        </div>
    </div>
</div>

<!--
////////////////////////////////////////////////////
//Phase 2 - Client - After Loaders
////////////////////////////////////////////////////
-->
<div class="panel panel-default">
    <div class="panel-heading" role="tab" id="headingThree">
        <h4 class="panel-title">
            <a class="h4 collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseThree"
               aria-expanded="false" aria-controls="collapseThree">
                Phase 2 - Client - After Loaders
            </a>
        </h4>
    </div>
    <div id="collapseThree" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingThree">
        <div class="panel-body">
            <p>This is for catching all of the games that crash.</p>

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
                <div class="col-sm-3">gamePhase</div>
                <div class="col-sm-2">integer</div>
                <div class="col-sm-3">1</div>
                <div class="col-sm-4">Must have value of 2 in this phase.</div>
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

            <hr/>

            <h3>Example Schema</h3>

            <!--<pre class="pre-scrollable">
            [
                {
                    "authKey": "asdfhkj324jklnfadssdafsd",
                    "matchID": "21347923432",
                    "modID": "7adfki234jlk23",
                    "hostSteamID32": "2875155",
                    "flags": [
                        "ctf15",
                        "kill50"
                    ],
                    "gameStarted": 1,
                    "isDedicated": 1,
                    "winningTeam": 5,
                    "gameDuration": 3954,
                    "numPlayers": 4,
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
            ]
            </pre>-->
        </div>
    </div>
</div>

<!--
////////////////////////////////////////////////////
//Phase 2 - Server - After Loaders
////////////////////////////////////////////////////
-->
<div class="panel panel-default">
    <div class="panel-heading" role="tab" id="headingFour">
        <h4 class="panel-title">
            <a class="h4 collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseFour"
               aria-expanded="false" aria-controls="collapseFour">
                Phase 2 - Server - After Loaders
            </a>
        </h4>
    </div>
    <div id="collapseFour" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingFour">
        <div class="panel-body">
            <p>This is the response from the server after receiving the communication from the client for Phase 2. The
                result field will either be 0 or 1. A result of 0 indicates there was a failure. There may be an
                accompanying textual error, for debugging purposes.</p>

<pre class="pre-scrollable">
[
    {
        "result": 0,
        "resultError" : "Bad JSON"
    }
]
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
            <p>This is for catching all of the games that crash.</p>

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
                <div class="col-sm-3">gamePhase</div>
                <div class="col-sm-2">integer</div>
                <div class="col-sm-3">1</div>
                <div class="col-sm-4">Must have value of 3 in this phase.</div>
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
                <div class="col-sm-3">numPlayers</div>
                <div class="col-sm-2">integer</div>
                <div class="col-sm-7">4</div>
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
                <div class="col-sm-7">2</div>
            </div>

            <span class="h4">&nbsp;</span>

            <hr/>

            <h3>Example Schema</h3>

<pre class="pre-scrollable">
[
    {
        "authKey": "asdfhkj324jklnfadssdafsd",
        "matchID": "21347923432",
        "modID": "7adfki234jlk23",
        "hostSteamID32": "2875155",
        "flags": [
            "ctf15",
            "kill50"
        ],
        "gameStarted": 1,
        "isDedicated": 1,
        "winningTeam": 5,
        "gameDuration": 3954,
        "numPlayers": 4,
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
]
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
[
    {
        "result": 0,
        "resultError" : "Bad JSON"
    }
]
</pre>
        </div>
    </div>
</div>


</div>
