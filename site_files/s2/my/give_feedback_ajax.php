<?php
try {
    require_once('../../connections/parameters.php');
    require_once('../../global_functions.php');

    if (!isset($_SESSION)) {
        session_start();
    }

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcached = new Cache(NULL, NULL, $localDev);

    checkLogin_v2();
    if (empty($_SESSION['user_id64'])) throw new Exception('Not logged in!');

    if (
        empty($_POST['modID']) || !is_numeric($_POST['modID']) ||
        !isset($_POST['modBroken']) || !is_numeric($_POST['modBroken']) ||
        !isset($_POST['modFunRating']) || !is_numeric($_POST['modFunRating']) ||
        !isset($_POST['modConceptRating']) || !is_numeric($_POST['modConceptRating'])
    ) {
        throw new Exception('Missing or invalid required parameter(s)!');
    }

    $allowedRatingValues = array(1, 2, 3, 4, 5);

    $submitterUserID = $_SESSION['user_id64'];
    $modID = $_POST['modID'];
    $modBroken = !empty($_POST['modBroken'])
        ? 1
        : 0;
    $modFunRating = !empty($_POST['modFunRating']) && in_array($_POST['modFunRating'], $allowedRatingValues)
        ? $_POST['modFunRating']
        : 0;
    $modConceptRating = !empty($_POST['modConceptRating']) && in_array($_POST['modConceptRating'], $allowedRatingValues)
        ? $_POST['modConceptRating']
        : 0;
    $modProblem = !empty($_POST['modProblem'])
        ? htmlentities($_POST['modProblem'])
        : NULL;
    $modComment = !empty($_POST['modComment'])
        ? htmlentities($_POST['modComment'])
        : NULL;

    if (!empty($modBroken) && empty($modProblem)) {
        throw new Exception('You must describe how this mod is broken!');
    }

    if (empty($modBroken) && !empty($modProblem)) {
        throw new Exception('You must mark this mod as broken, if you populate the problem field!');
    }

    $insertSQL = $db->q(
        'INSERT INTO `mod_feedback`
            (
                `mod_id`,
                `feedback_submitter`,
                `feedback_broken`,
                `feedback_fun_rating`,
                `feedback_concept_rating`,
                `feedback_problem`,
                `feedback_comment`
            )
            VALUES (?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE
                `feedback_broken` = VALUES(`feedback_broken`),
                `feedback_fun_rating` = VALUES(`feedback_fun_rating`),
                `feedback_concept_rating` = VALUES(`feedback_concept_rating`),
                `feedback_problem` = VALUES(`feedback_problem`),
                `feedback_comment` = VALUES(`feedback_comment`);',
        'isiiiss',
        $modID, $submitterUserID, $modBroken, $modFunRating, $modConceptRating, $modProblem, $modComment
    );

    if ($insertSQL) {
        $json_response['result'] = 'Feedback submitted!';
    } else {
        throw new Exception('Feedback not submitted!');
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