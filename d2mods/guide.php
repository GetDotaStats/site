<h1>Integrating Stat Collection</h1>
<hr/>

<span class="h4">&nbsp;</span>

<div class="text-center">
    <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__mod_request">Add a mod</a>
    <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__my_mods">My mods</a>
</div>

<span class="h4">&nbsp;</span>

<div class="alert alert-danger" role="alert"><strong>Special thanks to:</strong> <a href="https://github.com/SinZ163/"
                                                                                    target="_blank">SinZ163</a>, <a
        href="https://github.com/tetl/" target="_blank">Tet</a>, and <a href="https://github.com/ash47/"
                                                                        target="_blank">Ash47</a> for their hard-work in
    testing and developing the Lua and Flash code that makes this all possible. I take no credit for the Lua and Flash
    found here, as I have just collated and reformatted it.
</div>

<p>The example repo can be found here: <a href="https://github.com/GetDotaStats/stat-collection/"
                                                    target="_blank">GetDotaStats/stat-collection</a>. All of the
    required libraries and example code is in there. Implementation simply involves splicing the statcollection logic
    into your mod.</p>

<hr/>

<h2>Quick Start</h2>

<ol>
    <li>Drop your mod into a local copy of our example repo for <a href="https://github.com/GetDotaStats/stat-collection/"
                                                                target="_blank">GetDotaStats/stat-collection</a>, making
        sure to merge your <code>addon_game_mode.lua</code> with ours.
    </li>
    <li>Register your mod <a target="_blank" href="#d2mods__mod_request">on our site</a>. Take the time to read the
        description of all the fields, as they can only be changed by site staff. The map field is important, as an
        incorrect entry there will prevent users from playing the mod via the Lobby Explorer!
    </li>
    <li>Update the <code>addon_game_mode.lua</code> by replacing the placeholder modID with the
        one you got from registration <a target="_blank" href="#d2mods__my_mods">on our site</a>.
    </li>
    <li>Make sure that your mod sets an end game condition nicely. The stat-collection library listens for the
        "game_state" being changed to "postgame" which will generally happen when you invoke SetGameWinner().
    </li>
    <li>Run the mod via the workshop tools and successfully complete a run through of your mod (i.e. get to the end game
        condition)
    </li>
    <li>Check the <a target="_blank" href="//getdotastats.com/d2mods/log-test.html?1">test-log</a> for a reference to
        your modID. (<strong>Note:</strong> Some browsers cache that page strangely. Make sure to increment the number
        at the end of the URL after each refresh)
    </li>
    <li>After passing the test (seeing your modID in the log-test), modify your <code>scripts/stat_collection.kv</code>
        by changing the "live" value from 0 to 1
    </li>
    <li>Run the mod again via the workshop tools and successfully complete a run through of your mod (i.e. get to the
        end game condition), making sure the game lasts for more than 3 minutes.
    </li>
    <li>Within a minute of successfully completing a game, your mod page should now show that you have a game
        recorded. Your mod will be approved the next time a site staff member checks the queue. You can hasten this
        process by notifying someone in the <a target="_blank"
                                               href="https://kiwiirc.com/client/irc.gamesurge.net/?#getdotastats">IRC
            channel</a>.
    </li>
    <li>If your mod has any options that can be set by users at the start of the game, you will want to take the time to
        also integrate the stat-options library (not detailed in this guide, but can be found in the root of the
        example repo). Enabling this add-on will allow users to set these options in the lobby, before the game starts. In
        the future, users will also be able to filter lobbies based on these options.
    </li>
    <li>There are further helpful libraries for services we offer, including:
        <ul>
            <li><a target="_blank" href="https://github.com/GetDotaStats/stat-highscore">stat-highscore</a> <span
                        class="label label-success">LIVE</span> -- Enables users to have personal scores and a
                global leaderboard that can be viewed on the site.
            </li>
            <li><a target="_blank" href="https://github.com/GetDotaStats/stat-rpg">stat-rpg</a> <span
                        class="label label-warning">TEST</span> -- Enables persistent data across game sessions.
                Great for RPG experiences where you want characters to carry over into new games.
            </li>
            <li><a target="_blank" href="https://github.com/GetDotaStats/stat-achievements">stat-achievements</a> <span
                        class="label label-danger">WIP</span> -- Enables mods to have achievements that users can
                unlock
                that look and work like regular steam achievements.
            </li>
        </ul>
    </li>
</ol>

<hr/>

<h2>Troubleshooting Guide</h2>

<ol>
    <li>Ensure that you have all the files from the <a href="https://github.com/GetDotaStats/stat-collection/"
                                                       target="_blank">GetDotaStats/stat-collection</a> example repo in
        your mod.
    </li>
    <li>Check the whitespaces and brackets in your <code>*.kv</code> and <code>*.txt</code> files.</li>
    <li>Check the numbering and strip comments in your <code>flash3/custom_ui.txt</code> file. If you do not have any
        custom UI elements, make "StatsCollection" the first entry.
    </li>
    <li>In the console enable additional logging <code>scaleform_spew 1</code> and look for any errors relating to the
        stat-collection.
    </li>
    <li>If all of the above fails, get in contact with us via any of the methods at the bottom of this page. The
        recommended channel of communication is via IRC.
    </li>
