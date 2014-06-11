<?php
require_once('./functions.php');
require_once('../connections/parameters.php');

try {
    if (!isset($_SESSION)) {
        session_start();
    }

    $db = new dbWrapper($hostname_steamtracks, $username_steamtracks, $password_steamtracks, $database_steamtracks, false);
    $steamtracks = new steamtracks($steamtracks_api_key, $steamtracks_api_secret, false);

    if (!empty($_SESSION['user_id'])) {
        $steamid64 = $_SESSION['user_id'];
        $steamid32 = convert_steamid($steamid64);
    }

    $user_details = $_SESSION['user_details'];

    $user_name = !empty($user_details->personaname)
        ? $user_details->personaname
        : NULL;

    if ($_GET['status'] == 'success' && !empty($steamid32)) {
        $file_name_location = '../sig/images/generated/' . $steamid32 . '.png';

        if (file_exists($file_name_location)) {
            @unlink($file_name_location);
        }
    }


    if (isset($_GET['status'])) {
        echo '<span class="warning">';
        switch ($_GET['status']) {
            case 'success':
                echo 'Sucessfully enrolled as new user!';
                break;
            case 'sqlfailure':
                echo 'Could not insert your stats into database. This means that we may already have stats for you. If you can see the app listed under your steamtracks apps list, and you have the bot added, then we will automatically grab your stats later.';
                break;
            case 'apifailure':
                echo 'Failure receiving account stats. If you signed up correctly, we will retry grabbing your stats automatically at a later date.';
                break;
            case 'missingidtoken':
                echo 'Missing steam_id or token. <a class="nav-clickable" href="#steamtracks/?status=apifailure">Please try again.</a>';
                break;
            case 'sidfailure':
                echo 'Bad Steam ID given via SteamTracks. Report this error in the chatbox or github';
                break;
        }
        echo '</span><br />';
    }

    if (empty($steamid32)) {
        echo 'To get your own Dota2 signature, login via steam. Logging in does not grant us access to your private stats, like MMR. After logging in, you will be presented with your signature and also have the option of adding your MMR to your signature via SteamTracks OAuth.<br /><br />';
        echo '<a href="./steamtracks/auth/?login"><img src="./steamtracks/assets/images/steam_small.png" alt="Sign in with Steam"/></a><br /><br />';
    } else {
        echo '<strong>Logged in as:</strong> ' . $user_name . '<br />';

        echo '<a class="nav-clickable" href="#steamtracks__auth/?logout">Logout</a><br /><br />';

        echo '<img src="http://getdotastats.com/sig/' . $steamid32 . '.png" /><br />';
        echo '<strong>Your signature link:</strong> <a target="__new" href="http://getdotastats.com/sig/' . $steamid32 . '.png">http://getdotastats.com/sig/' . $steamid32 . '.png</a><br /><br />';

        echo 'Signatures are cached for up to 2hours. MMR stats are updated every 12hours. As long as you have the bot added, do not fret! Your stats will eventually update.<br /><br />';

        echo '<strong>Adding MMR to your sig:</strong><br />';
    }

    $gotDBstats = $db->q(
        'SELECT * FROM `mmr` WHERE `steam_id` = ? LIMIT 0,1;',
        'i',
        $steamid32
    );

    if (((!isset($_GET['status']) && empty($gotDBstats)) || $_GET['status'] == 'readd') && !empty($steamid32)) {
        $token_response = $steamtracks->signup_token($steamid32, 'true'); //GET TOKEN

        if (!empty($token_response['result']['token'])) {
            $token = $token_response['result']['token'];
            echo '<br /><br /><a href="https://steamtracks.com/appauth/' . $token . '">CLICK HERE TO GIVE US ACCESS TO ADD YOUR MRR TO THE ABOVE SIGNATURE</a><br /><br />';
        } else {
            var_dump($token_response);
        }
    } else if (!empty($gotDBstats)) {
        echo 'We already have stats for you. If you removed yourself from the app, you can <a class="nav-clickable" href="#steamtracks/?status=readd">re-add yourself here</a>.<br />';
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
