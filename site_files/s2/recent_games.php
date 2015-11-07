<?php
require_once('../connections/parameters.php');
require_once('../global_functions.php');
require_once('./functions.php');

if (!isset($_SESSION)) {
    session_start();
}

try {
    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    $SQLstatementWhere = array();
    $SQLdeclaration = '';
    $SQLvalues = array();

    $filterPhase = !empty($_GET['p']) && is_numeric($_GET['p'])
        ? $_GET['p']
        : -1;

    switch ($filterPhase) {
        case 1:
            $SQLstatementWhere[] = 's2.`matchPhaseID` = ?';
            $SQLdeclaration .= 'i';
            $SQLvalues[] = 1;
            break;
        case 2:
            $SQLstatementWhere[] = 's2.`matchPhaseID` = ?';
            $SQLdeclaration .= 'i';
            $SQLvalues[] = 2;
            break;
        case 3:
            $SQLstatementWhere[] = 's2.`matchPhaseID` = ?';
            $SQLdeclaration .= 'i';
            $SQLvalues[] = 3;
            break;
    }

    $filterModID = !empty($_GET['m']) && is_numeric($_GET['m'])
        ? $_GET['m']
        : NULL;

    if (!empty($filterModID)) {
        $modIDLookup = cached_query(
            's2_recent_games_modlookup' . $filterModID,
            'SELECT
                  ml.`mod_id`
                FROM `mod_list` ml
                WHERE ml.`mod_id` = ?
                LIMIT 0,1;',
            'i',
            array($filterModID),
            5
        );

        if (!empty($modIDLookup)) {
            $modID = $filterModID;
        } else {
            $modID = NULL;
        }
    } else {
        $modID = NULL;
    }

    if (!empty($modID)) {
        $SQLstatementWhere[] = 's2.`modID` = ?';
        $SQLdeclaration .= 'i';
        $SQLvalues[] = $modID;

        echo modPageHeader($modID, $CDN_image);
    }

    if (!empty($SQLstatementWhere)) {
        $SQLstatementWhere = 'WHERE ' . implode(' AND ', $SQLstatementWhere);
    } else {
        $SQLstatementWhere = '';
    }

    echo '<h3>Recently Played Games</h3>';

    echo '<p>A list of the last 30 games that have been played. Ordered by when the game details were last updated.</p>';

    //SELECT JUMP MENU FOR MOD FILTERING
    {
        $modList = cached_query(
            's2_recent_games_mod_list',
            'SELECT
                  ml.`mod_id`,
                  ml.`mod_name`
                FROM `mod_list` ml
                WHERE ml.`mod_active` = 1
                ORDER BY ml.`mod_name`;',
            NULL,
            NULL,
            5
        );

        echo '<form id="modSearch">';
        echo '<div class="row">
                    <div class="col-md-2"><strong>Mod Filter</strong></div>
                    <div class="col-md-5">
                        <select id="modSearch_id" class="formTextArea boxsizingBorder" onChange="jumpMenuMod(this)" required>
                            <option value="-">-</option>';

        if (!empty($modList)) {
            foreach ($modList as $key => $value) {
                echo '<option' . (($value['mod_id'] == $filterModID) ? ' selected' : '') . ' value="' . $value['mod_id'] . '">' . $value['mod_name'] . '</option>';
            }
        }

        echo '          </select>
                    </div>
                </div>';
        echo '</form>';

        echo '<span class="h5">&nbsp;</span>';

        echo '<form id="phaseSearch">';
        echo '<div class="row">
                    <div class="col-md-2"><strong>Phase Filter</strong></div>
                    <div class="col-md-3">
                        <select id="modSearch_id" class="formTextArea boxsizingBorder" onChange="jumpMenuPhase(this)" required>
                            <option value="-">-</option>
                            <option' . (($filterPhase == 1) ? ' selected' : '') . ' value="1">Players Loaded</option>
                            <option' . (($filterPhase == 2) ? ' selected' : '') . ' value="2">Game Started</option>
                            <option' . (($filterPhase == 3) ? ' selected' : '') . ' value="3">Game Ended</option>
                        </select>
                    </div>
                </div>';
        echo '</form>';

        echo '<span class="h5">&nbsp;</span>';

        echo '<script type="application/javascript">
                function jumpMenuMod(selObj) {
                    var op = selObj.options[selObj.selectedIndex];
                    if(op.value != "-"){
                        loadPage("#s2__recent_games?m=" + op.value' . ($filterPhase != -1 ? ' + "&p=' . $filterPhase . '"' : '') . ', 1);
                    } else{
                        loadPage("#s2__recent_games"' . ($filterPhase != -1 ? ' + "?p=' . $filterPhase . '"' : '') . ', 1);
                    }
                }

                function jumpMenuPhase(selObj) {
                    var op = selObj.options[selObj.selectedIndex];
                    if(op.value != "-"){
                        loadPage("#s2__recent_games?p=" + op.value' . (!empty($filterModID) ? ' + "&m=' . $filterModID . '"' : '') . ', 1);
                    } else{
                        loadPage("#s2__recent_games"' . (!empty($filterModID) ? ' + "?m=' . $filterModID . '"' : '') . ', 1);
                    }
                }
            </script>';
    }

    $recentGames = cached_query(
        's2_recent_games_p' . $filterPhase . '_m' . $filterModID,
        'SELECT
              s2.`matchID`,
              s2.`matchAuthKey`,
              s2.`modID`,
              s2.`matchHostSteamID32`,
              s2.`matchPhaseID`,
              s2.`isDedicated`,
              s2.`matchMapName`,
              s2.`numPlayers`,
              s2.`numRounds`,
              s2.`matchDuration`,
              s2.`matchFinished`,
              s2.`schemaVersion`,
              s2.`dateUpdated`,
              s2.`dateRecorded`,

              ml.`mod_name`,
              ml.`mod_workshop_link`
            FROM `s2_match` s2
            LEFT JOIN `mod_list` ml ON s2.`modID` = ml.`mod_id`
            ' . $SQLstatementWhere . '
            ORDER BY s2.`dateUpdated` DESC
            LIMIT 0,30;',
        $SQLdeclaration,
        $SQLvalues,
        15
    );

    if (empty($recentGames)) {
        throw new Exception('No games recently played!');
    }

    echo '<div class="row">
                <div class="col-md-4"><strong>Mod</strong></div>
                <div class="col-md-6">
                    <div class="col-md-3 text-center"><strong>Players</strong></div>
                    <div class="col-md-3 text-center"><strong>Rounds</strong></div>
                    <div class="col-md-3 text-center"><strong>Duration</strong></div>
                    <div class="col-md-3 text-center"><strong>Phase</strong></div>
                </div>
                <div class="col-md-2 text-center"><strong>Updated</strong></div>
            </div>';

    echo '<span class="h4">&nbsp;</span>';

    foreach ($recentGames as $key => $value) {
        $matchPhase = matchPhaseToGlyhpicon($value['matchPhaseID']);

        echo '<div class="row searchRow">
                <a class="nav-clickable" href="#s2__mod?id=' . $value['modID'] . '">
                    <div class="col-md-4"><span class="glyphicon glyphicon-eye-open"></span> ' . $value['mod_name'] . '</div>
                </a>
                <a class="nav-clickable" href="#s2__match?id=' . $value['matchID'] . '">
                    <div class="col-md-6">
                        <div class="col-md-3 text-center">' . $value['numPlayers'] . '</div>
                        <div class="col-md-3 text-center">' . $value['numRounds'] . '</div>
                        <div class="col-md-3 text-right">' . secs_to_clock($value['matchDuration']) . '</div>
                        <div class="col-md-3 text-center">' . $matchPhase . '</div>
                    </div>
                    <div class="col-md-2 text-right">' . relative_time_v3($value['dateUpdated']) . '</div>
                </a>
            </div>';

        echo '<span class="h5">&nbsp;</span>';
    }

    echo '<hr />';


    echo '<span class="h4">&nbsp;</span>';

    echo '<div class="text-center">
                <a class="nav-clickable btn btn-default btn-lg" href="#s2__directory">Mod Directory</a>
                <a class="nav-clickable btn btn-default btn-lg" href="#s2__recent_games">Recent Games</a>
           </div>';

    echo '<span class="h4">&nbsp;</span>';
} catch (Exception $e) {
    echo formatExceptionHandling($e);
} finally {
    if (isset($memcache)) $memcache->close();
}