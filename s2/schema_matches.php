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

<p>We have just finished updating this library. We are working on making a simple guide, and ensuring this library is as
    easy to use as possible. If you are interested in testing or helping us, get in <a class="nav-clickable"
                                                                                       href="#contact">contact</a> with
    us.</p>

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
                    <div class="col-sm-3">modIdentifier</div>
                    <div class="col-sm-2">string</div>
                    <div class="col-sm-3">"7adfki234jlk23"</div>
                    <div class="col-sm-4">
                        <a class="nav-clickable" href="#s2__my__mods">Unique value assigned to your mod</a>
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
    "modIdentifier": "7adfki234jlk23",
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
    "modIdentifier": "7adfki234jlk23",
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
                    <div class="col-sm-3">modIdentifier</div>
                    <div class="col-sm-2">string</div>
                    <div class="col-sm-3">"7adfki234jlk23"</div>
                    <div class="col-sm-4">
                        <a class="nav-clickable" href="#s2__my__mods">Unique value assigned to your mod</a>
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
    "modIdentifier": "7adfki234jlk23",
    "steamID32": "2875155",
    "matchID": "213123342",
    "schemaVersion": 1
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
                    <div class="col-sm-3">modIdentifier</div>
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

                <span class="h4">&nbsp;</span>

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
    "modIdentifier": "7adfki234jlk23",
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
            "connectionState": 2
        },
        {
            "playerName": "ash47",
            "steamID32": "2875156",
            "connectionState": 2
        },
        {
            "playerName": "BMD",
            "steamID32": "2875157",
            "connectionState": 2
        },
        {
            "playerName": "sinz",
            "steamID32": "2875158",
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
            <p>This is for catching all of the games that properly end. The main difference here is that the resulting
                data can be
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
                    <div class="col-sm-3">modIdentifier</div>
                    <div class="col-sm-2">string</div>
                    <div class="col-sm-7">"7adfki234jlk23"</div>
                </div>

                <div class="row">
                    <div class="col-sm-3">gameDuration</div>
                    <div class="col-sm-2">integer</div>
                    <div class="col-sm-7">3954</div>
                </div>

                <div class="row">
                    <div class="col-sm-3">gameFinished</div>
                    <div class="col-sm-2">integer</div>
                    <div class="col-sm-3">1</div>
                    <div class="col-sm-4">Default value of 1 if not defined</div>
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
                    <div class="col-sm-2">players</div>
                    <div class="col-sm-2">array</div>
                    <div class="col-sm-7">&nbsp;</div>
                </div>

                <span class="h4">&nbsp;</span>

                <div class="row">
                    <div class="col-sm-2">&nbsp;</div>
                    <div class="col-sm-2">steamID32</div>
                    <div class="col-sm-2">string</div>
                    <div class="col-sm-6">"2875155"</div>
                </div>

                <div class="row">
                    <div class="col-sm-2">&nbsp;</div>
                    <div class="col-sm-2">isWinner</div>
                    <div class="col-sm-2">integer</div>
                    <div class="col-sm-6">1</div>
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
    "matchID": 21347923432,
    "modIdentifier": "7adfki234jlk23",
    "gameDuration": 3954,
    "schemaVersion": 1,
    "rounds": {
        "0": {
            "players": [
                {
                    "steamID32": "2875155",
                    "isWinner": 1,
                    "connectionState": 2
                },
                {
                    "steamID32": "2875156",
                    "isWinner": 0,
                    "connectionState": 2
                },
                {
                    "steamID32": "2875157",
                    "isWinner": 0,
                    "connectionState": 2
                },
                {
                    "steamID32": "2875158",
                    "isWinner": 1,
                    "connectionState": 2
                },
                {
                    "steamID32": "0",
                    "isWinner": 1,
                    "connectionState": 2
                },
                {
                    "steamID32": "0",
                    "isWinner": 0,
                    "connectionState": 2
                }
            ]
        },
        "1": {
            "players": [
                {
                    "steamID32": "2875155",
                    "isWinner": 1,
                    "connectionState": 2
                },
                {
                    "steamID32": "2875156",
                    "isWinner": 0,
                    "connectionState": 2
                },
                {
                    "steamID32": "2875157",
                    "isWinner": 0,
                    "connectionState": 2
                },
                {
                    "steamID32": "2875158",
                    "isWinner": 0,
                    "connectionState": 2
                },
                {
                    "steamID32": "0",
                    "isWinner": 0,
                    "connectionState": 2
                },
                {
                    "steamID32": "0",
                    "isWinner": 1,
                    "connectionState": 2
                }
            ]
        }
    }
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
            <p>If your mod wishes to capture additional data (such as scoring, items or abilities), the developer will
                need to contact an admin and have their schema entered and approved in the system (<a
                    class="nav-clickable" href="#admin__mod_schema">HERE</a>). Each implementation is unique and
                requires careful planning for current and future needs.</p>

            <p><strong>Endpoint:</strong> <code>POST http://getdotastats.com/s2/api/s2_custom.php || "payload" =
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
                    <div class="col-sm-3">modIdentifier</div>
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

                <div class="row">
                    <div class="col-sm-3">rounds</div>
                    <div class="col-sm-2">array</div>
                    <div class="col-sm-7">&nbsp;</div>
                </div>

                <!--game array-->

                <div class="row">
                    <div class="col-sm-1">&nbsp;</div>
                    <div class="col-sm-3">game</div>
                    <div class="col-sm-2">key-value array</div>
                    <div class="col-sm-6">&nbsp;</div>
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

                <!--players array-->

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
    "modIdentifier": "7adfki234jlk23",
    "schemaAuthKey": "K65S5J7HFD",
    "schemaVersion": 1,
    "rounds": {
        "0": {
            "game": {
                    "customValue1": "XXXX",
                    "customValue2": "XXXX"
            },
            "players": [
                {
                    "steamID32": "2875155",
                    "customValue1": "XXXX",
                    "customValue2": "XXXX"
                },
                {
                    "steamID32": "2875156",
                    "customValue1": "XXXX",
                    "customValue2": "XXXX"
                },
                {
                    "steamID32": "2875157",
                    "customValue1": "XXXX",
                    "customValue2": "XXXX"
                },
                {
                    "steamID32": "2875158",
                    "customValue1": "XXXX",
                    "customValue2": "XXXX"
                },
                {
                    "steamID32": "0",
                    "customValue1": "XXXX",
                    "customValue2": "XXXX"
                },
                {
                    "steamID32": "0",
                    "customValue1": "XXXX",
                    "customValue2": "XXXX"
                }
            ]
        },
        "1": {
            "game": {
                    "customValue1": "XXXX",
                    "customValue2": "XXXX"
            },
            "players": [
                {
                    "steamID32": "2875155",
                    "customValue1": "XXXX",
                    "customValue2": "XXXX"
                },
                {
                    "steamID32": "2875156",
                    "customValue1": "XXXX",
                    "customValue2": "XXXX"
                },
                {
                    "steamID32": "2875157",
                    "customValue1": "XXXX",
                    "customValue2": "XXXX"
                },
                {
                    "steamID32": "2875158",
                    "customValue1": "XXXX",
                    "customValue2": "XXXX"
                },
                {
                    "steamID32": "0",
                    "customValue1": "XXXX",
                    "customValue2": "XXXX"
                },
                {
                    "steamID32": "0",
                    "customValue1": "XXXX",
                    "customValue2": "XXXX"
                }
            ]
        }
    }
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
                The result field will either be 0 or 1. A result of 0 indicates there was a failure. There may be an
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