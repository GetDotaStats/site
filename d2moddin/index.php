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

            $d2moddin_user = simple_cached_query('d2moddin_user'.$steamid64,
                "SELECT * FROM `invite_key` WHERE `steam_id` = " . $steamid64 . " LIMIT 0,1;",
                30);
            if (empty($d2moddin_user)) {
                $d2moddin_user = $db->q(
                    'INSERT INTO `invite_key` (`steam_id`) VALUES (?);',
                    'i',
                    $steamid64
                );

                $memcache->delete('d2moddin_user'.$steamid64);
                $d2moddin_user = simple_cached_query('d2moddin_user'.$steamid64,
                    "SELECT * FROM `invite_key` WHERE `steam_id` = " . $steamid64 . " LIMIT 0,1;",
                    30);
            }

            $d2moddin_stats = simple_cached_query('d2moddin_stats',
                "SELECT COUNT(*) as total_users FROM `invite_key`;",
                30);
            $d2moddin_stats = $d2moddin_stats[0];

            print_r($gotDBstats);

            echo '<h1>You are #'.$d2moddin_user['queue_id'].' in the queue.</h1><br />';

            if($d2moddin_user['invited']){
                echo 'You have received an invite! <a href="http://d2modd.in/" target="_new">You can now login via d2moddin vai this link.</a>';
            }
            else{
                echo 'You have not been invited yet. There are '.$d2moddin_stats['total_users'].' in the queue.';
            }



        }
    } else {
        echo 'No DB';
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
?>
