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

    if (empty($_POST['schema_mod_id'])) {
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
    $modSchemaVersionCheck = cached_query(
        'admin_custom_schema_msvc' . $modID,
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
        $modID,
        15
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

    //Let's start constructing our query!
    $sqlFieldsName = '';
    $sqlFieldsPlaceholder = '';
    $sqlFieldsDeclaration = '';
    $sqlFields = array($modID, $schemaAuth, $schemaVersion, $schemaSubmitterUserID64);

    //Add the custom Game fields to the SQL input fields
    $schemaCustomGameMaxFields = 5;
    for ($i = 1; $i <= $schemaCustomGameMaxFields; $i++) {
        //While we are looping, let's name our table fields and make placeholders too!
        $sqlFieldsName .= ', `customGameValue' . $i . '_display`, `customGameValue' . $i . '_name`, `customGameValue' . $i . '_objective`';
        $sqlFieldsPlaceholder .= ', ?, ?, ?';
        $sqlFieldsDeclaration .= 'ssi';

        if (!empty($_POST['cgv' . $i . '_display']) && !empty($_POST['cgv' . $i . '_name'])) {
            if (empty($_POST['cgv' . $i . '_objective'])) {
                throw new Exception('Missing objective for custom Game Value ' . $i . '!');
            }

            $sqlFields[] = htmlentities($_POST['cgv' . $i . '_display']);
            $sqlFields[] = htmlentities($_POST['cgv' . $i . '_name']);
            $sqlFields[] = htmlentities($_POST['cgv' . $i . '_objective']);
        } else {
            $sqlFields[] = NULL;
            $sqlFields[] = NULL;
            $sqlFields[] = NULL;
        }
    }

    //Add the custom Player fields to the SQL input fields
    $schemaCustomPlayerMaxFields = 15;
    for ($i = 1; $i <= $schemaCustomPlayerMaxFields; $i++) {
        //While we are looping, let's name our table fields and make placeholders too!
        $sqlFieldsName .= ', `customPlayerValue' . $i . '_display`, `customPlayerValue' . $i . '_name`, `customPlayerValue' . $i . '_objective`';
        $sqlFieldsPlaceholder .= ', ?, ?, ?';
        $sqlFieldsDeclaration .= 'ssi';

        if (!empty($_POST['cpv' . $i . '_display']) && !empty($_POST['cpv' . $i . '_name'])) {
            if (empty($_POST['cpv' . $i . '_objective'])) {
                throw new Exception('Missing objective for custom Player Value ' . $i . '!');
            }

            $sqlFields[] = htmlentities($_POST['cpv' . $i . '_display']);
            $sqlFields[] = htmlentities($_POST['cpv' . $i . '_name']);
            $sqlFields[] = htmlentities($_POST['cpv' . $i . '_objective']);
        } else {
            $sqlFields[] = NULL;
            $sqlFields[] = NULL;
            $sqlFields[] = NULL;
        }
    }

    $insertSQL = $db->q(
        'INSERT INTO `s2_mod_custom_schema`
              (
                `modID`,
                `schemaAuth`,
                `schemaVersion`,
                `schemaSubmitterUserID64`
                ' . $sqlFieldsName . '
              )
            VALUES (?, ?, ?, ?' . $sqlFieldsPlaceholder . ');',
        'ssis' . $sqlFieldsDeclaration,
        $sqlFields
    );

    if ($insertSQL) {
        $json_response['result'] = "Success! Custom Game Schema added to DB.";
    } else {
        throw new Exception('Custom Game Schema not added to DB!');
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