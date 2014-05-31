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

        $steamid64 = '';
        if (!empty($_SESSION['user_id'])) {
            $steamid64 = $_SESSION['user_id'];
        }

        $user_details = !empty($_SESSION['user_details'])
            ? $_SESSION['user_details']
            : NULL;

        if (empty($steamid64)) {
            echo 'To sign-up for your invite to D2Modd.in, login via steam. After logging in, you will be entered into the queue for an invite.<br /><br />';
            echo '<a href="./d2moddin/auth/?login"><img src="./d2moddin/assets/images/steam_small.png" alt="Sign in with Steam"/></a><br /><br />';
        } else {
            echo '<strong>Logged in as:</strong> ' . $user_details->personaname . '<br />';
            echo '<a href="./d2moddin/auth/?logout">Logout</a><br /><br />';

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

            $gotDBstats = $db->q(
                'SELECT * FROM `invite_key` WHERE `steam_id` = ? LIMIT 0,1;',
                'i',
                $steamid64
            );
            $gotDBstats = $gotDBstats[0];

            print_r($gotDBstats);

        }
    } else {
        echo 'No DB';
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
?>
