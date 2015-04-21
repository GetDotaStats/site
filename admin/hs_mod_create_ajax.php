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
        empty($_POST['highscore_name']) &&
        empty($_POST['highscore_modid']) && $_POST['highscore_modid'] == '--' &&
        empty($_POST['highscore_description']) &&
        //AESTHETICS
        !isset($_POST['highscore_objective']) &&
        !isset($_POST['highscore_operator']) &&
        !isset($_POST['highscore_factor']) && !is_numeric($_POST['highscore_factor']) &&
        !isset($_POST['highscore_decimals']) && !is_numeric($_POST['highscore_decimals'])
    ) {
        throw new Exception('Missing or invalid required parameter(s)!');
    }

    $highscoreName = htmlentities($_POST['highscore_name']);

    $highscoreModID = htmlentities($_POST['highscore_modid']);

    $highscoreDescription = htmlentities($_POST['highscore_description']);

    $highscoreObjective = !empty($_POST['highscore_objective'])
        ? $_POST['highscore_objective']
        : 'min';

    $highscoreOperator = !empty($_POST['highscore_operator'])
        ? $_POST['highscore_operator']
        : 'multiply';

    $highscoreFactor = !empty($_POST['highscore_factor']) && is_numeric($_POST['highscore_factor'])
        ? $_POST['highscore_factor']
        : 1;

    $highscoreDecimals = isset($_POST['highscore_decimals']) && is_numeric($_POST['highscore_decimals'])
        ? $_POST['highscore_decimals']
        : 2;

    $insertSQL = $db->q(
        'INSERT INTO `stat_highscore_mods_schema`
              (
                `modID`,
                `highscoreName`,
                `highscoreDescription`,
                `highscoreObjective`,
                `highscoreOperator`,
                `highscoreFactor`,
                `highscoreDecimals`
              )
            VALUES (?, ?, ?, ?, ?, ?, ?);',
        'sssssss', //STUPID x64 windows PHP is actually x86
        array(
            $highscoreModID,
            $highscoreName,
            $highscoreDescription,
            $highscoreObjective,
            $highscoreOperator,
            $highscoreFactor,
            $highscoreDecimals
        )
    );

    if ($insertSQL) {
        $json_response['result'] = "Success! Highscore type added to DB and under the account of the associated mod's developer.";
    } else {
        throw new Exception('Highscore type not added to DB!');
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