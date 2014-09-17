<div class="page-header">
    <h2>Guide for Adding Stats to your Mods
        <small>BETA</small>
    </h2>
</div>

<p>This guide is still a Work-In-Progress, so check back later.</p>

<p>Initial experimentation has revealed that via a combination of Flash and LUA, we can open socket connections with
    remote servers. We plan to take advantage of this by opening a socket back to our servers at the end of each game
    for stat gathering purposes. Before starting this guide, please ensure that you have added your mod to our
    directory. You will be provided with an encryption key that will be required towards the end of the guide.</p>

<h3>Data Schema</h3>

<p>Via Flash and LUA, you will communicate the following in JSON.</p>

<div class="table-responsive">
    <table class="table table-striped table-hover">
        <tr>
            <th width="130">Parameter</th>
            <th width="50">&nbsp;</th>
            <th width="70">Default</th>
            <th width="70">Type</th>
            <th>Example</th>
            <th width="300">Notes</th>
        </tr>
        <tr>
            <td>matchID</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>&nbsp;</td>
            <td>string</td>
            <td>98426ea5f41590</td>
            <td>Unique repeatable hash that will be repeatable for all of the clients in the same game (i.e. MD5 hash of modID, serverAddress, serverPort, and dateEnded)</td>
        </tr>
        <tr>
            <td>modID</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>&nbsp;</td>
            <td>string</td>
            <td>4d710f4c81bf6402e5</td>
            <td>Unique modID <a class="nav-clickable" href="#d2mods__my_mods" target="_blank">assigned to your
                    mod</a></td>
        </tr>
        <tr>
            <td>modes</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>array</td>
            <td>ctf, 1v1, best100, best20</td>
            <td>Array of modes (even if only one mode selected)</td>
        </tr>
        <tr>
            <td>version</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>&nbsp;</td>
            <td>string</td>
            <td>2.0.12</td>
            <td>Version of the mod</td>
        </tr>
        <tr>
            <td>duration</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>&nbsp;</td>
            <td>integer</td>
            <td>1234</td>
            <td>Duration of the game in seconds</td>
        </tr>
        <tr>
            <td>winner</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>&nbsp;</td>
            <td>integer</td>
            <td>2</td>
            <td>Winning Team ID</td>
        </tr>
        <tr>
            <td>numTeams</td>
            <td>&nbsp;</td>
            <td>2</td>
            <td>integer</td>
            <td>2</td>
            <td>Number of teams playing (in preparation of multi-team support getting added)</td>
        </tr>
        <tr>
            <td>numPlayers</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>&nbsp;</td>
            <td>integer</td>
            <td>8</td>
            <td>Number of players in game (important this is set as it effects if games are counted for stats)</td>
        </tr>
        <tr>
            <td>serverAddress</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>&nbsp;</td>
            <td>string</td>
            <td>8</td>
            <td>Server address including port</td>
        </tr>
        <tr>
            <td>dateEnded</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>&nbsp;</td>
            <td>integer</td>
            <td>1409461194</td>
            <td>Match ending time as a Unix Timestamp</td>
        </tr>
        <tr>
            <td>rounds</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>&nbsp;</td>
            <td>array</td>
            <td>roundInfo</td>
            <td>&nbsp;</td>
        </tr>
    </table>
</div>

<h4>roundInfo</h4>
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <tr>
            <th width="130">Parameter</th>
            <th width="50">&nbsp;</th>
            <th width="70">Default</th>
            <th width="70">Type</th>
            <th>Example</th>
            <th width="300">Notes</th>
        </tr>
        <tr>
            <td>winner</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>&nbsp;</td>
            <td>integer</td>
            <td>2</td>
            <td>Winning team of the round (fill this even if you only have a single round)</td>
        </tr>
        <tr>
            <td>duration</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>&nbsp;</td>
            <td>integer</td>
            <td>1234</td>
            <td>Duration of the round in seconds</td>
        </tr>
        <tr>
            <td>players</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>&nbsp;</td>
            <td>array</td>
            <td>playerInfo</td>
            <td>&nbsp;</td>
        </tr>
    </table>
</div>

