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
        empty($_POST['minigameID']) ||
        empty($_POST['minigameObjective']) ||
        empty($_POST['minigameOperator']) ||
        empty($_POST['minigameFactor']) || !is_numeric($_POST['minigameFactor']) ||
        !isset($_POST['minigameDecimals']) || !is_numeric($_POST['minigameDecimals']) ||
        empty($_POST['minigameDescription']) ||
        !isset($_POST['minigameActive']) || !is_numeric($_POST['minigameActive'])
    ) {
        throw new Exception('Missing or invalid required parameter(s)!');
    }

    $mgID = htmlentities($_POST['minigameID']);
    $mgObjective = htmlentities($_POST['minigameObjective']);
    $mgOperator = htmlentities($_POST['minigameOperator']);
    $mgFactor = htmlentities($_POST['minigameFactor']);
    $mgDecimals = htmlentities($_POST['minigameDecimals']);
    $mgDescription = htmlentities($_POST['minigameDescription']);
    $mgActive = htmlentities($_POST['minigameActive']);

    $insertSQL = $db->q(
        'UPDATE `stat_highscore_minigames`
          SET
            `minigameActive` = ?,
            `minigameObjective` = ?,
            `minigameOperator` = ?,
            `minigameFactor` = ?,
            `minigameDecimals` = ?,
            `minigameDescription` = ?
          WHERE `minigameID` = ?;',
        'issiiss',
        $mgActive, $mgObjective, $mgOperator, $mgFactor, $mgDecimals, $mgDescription, $mgID
    );

    if ($insertSQL) {
        $json_response['result'] = 'Mini Game updated!';
    } else {
        throw new Exception('Mini Game not updated!');
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