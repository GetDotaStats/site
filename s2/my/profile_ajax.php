<?php
try {
    require_once('../../global_functions.php');
    require_once('../../connections/parameters.php');

    if (!isset($_SESSION)) {
        session_start();
    }

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    checkLogin_v2();
    if (empty($_SESSION['user_id64'])) throw new Exception('Not logged in!');

    $userID64 = $_SESSION['user_id64'];

    $steamIDmanipulator = new SteamID($userID64);
    $userID32 = $steamIDmanipulator->getsteamID32();
    $userID64 = $steamIDmanipulator->getSteamID64();

    if (
        empty($_POST['user_email']) ||
        !isset($_POST['sub_dev_news']) || ($_POST['sub_dev_news'] != 0 && $_POST['sub_dev_news'] != 1) ||
        !isset($_POST['mmr_public']) || ($_POST['mmr_public'] != 0 && $_POST['mmr_public'] != 1)
    ) {
        throw new Exception('Missing or invalid required parameter(s)!');
    }

    if (!filter_var($_POST['user_email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address!');
    }

    $userEmail = $_POST['user_email'];
    $userEmailSubbedDevNews = $_POST['sub_dev_news'];
    $mmrPublic = $_POST['mmr_public'];

    $userCheck = cached_query(
        's2_user_profile_page_check' . $userID64,
        'SELECT
                gu.`user_id64`,
                gu.`user_id32`,
                gu.`user_name`,
                gu.`user_avatar`,
                gu.`user_avatar_medium`,
                gu.`user_avatar_large`,
                gu.`date_recorded`
            FROM `gds_users` gu
            WHERE gu.`user_id64` = ?
            LIMIT 0,1;',
        's',
        $userID64,
        5
    );

    if (empty($userCheck)) {
        throw new Exception('User does no exist on site!');
    }

    $SQLresult = $db->q(
        'INSERT INTO `gds_users_options`
                (
                    `user_id32`,
                    `user_id64`,
                    `user_email`,
                    `sub_dev_news`,
                    `mmr_public`,
                    `date_recorded`
                )
            VALUES (?, ?, ?, ?, ?, NULL)
            ON DUPLICATE KEY UPDATE
                `user_email` = VALUES(`user_email`),
                `sub_dev_news` = VALUES(`sub_dev_news`),
                `mmr_public` = VALUES(`mmr_public`);',
        'sssii',
        array($userID32, $userID64, $userEmail, $userEmailSubbedDevNews, $mmrPublic)
    );

    if ($SQLresult) {
        $json_response['result'] = 'success';

        $irc_message = new irc_message($webhook_gds_site_admin);

        $message = array(
            array(
                $irc_message->colour_generator('red'),
                '[USER]',
                $irc_message->colour_generator(NULL),
            ),
            array(
                $irc_message->colour_generator('green'),
                '[PROFILE]',
                $irc_message->colour_generator(NULL),
            ),
            array(
                $irc_message->colour_generator('bold'),
                $irc_message->colour_generator('blue'),
                'Updated:',
                $irc_message->colour_generator(NULL),
                $irc_message->colour_generator('bold'),
            ),
            array(html_entity_decode($userCheck[0]['user_name'])),
            array(
                $irc_message->colour_generator('orange'),
                '[' . $userID64 . ']',
                $irc_message->colour_generator(NULL),
            ),
            array(' || http://getdotastats.com/#s2__user?id=' . $userID32),
        );

        $message = $irc_message->combine_message($message);
        $irc_message->post_message($message, array('localDev' => $localDev));
    } else {
        $json_response['error'] = 'No changes made to user profile.';
    }
} catch (Exception $e) {
    $json_response['error'] = 'Caught Exception: ' . $e->getMessage();
} finally {
    if (isset($memcache)) $memcache->close();
    if (!isset($json_response)) $json_response = array('error' => 'Unknown exception');
}

try {
    echo utf8_encode(json_encode($json_response));
} catch (Exception $e) {
    unset($json_response);
    $json_response['error'] = 'Caught Exception: ' . $e->getMessage();
    echo utf8_encode(json_encode($json_response));
}