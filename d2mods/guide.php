<div class="page-header">
    <h2>Guide for Adding Stats to your Mods
        <small>BETA</small>
    </h2>
</div>

<p>This section is a Work-In-Progress, so check back later.</p>

<p>Initial experimentation has revealed that via a combination of Flash and LUA, we can open socket connections with
    remote servers. We plan to take advantage of this by opening a socket back to our servers at the end of each game
    for stat gathering purposes. Before starting this guide, please ensure that you have added your mod to our
    directory. You will be provided with an encryption key that will be required towards the end of the guide.</p>

<h3>Gathering the Data</h3>

<p>Via Flash and LUA, you will communicate the following in JSON.</p>

<ul>
    <li>matchID -- Match ID - needs to be a unique repeatable hash for all of the clients (try hashing dateEnded,
        duration, modID, serverAddress)
    </li>
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
    <li>player -- Player data
        <ul>
            <li>playerNickname -- Player Nickname</li>
            <li>steamID32 -- Player's steam account ID (same as Dotabuff's)</li>
            <li>steamID64 -- Player's steam ID (starts with 765)</li>
            <li>leaverStatus -- Leaver Status ID</li>
            <li>teamID -- Team ID - <strong>we currently can only do Radiant and Dire</strong></li>
            <li>slotID -- Slot ID - <strong>wrt. their team</strong></li>
            <li>heroID -- Hero ID
                <ul>
                    <li>level</li>
                    <li>structureDamage</li>
                    <li>heroDamage</li>
                    <li>kills</li>
                    <li>assists</li>
                    <li>deaths</li>
                    <li>abilities
                        <ul>
                            <li>abilityID - <strong>repeat, only the hero chosen abilities</strong></li>
                        </ul>
                    </li>
                </ul>
            </li>
            <li>items
                <ul>
                    <li>gameTime - <strong>repeat</strong>
                        <ul>
                            <li>itemID</li>
                        </ul>
                    </li>
                </ul>
            </li>
        </ul>
    </li>
</ul>

<p>You will first need to implement your Flash and LUA methods for gathering the above data. Failing to collect all of
    the
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
    sample Flash and LUA code for gathering some of the required statistics:</p>

<div class="panel panel-default">
    <div class="panel-body">
        Alan, add code here. In the meantime, <a
            href="https://github.com/SinZ163/TrollsAndElves/blob/master/StatSource/StatsCollection.as" target="_blank">SinZ163
            has a whole script up for collection and communication</a>
    </div>
</div>

<p>You should now test that your JSON looks the same as the schema provided above. If so, you are now ready to test
    transmitting this JSON to our servers.</p>

<h3>Sending the Data</h3>

<p>Below is sample code for sending the JSON via sockets.</p>

<div class="panel panel-default">
    <div class="panel-body">
        <a href="https://github.com/SinZ163/TrollsAndElves/blob/master/StatSource/StatsCollection.as" target="_blank">SinZ163's
            code, and his GitHub should have the latest working copy</a>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-body">
        <br/>Steps:
        <ul>
            <li>Create message body of JSON</li>
            <li>Add a timestamp to the message</li>
            <li>Encrypt message using the encryption key unique to your mod</li>
            <li>Open socket request to 176.31.182.87 on port 4444</li>
            <li>After receiving welcome message, send mod identifier</li>
            <li>After receiving ack, send message</li>
            <li>After receiving ack, close connection</li>
        </ul>
        You can view if it worked by looking at <a href="//getdotastats.com/d2mods/list_messages.php" target="_new">our
            list of messages</a>
    </div>
</div>

<p>The above code should compile into "resource/flash3/StatsCollection.swf", where it can be put into any game mode. It
    will send the data if LUA sends the event, and it is in custom_events too. The compiled flash will need to be called
    in custom_ui.</p>

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

<p>This method of stat collection is new and experimental, so feel free to contact me via <a
        href="http://github.com/GetDotaStats/site/issues" target="_new">Github Issues</a>/<a
        href="http://steamcommunity.com/id/jimmydorry/" target="_new">Steam</a>/<a
        href="irc://irc.gamesurge.net:6667/#getdotastats" target="_new">IRC</a>/Site Chatbox. If contacting me via
    Steam, make sure to leave a message on my profile, as I will likely not add you otherwise.</p>
