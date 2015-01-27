<div class="page-header">
    <h2>Schema of Data Collected by the Stat Module
        <small>BETA</small>
    </h2>
</div>

<p>This schema is a work in progress, and may not always be up-to-date in depicting what data is collected by the
    module.</p>

<div class="alert alert-danger" role="alert"><strong>Special thanks to:</strong> <a href="https://github.com/SinZ163/"
                                                                                    target="_blank">SinZ163</a>, <a
        href="https://github.com/tetl/" target="_blank">Tet</a>, and <a href="https://github.com/ash47/"
                                                                        target="_blank">Ash47</a> for their hard-work in
    testing and developing the Lua and Flash code that makes this all possible. I take no credit for the Lua and Flash
    found here, as I have just collated and reformatted it.
</div>

<p>The barebones example repo can be found here: <a href="https://github.com/GetDotaStats/stat-collection/"
                                                    target="_blank">GetDotaStats/stat-collection</a>. All of the
    required libraries and example code is in there. Implementation simply involves splicing the statcollection logic
    into your mod.</p>

<p>Via Flash and LUA, you will communicate the following in JSON. Statistics that are "auto" are handled by the library
    automatically.</p>

<div class="table-responsive">
    <table class="table table-striped table-hover">
        <tr>
            <th width="130">Parameter</th>
            <th width="50">Auto</th>
            <th width="70">Type</th>
            <th>Example</th>
            <th width="300">Notes</th>
        </tr>
        <tr>
            <td>matchID</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>string</td>
            <td>98426ea5f41590</td>
            <td>Unique repeatable hash that will be repeatable for all of the clients in the same game (i.e. MD5 hash of
                modID, serverAddress, serverPort, and dateEnded)
            </td>
        </tr>
        <tr>
            <td>modID</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>string</td>
            <td>4d710f4c81bf6402e5</td>
            <td>Unique modID <a class="nav-clickable" href="#d2mods__my_mods">assigned to your mod</a></td>
        </tr>
        <tr>
            <td>modes</td>
            <td>&nbsp;</td>
            <td>array</td>
            <td>ctf, 1v1, best100, best20</td>
            <td>Array of modes (even if only one mode selected)</td>
        </tr>
        <tr>
            <td>version</td>
            <td>&nbsp;</td>
            <td>string</td>
            <td>2.0.12</td>
            <td>Version of the mod</td>
        </tr>
        <tr>
            <td>duration</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>integer</td>
            <td>1234</td>
            <td>Duration of the game in seconds</td>
        </tr>
        <tr>
            <td>winner</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>integer</td>
            <td>2</td>
            <td>Winning Team ID</td>
        </tr>
        <tr>
            <td>serverAddress</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>string</td>
            <td>8</td>
            <td>Server address including port</td>
        </tr>
        <tr>
            <td>dateEnded</td>
            <td>&nbsp;</td>
            <td>integer</td>
            <td>1409461194</td>
            <td>Match ending time as a Unix Timestamp</td>
        </tr>
        <tr>
            <td>rounds</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>array</td>
            <td>rounds</td>
            <td>&nbsp;</td>
        </tr>
    </table>
</div>

<h4>rounds
    <small>Needs to be manually implemented</small>
</h4>
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <tr>
            <th width="130">Parameter</th>
            <th width="50">Auto</th>
            <th width="70">Type</th>
            <th>Example</th>
            <th width="300">Notes</th>
        </tr>
        <tr>
            <td>winner</td>
            <td>&nbsp;</td>
            <td>integer</td>
            <td>2</td>
            <td>Winning team of the round (fill this even if you only have a single round)</td>
        </tr>
        <tr>
            <td>duration</td>
            <td>&nbsp;</td>
            <td>integer</td>
            <td>1234</td>
            <td>Duration of the round in seconds</td>
        </tr>
        <tr>
            <td>players</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>array</td>
            <td>playerInfo</td>
            <td>&nbsp;</td>
        </tr>
    </table>
</div>

<h4>players</h4>
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <tr>
            <th width="130">Parameter</th>
            <th width="50">Auto</th>
            <th width="70">Type</th>
            <th>Example</th>
            <th width="300">Notes</th>
        </tr>
        <tr>
            <td>teamID</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>integer</td>
            <td>2</td>
            <td>Player's team ID (will obviously be 2 or 3 for now)</td>
        </tr>
        <tr>
            <td>slotID</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>integer</td>
            <td>3</td>
            <td>Player's slot ID in their team</td>
        </tr>
        <tr>
            <td>steamID32</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>integer</td>
            <td>28755155</td>
            <td>Player's account ID as returned by GetSteamAccountID()</td>
        </tr>
        <tr>
            <td>playerName</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>string</td>
            <td>ᅠ<┼jiæ░d▒r▓y┼ ҉҈ᅠ</td>
            <td>Steam persona name of the player</td>
        </tr>
        <tr>
            <td>connectionState</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>integer</td>
            <td>4</td>
            <td>As recorded at the start of the game. 0 = unknown, 1 = not yet connected, 2 = connected, 3 =
                disconnected
                match, 4 = abandoned, 5 = loading, 6 = failed (<a
                    href="https://github.com/SteamRE/SteamKit/blob/f6c0578506690d63a2b159340fe19835fe33564c/Resources/Protobufs/dota/dota_gcmessages_common.proto#L564"
                    target="_blank">refer to enum DOTAConnectionState_t</a>)
            </td>
        </tr>
        <tr>
            <td>hero</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>array</td>
            <td>heroInfo</td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td>items</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>array</td>
            <td>items</td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td>abilities</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>array</td>
            <td>abilities</td>
            <td>&nbsp;</td>
        </tr>
    </table>
