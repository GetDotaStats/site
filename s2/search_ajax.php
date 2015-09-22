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

    if (empty($_POST['search_term'])) {
        throw new Exception('Missing or invalid required parameter(s)!');
    }

    $searchTerm = $_POST['search_term'];

    $resultsArray = array();

    //match check
    {
        if (is_numeric($searchTerm)) {
            $matchCheck = cached_query(
                'search_match_check' . $searchTerm,
                'SELECT
                        s2.`matchID`,
                        s2.`numPlayers`,
                        s2.`numRounds`,
                        s2.`matchDuration`,
                        s2.`dateRecorded`,

                        ml.`mod_name`
                    FROM `s2_match` s2
                    JOIN `mod_list` ml ON s2.`modID` = ml.`mod_id`
                    WHERE s2.`matchID` = ?
                    LIMIT 0,1;',
                's',
                $searchTerm,
                1
            );

            if (!empty($matchCheck)) {
                $resultsArray['Matches'] = array(
                    'matchID' => $matchCheck[0]['matchID'],
                    'modName' => $matchCheck[0]['mod_name'],
                    'numPlayers' => $matchCheck[0]['numPlayers'],
                    'numRounds' => $matchCheck[0]['numRounds'],
                    'matchDuration' => $matchCheck[0]['matchDuration'],
                    'dateRecorded' => $matchCheck[0]['dateRecorded'],
                );
            }
        }
    }

    //steam profile url check
    {
        if (stristr($searchTerm, 'steamcommunity.com/id/')) {
            $searchTerm = cut_str($searchTerm, 'steamcommunity.com/id/');
        }

        if (stristr($searchTerm, 'steamcommunity.com/profiles/')) {
            $searchTerm = cut_str($searchTerm, 'steamcommunity.com/profiles/');
        }
    }

    //username check
    {
        $usernameCheck = cached_query(
            'search_username_check' . $searchTerm,
            "SELECT
                    s2mpn.`steamID32`,
                    s2mpn.`steamID64`,
                    s2mpn.`playerName`,
                    s2mpn.`playerVanity`,
                    s2mpn.`dateUpdated`
                FROM `s2_match_players_name` s2mpn
                WHERE s2mpn.`playerName` LIKE ?
                LIMIT 0,25;",
            's',
            array($searchTerm . '%'),
            1
        );

        if (!empty($usernameCheck)) {
            foreach ($usernameCheck as $key => $value) {
                $resultsArray['Usernames'][] = array(
                    'steamID32' => $value['steamID32'],
                    'steamID64' => $value['steamID64'],
                    'playerName' => $value['playerName'],
                    'playerVanity' => $value['playerVanity'],
                    'dateUpdated' => $value['dateUpdated'],
                );
            }
        }
    }

    //vanity check
    {
        $vanityCheck = cached_query(
            'search_vanity_check' . $searchTerm,
            "SELECT
                    s2mpn.`steamID32`,
                    s2mpn.`steamID64`,
                    s2mpn.`playerName`,
                    s2mpn.`playerVanity`,
                    s2mpn.`dateUpdated`
                FROM `s2_match_players_name` s2mpn
                WHERE s2mpn.`playerVanity` = ?
                LIMIT 0,25;",
            's',
            array($searchTerm),
            1
        );

        if(empty($vanityCheck) && !$localDev){
            $steamWebAPI = new steam_webapi($api_key2);

            //Do webapi request
            $vanityAPIcheck = $steamWebAPI->ResolveVanityURL($searchTerm);

            if(!empty($vanityAPIcheck)){
                if($vanityAPIcheck['response']['success'] == 1 && !empty($vanityAPIcheck['response']['steamid'])){
                    $vanitySteamID64 = $vanityAPIcheck['response']['steamid'];

                    //Store vanity against ID if it exists in table
                    $sqlResult = $db->q(
                        'UPDATE `s2_match_players_name` SET `playerVanity` = ? WHERE `steamID64` = ?;',
                        'ss',
                        array($searchTerm, $vanitySteamID64)
                    );

                    //if added to table, do another check
                    if($sqlResult){
                        $vanityCheck = $db->q(
                            "SELECT
                                    s2mpn.`steamID32`,
                                    s2mpn.`steamID64`,
                                    s2mpn.`playerName`,
                                    s2mpn.`playerVanity`,
                                    s2mpn.`dateUpdated`
                                FROM `s2_match_players_name` s2mpn
                                WHERE s2mpn.`playerVanity` = ?
                                LIMIT 0,25;",
                            's',
                            array($searchTerm),
                            1
                        );
                    }
                }
            }

        }

        if (!empty($vanityCheck)) {
            foreach ($vanityCheck as $key => $value) {
                $resultsArray['Vanity'][] = array(
                    'steamID32' => $value['steamID32'],
                    'steamID64' => $value['steamID64'],
                    'playerName' => $value['playerName'],
                    'playerVanity' => $value['playerVanity'],
                    'dateUpdated' => $value['dateUpdated'],
                );
            }
        }
    }

    //userID check
    {
        if (is_numeric($searchTerm)) {
            $userIDCheck = cached_query(
                'search_userID_check' . $searchTerm,
                'SELECT
                        s2mpn.`steamID32`,
                        s2mpn.`steamID64`,
                        s2mpn.`playerName`,
                        s2mpn.`playerVanity`,
                        s2mpn.`dateUpdated`
                    FROM `s2_match_players_name` s2mpn
                    WHERE s2mpn.`steamID32` = ? OR s2mpn.`steamID64` = ?
                    LIMIT 0,1;',
                'ss',
                array($searchTerm, $searchTerm),
                1
            );

            if (!empty($userIDCheck)) {
                foreach ($userIDCheck as $key => $value) {
                    $resultsArray['UserIDs'][] = array(
                        'steamID32' => $value['steamID32'],
                        'steamID64' => $value['steamID64'],
                        'playerName' => $value['playerName'],
                        'playerVanity' => $value['playerVanity'],
                        'dateUpdated' => $value['dateUpdated'],
                    );
                }
            }
        }
    }


    if (empty($resultsArray)) {
        throw new Exception('No results found for searchTerm.');
    } else {
        $formattedTable = '';

        foreach ($resultsArray as $key => $value) {
            $formattedTable .= '<div class="row h4">' . $key . '</div>';
            $formattedTable .= '<span class="h5">&nbsp;</span>';

            if ($key == 'Matches') {
                $formattedTable .= '<div class="row">
                        <div class="col-md-1">&nbsp;</div>
                        <div class="col-md-3"><strong>Mod</strong></div>
                        <div class="col-md-1 text-center"><strong>Players</strong></div>
                        <div class="col-md-1 text-center"><strong>Rounds</strong></div>
                        <div class="col-md-1 text-center"><strong>Duration</strong></div>
                        <div class="col-md-2">&nbsp;</div>
                        <div class="col-md-3 text-center"><strong>Recorded</strong></div>
                    </div>';
                $formattedTable .= '<span class="h5">&nbsp;</span>';

                $formattedTable .= '<div class="row">
                    <a class="searchRow nav-clickable" href="#s2__match?id=' . $value['matchID'] . '">
                            <div class="col-md-1"><span class="glyphicon glyphicon-eye-open"></span></div>
                            <div class="col-md-3">' . $value['modName'] . '</div>
                            <div class="col-md-1 text-center">' . $value['numPlayers'] . '</div>
                            <div class="col-md-1 text-center">' . $value['numRounds'] . '</div>
                            <div class="col-md-1 text-center">' . secs_to_clock($value['matchDuration']) . '</div>
                            <div class="col-md-2">&nbsp;</div>
                            <div class="col-md-3 text-right">' . relative_time_v3($value['dateRecorded']) . '</div>
                    </a>
                        </div>';
                $formattedTable .= '<span class="h5">&nbsp;</span>';
            }

            if ($key == 'Usernames' || $key == 'UserIDs' || $key == 'Vanity') {
                $formattedTable .= '<div class="row">
                        <div class="col-md-1">&nbsp;</div>
                        <div class="col-md-3"><strong>User</strong></div>
                        <div class="col-md-3"><strong>Vanity</strong></div>
                        <div class="col-md-2"><strong>External Links</strong></div>
                        <div class="col-md-3 text-center"><strong>Updated</strong></div>
                    </div>';
                $formattedTable .= '<span class="h5">&nbsp;</span>';

                foreach ($value as $key2 => $value2) {
                    $vanityURL = !empty($value2['playerVanity'])
                        ? $value2['playerVanity']
                        : '??';

                    $steamlinks['community'] = !empty($value2['steamID64'])
                        ? '<a target="_blank" href="https://steamcommunity.com/profiles/' . $value2['steamID64'] . '"><span class="glyphicon glyphicon-new-window"></span> SC</a>'
                        : '';

                    $steamlinks['dotabuff'] = !empty($value2['steamID32'])
                        ? '<a target="_blank" href="http://dotabuff.com/players/' . $value2['steamID32'] . '/"><span class="glyphicon glyphicon-new-window"></span> DB</a>'
                        : '';

                    $externalLinks = implode('  ',$steamlinks);


                    $formattedTable .= '<div class="row">
                        <a class="searchRow nav-clickable" href="#s2__user?id=' . $value2['steamID32'] . '">
                                <div class="col-md-1"><span class="glyphicon glyphicon-eye-open"></span></div>
                                <div class="col-md-3">' . $value2['playerName'] . '</div>
                                <div class="col-md-3">' . $vanityURL . '</div>
                                <div class="col-md-2 text-center">' . $externalLinks . '</div>
                                <div class="col-md-3 text-right">' . relative_time_v3($value2['dateUpdated']) . '</div>
                        </a>
                            </div>';
                    $formattedTable .= '<span class="h5">&nbsp;</span>';
                }
            }
        }

        $json_response['searchResults'] = $formattedTable;
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