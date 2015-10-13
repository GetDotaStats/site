<?php
if (!isset($_SESSION)) {
    session_start();
}
?>
<div class="page-header"><h2>Integrating Stat Collection</h2></div>

<h3>Quick Start</h3>

<p>There are three stages of integration. For a fast integration, please follow these instructions in the correct
    order.</p>

<h4>Stage 1 - Before you begin</h4>

<ol>
    <li>Grab a copy of the library via our public repo <a target="_blank"
                                                          href="https://github.com/GetDotaStats/stat-collection">GetDotaStats/stat-collection</a>.
    </li>
    <li>Login to <a target="_blank" href="http://getdotastats.com">http://getdotastats.com</a>, by clicking the big
        green button at the top of this page.
    </li>
    <li>Register your mod on the site by navigating to `<em>Custom Games -> Mods (My Section) -> Add a new mod</em>`, or
        by going straight to the <a class="nav-clickable" href="#s2__my__mod_request">registration form</a>.
    </li>
    <li>Go back to your list of mods by navigating to `<em>Custom Games -> Mods (My Section)</em>`, or
        by going straight to the <a class="nav-clickable" href="##s2__my__mods">My Mods page</a>. You should now see a
        new entry there, that matches the mod your just registered.
    </li>
    <li>Take note of your modID key of 32characters. If you lose this string, refer back to this page.</li>
    <li>Make sure not to share this key, as it is unique to your mod and is used when recording stats! If you use
        Github, add a <code>.gitignore</code> file to the root of your project. Adding the following line to prevent
        accidentally leaking your modID.
        <pre>settings.kv</pre>
    </li>
    <li>An Admin will review your mod registration and approve it if it meets the submission guidelines outlined on the
        registration page. While your mod is reviewed, you can continue following this guide.
    </li>
</ol>

<h4>Stage 2 - Basic Integration</h4>

<p>Now that you have the library and have completed the sign-up process, we can start the actual integration.</p>

<ol>
    <li>Merge the files downloaded in (Stage 1 - Step 1). If done successfully, you will see a statcollection
        folder in your <code>game/YOUR_ADDON/scripts/vscripts</code> folder.
    </li>
    <li>In your <code>addon_game_mode.lua</code> file, add a require statement at the top of your code that points at
        our library initialiser file.
        <pre>require("statcollection/init")</pre>
    </li>
    <li>Go into the <code>scripts/vscripts/statcollection</code> folder and inside the <code>settings.kv</code> file,
        change the modID XXXXXXX value to the modID key you noted above (Stage 1 - Step 4).
    </li>
    <li>Check your game logic to ensure you set player win conditions nicely. This library hooks the SetGameWinner()
        function, so make sure to convert all of your MakeTeamLose() calls into SetGameWinner() calls. Also make sure to
        check every win and lose condition, as this library will only send stats at POST_GAME after a winner has been
        declared.
    </li>
    <?php
    if (!empty($_SESSION['user_id64'])) {
        $myProfileText = ', or by going straight to your <a
            class="nav-clickable" href="#s2__user?id=' . $_SESSION['user_id64'] . '">Public
            Profile</a>';
    } else {
        $myProfileText = '';
    }
    ?>
    <li>Test your custom game (via workshop tools is fine), and see if stats were recorded. You can find games recently
        recorded against your steamID by navigating to `<em>Custom Games -> Public Profile (My
            Section)</em>`<?= $myProfileText ?>.
    </li>
    <li>You have completed the basic integration successfully if your games are recorded with a Phase value of 3 or
        higher (a column in the tables on both pages). If you don't see any recorded games, or they are not reaching
        Phase 3, refer to the troubleshooting section below.
    </li>
    <li>Update your <code>settings.kv</code> by setting "TESTING" to false, and the "MIN_PLAYERS" to the minimum number
        of players required to have a proper game.
    </li>
</ol>

<h4>Stage 3 - Advanced Integration <code>OPTIONAL</code></h4>