<h4>playerInfo</h4>
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <tr>
            <th width="130">Parameter</th>
            <th width="50">&nbsp;</th>
            <th width="70">Default</th>
            <th width="70">Type</th>
            <th>Example</th>
            <th width="300">Notes</th>
        </tr>
        <tr>
            <td>playerName</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>&nbsp;</td>
            <td>string</td>
            <td>ᅠ<┼jiæ░d▒r▓y┼ ҉҈ᅠ</td>
            <td>Steam persona name of the player</td>
        </tr>
        <tr>
            <td>steamID32</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>&nbsp;</td>
            <td>integer</td>
            <td>28755155</td>
            <td>Player's steam account ID (same as Dotabuff's)</td>
        </tr>
        <tr>
            <td>steamID64</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>&nbsp;</td>
            <td>integer</td>
            <td>76561197989020883</td>
            <td>Player's steam ID (starts with 765)</td>
        </tr>
        <tr>
            <td>leaverStatus</td>
            <td>&nbsp;</td>
            <td>0</td>
            <td>integer</td>
            <td>4</td>
            <td>0 = none, 1 = disconnected, 2 = disconnected timeout, 3 = abandoned match, 4 = AFK (no xp for 5mins), 5
                = never connected, 6 = never connected too long (reached the timeout) (<a
                    href="https://github.com/SteamRE/SteamKit/blob/master/Resources/Protobufs/dota/dota_gcmessages_common.proto#L519"
                    target="_blank">refer to enum</a>)
            </td>
        </tr>
        <tr>
            <td>teamID</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>&nbsp;</td>
            <td>integer</td>
            <td>2</td>
            <td>Player's team ID (will obviously be 2 or 3 for now)</td>
        </tr>
        <tr>
            <td>slotID</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>&nbsp;</td>
            <td>integer</td>
            <td>3</td>
            <td>Player's slot ID in their team</td>
        </tr>
        <tr>
            <td>hero</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>&nbsp;</td>
            <td>array</td>
            <td>heroInfo</td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td>items</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>array</td>
            <td>itemsInfo</td>
            <td>&nbsp;</td>
        </tr>
    </table>
</div>

<h4>heroInfo</h4>
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <tr>
            <th width="130">Parameter</th>
            <th width="50">&nbsp;</th>
            <th width="70">Default</th>
            <th width="70">Type</th>
            <th>Example</th>
            <th width="300">Notes</th>
        </tr>
        <tr>
            <td>heroID</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>&nbsp;</td>
            <td>integer</td>
            <td>100</td>
            <td>Hero ID of the player</td>
        </tr>
        <tr>
            <td>level</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>&nbsp;</td>
            <td>integer</td>
            <td>25</td>
            <td>Level of the player</td>
        </tr>
        <tr>
            <td>structureDamage</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>&nbsp;</td>
            <td>integer</td>
            <td>10203</td>
            <td>Damage player has done to structures</td>
        </tr>
        <tr>
            <td>heroDamage</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>&nbsp;</td>
            <td>integer</td>
            <td>39234</td>
            <td>Damage player has done to other players</td>
        </tr>
        <tr>
            <td>kills</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>&nbsp;</td>
            <td>integer</td>
            <td>13</td>
            <td>Kills player has performed</td>
        </tr>
        <tr>
            <td>assists</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>&nbsp;</td>
            <td>integer</td>
            <td>24</td>
            <td>Kills player has assisted with</td>
        </tr>
        <tr>
            <td>deaths</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>&nbsp;</td>
            <td>integer</td>
            <td>2</td>
            <td>Deaths player has accrued</td>
        </tr>
        <tr>
            <td>abilities</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>array</td>
            <td>abilitiesInfo</td>
            <td>&nbsp;</td>
        </tr>
    </table>
</div>

<h4>abilitiesInfo</h4>
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <tr>
            <th width="130">Parameter</th>
            <th width="50">&nbsp;</th>
            <th width="70">Default</th>
            <th width="70">Type</th>
            <th>Example</th>
            <th width="300">Notes</th>
        </tr>
        <tr>
            <td>abilityID</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>&nbsp;</td>
            <td>integer</td>
            <td>231</td>
            <td>Ability ID</td>
        </tr>
        <tr>
            <td>abilityName</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>&nbsp;</td>
            <td>string</td>
            <td>ability_testmod_build_elemental</td>
            <td>Name of ability (Unlocalised string)</td>
        </tr>
        <tr>
            <td>level</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>&nbsp;</td>
            <td>integer</td>
            <td>5</td>
            <td>Level of ability</td>
        </tr>
    </table>
