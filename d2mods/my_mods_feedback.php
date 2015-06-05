<?php
require_once('../global_functions.php');
require_once('../connections/parameters.php');

try {
    if (!isset($_SESSION)) {
        session_start();
    }

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    checkLogin_v2();
    if (empty($_SESSION['user_id64'])) throw new Exception('Not logged in!');

    $userID64 = $_SESSION['user_id64'];

    $modListFeedback = cached_query(
        'd2mods_my_mods_feedback_' . $userID64,
        'SELECT
                ml.`steam_id64`,
                ml.`mod_name`,

                mf.`mod_id`,
                mf.`feedback_submitter`,
                mf.`feedback_broken`,
                mf.`feedback_fun_rating`,
                mf.`feedback_concept_rating`,
                mf.`feedback_problem`,
                mf.`feedback_comment`,
                mf.`date_recorded` AS feedback_date_recorded
            FROM `mod_list` ml
            JOIN `mod_feedback` mf ON ml.`mod_id` = mf.`mod_id`
            WHERE ml.`steam_id64` = ?
            ORDER BY mf.`date_recorded` DESC;',
        's',
        $userID64,
        5
    );

    echo '<h2>My Feedback <small>BETA</small></h2>';

    echo '<p>Thrown together in 5mins. No hate please</p>';

    echo '<div class="text-center">
            <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__my_mods">My Mods</a>
        </div>';

    echo '<hr />';

    if (empty($modListFeedback)) {
        throw new Exception('No feedback given yet!');
    }

    foreach ($modListFeedback as $key => $value) {
        $modName = $value['mod_name'];
        $feedbackSubmitter = $value['feedback_submitter'];
        $feedbackBroken = $value['feedback_broken'];
        $feedbackFunRating = $value['feedback_fun_rating'];
        $feedbackConceptRating = $value['feedback_concept_rating'];
        $feedbackProblem = $value['feedback_problem'];
        $feedbackComment = $value['feedback_comment'];
        $feedbackDate = $value['feedback_date_recorded'];

        echo '<h4>' . $modName . '</h4>';
        echo '<strong>Submitted by:</strong> <a target="_new" href="https://steamcommunity.com/profiles/' . $feedbackSubmitter . '">' . $feedbackSubmitter . '</a> <span class="db_link">[' . relative_time_v3($feedbackDate, 1) . ']</span><br />';
        echo '<strong>Fun:</strong> ' . $feedbackFunRating . '<br />';
        echo '<strong>Concept:</strong> ' . $feedbackConceptRating . '<br />';
        echo '<strong>Broken:</strong> ' . $feedbackBroken . '<br />';
        echo '<strong>Problem:</strong> ' . $feedbackProblem . '<br />';
        echo '<strong>Comment:</strong> ' . $feedbackComment . '<br />';
        echo '<hr />';
    }


    echo '<span class="h4">&nbsp;</span>';
} catch (Exception $e) {
    echo formatExceptionHandling($e);
} finally {
    if (isset($memcache)) $memcache->close();
}