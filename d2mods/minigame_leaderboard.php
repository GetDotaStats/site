<?php
require_once('./functions.php');
require_once('../global_functions.php');
require_once('../connections/parameters.php');

try {
    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    $mgIdentifier = !empty($_GET['lid']) && is_numeric($_GET['lid'])
        ? $_GET['lid']
        : 1;

    $minigame = cached_query(
        'mg_lb_details_' . $mgIdentifier,
        'SELECT
                `minigameID`,
                `minigameIdentifier`,
                `minigameName`,
                `minigameDeveloper`,
                `minigameDescription`,
                `minigameSteamGroup`,
                `minigameActive`,
                `minigameObjective`,
                `minigameOperator`,
                `minigameFactor`,
                `minigameDecimals`,
                `date_recorded`
            FROM `stat_highscore_minigames`
            WHERE `minigameIdentifier` = ?
            LIMIT 0,1;',
        'i',
        $mgIdentifier,
        15
    );

    if (!empty($minigame)) {
        $minigame = $minigame[0];

        $mgID = $minigame['minigameID'];

        $mgName = !empty($minigame['minigameName'])
            ? $minigame['minigameName']
            : 'Unknown Mini Game';

        $mgDescription = !empty($minigame['minigameDescription'])
            ? $minigame['minigameDescription']
            : 'No description given.';

        echo '<h2>' . $mgName . ' <small>Leaderboard</small></h2>';

        echo '<p>' . $mgDescription . '</p>';

        echo '<div class="alert alert-info" role="alert"><p><strong>Note</strong>: The leaderboards are updated every 10minutes. New scores are highlighted for 2 hours.</p></div>';

        echo '<span class="h4">&nbsp;</span>';
        echo '<div class="text-center">
                    <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__minigame_highscores">Back to Highscores</a>
               </div>';
        echo '<span class="h4">&nbsp;</span>';

        $mgObjective = !empty($minigame['minigameObjective']) && $minigame['minigameObjective'] == 'min'
            ? 'ASC'
            : 'DESC';

        $mgOperator = !empty($minigame['minigameOperator']) && $minigame['minigameOperator'] == 'divide'
            ? true //dividing by factor
            : false; //multiplying by factor

        $mgFactor = !empty($minigame['minigameFactor']) && is_numeric($minigame['minigameFactor'])
            ? $minigame['minigameFactor']
            : 1;

        $mgDecimals = isset($minigame['minigameDecimals']) && is_numeric($minigame['minigameDecimals'])
            ? $minigame['minigameDecimals']
            : 2;

        $mgLeaderboardData = cached_query(
            'mg_lb_lbs_' . $mgID,
            'SELECT
                    `leaderboard`,
                    `user_id32`,
                    `highscore_value`,
                    `date_recorded`
                FROM `cron_hs`
                WHERE `minigameID` = ?
                ORDER BY `leaderboard`, `highscore_value` ' . $mgObjective . ';',
            's',
            $mgID,
            15
        );

        if (!empty($mgLeaderboardData)) {
            $mgLeaderboardArray = array();
            $mgTabs = array();

            foreach ($mgLeaderboardData as $key => $value) {
                $highscore_value = $mgOperator
                    ? $value['highscore_value'] / $mgFactor
                    : $value['highscore_value'] * $mgFactor;

                $mgLeaderboardArray[$value['leaderboard']][] = array(
                    'user_id32' => $value['user_id32'],
                    'highscore_value' => $highscore_value,
                    'highscore_value_unmod' => $value['highscore_value'],
                    'date_recorded' => $value['date_recorded'],
                );
            }

            echo '<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">';

            $i = 0;
            foreach ($mgLeaderboardArray as $key_lb => $value_lb) {
                $i++;

                echo '
                         <div class="panel panel-default">
                            <div class="panel-heading" role="tab" id="heading' . $i . '">
                              <h4 class="panel-title">
                                <a data-toggle="collapse" data-parent="#accordion" href="#collapse' . $i . '" aria-expanded="true" aria-controls="collapse' . $i . '">
                                  ' . $key_lb . '
                                </a>
                              </h4>
                            </div>
                            <div id="collapse' . $i . '" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="heading' . $i . '">
                              <div class="panel-body">
                              ';


                //echo '<h3>' . $key_lb . '</h3>';
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

                foreach ($value_lb as $key => $value) {
                    $score = !empty($value['highscore_value'])
                        ? number_format($value['highscore_value'], $mgDecimals)
                        : '??';

                    $relativeDate = relative_time_v2($value['date_recorded'], NULL, true);
                    $relativeDateRaw = relative_time_v2($value['date_recorded'], 'hour', true);

                    $timeColour = $relativeDateRaw['number'] <= 2
                        ? ' hs_lb_recent_score'
                        : '';

                    $newBadge = $relativeDateRaw['number'] <= 2
                        ? ' <span class="badge">!</span>'
                        : '';

                    if ($value['user_id32'] != 0) {
                        $mg_lb_details = cached_query(
                            'mg_lb_user_details' . $value['user_id32'],
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
                            $value['user_id32'],
                            1 * 60
                        );
                    } else {
                        $mg_lb_details = false;
                    }

                    if (!empty($mg_lb_details)) {
                        $userAvatar = !empty($mg_lb_details[0]['user_avatar'])
                            ? $mg_lb_details[0]['user_avatar']
                            : $CDN_image . '/images/misc/steam/blank_avatar.jpg';

                        if (!empty($mg_lb_details[0]['user_name']) && strlen($mg_lb_details[0]['user_name']) > 30) {
                            $mg_lb_details[0]['user_name'] = substr($mg_lb_details[0]['user_name'], 0, 26) . '...';
                        }

                        $userName = !empty($mg_lb_details[0]['user_name'])
                            ? '<span class="h3">
                                <a class="nav-clickable" href="#d2mods__profile?id=' . $value['user_id32'] . '">
                                    ' . $mg_lb_details[0]['user_name'] . '
                                </a>
                            </span>'
                            : '<span class="h3">
                                <a class="nav-clickable" href="#d2mods__profile?id=' . $value['user_id32'] . '">
                                    ?UNKNOWN?
                                </a>
                                <small>Sign in to update profile!</small>
                            </span>';

                        echo '<div class="row">
                                <div class="col-md-1 text-center">
                                    <span class="h3">' . ($key + 1) . '</span>
                                </div>
                                <div class="col-md-2 text-center">
                                    <span class="h3">' . $score . '</span>
                                </div>
                                <div class="col-md-1">
                                    <a class="nav-clickable" href="#d2mods__profile?id=' . $value['user_id32'] . '">
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
                        $userName = $value['user_id32'] == 0
                            ? '<span class="h3">Bots</span>'
                            : 'EXCEPTION OCCURRED!! COULDN\'T LOOKUP!!';

                        echo '<div class="row">
                                <div class="col-md-1 text-center">
                                    <span class="h3">' . ($key + 1) . '</span>
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


                echo '
                              </div>
                            </div>
                          </div>
                          ';


            }

            echo '</div>';
        } else {
            echo bootstrapMessage('Oh Snap', 'No leaderboards!', 'danger');
        }
    } else {
        echo bootstrapMessage('Oh Snap', 'No mini game matches those parameters!.', 'danger');
    }

    echo '<span class="h4">&nbsp;</span>';
    echo '<div class="text-center">
                    <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__minigame_highscores">Back to Highscores</a>
           </div>';
    echo '<span class="h4">&nbsp;</span>';

    $memcache->close();
} catch (Exception $e) {
    echo formatExceptionHandling($e);
}