</ol>

<hr/>

<h2>Detailed guide</h2>

<p>To get more out of this module, you will also need to refer to the
    <a href="#d2mods__guide_schema"
       target="_blank">schema</a> to see which data is not automatically
    captured. If you see data that you can collect and add to the schema, get in contact with us and we will try and
    accommodate your changes into the official schema, or add custom fields for your mod. This manually added data can
    be added during game play by calling addStats() method with your array of stats.</p>

<div class="alert alert-danger" role="alert">
    <span class="glyphicon glyphicon-exclamation-sign"></span>
    Code on this page may be out of date. Always use the Github where possible!
</div>

<h4>Put the LUA libraries in your "scripts/vscripts/lib/*" - <a
        href="https://github.com/GetDotaStats/stat-collection/tree/master/statcollection/scripts/vscripts/lib"
        target="_blank">GitHub</a></h4>

<pre class="pre-scrollable">
    The three libraries:

    * json.lua
    * md5.lua
    * statcollection.lua
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

    Testing = false --Useful for turning off stat-collection when developing

    if not Testing then --Only send stats when not testing
      statcollection.addStats({
        modID = 'XXXXXXXXXXXXXXXXXXX' --GET THIS FROM http://getdotastats.com/#d2mods__my_mods
      })
    end

    print( "Example stat collection game mode loaded." )
</pre>

<h4>Include the <strong><em>compiled</em></strong> flash code for sending data in your "resource/flash3" folder - <a
        href="https://github.com/GetDotaStats/stat-collection/raw/master/statcollection/resource/flash3/StatsCollection.swf"
        target="_blank">GitHub</a>
</h4>

<p>You can delete the <code>FlashSource</code> folder, as it is not used at all.</p>

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

<p>If you have existing UI elements, put them before the StatsCollection, otherwise put StatsCollection as the first.
    Your existing UI elements will probably need to be at a larger depth than the StatsCollection. Whitespace is
    important in this file.</p>

<pre class="pre-scrollable">
    "CustomUI"
    {
        "1"
        {
            "File" "StatsCollection"
            "Depth" "1"
        }
    }
</pre>

<h4>Create a custom event in your "scripts/custom_events.txt" - <a
        href="https://github.com/GetDotaStats/stat-collection/blob/master/statcollection/scripts/custom_events.txt"
        target="_blank">GitHub</a></h4>

<p>Check the whitespace in this file, as there have been reports that badly formatted files cause errors.</p>

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

<h4>Add flags to indicate mod settings <strong>(OPTIONAL)</strong> - <a
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

<h4>Add modes to indicate host options <strong>(OPTIONAL)</strong> - <a
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

<h4>Add module stats on your modules <strong>(OPTIONAL)</strong> - <a
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

<hr/>

<h2>Communication Channels</h2>

<p>By this stage, you should hopefully have stats integrated. This method of stat collection is new
    and experimental, so feel free to get in contact with us.</p>

<p>We strongly recommend that all developers join the <a target="_blank"
                                                         href="http://steamcommunity.com/groups/getdotastats-dev">Developer
        Steam Group</a> and subscribe to the relevant topics they will want updates on. As of writing they are:</p>

<span class="h4">&nbsp;</span>

<div class="text-center">
    <a target="_blank" class="btn btn-success btn-sm"
       href="http://steamcommunity.com/groups/getdotastats-dev/discussions/0/617328415073923572/">Site Changes</a>
    <a target="_blank" class="btn btn-success btn-sm"
       href="http://steamcommunity.com/groups/getdotastats-dev/discussions/0/617328415073922155/">Stat Collection</a>
    <a target="_blank" class="btn btn-success btn-sm"
       href="http://steamcommunity.com/groups/getdotastats-dev/discussions/0/617328415073932793/">Lobby Explorer</a>
    <a target="_blank" class="btn btn-success btn-sm"
       href="http://steamcommunity.com/groups/getdotastats-dev/discussions/0/617328415073939543/">Mini Games</a>
</div>

<span class="h4">&nbsp;</span>

<p>The official channels of contact are:</p>

<div class="text-center">
    <a target="_blank" class="btn btn-danger btn-sm" href="https://kiwiirc.com/client/irc.gamesurge.net/?#getdotastats">IRC
        #getdotastats</a>
    <a target="_blank" class="btn btn-danger btn-sm" href="http://chatwing.com/GetDotaStats">Site
        Chatbox</a>
    <a target="_blank" class="btn btn-danger btn-sm" href="https://github.com/GetDotaStats/stat-collection/issues">Github
        Issues</a>
    <a target="_blank" class="btn btn-danger btn-sm" href="http://steamcommunity.com/id/jimmydorry/">Steam</a>
    <a target="_blank" class="btn btn-danger btn-sm"
       href="http://steamcommunity.com/groups/getdotastats/discussions/1/">Steam Group</a>
</div>

<span class="h4">&nbsp;</span>

<p>I don't add randoms on steam, so leave a comment before adding me.</p>