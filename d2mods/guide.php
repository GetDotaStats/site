<div class="page-header">
    <h2>Guide for Adding Stats to your Mods
        <small>BETA</small>
    </h2>
</div>

<p>This guide is still a Work-In-Progress, so always use the code in the Github!</p>

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

<p>Initial experimentation has revealed that via a combination of Flash and LUA, we can open socket connections with
    remote servers. We plan to take advantage of this by opening a socket back to our servers at the end of each game
    for stat gathering purposes. Before starting this guide, <a class="nav-clickable" href="#d2mods__my_mods">please
        ensure that you have added your mod to our directory</a>. You will be provided with an encryption key that will
    be required towards the end of the guide.
</p>

<p>There is standard "cookie cutter" code available in the <a
        href="https://github.com/GetDotaStats/stat-collection/blob/master/statcollection/scripts/vscripts/lib/statcollection.lua"
        target="_blank">"scripts/vscripts/lib/statcollection.lua"</a> that should work for every mod. It is called
    automatically (It hooks the game_state >= postgame and calls to SetGameWinner(), so that if either of those
    conditions is met it will send the stats) at the end of the game via the getPlayerSnapshot() method. If your mod has
    multiple rounds, then you will need to modify that library such that you call it at the end of each round. If you
    are doing anything non-standard, or are having problems with your end game detection, call
    statcollection.disableAutoSend().
</p>
<p>To get more out of this module, you will also need to refer to the
    <a href="#d2mods__guide_schema"
       target="_blank">schema</a> to see which data is not automatically
    captured. If you see data that you can collect and add to the schema, get in contact with us and we will try and
    accommodate your changes into the official schema, or add custom fields for your mod. This manually added data can
    be added during game play by calling addStats() method with your array of stats.</p>

<h3>Setting up stat collection</h3>

<div class="alert alert-danger" role="alert">
    <span class="glyphicon glyphicon-exclamation-sign"></span>
    Code on this page may be out of date. Always use the Github where possible!
</div>

<h4>Include the <strong><em>compiled</em></strong> flash code for sending data in your "resource/flash3" folder - <a
        href="https://github.com/GetDotaStats/stat-collection/raw/master/statcollection/resource/flash3/StatsCollection.swf"
        target="_blank">GitHub</a>
</h4>

<pre class="pre-scrollable">
    package  {
        import flash.display.MovieClip;
        import flash.net.Socket;
        import flash.utils.ByteArray;
        import flash.events.Event;
        import flash.events.ProgressEvent;
        import flash.events.IOErrorEvent;
        import flash.utils.Timer;
        import flash.events.TimerEvent;

        public class StatsCollection extends MovieClip {
            public var gameAPI:Object;
            public var globals:Object;
            public var elementName:String;

            var sock:Socket;
            var json:String;

            var SERVER_ADDRESS:String = "176.31.182.87";
            var SERVER_PORT:Number = 4444;

            public function onLoaded() : void {
                // Tell the user what is going on
                trace("##Loading StatsCollection...");

                // Reset our json
                json = '';

                // Load KV
                var settings = globals.GameInterface.LoadKVFile('scripts/stat_collection.kv');

                // Load the live setting
                var live:Boolean = (settings.live == "1");

                // Load the settings for the given mode
                if(live) {
                    // Load live settings
                    SERVER_ADDRESS = settings.SERVER_ADDRESS_LIVE;
                    SERVER_PORT = parseInt(settings.SERVER_PORT_LIVE);

                    // Tell the user it's live mode
                    trace("StatsCollection is set to LIVE mode.");
                } else {
                    // Load live settings
                    SERVER_ADDRESS = settings.SERVER_ADDRESS_TEST;
                    SERVER_PORT = parseInt(settings.SERVER_PORT_TEST);

                    // Tell the user it's test mode
                    trace("StatsCollection is set to TEST mode.");
                }

                // Log the server
                trace("Server was set to "+SERVER_ADDRESS+":"+SERVER_PORT);

                // Hook the stat collection event
                gameAPI.SubscribeToGameEvent("stat_collection_part", this.statCollectPart);
                gameAPI.SubscribeToGameEvent("stat_collection_send", this.statCollectSend);
            }
            public function socketConnect(e:Event) {
                // We have connected successfully!
                trace('Connected to the server!');

                // Hook the data connection
                //sock.addEventListener(ProgressEvent.SOCKET_DATA, socketData);
                var buff:ByteArray = new ByteArray();
                writeString(buff, json + '\r\n');
                sock.writeBytes(buff, 0, buff.length);
                sock.flush();
            }
            private static function writeString(buff:ByteArray, write:String){
                trace("Message: "+write);
                trace("Length: "+write.length);
                buff.writeUTFBytes(write);
            }
            public function statCollectPart(args:Object) {
                // Tell the client
                trace("##STATS Part of that stat data recieved:");
                trace(args.data);

                // Store the extra data
                json = json + args.data;
            }
            public function statCollectSend(args:Object) {
                // Tell the client
                trace("##STATS Sending payload:");
                trace(json);

                // Create the socket
                sock = new Socket();
                sock.timeout = 10000; //10 seconds is fair..
                // Setup socket event handlers
                sock.addEventListener(Event.CONNECT, socketConnect);

                try {
                    // Connect
                    sock.connect(SERVER_ADDRESS, SERVER_PORT);
                } catch (e:Error) {
                    // Oh shit, there was an error
                    trace("##STATS Failed to connect!");

                    // Return failure
                    return false;
                }
            }
        }
    }
