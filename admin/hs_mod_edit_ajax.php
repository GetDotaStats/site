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
        !isset($_POST['highscore_ID']) || !is_numeric($_POST['highscore_ID']) ||
        empty($_POST['highscore_description']) ||
        !isset($_POST['highscore_active']) || !is_numeric($_POST['highscore_active']) ||
        //AESTHETICS
        !isset($_POST['highscore_objective']) ||
        !isset($_POST['highscore_operator']) ||
        !isset($_POST['highscore_factor']) || !is_numeric($_POST['highscore_factor']) ||
        !isset($_POST['highscore_decimals']) || !is_numeric($_POST['highscore_decimals'])
    ) {
        throw new Exception('Missing or invalid required parameter(s)!');
    }

    $highscoreID = htmlentities($_POST['highscore_ID']);
    $highscoreDescription = htmlentities($_POST['highscore_description']);
    $highscoreActive = htmlentities($_POST['highscore_active']);

    $highscoreObjective = htmlentities($_POST['highscore_objective']);
    $highscoreOperator = htmlentities($_POST['highscore_operator']);
    $highscoreFactor = htmlentities($_POST['highscore_factor']);
    $highscoreDecimals = htmlentities($_POST['highscore_decimals']);

    $insertSQL = $db->q(
        'UPDATE `stat_highscore_mods_schema`
          SET
            `highscoreDescription` = ?,
            `highscoreActive` = ?,
            `highscoreObjective` = ?,
            `highscoreOperator` = ?,
            `highscoreFactor` = ?,
            `highscoreDecimals` = ?
          WHERE `highscoreID` = ?;',
        'sissssi',
        $highscoreDescription, $highscoreActive, $highscoreObjective, $highscoreOperator, $highscoreFactor, $highscoreDecimals, $highscoreID
    );

    if ($insertSQL) {
        $json_response['result'] = 'Highscore type updated!';
    } else {
        throw new Exception('Highscore type not updated!');
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