</div>

<h4>hero</h4>
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <tr>
            <th width="130">Parameter</th>
            <th width="50">Auto</th>
            <th width="70">Type</th>
            <th>Example</th>
            <th width="300">Notes</th>
        </tr>
        <tr>
            <td>heroID</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>integer</td>
            <td>100</td>
            <td>Hero ID of the player</td>
        </tr>
        <tr>
            <td>level</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>integer</td>
            <td>25</td>
            <td>Level of the player</td>
        </tr>
        <tr>
            <td>structureDamage</td>
            <td>&nbsp;</td>
            <td>integer</td>
            <td>10203</td>
            <td>Damage player has done to structures</td>
        </tr>
        <tr>
            <td>heroDamage</td>
            <td>&nbsp;</td>
            <td>integer</td>
            <td>39234</td>
            <td>Damage player has done to other players</td>
        </tr>
        <tr>
            <td>kills</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>integer</td>
            <td>13</td>
            <td>Kills player has performed</td>
        </tr>
        <tr>
            <td>assists</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>integer</td>
            <td>24</td>
            <td>Kills player has assisted with</td>
        </tr>
        <tr>
            <td>deaths</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>integer</td>
            <td>2</td>
            <td>Deaths player has accrued</td>
        </tr>
        <tr>
            <td>gold</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>integer</td>
            <td>734</td>
            <td>Amount of gold player has accrued</td>
        </tr>
        <tr>
            <td>denies</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>integer</td>
            <td>20</td>
            <td>Denies player has performed</td>
        </tr>
        <tr>
            <td>lastHits</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>integer</td>
            <td>100</td>
            <td>Last hits player has performed</td>
        </tr>
    </table>
</div>

<h4>items</h4>
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <tr>
            <th width="130">Parameter</th>
            <th width="50">Auto</th>
            <th width="70">Type</th>
            <th>Example</th>
            <th width="300">Notes</th>
        </tr>
        <tr>
            <td>itemID</td>
            <td>&nbsp;</td>
            <td>integer</td>
            <td>255</td>
            <td>Item ID</td>
        </tr>
        <tr>
            <td>itemName</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>string</td>
            <td>item_testmod_wand_wizard</td>
            <td>Name of item (Unlocalised string)</td>
        </tr>
        <tr>
            <td>obtainStatus</td>
            <td>&nbsp;</td>
            <td>integer</td>
            <td>2</td>
            <td>0 = Purchased, 1 = Picked up, 2 = Given by ally</td>
        </tr>
        <tr>
            <td>lostStatus</td>
            <td>&nbsp;</td>
            <td>integer</td>
            <td>2</td>
            <td>0 = Sold, 1 = Dropped (including transferred to stash or ally), 2 = Used</td>
        </tr>
        <tr>
            <td>itemStartTime</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>integer</td>
            <td>123</td>
            <td>Number of seconds after round began that item was obtained</td>
        </tr>
        <tr>
            <td>itemEndTime</td>
            <td>&nbsp;</td>
            <td>integer</td>
            <td>255</td>
            <td>Number of seconds after round began that item was used/lost</td>
        </tr>
    </table>
</div>

<h4>abilities</h4>
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <tr>
            <th width="130">Parameter</th>
            <th width="50">Auto</th>
            <th width="70">Type</th>
            <th>Example</th>
            <th width="300">Notes</th>
        </tr>
        <tr>
            <td>abilityID</td>
            <td>&nbsp;</td>
            <td>integer</td>
            <td>231</td>
            <td>Ability ID</td>
        </tr>
        <tr>
            <td>abilityName</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>string</td>
            <td>phantom_lancer_doppelwalk</td>
            <td>Name of ability (Unlocalised string)</td>
        </tr>
        <tr>
            <td>level</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>integer</td>
            <td>5</td>
            <td>Level of ability</td>
        </tr>
    </table>
</div>

<p>Our library collects the above automatically. Below is a sample JSON string to demonstrate the kind of string we are
    expecting:</p>

<pre class="pre-scrollable">
{"matchID" : 123123123123, "modID" : "abcdabcdabcd", "modes" : {0 : "ar", 1 : "dr"}, "version" : 0.1.23, "duration" : 123, "winner" : 1, "numTeams" : 2, "numPlayers" : 10, "autoSurrender" : 0, "massDisconnect" : 0, "serverAddress" : "192.168.0.1:27001", "dateEnded" : 123123123123}
</pre>