</pre>

<h4>Call the compiled flash in your "resource/flash3/custom_ui.txt" - <a
        href="https://github.com/GetDotaStats/stat-collection/blob/master/statcollection/resource/flash3/custom_ui.txt"
        target="_blank">GitHub</a></h4>

<pre class="pre-scrollable">
    "CustomUI"
    {
        "1"
        {
            "File" "StatsCollection" //IF YOU HAVE A UI ELEMENT, THEN PUT IT AS 1, AND PUT STATS AS 2
            "Depth" "1" //IF YOU HAVE A UI ELEMENT, IT SHOULD BE SET AT 253
        }
    }
</pre>

<h4>Create a custom event in your "scripts/custom_events.txt" - <a
        href="https://github.com/GetDotaStats/stat-collection/blob/master/statcollection/scripts/custom_events.txt"
        target="_blank">GitHub</a></h4>

<pre class="pre-scrollable">
    "CustomEvents"
    {
        // Stat collection
        "stat_collection_part"
        {
            "data"          "string"
        }

        "stat_collection_send"
        {
        }
    }
</pre>

<h4>Create a KV in your "scripts/stat_collection.kv" to make your stats test or live - <a
        href="https://github.com/GetDotaStats/stat-collection/blob/master/statcollection/scripts/stat_collection.kv"
        target="_blank">GitHub</a></h4>

<p>Ensure that it is set to live = 0. After the tests are successful, you will come back and update this file.</p>

<pre class="pre-scrollable">
    "Settings" {
        // Set to 1 for live, or 0 for test
        "live"                  "0"

        // Test Settings
        "SERVER_ADDRESS_TEST"   "176.31.182.87"
        "SERVER_PORT_TEST"      "4444"

        // Live Settings
        "SERVER_ADDRESS_LIVE"   "176.31.182.87"
        "SERVER_PORT_LIVE"      "4445"
    }
</pre>

<h4>Record the modID at the start of the game - <a
        href="https://github.com/GetDotaStats/stat-collection/blob/master/statcollection/scripts/vscripts/addon_game_mode.lua"
        target="_blank">GitHub</a></h4>

<p>It is important that you record the correct modID, otherwise your stats will not be recorded against your mod.
    Re-usage of modID between mods is not allowed, as it will invalidate both your stats and the original mod's
    stats. You can get your modID from your <a class="nav-clickable" href="#d2mods__my_mods">mod
        listing</a>.</p>

<pre class="pre-scrollable">
    -- Load Stat collection (statcollection should be available from any script scope)
    require('lib.statcollection')
    statcollection.addStats({
        modID = 'XXXXXXXXXXXXXXXXXXX' --GET THIS FROM http://getdotastats.com/#d2mods__my_mods
    })

    print( "Example stat collection game mode loaded." )
</pre>

<h4>Add flags to indicate mod settings - <a
        href="https://github.com/ash47/LegendsOfDota/blob/fa672ce56159569089734caf09708307def6e73d/lod/scripts/vscripts/addon_game_mode.lua#L22"
        target="_blank">GitHub</a></h4>

<p>Some custom games have runtime configurations (or flags). As of writing, this data is collected but not displayed on
    the site. In the interest of data integrity, it is better to record this data now, than want it later. Flags are
    used for mod settings that a user has no control over. You could use this to indicate environment variables like
    which source engine it's running on, whether it's on one of the dedicated servers you set up for your mod, etc. All
    data must be strings or integers!</p>

<pre class="pre-scrollable">
    -- Add flags to our stat collector
    statcollection.addFlags({
        source1 = GameRules:isSource1()
    })
</pre>

<h4>Add modes to indicate host options - <a
        href="https://github.com/ash47/LegendsOfDota/blob/fa672ce56159569089734caf09708307def6e73d/lod/scripts/vscripts/addon_game_mode.lua#L1365"
        target="_blank">GitHub</a></h4>

<p>Some custom games have host defined choices (or modes). As of writing, this data is collected but not displayed on
    the site. In the interest of data integrity, it is better to record this data now, than want it later. Modes are
    used for mod settings that a user has control over. You could use this to indicate settings such as: starting gold,
    level cap, number of skills, etc. All data must be strings or integers!</p>

