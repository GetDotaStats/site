<div class="page-header">
    <h2>Schema for stat-collection Library v2
        <small>BETA</small>
    </h2>
</div>

<p>This schema is a work in progress. You will notice that many fields have been stripped out of this version. Only the
    most useful fields will be recorded, and any mods that want more detailed stats will collaborate directly with us
    for a more customised solution. With the increase in data volume, we need to take scaling into consideration.</p>

<div class="row">
    <div class="col-sm-3"><strong>Key</strong></div>
    <div class="col-sm-2"><strong>Type</strong></div>
    <div class="col-sm-7"><strong>Example</strong></div>
</div>
<span class="h4">&nbsp;</span>

<div class="row">
    <div class="col-sm-3">authKey</div>
    <div class="col-sm-2">string</div>
    <div class="col-sm-7">"asdfhkj324jklnfadssdafsd"</div>
</div>

<div class="row">
    <div class="col-sm-3">matchID</div>
    <div class="col-sm-2">string</div>
    <div class="col-sm-7">"21347923432"</div>
</div>

<div class="row">
    <div class="col-sm-3">modID</div>
    <div class="col-sm-2">string</div>
    <div class="col-sm-7">"7adfki234jlk23"</div>
</div>

<div class="row">
    <div class="col-sm-3">hostSteamID32</div>
    <div class="col-sm-2">string</div>
    <div class="col-sm-7">"2875155"</div>
</div>

<div class="row">
    <div class="col-sm-3">flags</div>
    <div class="col-sm-2">array</div>
    <div class="col-sm-7">["ctf15", "kill50", "king_of_the_hill", "15point"]</div>
</div>

<div class="row">
    <div class="col-sm-3">gameStarted</div>
    <div class="col-sm-2">integer</div>
    <div class="col-sm-7">1</div>
</div>

<div class="row">
    <div class="col-sm-3">isDedicated</div>
    <div class="col-sm-2">integer</div>
    <div class="col-sm-7">1</div>
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
    <div class="col-sm-7">5</div>
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

<hr />

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
        "schemaVersion": 5,
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