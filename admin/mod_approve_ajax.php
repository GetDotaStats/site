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

    $adminCheck = adminCheck($_SESSION['user_id64'], 'admin');
    if (empty($adminCheck)) throw new Exception('Not an admin!');

    if (
        empty($_POST['modID']) ||
        empty($_POST['modName']) ||
        empty($_POST['modMaps']) || $_POST['modMaps'] == 'One map per line' ||
        empty($_POST['modDescription']) ||
        empty($_POST['m_submit']) || ($_POST['m_submit'] != 'Approve' && $_POST['m_submit'] != 'Reject')

    ) {
        throw new Exception('Missing or invalid required parameter(s)!');
    }

    if ($_POST['m_submit'] == 'Reject' && empty($_POST['modRejectedReason'])) {
        throw new Exception('No reason given for mod rejection!');
    }

    $modID = htmlentities($_POST['modID']);
    $modName = htmlentities($_POST['modName']);
    $modDescription = htmlentities($_POST['modDescription']);
    $modGroup = !empty($_POST['modGroup'])
        ? htmlentities($_POST['modGroup'])
        : NULL;
    $modMaps = json_encode(array_map('trim', explode("\n", htmlentities($_POST['modMaps']))));
    $modRejected = $_POST['m_submit'] == 'Approve'
        ? 0
        : 1;
    $modRejectedReason = !empty($_POST['modRejectedReason']) && $modRejected == 1
        ? htmlentities($_POST['modRejectedReason'])
        : NULL;
    $modActive = $modRejected == 1
        ? 0
        : 1;

    $insertSQL = $db->q(
        'UPDATE `mod_list`
          SET
            `mod_active` = ?,
            `mod_name` = ?,
            `mod_description` = ?,
            `mod_steam_group` = ?,
            `mod_maps` = ?,
            `mod_rejected` = ?,
            `mod_rejected_reason` = ?
          WHERE `mod_id` = ?;',
        'issssiss',
        $modActive, $modName, $modDescription, $modGroup, $modMaps, $modRejected, $modRejectedReason, $modID
    );

    if ($insertSQL) {
        $json_response['result'] = 'Custom Game updated!';

        $queryResult = $modRejected == 1
            ? 'Rejected'
            : 'Approved';

        $irc_message = new irc_message($webhook_gds_site_admin);

        $message = array(
            array(
                $irc_message->colour_generator('red'),
                '[ADMIN]',
                $irc_message->colour_generator(NULL),
            ),
            array(
                $irc_message->colour_generator('green'),
                '[MOD]',
                $irc_message->colour_generator(NULL),
            ),
            array(
                $irc_message->colour_generator('bold'),
                $irc_message->colour_generator('blue'),
                $queryResult . ':',
                $irc_message->colour_generator(NULL),
                $irc_message->colour_generator('bold'),
            ),
            array($modName),
            array(
                $irc_message->colour_generator('bold'),
                $irc_message->colour_generator('blue'),
                'Reason:',
                $irc_message->colour_generator(NULL),
                $irc_message->colour_generator('bold'),
            ),
            array(substr($modRejectedReason, 0, 100)),
            array(' || http://getdotastats.com/#d2mods__stats?id=' . $modID),
        );

        $message = $irc_message->combine_message($message);
        $irc_message->post_message($message, array('localDev' => $localDev));
    } else {
        throw new Exception('Custom Game not updated!');
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