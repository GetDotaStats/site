<?php
try {
    require_once('../connections/parameters.php');
    require_once('../global_functions.php');
    require_once('./functions_v3.php');

    if (!isset($_SESSION)) {
        session_start();
    }

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    echo '<h2>Signature Generator</h2>';

    echo '<p>This page allows logged in users to create a forum signature based on their Dotabuff page and data
    submitted to the site.</p>';

    checkLogin_v2();
    if (empty($_SESSION['user_id64'])) throw new Exception('Not logged in! Please login via the Steam button at the top right of the screen.');

    $steamID = new SteamID($_SESSION['user_id64']);
    if (empty($steamID->getSteamID32()) || empty($steamID->getSteamID64())) throw new Exception('Not logged in! Please login via the Steam button at the top right of the screen.');

    if (isset($_GET['refresh'])) {
        updateUserDetails($steamID->getSteamID64(), $api_key1);

        $file_name_location = './images/generated/' . $steamID->getsteamID32() . '_main.png';
        if (file_exists($file_name_location)) {
            @unlink($file_name_location);
        }

        $file_name_location = './images/generated/' . $steamID->getsteamID32() . '_forum.png';
        if (file_exists($file_name_location)) {
            @unlink($file_name_location);
        }

        $file_name_location = './images/generated/' . $steamID->getsteamID32() . '_dotaroot.png';
        if (file_exists($file_name_location)) {
            @unlink($file_name_location);
        }
    }

    if (isset($_GET['refresh_db'])) {
        updateUserDetails($steamID->getSteamID64(), $api_key1);

        $cacheTimeHours = 2; //cache things for 2hours
        $required_hero_min_play = 14; //need fourteen games with a hero for it to count
        $user_details = get_account_details($steamID->getsteamID32(), 4, $required_hero_min_play, 1, $cacheTimeHours);
    }

    echo '<div class="row">
            <div class="text-center">
                <img src="http://getdotastats.com/sig/' . $steamID->getSteamID32() . '.png" /><br />
            </div>
        </div>';

    echo '<span class="h4">&nbsp;</span>';

    echo '<div class="row">
        <div class="col-sm-2">
            <strong>Signature link:</strong>
        </div>
        <div class="col-sm-10">
            <code>http://getdotastats.com/sig/' . $steamID->getSteamID32() . '.png</code>
        </div>
    </div>';

    echo '<span class="h4">&nbsp;</span>';

    echo '<div class="row">
            <div class="text-center">
                <a class="nav-clickable btn btn-danger btn-lg" href="#sig__generator?refresh">Refresh Signature</a>
                <a class="nav-clickable btn btn-danger btn-lg" href="#sig__generator?refresh_db">Refresh Dotabuff Cache</a>
            </div>
       </div>';

    echo '<hr />';

    echo '<h2>Debugging Checklist</h2>';

    $glyph_true = '<span class="label-success label"><span class="glyphicon glyphicon-ok"></span></span>';
    $glyph_false = '<span class="label-danger label"><span class="glyphicon glyphicon-remove"></span></span>';

    /////////////////////////////
    // Dotabuff checklist
    /////////////////////////////
    echo '<div class="row">
                <div class="col-sm-2 h3">Dotabuff</div>
            </div>';

    {
        $user_DB_details = cached_query(
            'sig_user_db_details' . $steamID->getsteamID32(),
            'SELECT
                    `user_id32`,
                    `user_id64`,
                    `last_match`,
                    `account_win`,
                    `account_loss`,
                    `account_abandons`,
                    `account_percent`,
                    `winRateHeroes`,
                    `mostPlayedHeroes`,
                    `date_updated`,
                    `date_recorded`
                FROM `sigs_dotabuff_info`
                WHERE `user_id32` = ?
                LIMIT 0,1;',
            's',
            $steamID->getsteamID32(),
            1
        );
        if (!empty($user_DB_details)) $user_DB_details = $user_DB_details[0];

        /////////////////////////////
        // Last Cached
        /////////////////////////////
        $DB_cached_date = !empty($user_DB_details) && !empty($user_DB_details['date_updated'])
            ? relative_time_v3($user_DB_details['date_updated'], 2, 'day', true)
            : NULL;

        $DB_cached_check = !empty($user_DB_details) && !empty($DB_cached_date) && $DB_cached_date['number'] <= 1
            ? $glyph_true
            : $glyph_false;

        if (!empty($user_DB_details)) {
            $DB_cached = !empty($user_DB_details['date_updated'])
                ? relative_time_v3($user_DB_details['date_updated'])
                : 'Un-determined';
        } else {
            $DB_cached = '&nbsp;';
        }

        $DB_cached_glyph = '<span class="glyphicon glyphicon-question-sign" title="Have your Dotabuff stats been cached today?"></span>';
        echo '<div class="row">
                <div class="col-sm-2">' . $DB_cached_glyph . ' <strong>Cached</strong></div>
                <div class="col-sm-1">' . $DB_cached_check . '</div>
                <div class="col-sm-9">' . $DB_cached . '</div>
            </div>';

        /////////////////////////////
        // winRateHeroes
        /////////////////////////////
        $DB_winRateHeroes_check = !empty($user_DB_details['winRateHeroes'])
            ? $glyph_true
            : $glyph_false;

        if (!empty($user_DB_details)) {
            try {
                if (!empty($user_DB_details['winRateHeroes'])) {
                    $DB_winRateHeroes_tmp = json_decode($user_DB_details['winRateHeroes'], true);
                    $DB_winRateHeroes = array();
                    if (!empty($DB_winRateHeroes_check)) {
                        foreach ($DB_winRateHeroes_tmp as $key => $value) {
                            if (!empty($value['name'])) $DB_winRateHeroes[] = $value['name'] . ' (' . $value['winrate'] . ')';
                        }

                        $DB_winRateHeroes = '<ul><li>' . implode('</li><li>', $DB_winRateHeroes) . '</li></ul>';
                    } else {
                        throw new Exception();
                    }
                } else {
                    throw new Exception();
                }
            } catch (Exception $e) {
                $DB_winRateHeroes = 'Un-determined heroes!';
            }
        } else {
            $DB_winRateHeroes = '&nbsp;';
        }

        $DB_winRateHeroes_glyph = '<span class="glyphicon glyphicon-question-sign" title="Have you played 15 or more games with at least one hero to determine which is your best?"></span>';
        echo '<div class="row">
                <div class="col-sm-2">' . $DB_winRateHeroes_glyph . ' <strong>Best Heroes</strong></div>
                <div class="col-sm-1">' . $DB_winRateHeroes_check . '</div>
                <div class="col-sm-9">' . $DB_winRateHeroes . '</div>
           </div>';

        /////////////////////////////
        // mostPlayedHeroes
        /////////////////////////////
        $DB_mostPlayedHeroes_check = !empty($user_DB_details['mostPlayedHeroes'])
            ? $glyph_true
            : $glyph_false;

        if (!empty($user_DB_details)) {
            try {
                if (!empty($user_DB_details['mostPlayedHeroes'])) {
                    $DB_mostPlayedHeroes_tmp = json_decode($user_DB_details['mostPlayedHeroes'], true);
                    $DB_mostPlayedHeroes = array();
                    if (!empty($DB_mostPlayedHeroes_check)) {
                        foreach ($DB_mostPlayedHeroes_tmp as $key => $value) {
                            if (!empty($value['name'])) $DB_mostPlayedHeroes[] = $value['name'] . ' (' . $value['gamesplayed'] . ' games)';
                        }

                        $DB_mostPlayedHeroes = '<ul><li>' . implode('</li><li>', $DB_mostPlayedHeroes) . '</li></ul>';
                    } else {
                        throw new Exception();
                    }
                } else {
                    throw new Exception();
                }
            } catch (Exception $e) {
                $DB_mostPlayedHeroes = 'Un-determined heroes!';
            }
        } else {
            $DB_mostPlayedHeroes = '&nbsp;';
        }

        $DB_mostPlayedHeroes_glyph = '<span class="glyphicon glyphicon-question-sign" title="Have you played 15 or more games with at least one hero to determine which is your most played?"></span>';
        echo '<div class="row">
                <div class="col-sm-2">' . $DB_mostPlayedHeroes_glyph . ' <strong>Most Played</strong></div>
                <div class="col-sm-1">' . $DB_mostPlayedHeroes_check . '</div>
                <div class="col-sm-9">' . $DB_mostPlayedHeroes . '</div>
           </div>';

        /////////////////////////////
        // Last Match
        /////////////////////////////
        $DB_last_match_date = !empty($user_DB_details) && !empty($user_DB_details['last_match'])
            ? relative_time_v3($user_DB_details['last_match'], 2, 'day', true)
            : NULL;

        $DB_last_match_check = !empty($user_DB_details) && !empty($DB_last_match_date) && $DB_last_match_date['number'] <= 14
            ? $glyph_true
            : $glyph_false;

        if (!empty($user_DB_details)) {
            $DB_last_match = !empty($user_DB_details['last_match'])
                ? 'played ' . relative_time_v3($user_DB_details['last_match'])
                : 'Un-determined';
        } else {
            $DB_last_match = '&nbsp;';
        }

        $DB_last_match_glyph = '<span class="glyphicon glyphicon-question-sign" title="Was your last match recorded on Dotabuff within the last two weeks?"></span>';
        echo '<div class="row">
                <div class="col-sm-2">' . $DB_last_match_glyph . ' <strong>Last Match</strong></div>
                <div class="col-sm-1">' . $DB_last_match_check . '</div>
                <div class="col-sm-9">' . $DB_last_match . '</div>
            </div>';
    }

    /////////////////////////////
    // Signature checklist
    /////////////////////////////
    echo '<div class="row">
                <div class="col-sm-2 h3">Signature</div>
            </div>';

    {
        $user_sig_details = cached_query(
            'sig_user_sig_details' . $steamID->getsteamID32(),
            'SELECT
                    `user_id32`,
                    `user_id64`,
                    `date_modified`,
                    `date_recorded`
                FROM `sigs_generated`
                WHERE `user_id32` = ?
                LIMIT 0,1;',
            's',
            $steamID->getsteamID32(),
            1
        );
        if (!empty($user_sig_details)) $user_sig_details = $user_sig_details[0];

        /////////////////////////////
        // Last Generated
        /////////////////////////////
        $sig_gen_date = !empty($user_sig_details) && !empty($user_sig_details['date_modified'])
            ? relative_time_v3($user_sig_details['date_modified'], 2, 'day', true)
            : NULL;

        $sig_gen_check = !empty($user_sig_details) && !empty($sig_gen_date) && $sig_gen_date['number'] <= 1
            ? $glyph_true
            : $glyph_false;

        if (!empty($sig_gen_date)) {
            $sig_gen = !empty($user_sig_details['date_modified'])
                ? relative_time_v3($user_sig_details['date_modified'])
                : 'Un-determined';
        } else {
            $sig_gen = '&nbsp;';
        }

        $sig_gen_glyph = '<span class="glyphicon glyphicon-question-sign" title="Was your signature regenerated today?"></span>';
        echo '<div class="row">
                <div class="col-sm-2">' . $sig_gen_glyph . ' <strong>Generated</strong></div>
                <div class="col-sm-1">' . $sig_gen_check . '</div>
                <div class="col-sm-9">' . $sig_gen . '</div>
            </div>';

    }

    echo '<hr />';

    echo '<h2>Questions & Answers</h2>';

    //Q1
    echo '<div class="row">
            <span class="h3">Q1. My signature is not updating!</span>
        </div>

        <div class="row">
            <ul>
                <li>Signatures are cached for 2 hours in browsers.</li>
                <li>You may need to refresh the image (not this page) by opening it in a new tab and pressing
                    <kdb><kdb>CTRL</kdb> + <kdb>R</kdb></kdb>.</li>
            </ul>
        </div>';

    echo '<span class="h3">&nbsp;</span>';

    //Q2
    echo '<div class="row">
            <span class="h3">Q2. Where did my MMR go?</span>
        </div>

        <div class="row">
            <ul>
                <li>We can no longer offer this service in the Reborn client.</li>
            </ul>
        </div>';

    echo '<span class="h3">&nbsp;</span>';

    //Q3
    echo '<div class="row">
            <span class="h3">Q3. My steam name and avatar is not updating!</span>
        </div>

        <div class="row">
            <ul>
                <li>Steam names and avatars are only updated when logging into the site.</li>
                <li>To perform a manual refresh, click the red refresh button above, and then do a hard
                    refresh of your signature (refer to Q1).</li>
            </ul>
        </div>';

    echo '<span class="h3">&nbsp;</span>';

    //Q4
    echo '<div class="row">
            <span class="h3">Q4. I am missing heroes in my signature!</span>
        </div>

        <div class="row">
            <ul>
                <li>We only count heroes with at least 15 games played.</li>
            </ul>
        </div>';

    echo '<span class="h3">&nbsp;</span>';

    //Q5
    echo '<div class="row">
            <span class="h3">Q5. One of the heroes is missing its picture!</span>
        </div>

        <div class="row">
            <ul>
                <li>This happens when an existing hero is renamed or a new hero is added.</li>
                <li>Report your issue in our <a target="_blank" href="http://github.com/GetDotaStats/site/issues">
                    Issue Tracker</a> or in the chatbox on the right hand side of this page.</li>
            </ul>
        </div>';

    echo '<span class="h3">&nbsp;</span>';

    //Q6
    echo '<div class="row">
            <span class="h3">Q6. My issue is not on the list!</span>
        </div>

        <div class="row">
            <ul>
                <li>Report your issue in our <a target="_blank" href="http://github.com/GetDotaStats/site/issues">
                    Issue Tracker</a>.</li>
            </ul>
        </div>';

    echo '<span class="h3">&nbsp;</span>';

    echo '<div class="text-center">
                <a class="nav-clickable btn btn-default btn-lg" href="#sig__usage">Signature Trends</a>
           </div>';

    echo '<span class="h3">&nbsp;</span>';
} catch (Exception $e) {
    echo formatExceptionHandling($e);
} finally {
    if (isset($memcache)) $memcache->close();
}