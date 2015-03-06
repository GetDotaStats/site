<?php
require_once('../global_functions.php');
require_once('./functions.php');
require_once('../connections/parameters.php');

try {
    if (!isset($_SESSION)) {
        session_start();
    }

    $db = new dbWrapper_v3($hostname_steamtracks, $username_steamtracks, $password_steamtracks, $database_steamtracks, false);

    if (!empty($_SESSION['user_id64'])) {
        $steamid64 = $_SESSION['user_id64'];
        $steamid32 = $_SESSION['user_id32'];
    }

    echo '<h3>Signature Generator</h3>';

    echo '<span class="h4">&nbsp;</span>';

    echo '<div class="panel panel-warning">
            <div class="panel-heading">
                <h3 class="panel-title">Update!</h3>
            </div>
            <div class="panel-body">We have just rolled out the new MMR tracking system. The MMR on your signature is now
            obtained via our Lobby Explorer. Make sure you are running the latest version, and have opted into MMR sharing.
            Be aware that for the first 2hours of its release, you may need to hard refresh your signature (CTRL + R) to see it updated.
            Report issues to our <a target="_blank" href="http://github.com/GetDotaStats/site/issues">Issue Tracker</a>.
            </div>
        </div>';

    echo '<span class="h4">&nbsp;</span>';

    if (empty($steamid32)) {
        echo 'To get your own Dota2 signature, login via steam at the top right of the screen. Logging in does not grant us access to your private data. After logging in, you will be presented with your signature and also have the option of adding your MMR to your signature via SteamTracks OAuth.<br /><br />';
    } else {
        echo '<img src="http://getdotastats.com/sig/' . $steamid32 . '.png" /><br />';
        echo '<strong>Your signature link:</strong> <a target="__new" href="http://getdotastats.com/sig/' . $steamid32 . '.png">http://getdotastats.com/sig/' . $steamid32 . '.png</a><br /><br />';

        echo '<div class="panel panel-danger">
            <div class="panel-heading">
                <h3 class="panel-title">Note</h3>
            </div>
            <div class="panel-body">Signatures are cached for 2 hours. MMR is updated in our back-end every time you open the
            Dota2 client (if the MMR has changed since the last report), but not updated on the signature until it expires. Your username
            and avatar only update when you login to the site again. We will make it easier to expire your signature in the near future.
            </div>
        </div>';
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
