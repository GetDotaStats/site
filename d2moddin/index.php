<?php
require_once('./functions.php');
require_once('./connections/parameters.php');

if (!isset($_SESSION)) {
    session_start();
}

try {
    $db = new dbWrapper($hostname, $username, $password, $database, false);
    if ($db) {
        $memcache = new Memcache;
        $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

        $steamid64 = null;
        if (!empty($_SESSION['user_id'])) {
            $steamid64 = $_SESSION['user_id'];
        }

        $user_details = !empty($_SESSION['user_details'])
            ? $_SESSION['user_details']
            : NULL;

        if (!empty($steamid64)) {
            $gotDBstats = $db->q(
                'SELECT * FROM `invite_key` WHERE `steam_id` = ? LIMIT 0,1;',
                'i',
                $steamid64
            );
            if (empty($gotDBstats)) {
                $gotDBstats = $db->q(
                    'INSERT INTO `invite_key` (`steam_id`) VALUES (?);',
                    'i',
                    $steamid64
                );
            }
        }

        if (empty($steamid64)) {
            echo 'To sign-up for your invite to D2Modd.in, login via steam. Logging in does not grant us access to your private stats, like MMR. After logging in, you will be entered into the queue for an invite.<br /><br />';
            echo '<a href="./d2moddin/auth/?login"><img src="./d2moddin/assets/images/steam_small.png" alt="Sign in with Steam"/></a><br /><br />';
        } else {
            echo '<strong>Logged in as:</strong> ' . $user_details->personaname . '<br />';
            echo '<a href="./d2moddin/auth/?logout">Logout</a><br /><br />';
        }

        $gotDBstats = $db->q(
            'SELECT * FROM `invite_key` WHERE `steam_id` = ? LIMIT 0,1;',
            'i',
            $steamid64
        );
        if (!empty($gotDBstats)) {
            $gotDBstats = $gotDBstats[0];
            print_r($gotDBstats);
        }

        exit();

        if (((!isset($_GET['status']) && empty($gotDBstats)) || $_GET['status'] == 'readd') && !empty($steamid32)) {
            $token_response = $steamtracks->signup_token($steamid32, 'true'); //GET TOKEN

            if (!empty($token_response['result']['token'])) {
                $token = $token_response['result']['token'];
                echo '<br /><br /><a href="https://steamtracks.com/appauth/' . $token . '">CLICK HERE TO GIVE US ACCESS TO ADD YOUR MRR TO THE ABOVE SIGNATURE</a><br /><br />';
            } else {
                var_dump($token_response);
            }
        } else if (!empty($gotDBstats)) {
            echo 'We already have stats for you. If you removed yourself from the app, you can <a href="./?status=readd">re-add yourself here</a>.<br />';
        } else if (isset($_GET['status'])) {
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
                    echo 'Missing steam_id or token. <a href="./">Please try again.</a>';
                    break;
            }
            echo '<br />';
        }

    } else {
        echo 'No DB';
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
?>
