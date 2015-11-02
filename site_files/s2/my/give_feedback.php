<?php
try {
    require_once('../../connections/parameters.php');
    require_once('../../global_functions.php');

    if (!isset($_SESSION)) {
        session_start();
    }

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    checkLogin_v2();
    if (empty($_SESSION['user_id64'])) throw new Exception('Not logged in!');

    echo '<h2>Give Feedback</h2>';
    echo '<p>This form allows users to submit feedback for mods.</p>';
    echo '<hr />';

    $modList = cached_query(
        'mod_feedback_list',
        'SELECT
                `mod_id`,
                `steam_id64`,
                `mod_identifier`,
                `mod_name`,
                `mod_description`,
                `mod_workshop_link`,
                `mod_steam_group`,
                `mod_active`,
                `mod_rejected`,
                `mod_rejected_reason`,
                `date_recorded`
            FROM `mod_list` ml
            WHERE ml.`mod_active` = 1 AND ml.`mod_rejected` <> 1
            ORDER BY `mod_name` ASC;',
        NULL,
        NULL,
        15
    );

    if (empty($modList)) {
        throw new Exception('No mods to give feedback on!');
    }

    echo '<form id="modFeedback">';

    $modID = $modList[0]['mod_id'];

    $modListSelectOptions = '';
    foreach ($modList as $key => $value) {
        $modListSelectOptions .= '<option value="' . $value['mod_id'] . '">' . $value['mod_name'] . '</option>';
    }

    $modFeedbackBrokenSelectOptionsArray = array('No', 'Yes');
    $modFeedbackBrokenSelectOptions = '';
    foreach ($modFeedbackBrokenSelectOptionsArray as $key => $value) {
        $modFeedbackBrokenSelectOptions .= '<option value="' . $key . '">' . $value . '</option>';
    }

    echo '<div class="row">
                <div class="col-md-1"><span class="h4">Name</span></div>
                <div class="col-md-1 text-center"><span class="glyphicon glyphicon-question-sign" title="The custom game you are giving feedback on"></span></div>
                <div class="col-md-6">
                    <select class="formTextArea boxsizingBorder" name="modID" required>' . $modListSelectOptions . '</select>
                </div>
            </div>';

    echo '<span class="h5">&nbsp;</span>';

    echo '<div class="row">
                <div class="col-md-1"><span class="h4">Broken</span></div>
                <div class="col-md-1 text-center"><span class="glyphicon glyphicon-question-sign" title="Does this custom game work?"></span></div>
                <div class="col-md-3">
                    <fieldset>
                        <input type="radio" id="modBroken_yes" name="modBroken" value="1" /><label for="modBroken_yes" title="Epic!">Yes</label><br />
                        <input type="radio" id="modBroken_no" name="modBroken" value="0" /><label for="modBroken_no" title="Great">No</label>
                    </fieldset>
                </div>
            </div>';

    echo '<span class="h5">&nbsp;</span>';

    echo '<div class="row">
                <div class="col-md-1"><span class="h4">Fun</span></div>
                <div class="col-md-1 text-center"><span class="glyphicon glyphicon-question-sign" title="How enjoyable is this custom game?"></span></div>
                <div class="col-md-3">
                    <fieldset class="rating">
                        <input type="radio" id="mer_star5" name="modFunRating" value="5" /><label for="mer_star5" title="Epic!">5 stars</label>
                        <input type="radio" id="mer_star4" name="modFunRating" value="4" /><label for="mer_star4" title="Great">4 stars</label>
                        <input type="radio" id="mer_star3" name="modFunRating" value="3" /><label for="mer_star3" title="OK">3 stars</label>
                        <input type="radio" id="mer_star2" name="modFunRating" value="2" /><label for="mer_star2" title="Poor">2 stars</label>
                        <input type="radio" id="mer_star1" name="modFunRating" value="1" /><label for="mer_star1" title="Bad">1 star</label>
                    </fieldset>
                </div>
            </div>';

    echo '<span class="h5">&nbsp;</span>';

    echo '<div class="row">
                <div class="col-md-1"><span class="h4">Concept</span></div>
                <div class="col-md-1 text-center"><span class="glyphicon glyphicon-question-sign" title="How good is the concept of this custom game?"></span></div>
                <div class="col-md-3">
                    <fieldset class="rating">
                        <input type="radio" id="mcr_star5" name="modConceptRating" value="5" /><label for="mcr_star5" title="Epic!">5 stars</label>
                        <input type="radio" id="mcr_star4" name="modConceptRating" value="4" /><label for="mcr_star4" title="Great">4 stars</label>
                        <input type="radio" id="mcr_star3" name="modConceptRating" value="3" /><label for="mcr_star3" title="OK">3 stars</label>
                        <input type="radio" id="mcr_star2" name="modConceptRating" value="2" /><label for="mcr_star2" title="Poor">2 stars</label>
                        <input type="radio" id="mcr_star1" name="modConceptRating" value="1" /><label for="mcr_star1" title="Bad">1 star</label>
                    </fieldset>
                </div>
            </div>';

    echo '<span class="h5">&nbsp;</span>';

    echo '<div class="row">
                <div class="col-md-1"><span class="h4">Problem</span></div>
                <div class="col-md-1 text-center"><span class="glyphicon glyphicon-question-sign" title="Is there any other information you would like the developer to know? Things like reproducible steps, etc."></span></div>
                <div class="col-md-6">
                    <textarea class="formTextArea boxsizingBorder" name="modProblem" rows="3" placeholder="What exactly is broken, and how can the developer reproduce the issue?"></textarea>
                </div>
            </div>';

    echo '<span class="h5">&nbsp;</span>';

    echo '<div class="row">
                <div class="col-md-1"><span class="h4">Comment</span></div>
                <div class="col-md-1 text-center"><span class="glyphicon glyphicon-question-sign" title="Is there any other information you would like the developer to know? Things like reproducible steps, etc."></span></div>
                <div class="col-md-6">
                    <textarea class="formTextArea boxsizingBorder" name="modComment" rows="3" placeholder="Is there anything you would like the developer to know?"></textarea>
                </div>
            </div>';

    echo '<span class="h5">&nbsp;</span>';

    echo '<div class="row">
                <div class="col-md-12 text-center"><span id="modAJAXResult" class="labelWarnings label label-danger"></span></div>
            </div>';

    echo '<span class="h5">&nbsp;</span>';

    echo '<div class="row">
                <div class="col-md-8 text-center">
                    <input name="submit" class="btn btn-success" type="submit" value="Submit">
                </div>
            </div>';

    echo '<span class="h5">&nbsp;</span>';

    echo '</form>';

    echo '<hr />';

    echo '<script type="application/javascript">
                    function htmlEntities(str) {
                        return String(str).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
                    }

                    $("#modFeedback").submit(function (event) {
                        event.preventDefault();

                        $.post("./s2/my/give_feedback_ajax.php", $("#modFeedback").serialize(), function (data) {
                            try {
                                if(data){
                                    var response = JSON.parse(data);
                                    if(response && response.error){
                                        $("#modAJAXResult").html(response.error);
                                    }
                                    else if(response && response.result){
                                        $("#modAJAXResult").html(response.result);
                                        loadPage("#s2__my__give_feedback",1);
                                    }
                                    else{
                                        $("#modAJAXResult").html(htmlEntities(data));
                                    }
                                }
                            }
                            catch(err) {
                                $("#modAJAXResult").html("Parsing Error: " + err.message + "<br />" + htmlEntities(data));
                            }
                        }, "text");
                    });
                </script>';

} catch (Exception $e) {
    echo formatExceptionHandling($e);
} finally {
    if (isset($memcache)) $memcache->close();
}