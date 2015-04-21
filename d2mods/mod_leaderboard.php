<?php
require_once('./functions.php');
require_once('../global_functions.php');
require_once('../connections/parameters.php');

try {
    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    $modHSidentifier = !empty($_GET['lid']) && is_numeric($_GET['lid'])
        ? $_GET['lid']
        : 1;

    $mod = cached_query(
        'mod_lb_details_' . $modHSidentifier,
        'SELECT
                ml.`mod_id`,
                ml.`mod_identifier`,
                shms.`highscoreID`,
                shms.`highscoreName`,
                shms.`highscoreDescription`,
                shms.`highscoreActive`,
                shms.`highscoreObjective`,
                shms.`highscoreOperator`,
                shms.`highscoreFactor`,
                shms.`highscoreDecimals`,
                shms.`date_recorded`,
                ml.`mod_name`
            FROM `stat_highscore_mods_schema` shms
            JOIN `mod_list` ml ON shms.`modID` = ml.`mod_identifier`
            WHERE shms.`highscoreID` = ?
            ORDER BY shms.`date_recorded`
            LIMIT 0,1;',
        's',
        array($modHSidentifier),
        15
    );

    if (empty($mod)) throw new Exception('No highscore type matching these parameters!');

    $mod = $mod[0];

    $modHSid = $mod['highscoreID'];
    $modID = $mod['mod_id'];
    $modIdentifier = $mod['mod_identifier'];

    $modHSmodName = !empty($mod['mod_name'])
        ? $mod['mod_name']
        : 'Unknown Mod';

    $modHSlbName = !empty($mod['highscoreName'])
        ? $mod['highscoreName']
        : 'Unknown Leaderboard';

    $modHSDescription = !empty($mod['highscoreDescription'])
        ? $mod['highscoreDescription']
        : 'No description given.';

    echo '<span class="h4">&nbsp;</span>';

    echo '<div class="alert alert-info" role="alert"><p><strong>Note</strong>: The leaderboards are updated every 10minutes. New scores are highlighted for 2 hours.</p></div>';

    echo '<h2><a class="nav-clickable" href="#d2mods__stats?id=' . $modID . '">' . $modHSmodName . '</a> <small>' . $modHSlbName . '</small></h2>';

    echo '<p>' . $modHSDescription . '</p>';

    echo '<span class="h4">&nbsp;</span>';
    echo '<div class="text-center">
                    <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__mod_highscores">Back to Highscores</a>
                    <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__stats?id=' . $modID . '">Mod Details</a>
               </div>';
    echo '<span class="h4">&nbsp;</span>';

    $modHSObjective = !empty($mod['highscoreObjective']) && $mod['highscoreObjective'] == 'min'
        ? 'ASC'
        : 'DESC';

    $modHSOperator = !empty($mod['highscoreOperator']) && $mod['highscoreOperator'] == 'divide'
        ? true //dividing by factor
        : false; //multiplying by factor

    $modHSFactor = !empty($mod['highscoreFactor']) && is_numeric($mod['highscoreFactor'])
        ? $mod['highscoreFactor']
        : 1;

    $modHSDecimals = isset($mod['highscoreDecimals']) && is_numeric($mod['highscoreDecimals'])
        ? $mod['highscoreDecimals']
        : 2;

    $modHSLeaderboardData = cached_query(
        'mod_lb_lbs_' . $modHSid,
        'SELECT
                `userName`,
                `steamID32`,
                `highscoreValue`,
                `date_recorded`
            FROM `cron_hs_mod`
            WHERE `highscoreID` = ? AND `modID` = ?
            ORDER BY `highscoreValue` ' . $modHSObjective . ';',
        'ss',
        array($modHSid, $modIdentifier),
        15
    );

    if (empty($modHSLeaderboardData)) throw new Exception('No highscores matching these parameters!');

    echo '<div class="row">
                                <div class="col-md-1 text-center">
                                    <span class="h4">Rank</span>
                                </div>
                                <div class="col-md-2 text-center">
                                    <span class="h4">Score</span>
                                </div>
                                <div class="col-md-1 text-center">&nbsp;</div>
                                <div class="col-md-6 text-center">&nbsp;</div>
                                <div class="col-md-2 text-center">&nbsp;</div>
                            </div>';
    echo '<span class="h4">&nbsp;</span>';


    foreach ($modHSLeaderboardData as $key_lb => $value_lb) {
        $highscore_value = $modHSOperator
            ? $value_lb['highscoreValue'] / $modHSFactor
            : $value_lb['highscoreValue'] * $modHSFactor;

        $score = !empty($highscore_value)
            ? number_format($highscore_value, $modHSDecimals)
            : '??';

        $relativeDate = relative_time_v2($value_lb['date_recorded'], NULL, true);
        $relativeDateRaw = relative_time_v2($value_lb['date_recorded'], 'hour', true);

        $timeColour = $relativeDateRaw['number'] <= 2
            ? ' hs_lb_recent_score'
            : '';

        $newBadge = $relativeDateRaw['number'] <= 2
            ? ' <span class="badge">!</span>'
            : '';

        if ($value_lb['steamID32'] != 0) {
            $modHS_lb_details = cached_query(
                'mg_lb_user_details' . $value_lb['steamID32'],
                'SELECT
                        `user_id64`,
                        `user_id32`,
                        `user_name`,
                        `user_avatar`,
                        `user_avatar_medium`,
                        `user_avatar_large`
                FROM `gds_users`
                WHERE `user_id32` = ?
                LIMIT 0,1;',
                's',
                $value_lb['steamID32'],
                1 * 60
            );
        } else {
            $modHS_lb_details = false;
        }

        if (!empty($modHS_lb_details)) {
            $userAvatar = !empty($modHS_lb_details[0]['user_avatar'])
                ? $modHS_lb_details[0]['user_avatar']
                : $CDN_image . '/images/misc/steam/blank_avatar.jpg';

            if (!empty($modHS_lb_details[0]['user_name']) && strlen($modHS_lb_details[0]['user_name']) > 30) {
                $modHS_lb_details[0]['user_name'] = substr($modHS_lb_details[0]['user_name'], 0, 26) . '...';
            }

            $userName = !empty($modHS_lb_details[0]['user_name'])
                ? '<span class="h3">
                                <a class="nav-clickable" href="#d2mods__profile?id=' . $value_lb['steamID32'] . '">
                                    ' . $modHS_lb_details[0]['user_name'] . '
                                </a>
                            </span>'
                : '<span class="h3">
                                <a class="nav-clickable" href="#d2mods__profile?id=' . $value_lb['steamID32'] . '">
                                    ?UNKNOWN?
                                </a>
                                <small>Sign in to update profile!</small>
                            </span>';

            echo '<div class="row">
                                <div class="col-md-1 text-center">
                                    <span class="h3">' . ($key_lb + 1) . '</span>
                                </div>
                                <div class="col-md-2 text-center">
                                    <span class="h3">' . $score . '</span>
                                </div>
                                <div class="col-md-1">
                                    <a class="nav-clickable" href="#d2mods__profile?id=' . $value_lb['steamID32'] . '">
                                        <img alt="User Avatar" class="hof_avatar img-responsive center-block" src="' . $userAvatar . '" />
                                    </a>
                                </div>
                                <div class="col-md-5">
                                    ' . $userName . '
                                </div>
                                <div class="col-md-1 text-right">
                                    <span class="h5' . $timeColour . '">
                                    ' . $relativeDate['number'] . '
                                    </span>
                                </div>
                                <div class="col-md-2 text-left">
                                    <span class="h5' . $timeColour . '">
                                    ' . $relativeDate['time_string'] . $newBadge . '
                                    </span>
                                </div>
                            </div>';
            echo '<span class="h4">&nbsp;</span>';
        } else {
            $userName = $value_lb['steamID32'] == 0
                ? '<span class="h3">Bots</span>'
                : 'COULDN\'T LOOKUP USER!!';

            echo '<div class="row">
                                <div class="col-md-1 text-center">
                                    <span class="h3">' . ($key_lb + 1) . '</span>
                                </div>
                                <div class="col-md-2 text-center">
                                    <span class="h3">' . $score . '</span>
                                </div>
                                <div class="col-md-1">
                                    <img alt="User Avatar" class="hof_avatar img-responsive center-block" src="' . $CDN_image . '/images/misc/steam/blank_avatar.jpg' . '" />
                                </div>
                                <div class="col-md-5">
                                    ' . $userName . '
                                </div>
                                <div class="col-md-1 text-right">
                                    <span class="h5' . $timeColour . '">
                                    ' . $relativeDate['number'] . '
                                    </span>
                                </div>
                                <div class="col-md-2 text-left">
                                    <span class="h5' . $timeColour . '">
                                    ' . $relativeDate['time_string'] . $newBadge . '
                                    </span>
                                </div>
                            </div>';
            echo '<span class="h4">&nbsp;</span>';
        }
    }

    echo '<span class="h4">&nbsp;</span>';

    echo '<span class="h4">&nbsp;</span>';
    echo '<div class="text-center">
                    <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__mod_highscores">Back to Highscores</a>
                    <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__stats?id=' . $modID . '">Mod Details</a>
           </div>';
    echo '<span class="h4">&nbsp;</span>';

    $memcache->close();
} catch (Exception $e) {
    echo formatExceptionHandling($e);
}