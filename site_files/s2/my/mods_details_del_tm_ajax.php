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

    $modID = htmlentities($_POST['mod_id']);
    if (empty($_POST['team_member']) || !is_numeric($_POST['team_member'])) {
        throw new Exception('Missing or invalid required parameter!');
    }
    $teamMember = $_POST['team_member'];

    //Check if logged in user is on team
    $modDetailsAuthorisation = $db->q(
        'SELECT
                `mod_id`
              FROM mod_list_owners
              WHERE
                `mod_id` = ? AND
                `steam_id64` = ?
              LIMIT 0,1;',
        'is',
        array($modID, $_SESSION['user_id64'])
    );

    //Check if logged in user is an admin
    $adminCheck = adminCheck($_SESSION['user_id64'], 'admin');

    if (empty($modDetailsAuthorisation) && !$adminCheck) {
        throw new Exception('Not authorised to modify this mod!');
    }

    //Check if the modID is valid
    $modIDCheck = cached_query(
        's2_my_mods_details' . $modID,
        'SELECT
              ml.`mod_id`,
              ml.`steam_id64` AS developer_id64,
              ml.`mod_identifier`,
              ml.`mod_name`,
              ml.`mod_steam_group`,
              ml.`mod_workshop_link`,
              ml.`mod_active`,
              ml.`mod_rejected`,
              ml.`mod_rejected_reason`,
              ml.`mod_size`,
              ml.`workshop_updated`,
              ml.`date_recorded` AS mod_date_added
          FROM `mod_list` ml
          WHERE ml.`mod_id` = ?;',
        'i',
        array($modID),
        5
    );

    if (empty($modIDCheck)) {
        throw new Exception('Invalid modID!');
    }

    if ($modIDCheck[0]['developer_id64'] == $teamMember) {
        throw new Exception('Can\'t remove mod owner!');
    }

    $removeSQL = $db->q(
        'DELETE FROM `mod_list_owners` WHERE `mod_id` = ? AND `steam_id64` = ?;',
        'is',
        array($modID, $teamMember)
    );

    if ($removeSQL) {
        $json_response['result'] = 'Success!';

        $irc_message = new irc_message($webhook_gds_site_normal);

        $message = array(
            array(
                $irc_message->colour_generator('red'),
                '[MOD]',
                $irc_message->colour_generator(NULL),
            ),
            array(
                $irc_message->colour_generator('green'),
                '[TEAM MEMBERS]',
                $irc_message->colour_generator(NULL),
            ),
            array(
                $irc_message->colour_generator('bold'),
                $irc_message->colour_generator('blue'),
                'Removed:',
                $irc_message->colour_generator(NULL),
                $irc_message->colour_generator('bold'),
            ),
            array($teamMember . ' from'),
            array(
                $irc_message->colour_generator('orange'),
                $modIDCheck[0]['mod_name'],
                $irc_message->colour_generator(NULL),
            ),
            array(' || http://getdotastats.com/#s2__my__mods_details?id=' . $modID),
        );

        $message = $irc_message->combine_message($message);
        $irc_message->post_message($message, array('localDev' => $localDev));
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