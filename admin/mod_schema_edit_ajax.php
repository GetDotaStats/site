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

    if (empty($_POST['schema_id']) || !is_numeric($_POST['schema_id'])) {
        throw new Exception('Missing or invalid required parameter(s)!');
    }

    $schemaID = htmlentities($_POST['schema_id']);

    //Grab schema details
    $schemaDetails = cached_query(
        'admin_custom_schema_sic' . $schemaID,
        'SELECT
              `schemaID`,
              `modID`,
              `schemaAuth`,
              `schemaApproved`,
              `schemaRejected`,
              `schemaVersion`,
              `dateRecorded`
            FROM `s2_mod_custom_schema`
            WHERE `schemaID` = ?
            LIMIT 0,1;',
        'i',
        $schemaID,
        1
    );

    if (empty($schemaDetails)) {
        throw new Exception('Invalid schemaID!');
    }

    $schemaModID = $schemaDetails[0]['modID'];
    $schemaSubmitterUserID64 = $_SESSION['user_id64'];
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

        //rejected schemas can't be approved
        $schemaApproved = 0;
    } else {
        $schemaRejectedReason = NULL;
    }

    if (($schemaApproved == 1 && $schemaDetails[0]['schemaApproved'] == 0) || ($schemaRejected == 1 && $schemaDetails[0]['schemaRejected'] == 0)) {
        //use old version and auth key if approving or rejecting a schema
        $schemaVersion = $schemaDetails[0]['schemaVersion'];
        $schemaAuth = $schemaDetails[0]['schemaAuth'];
    } else {
        //find out what the highest schema version is
        $highestSchemaVersion = cached_query(
            'admin_custom_schema_hsv' . $schemaModID,
            'SELECT
                MAX(schemaVersion) AS schemaVersion
                FROM `s2_mod_custom_schema`
                WHERE `modID` = ?
                LIMIT 0,1;',
            'i',
            $schemaModID,
            1
        );

        //increment the schema version
        $schemaVersion = $highestSchemaVersion[0]['schemaVersion'] + 1;

        //Generate the schema auth key
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $schemaAuth = '';
        for ($i = 0; $i < 16; $i++) {
            $schemaAuth .= $characters[rand(0, 35)];
        }
    }

    //Let's start constructing our query!
    $sqlFieldsName = '`modID`, `schemaAuth`, `schemaVersion`, `schemaApproved`, `schemaRejected`, `schemaRejectedReason`, `schemaSubmitterUserID64`';
    $sqlFieldsPlaceholder = '?, ?, ?, ?, ?, ?, ?';
    $sqlFieldsDeclaration = 'isiiiss';
    $sqlFields = array($schemaModID, $schemaAuth, $schemaVersion, $schemaApproved, $schemaRejected, $schemaRejectedReason, $schemaSubmitterUserID64);

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
              (' . $sqlFieldsName . ')
            VALUES (' . $sqlFieldsPlaceholder . ')
            ON DUPLICATE KEY UPDATE
                `schemaApproved` = VALUES(`schemaApproved`),
                `schemaRejected` = VALUES(`schemaRejected`),
                `schemaRejectedReason` = VALUES(`schemaRejectedReason`);',
        $sqlFieldsDeclaration,
        $sqlFields
    );

    $schemaIDNew = $db->last_index();

    if ($insertSQL) {
        $json_response['result'] = "Success! Custom Game Schema #$schemaIDNew added to DB.";
        $json_response['schemaID'] = $schemaIDNew;
    } else {
        throw new Exception('No change made to schema! Ensure there are new changes above and is not rejected!');
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