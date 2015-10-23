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

    if (empty($_POST['schema_mod_id']) || !is_numeric($_POST['schema_mod_id'])) {
        throw new Exception('Missing or invalid required parameter(s)!');
    }

    $modID = htmlentities($_POST['schema_mod_id']);

    //Check if the modID is valid
    $modIDCheck = cached_query(
        'admin_custom_schema_mic' . $modID,
        'SELECT
                `mod_id`,
                `steam_id64`,
                `mod_name`,
                `mod_description`,
                `mod_workshop_link`,
                `mod_steam_group`,
                `mod_active`,
                `mod_rejected`,
                `mod_rejected_reason`,
                `mod_maps`,
                `mod_max_players`,
                `mod_options_enabled`,
                `mod_options`,
                `date_recorded`
            FROM `mod_list`
            WHERE `mod_id` = ?
            LIMIT 0,1;',
        'i',
        $modID,
        15
    );

    if (empty($modIDCheck)) {
        throw new Exception('Invalid modID!');
    }

    //Grab the highest versioned schema
    $modSchemaVersionCheck = $db->q(
        'SELECT
              `schemaID`,
              `modID`,
              `schemaAuth`,
              `schemaVersion`,
              `schemaApproved`,
              `schemaRejected`,
              `schemaRejectedReason`,
              `dateRecorded`
            FROM `s2_mod_custom_schema`
            WHERE `modID` = ?
            ORDER BY `schemaVersion` DESC
            LIMIT 0,1;',
        'i',
        $modID
    );

    $schemaVersion = !empty($modSchemaVersionCheck[0]['schemaVersion'])
        ? ($modSchemaVersionCheck[0]['schemaVersion'] + 1)
        : 1;

    $schemaSubmitterUserID64 = $_SESSION['user_id64'];

    //Generate the schema auth key
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $schemaAuth = '';
    for ($i = 0; $i < 16; $i++) {
        $schemaAuth .= $characters[rand(0, 35)];
    }

    //////////////////////////////
    //INSERT SCHEMA DETAILS
    //////////////////////////////
    $insertSQLschema = $db->q(
        'INSERT INTO `s2_mod_custom_schema`
              (
                `modID`,
                `schemaAuth`,
                `schemaVersion`,
                `schemaSubmitterUserID64`
              )
            VALUES (?, ?, ?, ?);',
        'isis',
        array($modID, $schemaAuth, $schemaVersion, $schemaSubmitterUserID64)
    );

    if ($insertSQLschema) {
        $schemaID = $db->last_index();
        $json_response['result'] = "Success! Custom Game Schema #$schemaID added to DB.";
    } else {
        throw new Exception('Custom Game Schema not added to DB!');
    }

    //////////////////////////////
    //INSERT SCHEMA FIELDS
    //////////////////////////////
    $numPostFields = floor(count($_POST) / 3);

    for ($i = 1; $i <= $numPostFields; $i++) {
        if (!empty($_POST['cgv_display' . $i]) && !empty($_POST['cgv_name' . $i])) {
            //Custom Game Values check and insert

            if (empty($_POST['cgv_objective' . $i]) || !is_numeric($_POST['cgv_objective' . $i])) {
                throw new Exception("Invalid or missing objective for custom Game Value $i!");
            }

            if (!isset($_POST['cgv_isgroupable' . $i]) || !is_numeric($_POST['cgv_isgroupable' . $i])) {
                throw new Exception("Invalid or missing isGroupable for custom Game Value $i!");
            }

            $insertSQL = $db->q(
                'INSERT INTO `s2_mod_custom_schema_fields`
                      (
                        `schemaID`,
                        `fieldOrder`,
                        `fieldType`,
                        `customValueDisplay`,
                        `customValueName`,
                        `customValueObjective`,
                        `isGroupable`
                      )
                    VALUES (?, ?, 1, ?, ?, ?, ?);',
                'iissii',
                array(
                    $schemaID,
                    $i,
                    htmlentities($_POST['cgv_display' . $i]),
                    htmlentities($_POST['cgv_name' . $i]),
                    $_POST['cgv_objective' . $i],
                    $_POST['cgv_isgroupable' . $i]
                )
            );

            if ($insertSQL) {
                $json_response['resultG' . $i] = "Success! Custom Game Value #$i added to DB.";
            } else {
                throw new Exception('Custom Game Value not added to DB!');
            }
        }

        if (!empty($_POST['cpv_display' . $i]) && !empty($_POST['cpv_name' . $i])) {
            //Custom Player Values check and insert

            if (empty($_POST['cpv_objective' . $i]) || !is_numeric($_POST['cpv_objective' . $i])) {
                throw new Exception("Invalid or missing objective for custom Player Value $i!");
            }

            if (!isset($_POST['cpv_isgroupable' . $i]) || !is_numeric($_POST['cpv_isgroupable' . $i])) {
                throw new Exception("Invalid or missing isGroupable for custom Player Value $i!");
            }

            $insertSQL = $db->q(
                'INSERT INTO `s2_mod_custom_schema_fields`
                      (
                        `schemaID`,
                        `fieldOrder`,
                        `fieldType`,
                        `customValueDisplay`,
                        `customValueName`,
                        `customValueObjective`,
                        `isGroupable`
                      )
                    VALUES (?, ?, 2, ?, ?, ?, ?);',
                'iissii',
                array(
                    $schemaID,
                    $i,
                    htmlentities($_POST['cpv_display' . $i]),
                    htmlentities($_POST['cpv_name' . $i]),
                    $_POST['cpv_objective' . $i],
                    $_POST['cpv_isgroupable' . $i]
                )
            );

            if ($insertSQL) {
                $json_response['resultP' . $i] = "Success! Custom Player Value #$i added to DB.";
            } else {
                throw new Exception('Custom Player Value not added to DB!');
            }
        }
    }

    if ($insertSQLschema) {
        $irc_message = new irc_message($webhook_gds_site_normal);

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
                'Created:',
                $irc_message->colour_generator(NULL),
                $irc_message->colour_generator('bold'),
            ),
            array($modIDCheck[0]['mod_name']),
            array(
                $irc_message->colour_generator('orange'),
                'v' . $schemaVersion,
                $irc_message->colour_generator(NULL),
            ),
            array(' || http://getdotastats.com/#admin__mod_schema_edit?id=' . $schemaID),
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