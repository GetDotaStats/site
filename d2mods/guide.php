<div class="page-header">
    <h2>Guide for Adding Stats to your Mods
        <small>BETA</small>
    </h2>
</div>

<p>This section is a Work-In-Progress, so check back later.</p>

<p>Initial experimentation has revealed that via LUA or Flash, we can open socket connections with remote servers. We
    plan to take advantage of this by opening a socket back to this server at the end of each game. Before starting this
    guide, please ensure that you have added your mod to our directory. You will be provided with an encryption key that
    will be required towards the end of the guide.</p>

<h3>Gathering the Data</h3>

<p>Via LUA, you will communicate the following in JSON.</p>

<ul>
    <li>matchID -- Match ID</li>
    <li>modID -- Mod Identifier</li>
    <li>modes -- Game mode flags - <strong>as an array, if applicable</strong></li>
    <li>version -- Map version</li>
    <li>duration -- Game duration in seconds</li>
    <li>winner -- Winning Team ID</li>
    <li>numTeams -- Number of Teams</li>
    <li>numPlayers -- Number of Players</li>
    <li>autoSurrender -- Automatic Surrender - <strong>boolean for a team forfeiting</strong></li>
    <li>massDisconnect -- Mass Disconnect - <strong>boolean for everyone being disconnected</strong></li>
    <li>serverAddress -- Server Address - <strong>including port</strong></li>
    <li>dateEnded -- Match Ending Unix Timestamp</li>
    <li>players -- Player data - <strong>repeat</strong>
        <ul>
            <li>Player Nickname</li>
            <li>steamID32</li>
            <li>steamID64</li>
            <li>Leaver Status</li>
            <li>Team ID</li>
            <li>Slot ID - <strong>wrt. their team</strong></li>
            <li>Hero ID
                <ul>
                    <li>Level</li>
                    <li>Tower Damage</li>
                    <li>Hero Damage</li>
                    <li>Kills</li>
                    <li>Assists</li>
                    <li>Deaths</li>
                    <li>Abilities
                        <ul>
                            <li>Ability ID - <strong>repeat, only the hero chosen abilities</strong></li>
                        </ul>
                    </li>
                </ul>
            </li>
            <li>Items
                <ul>
                    <li>Game Time - <strong>repeat</strong>
                        <ul>
                            <li>Item ID</li>
                        </ul>
                    </li>
                </ul>
            </li>
        </ul>
    </li>
</ul>

<p>You will first need to implement your LUA methods for gathering the above data. Failing to collect all of the
    required data may result in your mod getting de-listed, or stats not functioning correctly. Below is a sample JSON
    schema:</p>

<div class="panel panel-default">
    <div class="panel-body">
        {"matchID" : 123123123123, "modID" : "abcdabcdabcd", "modes" : {0 : "ar", 1 : "dr"}, "version" : 0.1.23,
        "duration" : 123, "winner" : 1, "numTeams" : 2, "numPlayers" : 10, "autoSurrender" : 0, "massDisconnect" : 0,
        "serverAddress" : "192.168.0.1:27001", "dateEnded" : 123123123123}
    </div>
</div>

<p>There is no standard cookie cutter code that will work for every mod, but much of it should be the same. Below is
    sample LUA code for gathering some of the required statistics:</p>

<div class="panel panel-default">
    <div class="panel-body">
        Alan, add code here
    </div>
</div>

<p>You should now test that your JSON looks the same as the schema provided above. If so, you are now ready to test
    transmitting this JSON to our servers.</p>

<h3>Sending the Data</h3>

<p>Below is sample code for sending the JSON via sockets.</p>

<div class="panel panel-default">
    <div class="panel-body">
        Alan, add code here
    </div>
</div>

<p>As you can see above, you will need to add the encryption key specific to your mod. <strong>It is important not
        to share this key!</strong> If we see any unusual activity associated with a key, we will revoke the mod and
    investigate. It is important that the stats gathered are legitimate. If your transmitting code works, it is now
    time to move to the final step... making it more difficult for people to fake your stats and protecting your
    data transmission.</p>

<h3>Strengthening Security of your Stats and Encryption</h3>

<p>You will now need to obsfucate and compile the LUA encryption routine, as anyone that downloads your mod off the
    workshop can see all of the source code. We recommend using the following tool to obsfucate and compile this
    part of your LUA code.</p>

<div class="panel panel-default">
    <div class="panel-body">
        Alan, make steps and find program
    </div>
</div>

<p>You are now ready to go! Upload your mod to the workshop and see if it works!</p>

<p>This method of stat collection is very new, so feel free to contact me via <a
        href="http://github.com/GetDotaStats/site/issues" target="_new">Github Issues</a>/<a
        href="steamcommunity.com/id/jimmydorry/" target="_new">Steam</a>/Chatbox. If contacting me via Steam, make
    sure to leave a message on my profile, as I will likely not add you otherwise.</p>
