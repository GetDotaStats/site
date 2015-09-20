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

    $searchTerm = htmlentities($_POST['search_term']);

    $resultsArray = array();

    //match check
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
                WHERE s2mpn.`playerName` LIKE ? OR s2mpn.`playerVanity` LIKE ?
                LIMIT 0,25;",
            'ss',
            array($searchTerm . '%', $searchTerm . '%'),
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


    //steam profile url check
    //userID check


    if (empty($resultsArray)) {
        throw new Exception('No results found for searchTerm.');
    //} else if (count($resultsArray) == 1) {
        //return a URL if just one result
    //    throw new Exception('Only one result found! Should be returning a URL!');
    } else {
        //return a searchResults array if multiple results
        $json_response['searchResults'] = $resultsArray;
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