<pre class="pre-scrollable">
    -- Add settings to our stat collector
    statcollection.addStats({
        modes = {
            useEasyMode = useEasyMode,
            bonusGold = bonusGold,
            startingLevel = startingLevel,
            gamemode = gamemode,
            hideSkills = hideSkills,
            banTrollCombos = banTrollCombos,
            hostBanning = hostBanning,
            maxBans = maxBans,
            maxHeroBans = maxHeroBans,
            banningTime = banningTime,
            maxSlots = maxSlots,
            maxSkills = maxSkills,
            maxUlts = maxUlts
        }
    })
</pre>

<h4>Add module stats on your modules - <a
        href="https://github.com/ash47/LegendsOfDota/blob/9bd94ec2a5830c352fce3af13d8568ab32f6f2ff/lod/scripts/vscripts/lib/loadhelper.lua#L91"
        target="_blank">GitHub</a></h4>

<p>If you ever make a module and want to track which mods implement it, we have you covered! If they have implemented
    our stat module as well, then simply make a call to the addModuleStats() method. You can track what specific module
    options they have enabled, and depending on the module, you may want to record additional variables. All data must
    be strings or integers!</p>

<pre class="pre-scrollable">
    -- If they have stats, record that our system is in use
    if statcollection then
        -- Add the stats
        statcollection.addModuleStats('loadHelper', {
            enabled = true,
            hostSlotID = hostID,
        })
    end
</pre>

<h4>Finally!</h4>

<p>Now that you have the code implemented to collect and send stats, why not test it out? You can monitor what test data
    we receive via our logs
    <a href="./d2mods/log-test.html" target="_blank">test</a>
    ||
    <a href="./d2mods/log-live.html" target="_blank">live</a>.
    By default, you will be submitting to the test server. When you modify your "scripts/stat_collection.kv" by setting
    live = 1, your stats will appear in the live log and be eligible for recording. Only stats from approved mods are
    guaranteed to be accepted on the live server. Live stats that are successfully parsed will be recorded in our
    <a href="#d2mods__recent_games" target="_blank">database</a>. The test log records all data sent, while the live log
    will only record failures.
</p>

<div class="alert alert-danger">
    The stats are only sent at the end of a game, which can be triggered when the "game_state >= postgame" or when
    SetGameWinner() has been called. You can register a console command for easier testing. Refer to this
    <a target="_blank"
       href="https://github.com/GetDotaStats/Invoker-Wars/blob/280eb0c105a9cae2595266bf3f997830416b06fa/invoker_wars/scripts/vscripts/addon_game_mode.lua#L59"
       target="_blank">example</a>.
</div>

<h3>Understanding how the stat collection works</h3>

<p>Have a look at the <a
        href="https://github.com/GetDotaStats/stat-collection/blob/master/statcollection/scripts/vscripts/lib/statcollection.lua"
        target="_blank">statcollection library</a>.
    This library handles the data you collect, and even abstracts the process for sending the stats from the rest of
    your logic.</p>

<h3>Custom Flash to send JSON</h3>

<p>If you want to understand what the compiled Flash is doing (or make your own), it essentially just opens a socket
    connection to 176.31.182.87 on port 4444 (for testing) OR 4445 (live) and sends the JSON string.</p>

<h3>Final steps</h3>

<p>You are now ready to go! Upload your mod to the workshop and see if it works! This method of stat collection is new
    and experimental, so feel free to contact me via any of the lines of communication listed below.</p>

<p>For those not familiar with IRC, try the <a target="_blank" href="http://client00.chat.mibbit.com/">mibbit client</a>.
    <span class="glyphicon glyphicon-question-sign" title="server: GAMESURGE, channel: #getdotastats"></span></p>

<p>&nbsp;</p>

<div class="text-center">
    <a target="_blank" class="btn btn-danger btn-sm" href="irc://irc.gamesurge.net:6667/#getdotastats">IRC
        #getdotastats <span class="glyphicon glyphicon-question-sign"
                            title="server: GAMESURGE, channel: #getdotastats"></span></a>
    <a target="_blank" class="btn btn-danger btn-sm" href="http://chatwing.com/GetDotaStats">Site
        Chatbox</a>
    <a target="_blank" class="btn btn-danger btn-sm" href="https://github.com/GetDotaStats/stat-collection/issues">Github
        Issues</a>
    <a target="_blank" class="btn btn-danger btn-sm" href="http://steamcommunity.com/id/jimmydorry/">Steam</a>
    <a target="_blank" class="btn btn-danger btn-sm"
       href="http://steamcommunity.com/groups/getdotastats/discussions/1/">Steam Group</a>
</div>

<p>&nbsp;</p>

<p>I don't add randoms on steam, so leave a comment before adding me.</p>


<h3>Miscellaneous Notes</h3>

<ul>
    <li>Do not re-use IDs for abilities, items, etc. If you remove an item from the game, and later add another, it is
        important that you do not re-use an existing ID as this will break the integrity of your stats database.
    </li>
</ul>