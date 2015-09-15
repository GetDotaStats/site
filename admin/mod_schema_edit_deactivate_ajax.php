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
        empty($_POST['schema_id']) || !is_numeric($_POST['schema_id']) ||
        empty($_POST['schema_mod_id']) || !is_numeric($_POST['schema_mod_id'])
    ) {
        throw new Exception('Missing or invalid required parameter(s)!');
    }

    $schemaID = $_POST['schema_id'];
    $schemaModID = $_POST['schema_mod_id'];

    $schemaCheck = cached_query(
        'admin_schema_check_deactivate',
        'SELECT *
            FROM `s2_mod_custom_schema` s2mcs
            INNER JOIN `mod_list` ml ON s2mcs.`modID` = ml.`mod_id`
            WHERE s2mcs.`schemaID` = ? AND s2mcs.`modID` = ?
            LIMIT 0,1;',
        'ii',
        array($schemaID, $schemaModID),
        1
    );

    if(empty($schemaCheck)) throw new Exception('Invalid schema!');

    $updateSQL = $db->q(
        'UPDATE `s2_mod_custom_schema` SET `schemaApproved` = 0 WHERE `schemaRejected` = 0 AND `modID` = ? AND `schemaID` <> ?;',
        'ii',
        $schemaModID, $schemaID
    );

    if ($updateSQL) {
        $json_response['result'] = "Success! Custom Game Schemas for mod #$schemaModID now de-activated.";

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
                'De-activated schemas for:',
                $irc_message->colour_generator(NULL),
                $irc_message->colour_generator('bold'),
            ),
            array($schemaCheck[0]['mod_name']),
            array(' || http://getdotastats.com/#admin__mod_schema_edit?id=' . $schemaID),
        );

        $message = $irc_message->combine_message($message);
        $irc_message->post_message($message, array('localDev' => $localDev));
    } else {
        throw new Exception('No changes made to DB. There were probably no schemas to de-activate.');
    }
} catch (Exception $e) {
    $json_response['error'] = 'Caught Exception: ' . $e->getMessage();
} finally {
    if (isset($memcache)) $memcache->close();
}

try {
    echo utf8_encode(json_encode($json_response));
} catch (Exception $e) {
    unset($json_response);
    $json_response['error'] = 'Caught Exception: ' . $e->getMessage();
    echo utf8_encode(json_encode($json_response));
}