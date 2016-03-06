<?php
try {
    require_once('../global_functions.php');
    require_once('../connections/parameters.php');

    if (!isset($_SESSION)) {
        session_start();
    }

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcached = new Cache(NULL, NULL, $localDev);

    checkLogin_v2();
    if (empty($_SESSION['user_id64'])) throw new Exception('Not logged in!');

    $adminCheck = adminCheck($_SESSION['user_id64'], 'admin');
    if (empty($adminCheck)) throw new Exception('Not an admin!');

    if (empty($_POST['schema_id']) || !is_numeric($_POST['schema_id'])) {
        throw new Exception('Missing or invalid required parameter(s)!');
    }

    $schemaID = htmlentities($_POST['schema_id']);

    //Grab schema details
    $schemaDetails = cached_query(
        'admin_custom_schema_sic' . $schemaID,
        'SELECT
              s2mcs.`schemaID`,
              s2mcs.`modID`,
              s2mcs.`schemaAuth`,
              s2mcs.`schemaApproved`,
              s2mcs.`schemaRejected`,
              s2mcs.`schemaVersion`,
              s2mcs.`dateRecorded`,

              ml.*
            FROM `s2_mod_custom_schema` s2mcs
            LEFT JOIN `mod_list` ml ON s2mcs.`modID` = ml.`mod_id`
            WHERE s2mcs.`schemaID` = ?
            LIMIT 0,1;',
        'i',
        $schemaID,
        1
    );

    if (empty($schemaDetails)) throw new Exception('Invalid schemaID!');

    $schemaModID = $schemaDetails[0]['modID'];
    $schemaVersion = $schemaDetails[0]['schemaVersion'];
    $schemaVersionOriginal = $schemaVersion;
    $schemaSubmitterUserID64 = $_SESSION['user_id64'];
    $numPostFields = floor(count($_POST) / 3);

    $schemaFieldsSQL = cached_query(
        'admin_custom_schema_fields' . $schemaID,
        'SELECT
                s2mcsf.`fieldType`,
                s2mcsf.`fieldOrder`,
                s2mcsf.`customValueName`
            FROM `s2_mod_custom_schema_fields` s2mcsf
            WHERE s2mcsf.`schemaID` = ?;',
        'i',
        array($schemaID),
        15
    );

    if (empty($schemaFieldsSQL)) throw new Exception('Schema has no fields!');

    $schemaFieldArray = array();
    foreach ($schemaFieldsSQL as $key => $value) {
        $schemaFieldArray[$value['fieldType']][$value['fieldOrder']] = $value['customValueName'];
    }

    $failedVersionCheck = FALSE;
    for ($i = 1; $i <= $numPostFields; $i++) {
        if (!empty($_POST['cgv_name' . $i]) && (!isset($schemaFieldArray[1][$i]) || $schemaFieldArray[1][$i] != $_POST['cgv_name' . $i])) {
            $failedVersionCheck = TRUE;
        }

        if (!empty($_POST['cpv_name' . $i]) && (!isset($schemaFieldArray[2][$i]) || $schemaFieldArray[2][$i] != $_POST['cpv_name' . $i])) {
            $failedVersionCheck = TRUE;
        }
    }

    //IF WE FAILED THE VERSION CHECK, INCREMENT THE VERSION
    $insertSQLschema = FALSE;
    if ($failedVersionCheck) {
        //find out what the highest schema version is
        $highestSchemaVersion = $db->q(
            'SELECT
                MAX(schemaVersion) AS schemaVersion
                FROM `s2_mod_custom_schema`
                WHERE `modID` = ?
                LIMIT 0,1;',
            'i',
            $schemaModID
        );

        //increment the schema version
        $schemaVersion = $highestSchemaVersion[0]['schemaVersion'] + 1;

        //Generate the schema auth key
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $schemaAuth = '';
        for ($i = 0; $i < 16; $i++) {
            $schemaAuth .= $characters[rand(0, 35)];
        }

        //create schema with new version
        $insertSQLschema = $db->q(
            'INSERT INTO `s2_mod_custom_schema`
                  (`modID`, `schemaAuth`, `schemaVersion`, `schemaSubmitterUserID64`)
                VALUES (?, ?, ?, ?);',
            'isis',
            array($schemaModID, $schemaAuth, $schemaVersion, $schemaSubmitterUserID64)
        );

        if ($insertSQLschema) {
            $schemaID = $db->last_index();
            if(!empty($json_response['result'])){
                $json_response['result'] .= ", Custom Game Schema #{$schemaID} added to DB";
            } else{
                $json_response['result'] = "Custom Game Schema #{$schemaID} added to DB";
            }
        } else {
            throw new Exception('No change made to schema! Ensure there are new changes above and is not rejected!');
        }
    }


    //////////////////////////////
    //EDIT SCHEMA FIELDS
    //////////////////////////////
    $insertSQLfields = FALSE;
    for ($i = 1; $i <= $numPostFields; $i++) {
        if (!empty($_POST['cgv_display' . $i]) && !empty($_POST['cgv_name' . $i])) {
            //Custom Game Values check and insert

            if (empty($_POST['cgv_objective' . $i]) || !is_numeric($_POST['cgv_objective' . $i])) {
                throw new Exception("Invalid or missing objective for custom Game Value $i!");
            }

            if (!isset($_POST['cgv_isgroupable' . $i]) || !is_numeric($_POST['cgv_isgroupable' . $i])) {
                throw new Exception("Invalid or missing isGroupable for custom Game Value $i!");
            }

            if (!isset($_POST['cgv_graph' . $i]) || !is_numeric($_POST['cgv_graph' . $i])) {
                throw new Exception("Invalid or missing isGraph for custom Game Value $i!");
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
                        `isGroupable`,
                        `noGraph`
                      )
                    VALUES (?, ?, 1, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        `customValueDisplay` = VALUES(`customValueDisplay`),
                        `customValueName` = VALUES(`customValueName`),
                        `customValueObjective` = VALUES(`customValueObjective`),
                        `isGroupable` = VALUES(`isGroupable`),
                        `noGraph` = VALUES(`noGraph`);',
                'iissiii',
                array(
                    $schemaID,
                    $i,
                    htmlentities($_POST['cgv_display' . $i]),
                    htmlentities($_POST['cgv_name' . $i]),
                    $_POST['cgv_objective' . $i],
                    $_POST['cgv_isgroupable' . $i],
                    $_POST['cgv_graph' . $i]
                )
            );

            if ($insertSQL) {
                $insertSQLfields = TRUE;
                if(!empty($json_response['result'])){
                    $json_response['result'] .= ", CGV #$i updated.";
                } else{
                    $json_response['result'] = "CGV #$i updated.";
                }
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

            if (!isset($_POST['cpv_graph' . $i]) || !is_numeric($_POST['cpv_graph' . $i])) {
                throw new Exception("Invalid or missing isGraph for custom Player Value $i!");
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
                        `isGroupable`,
                        `noGraph`
                      )
                    VALUES (?, ?, 2, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        `customValueDisplay` = VALUES(`customValueDisplay`),
                        `customValueName` = VALUES(`customValueName`),
                        `customValueObjective` = VALUES(`customValueObjective`),
                        `isGroupable` = VALUES(`isGroupable`),
                        `noGraph` = VALUES(`noGraph`);',
                'iissiii',
                array(
                    $schemaID,
                    $i,
                    htmlentities($_POST['cpv_display' . $i]),
                    htmlentities($_POST['cpv_name' . $i]),
                    $_POST['cpv_objective' . $i],
                    $_POST['cpv_isgroupable' . $i],
                    $_POST['cpv_graph' . $i]
                )
            );

            if ($insertSQL) {
                $insertSQLfields = TRUE;
                if(!empty($json_response['result'])){
                    $json_response['result'] .= ", CPV #$i updated.";
                } else{
                    $json_response['result'] = "CPV #$i updated.";
                }
            }
        }
    }

    if ($failedVersionCheck && $insertSQLschema) {
        $json_response['schemaID'] = $schemaID;

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
                'Edited (new):',
                $irc_message->colour_generator(NULL),
                $irc_message->colour_generator('bold'),
            ),
            array($schemaDetails[0]['mod_name']),
            array(
                $irc_message->colour_generator('orange'),
                'v' . $schemaVersionOriginal,
                $irc_message->colour_generator(NULL),
            ),
            array('-->'),
            array(
                $irc_message->colour_generator('orange'),
                'v' . $schemaVersion,
                $irc_message->colour_generator(NULL),
            ),
            array(' || http://getdotastats.com/#admin__mod_schema_edit?id=' . $schemaID),
        );

        $message = $irc_message->combine_message($message);
        $irc_message->post_message($message, array('localDev' => $localDev));
    } else if ($insertSQLfields) {
        $json_response['schemaID'] = $schemaID;

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
                'Edited:',
                $irc_message->colour_generator(NULL),
                $irc_message->colour_generator('bold'),
            ),
            array($schemaDetails[0]['mod_name']),
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
    if (isset($memcached)) $memcached->close();
    if (!isset($json_response)) $json_response = array('error' => 'Unknown exception');
}

try {
    echo utf8_encode(json_encode($json_response));
} catch (Exception $e) {
    unset($json_response);
    $json_response['error'] = 'Caught Exception: ' . $e->getMessage();
    echo utf8_encode(json_encode($json_response));
}