</div>

<h4>itemsInfo</h4>
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <tr>
            <th width="130">Parameter</th>
            <th width="50">&nbsp;</th>
            <th width="70">Default</th>
            <th width="70">Type</th>
            <th>Example</th>
            <th width="300">Notes</th>
        </tr>
        <tr>
            <td>itemID</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>&nbsp;</td>
            <td>integer</td>
            <td>255</td>
            <td>Item ID</td>
        </tr>
        <tr>
            <td>itemName</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>&nbsp;</td>
            <td>string</td>
            <td>item_testmod_wand_wizard</td>
            <td>Name of item (Unlocalised string)</td>
        </tr>
        <tr>
            <td>obtainStatus</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>&nbsp;</td>
            <td>integer</td>
            <td>2</td>
            <td>0 = Purchased, 1 = Picked up, 2 = Given by ally</td>
        </tr>
        <tr>
            <td>lostStatus</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>&nbsp;</td>
            <td>integer</td>
            <td>2</td>
            <td>0 = Sold, 1 = Dropped (including transferred to stash or ally), 2 = Used</td>
        </tr>
        <tr>
            <td>itemStartTime</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>&nbsp;</td>
            <td>integer</td>
            <td>123</td>
            <td>Number of seconds after round began that item was obtained</td>
        </tr>
        <tr>
            <td>itemEndTime</td>
            <td><span class="glyphicon glyphicon-ok"></span></td>
            <td>&nbsp;</td>
            <td>integer</td>
            <td>255</td>
            <td>Number of seconds after round began that item was used/lost</td>
        </tr>
    </table>
</div>

<p>You will first need to implement your Flash and LUA methods for gathering the above data. Failing to collect all of
    the required data may result in your mod getting de-listed, or stats not functioning correctly. Below is a sample
    JSON to demonstrate the kind of string we are expecting:</p>

<pre class="pre-scrollable">
{"matchID" : 123123123123, "modID" : "abcdabcdabcd", "modes" : {0 : "ar", 1 : "dr"}, "version" : 0.1.23, "duration" : 123, "winner" : 1, "numTeams" : 2, "numPlayers" : 10, "autoSurrender" : 0, "massDisconnect" : 0, "serverAddress" : "192.168.0.1:27001", "dateEnded" : 123123123123}
</pre>

<p>There is no standard cookie cutter code that will work for every mod, but much of it should be the same. You
    essentially just need to build an array during the game duration that matches the schema above.</p>

<p>Before continuing, you should test that your JSON looks the same as the schema provided above. If so, you are ready
    to test transmitting the JSON to our servers.</p>

<h3>Sending the Data</h3>

<p>Now that you have data to send, you need to: </p>

<h4>Include the <strong><em>compiled</em></strong> flash code for sending data in your "resource/flash3" folder - <a
        href="https://github.com/SinZ163/TrollsAndElves/raw/master/resource/flash3/StatsCollection.swf"
        target="_blank">GitHub</a> || <a href="./d2mods/resources/StatsCollection.swf" target="_blank">site copy</a></h4>

<pre class="pre-scrollable">
    package  {
        import flash.display.MovieClip;
        import flash.net.Socket;
        import flash.utils.ByteArray;
        import flash.events.Event;
        import flash.events.ProgressEvent;
        import flash.events.IOErrorEvent;

        public class StatsCollection extends MovieClip {
            public var gameAPI:Object;
            public var globals:Object;
            public var elementName:String;

            var sock:Socket;
            var json:String;
            var SERVER_ADDRESS:String = "176.31.182.87";
            var SERVER_PORT:Number = 4444;

            public function onLoaded() : void {
                trace("##Loading StatsCollection by SinZ");
                gameAPI.SubscribeToGameEvent("stat_collection", this.statCollect);
            }

            public function socketConnect(e:Event) {
                // We have connected successfully!
                trace('Connected to the server!');
                var buff:ByteArray = new ByteArray();
                writeString(buff, json + "\n");
                sock.writeBytes(buff, 0, buff.length);
                sock.flush();
            }

            private static function writeString(buff:ByteArray, write:String){
                trace("Message: "+write);
                trace("Length: "+write.length);
                buff.writeUTF(write);
                for(var i = 0; i < write.length; i++){
                    buff.writeByte(0);
                }
            }

            public function statCollect(args:Object) {
                trace("##STATS Received data from server");
                delete args.splitscreenplayer;
                json = args.json;
                sock = new Socket();
                // Setup socket event handlers
                sock.addEventListener(Event.CONNECT, socketConnect);

                try {
                    sock.connect(SERVER_ADDRESS, SERVER_PORT);
                } catch (e:Error) {
                    trace("##STATS Failed to connect!");
                    return false;
                }
            }
        }
    }
