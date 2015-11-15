<?php
try {
    require_once('../connections/parameters.php');
    require_once('../global_functions.php');

    if (!isset($_SESSION)) {
        session_start();
    }

    echo '
        <head>
            <link href="./hof/auction.css?9" rel="stylesheet" type="text/css" >
        </head>
        ';

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    checkLogin_v2();

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    $canAccessUserProfile = false;
    if (!empty($_SESSION['user_id64'])) {
        $accessCheck = $db->q('SELECT * FROM `hof_golden_profiles` WHERE `user_id64` = ? LIMIT 0,1;',
            's',
            $_SESSION['user_id64']);

        $adminCheck = adminCheck($_SESSION['user_id64'], 'admin');

        if (!empty($accessCheck) || !empty($_SESSION['isAdmin'])) {
            $canAccessUserProfile = true;
        }
    }

    $hofDetails = simple_cached_query(
        'hof_golden_profiles_list',
        'SELECT
              hof_gp.`auction_rank`,
              hof_gp.`user_id64`,
              hof_gp.`user_id32`,
              hof_gp.`isInGroup`,
              gdsu.`user_name`,
              gdsu.`user_avatar`,
              gdsu.`user_avatar_medium`,
              gdsu.`user_avatar_large`
            FROM `hof_golden_profiles` hof_gp
            LEFT JOIN `gds_users` gdsu ON hof_gp.`user_id64` = gdsu.`user_id64`
            ORDER BY hof_gp.`auction_rank` ASC;',
        10
    );
    if (empty($hofDetails)) throw new Exception('No Hall of Fame entries!');

    $table = '<div id="hof_block">';
    $table .= '<h2>2014 Holiday Profile - Hall of Fame</h2>';
    $table .= '<p>The below users are recognised for their valiant efforts in obtaining a limited edition <a href="http://steamcommunity.com/auction/item/1890-2014-Holiday-Profile" target="_blank">2014 Winter profile</a><br />A gold border indicates that the user is in the official <a href="http://steamcommunity.com/groups/golden_profiles" target="_blank">Steam Group</a></p>';

    foreach ($hofDetails as $key => $value) {
        $holidayBackground = 'http://cdn.akamai.steamstatic.com/steam/clusters/holiday2014_auction/dc9e02780a41ffde098796ac/golden_184x69_english.jpg?t=1418577448';
        $avatar = '//static.getdotastats.com/images/misc/hof/golden_profiles/new_user.jpg';

        if (!empty($value['user_id64']) && $value['user_id64'] != -1) {
            $avatar = !empty($value['user_avatar'])
                ? $value['user_avatar']
                : $avatar;

            $username = !empty($value['user_name'])
                ? $value['user_name']
                : '??';

            $isInGroup = !empty($value['isInGroup']) && $value['isInGroup'] == 1
                ? ' hof_avatar_in_group'
                : '';

            $usernameProfileLink = $canAccessUserProfile && !empty($value['user_id64']) && $value['user_id64'] != -1
                ? '<a class="hof_profile_link" target="_blank" href="http://steamcommunity.com/profiles/' . $value['user_id64'] . '">' . $username . '</a>'
                : $username;

            $table .= '<span class="auction_round auction_round_ended"><img class="round_bg" src="' . $holidayBackground . '"><span class="round_winner"><img class="hof_avatar' . $isInGroup . '" src="' . $avatar . ' " alt="">' . $usernameProfileLink . '</span></span>';

        } else {
            $table .= '<span class="auction_round"><img class="round_bg" src="' . $holidayBackground . '"></span>';
        }
    }

    $table .= '</div>';
    echo $table;

    echo '<br /><br />';

    echo '<div class="text-center">
                <a class="nav-clickable btn btn-default btn-lg" href="#s2__directory">Return to Home</a>
            </div>';
} catch (Exception $e) {
    $message = 'Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage();
    echo bootstrapMessage('Oh Snap', $message, 'danger');
} finally {
    if (isset($memcache)) $memcache->close();
}