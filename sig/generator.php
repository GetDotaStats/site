<?php
try {
    require_once('../global_functions.php');
    require_once('../connections/parameters.php');

    if (!isset($_SESSION)) {
        session_start();
    }

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    checkLogin_v2();
    if (empty($_SESSION['user_id64'])) throw new Exception('Not logged in!');

    $steamID = new SteamID($_SESSION['user_id64']);
    if (empty($steamID->getSteamID32()) || empty($steamID->getSteamID64())) throw new Exception('Bad steamID!');

    echo '<h3>Signature Generator</h3>';

    echo '<span class="h4">&nbsp;</span>';

    echo '<div class="panel panel-warning">
            <div class="panel-heading">
                <h3 class="panel-title">Update!</h3>
            </div>
            <div class="panel-body">We have just rolled out the new MMR tracking system. The MMR on your signature is now
            obtained via our Lobby Explorer. Make sure you are running the latest version, and have opted into MMR sharing.
            Report issues to our <a target="_blank" href="http://github.com/GetDotaStats/site/issues">Issue Tracker</a>.
            </div>
        </div>';

    echo '<span class="h4">&nbsp;</span>';

    if (empty($steamID->getSteamID32())) {
        echo 'To get your own Dota2 signature, login via steam at the top right of the screen. Logging in does not grant us access to your private data. After logging in, you will be presented with your signature and also have the option of adding your MMR to your signature via SteamTracks OAuth.<br /><br />';
    } else {
        if (isset($_GET['refresh'])) {
            updateUserDetails($steamID->getSteamID64(), $api_key1);

            $file_name_location = './images/generated/' . $steamID->getsteamID32() . '_main.png';
            if (file_exists($file_name_location)) {
                @unlink($file_name_location);
            }
        }

        echo '<div class="row">
                <div class="text-center">
                    <img src="http://getdotastats.com/sig/' . $steamID->getSteamID32() . '.png" /><br />
                    <p><strong>Your signature link:</strong> <a target="__new" href="http://getdotastats.com/sig/' . $steamID->getSteamID32() . '.png">http://getdotastats.com/sig/' . $steamID->getSteamID32() . '.png</a></p>
                    <p><a class="nav-clickable btn btn-danger btn-lg" href="#sig__generator/?refresh">Refresh Signature</a></p>
                </div>
            </div>';

        echo '<div class="panel panel-danger">
            <div class="panel-heading">
                <h3 class="panel-title">Note</h3>
            </div>
            <div class="panel-body">Signatures are cached for 2 hours in your browser. After refreshing your signature, you will
            need hard refresh (CTRL + R) on the image link to expire it in your browser. This will not work in all browsers.
            MMR is updated every time you open the Dota2 client. Your username and avatar only update when you login to the
            site again, or click the Refresh button above.
            </div>
        </div>';
    }
} catch (Exception $e) {
    echo formatExceptionHandling($e);
} finally {
    if (isset($memcache)) $memcache->close();
}