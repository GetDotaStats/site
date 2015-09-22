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
        empty($_POST['schema_id']) || !is_numeric($_POST['schema_id'])
    ) {
        throw new Exception('Missing or invalid required parameter(s)!');
    }

    $schemaID = htmlentities($_POST['schema_id']);

    $schemaCheck = cached_query(
        'admin_schema_check_approve_reject',
        'SELECT
              *
            FROM `s2_mod_custom_schema` s2mcs
            LEFT JOIN `mod_list` ml ON s2mcs.`modID` = ml.`mod_id`
            WHERE s2mcs.`schemaID` = ?
            LIMIT 0,1;',
        'i',
        array($schemaID),
        1
    );

    if (empty($schemaCheck)) throw new Exception('Invalid schema!');

    $schemaApproverUserID64 = $_SESSION['user_id64'];

    $schemaApproved = !empty($_POST['schema_approved']) && $_POST['schema_approved'] == 1
        ? 1
        : 0;
    $schemaRejected = !empty($_POST['schema_rejected']) && $_POST['schema_rejected'] == 1
        ? 1
        : 0;

    if ($schemaRejected == 1) {
        //ensure that rejected schemas have a reason
        if (empty($_POST['schema_rejected_reason'])) throw new Exception('Must give reason for rejecting mod!');
        //prevent approving and rejecting a mod at the same time
        if ($schemaApproved == 1) throw new Exception('Must un-approve mod if rejecting!');

        $schemaRejectedReason = htmlentities($_POST['schema_rejected_reason']);
    } else {
        $schemaRejectedReason = NULL;
    }

    $updateSQL = $db->q(
        'UPDATE `s2_mod_custom_schema` SET `schemaApproved` = ?, `schemaRejected` = ?, `schemaRejectedReason` = ? WHERE `schemaID` = ?;',
        'iisi',
        $schemaApproved, $schemaRejected, $schemaRejectedReason, $schemaID
    );

    if ($updateSQL) {
        $SQLresult = $schemaApproved == 1
            ? 'Approved:'
            : 'Rejected:';
        $json_response['result'] = "Success! Custom Game Schema #$schemaID now $SQLresult.";

        $SQLresult .= $schemaApproved == 1
            ? ''
            : ' `' . $schemaRejectedReason . '`';

        $irc_message = new irc_message($webhook_gds_site_admin);

        $message = array(
            array(
                $irc_message->colour_generator('red'),
                '[ADMIN]',
                $irc_message->colour_generator(NULL),
            ),
            array(
                $irc_message->colour_generator('green'),
                '[SCHEMA]',
                $irc_message->colour_generator(NULL),
            ),
            array(
                $irc_message->colour_generator('bold'),
                $irc_message->colour_generator('blue'),
                $SQLresult,
                $irc_message->colour_generator(NULL),
                $irc_message->colour_generator('bold'),
            ),
            array($schemaCheck[0]['mod_name']),
            array(
                $irc_message->colour_generator('orange'),
                'v' . $schemaCheck[0]['schemaVersion'],
                $irc_message->colour_generator(NULL),
            ),
            array(' || http://getdotastats.com/#admin__mod_schema_edit?id=' . $schemaID),
        );

        $message = $irc_message->combine_message($message);
        $irc_message->post_message($message, array('localDev' => $localDev));
    } else {
        throw new Exception('No changes made to DB.');
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