</pre>

<h4>Call the compiled flash in your "resource/flash3/custom_ui.txt" - <a
        href="https://github.com/SinZ163/TrollsAndElves/blob/master/resource/flash3/custom_ui.txt#L8-L12"
        target="_blank">GitHub</a></h4>

<pre class="pre-scrollable">
    "CustomUI"
    {
        "1"
        {
            "File" "TrollsAndElves"
            "Depth" "16"
        }
        "2"
        {
            "File" "StatsCollection"
            "Depth" "1"
        }
    }
</pre>

<h4>Create a custom event in your "blob/master/scripts/custom_events.txt" - <a
        href="https://github.com/SinZ163/TrollsAndElves/blob/master/scripts/custom_events.txt#L23-L28"
        target="_blank">GitHub</a></h4>

<pre class="pre-scrollable">
    "CustomEvents"
    {
        //StatsCollection Service by SinZ and jimmydorry
        "stat_collection"
        {
        "json"          "string"
        }
        //End StatsCollection
</pre>

<h4>Fire the "stat_collection" event and give it the JSON - <a
        href="https://github.com/SinZ163/TrollsAndElves/blob/master/scripts/vscripts/TrollsAndElves.lua#L142-L150"
        target="_blank">GitHub</a></h4>

<pre class="pre-scrollable">
    print("###StatsCollection sending stats")
    FireGameEvent("stat_collection", {
        json = JSON:encode({
            fakedata1 = "testing 123",
            fakedata2 = "321 gnitset",
            modid = "TrollsAndElves",
            fancyinfo = "yolo swaggins and the fellowship of the bling"
        })
    })
</pre>

<p>Now that you have the code implemented to send, why not test it out? You can monitor what test data we receive via
    our <a href="./d2mods/list_messages.php" target="_blank">database</a> and <a href="./d2mods/log-test.html"
                                                                                 target="_blank">test console</a> ||
    <a href="./d2mods/log-live.html" target="_blank">live console</a></p>

<h3>Custom Flash to send JSON</h3>

<p>If you want to understand what the compiled Flash is doing (or make your own), it essentially just opens a socket
    connection to 176.31.182.87 on port 4444 (for testing) OR 4445 (live) and sends the JSON string.</p>

<h3>Final steps</h3>

<p>If you are happy that the test data works, replace the compiled flash with the flash that points to the live data
    collection server <a
        href="https://github.com/SinZ163/TrollsAndElves/raw/master/resource/flash3/StatsCollection_live.swf"
        target="_blank">here</a></p>

<p>You are now ready to go! Upload your mod to the workshop and see if it works!</p>

<p>This method of stat collection is new and experimental, so feel free to contact me via <a
        href="http://github.com/GetDotaStats/site/issues" target="_new">Github Issues</a>/<a
        href="http://steamcommunity.com/id/jimmydorry/" target="_new">Steam</a>/<a
        href="irc://irc.gamesurge.net:6667/#getdotastats" target="_new">IRC</a>/Site Chatbox.</p>
<p>If contacting me via Steam, make sure to leave a message on my profile, as I will likely not add you otherwise.</p>

<h3>Miscellaneous Guidelines</h3>

<ul>
    <li>Do not re-use IDs for abilities, items, etc. If you remove an item from the game, and later add another, it is
        important that you do not re-use an existing ID as this will break the integrity of your stats database.
    </li>
</ul>