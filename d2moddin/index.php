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
            echo '<strong>User ID:</strong> ' . $steamid64 . '<br />';
            echo '<a href="./d2moddin/auth/?logout">Logout</a><br /><br />';

            $gotDBstats = simple_cached_query('d2moddin_user'.$steamid64,
                "SELECT * FROM `invite_key` WHERE `steam_id` = " . $steamid64 . " LIMIT 0,1;",
                30);
            if (empty($gotDBstats)) {
                $gotDBstats = $db->q(
                    'INSERT INTO `invite_key` (`steam_id`) VALUES (?);',
                    'i',
                    $steamid64
                );

                $memcache->delete('d2moddin_user'.$steamid64);
                $gotDBstats = simple_cached_query('d2moddin_user'.$steamid64,
                    "SELECT * FROM `invite_key` WHERE `steam_id` = " . $steamid64 . " LIMIT 0,1;",
                    30);
            }

            print_r($gotDBstats);

        }
    } else {
        echo 'No DB';
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
?>