<ul>
    <li>Now that you have basic stats, you are encouraged to create game-specific stats. Having a schema is the best way
        to acquire relevant stats about your custom game, such as pick and winrates of different heroes, keeping track
        of special game events, many other things that you might find appropriate to register and track. This
        information can help you decide what changes or additions to make.
    </li>
    <li>Keep in mind that all stats that you send need to form a snapshot of the end game results. Time Series data that
        attempts to match player actions to timings (like an array of item purchase times) do not belong in this library
        (we plan to release a solution for this soon). Data that you send us must not be too unique either (like an item
        build order that is slot sensitive). The data must be aggregatable given a large enough sample. The last thing
        to keep in mind is that values can not be longer than 100characters. We are working towards improving this in
        the near future.
    </li>
    <li>Making a custom schema requires that you build your own custom array of stats and write your own Lua functions
        to put data into them. In the <code>scripts/vscripts/statcollection/schema_examples</code> folder we provide
        examples of how various mods implemented their tracking.
    </li>
    <li>If your game uses a Round system (where progress is reset between rounds) and you would like to treat each round
        as a separate match, the library can handle it! You will need to get in contact with us for implementation
        concerns, but you would need to manually invoke the stat sending function and update your
        <code>settings.kv</code> to enable rounds.
    </li>
    <li>Sending custom data is done inside <code>schema.lua</code>. The data to send is split into 3 parts:
        <em>Flags</em>, <em>Game</em>, and <em>Players</em>.
        <ul>
            <li><strong>Flags</strong>
                <ul>
                    <li>The <em>Flags</em> array contains general information about the game, determined before the
                        PRE_GAME phase.
                    </li>
                    <li>Flags are recorded by calling the setFlags() function any where you can access the library class
                        from.
                    </li>
                    <li>The recommended place to set flags is near the top of your schema file in the init()
                        function.
                    </li>
                    <li>You can set the same flag multiple times. If the flag is already defined, it will be
                        overwritten.
                    </li>
                    <li>You can set a flag at any point of time, up until PRE_GAME.</li>
                    <li>A code example of setting a flag:
                        <pre>statCollection:setFlags({version = '4.20'})</pre>
                    </li>
                    <li>
                        Some examples of potential values are:
                        <ul>
                            <li>Mod version (manually incremented by the mod developer)</li>
                            <li>Map name (tracked by default)</li>
                            <li>Victory condition (e.g. 50kills, 10mins, etc.)</li>
                            <li>Lobby options</li>
                            <li>Hero selection options</li>
                        </ul>
                    </li>
                </ul>
            </li>
            <li><strong>Game</strong>
                <ul>
                    <li>The <em>Game</em> array should contain general info about the game session, determined after the
                        PRE_GAME phase.
                    </li>
                    <li>Refer to the default or example schemas (inside the <code>schema_examples</code> folder) for
                        implementation, specifically the lines in the BuildGameArray().
                    </li>
                    <li>Some examples of potential values are:
                        <ul>
                            <li>The number of Roshan kills</li>
                            <li>The number of remaining towers for Team #1</li>
                            <li>Any settings decided after the pre-game phase</li>
                        </ul>
                    </li>
                </ul>
            </li>
            <li><strong>Players</strong>
                <ul>
                    <li>The <em>Players</em> array should contain information specific to each player.</li>
                    <li>Refer to the default or example schemas (inside the <code>schema_examples</code> folder) for
                        implementation, specifically the lines in the BuildPlayersArray().
                    </li>
                    <li>Some examples of potential values are:
                        <ul>
                            <li>Hero name (you could use custom names)</li>
                            <li>Kills</li>
                            <li>Level</li>
                            <li>Item list (a comma delimited string of the item held at the end of the game)</li>
                            <li>Ability name</li>
                            <li>Ability level</li>
                            <li>Wood farmed</li>
                            <li>Buildings created</li>
                            <li>Trees planted</li>
                        </ul>
                    </li>
                </ul>
            </li>
        </ul>
    </li>
    <li><strong>Implementation steps</strong>
        <ol>
            <li>Create your schema after reading the above.</li>
            <li>Ensure that your <code>settings.kv</code> has "TESTING" set to true.</li>
            <li>Clear your console log and play a single match of your custom game.</li>
            <li>Save the console log to a pastebin, hastebin, or other text hosting service.</li>
            <li>Create a new issue in our <a target="_blank"
                                             href="https://github.com/GetDotaStats/stat-collection/issues/new">issue
                    tracker</a>, with the following in it:
                <ul>
                    <li>Issue Title: [SCHEMA] <em>Mod name</em></li>
                    <li>Issue Body:
                        <ul>
                            <li>Link to your console log</li>
                            <li>Link to your <code>settings.kv</code> (censor the modID)</li>
                            <li>Link to your <code>schema.lua</code></li>
                            <li>Link to your <code>addon_game_mode.lua</code> (and any other Lua file that defines the
                                functions you pull
                                stats from)
                            </li>
                        </ul>
                    </li>
                </ul>
            </li>
            <li>When an admin has accepted or looked at your schema, they will post back to that issue. There will
                likely be multiple iterations of your schema, as the admin will likely have suggestions for improvement.
            </li>
            <li>When your schema is accepted, go back to your mod list by navigating to `<em>Custom Games -> Mods (My
                    Section)</em>`, or by going straight to the <a class="nav-clickable" href="#s2__my__mods">My Mods
                    page</a>. Note your new schemaID, and update your <code>settings.kv</code> accordingly.
            </li>
        </ol>
    </li>
</ul>

<hr/>

<h4>Troubleshooting FAQ</h4>

<dl>
    <dt>It's not working!</dt>
    <dd>Look in your console log, and do a search for lines starting with "Stat Collection:"</dd>

    <dt>My Mod Stats (Stage 2) stopped working!</dt>
    <dd>
        <ul>
            <li>Have a look in your console log for an error.</li>
            <li>Check that your modID matches the one in your Mod page.</li>
        </ul>
    </dd>

    <dt>My Schema Stats (Stage 3) stopped working!</dt>
    <dd>
        <ul>
            <li>Have a look in your console log for an error.</li>
            <li>Check that your schemaID matches the one in your Mod page.</li>
        </ul>
    </dd>

    <dt>My custom game never reaches Phase 3!</dt>
    <dd>
        <ul>
            <li>Have a look in your console log for an error.</li>
            <li>Check your win conditions. We hook SetGameWinner(), so make sure you don't use MakeTeamLose().</li>
        </ul>
    </dd>
</dl>

<span class="h4">&nbsp;</span>