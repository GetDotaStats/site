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
        empty($_POST['modWorkshop']) ||
        !isset($_POST['modActive'])
    ) {
        throw new Exception('Missing or invalid required parameter(s)!');
    }

    $modID = htmlentities($_POST['modID']);
    $modName = htmlentities($_POST['modName']);
    $modDescription = htmlentities($_POST['modDescription']);
    $modGroup = !empty($_POST['modGroup'])
        ? htmlentities($_POST['modGroup'])
        : NULL;
    $modMaps = json_encode(array_map('trim', explode("\n", htmlentities($_POST['modMaps']))));
    $modWorkshop = htmlentities($_POST['modWorkshop']);
    $modActive = htmlentities($_POST['modActive']);

    $insertSQL = $db->q(
        'UPDATE `mod_list`
          SET
            `mod_active` = ?,
            `mod_name` = ?,
            `mod_description` = ?,
            `mod_steam_group` = ?,
            `mod_maps` = ?,
            `mod_workshop_link` = ?
          WHERE `mod_identifier` = ?;',
        'issssss',
        $modActive, $modName, $modDescription, $modGroup, $modMaps, $modWorkshop, $modID
    );

    if ($insertSQL) {
        $json_response['result'] = 'Custom Game updated!';
    } else {
        throw new Exception('Custom Game not updated!');
    }

    $memcache->close();
} catch (Exception $e) {
    $json_response['error'] = 'Caught Exception: ' . $e->getMessage();
}

try {
    echo utf8_encode(json_encode($json_response));
} catch (Exception $e) {
    unset($json_response);
    $json_response['error'] = 'Caught Exception: ' . $e->getMessage();
    echo utf8_encode(json_encode($json_response));
}