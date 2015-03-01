<?php
require_once('./functions.php');
require_once('../global_functions.php');
require_once('../connections/parameters.php');

if (!isset($_SESSION)) {
    session_start();
}

try {
    echo '<h2>Request Mini Game</h2>';

    echo '<p>Until we can make a robust procedure for adding mini-games to our Lobby Explorer package, all requests will need to go through site admins and moderators, and be handled manually.
        Please get in contact with us via any of the methods below. Our IRC channel is the preferred method, as the chatbox can move fast and go unnoticed by staff.</p>';

    echo '<p>For those not familiar with IRC, try the <a target="_blank" href="https://kiwiirc.com/client/irc.gamesurge.net/?#getdotastats">kiwiirc client</a>.</p>';

    echo '<p>&nbsp;</p>';

    echo '<div class="text-center">
                <a target="_blank" class="btn btn-danger btn-sm" href="irc://irc.gamesurge.net:6667/#getdotastats">IRC #getdotastats</a>
                <a target="_blank" class="btn btn-danger btn-sm" href="http://chatwing.com/GetDotaStats">Site Chatbox</a>
                <a target="_blank" class="btn btn-danger btn-sm" href="https://github.com/GetDotaStats/GetDotaLobby/issues">Github Issues</a>
                <a target="_blank" class="btn btn-danger btn-sm" href="http://steamcommunity.com/id/jimmydorry/">Steam Profile</a>
                <a target="_blank" class="btn btn-danger btn-sm" href="http://steamcommunity.com/groups/getdotastats/discussions/2/">Steam Group</a>
        </div>';

    echo '<p>&nbsp;</p>';

    echo '<p>I don\'t add randoms on steam, so leave a comment on my profile before adding me.</p>';

    echo '<p>
                <div class="text-center">
                    <!--<a class="nav-clickable btn btn-default btn-lg" href="#d2mods__minigame_directory">Mini Game Directory</a>-->
                    <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__my_minigames">My Mini Games</a>
                    <!--<a class="nav-clickable btn btn-default btn-lg" href="#d2mods__minigame_guide">Mini Game Guide</a>-->
                </div>
            </p>';
} catch (Exception $e) {
    echo formatExceptionHandling($e);
}