#!/usr/bin/php -q
<?php
require_once('../../../global_functions.php');
require_once('../../../connections/parameters.php');
require_once('../../../steamtracks/functions.php');

try {
    $db = new dbWrapper($hostname_sig, $username_sig, $password_sig, $database_sig, true);
    $steamtracks = new steamtracks($steamtracks_api_key, $steamtracks_api_secret, false);

    $page = 1;
    $players = array();

    $users_response = $steamtracks->users($page);

    $num_successes = 0;
    $num_failures1 = 0;
    $num_failures2 = 0;

    if (!empty($users_response['result'])) {
        //echo '<pre>';
        //print_r($users_response['result']);
        //echo '</pre>';

        foreach ($users_response['result']['users'] as $key => $value) {
            $player_id = $value['steamid32'];
            $players[$player_id]['playerName'] = $value['playerName'];

            $players[$player_id]['privateProfile'] = $value['dota2']['privateProfile'];
            $players[$player_id]['level'] = $value['dota2']['level'];
            //$players[$player_id]['recruitmentLevel'] = $value['dota2']['recruitmentLevel'];
            $players[$player_id]['wins'] = $value['dota2']['wins'];

            $players[$player_id]['calibrationGamesRemaining'] = $value['dota2']['calibrationGamesRemaining'];
            $players[$player_id]['competitiveRank'] = $value['dota2']['competitiveRank'];
            $players[$player_id]['soloCalibrationGamesRemaining'] = $value['dota2']['soloCalibrationGamesRemaining'];
            $players[$player_id]['soloCompetitiveRank'] = $value['dota2']['soloCompetitiveRank'];

            $players[$player_id]['friendly'] = $value['dota2']['friendly'];
            $players[$player_id]['leadership'] = $value['dota2']['leadership'];
            $players[$player_id]['forgiving'] = $value['dota2']['forgiving'];
            $players[$player_id]['teaching'] = $value['dota2']['teaching'];
        }

        if ($users_response['result']['num_pages'] > $page) {
            for ($page = 2; $page <= $users_response['result']['num_pages']; $page++) {
                $users_response = $steamtracks->users($page);

                if (!empty($users_response['result'])) {
                    foreach ($users_response['result']['users'] as $key => $value) {
                        $player_id = $value['steamid32'];
                        $players[$player_id]['playerName'] = $value['playerName'];

                        $players[$player_id]['privateProfile'] = $value['dota2']['privateProfile'];
                        $players[$player_id]['level'] = $value['dota2']['level'];
                        //$players[$player_id]['recruitmentLevel'] = $value['dota2']['recruitmentLevel'];
                        $players[$player_id]['wins'] = $value['dota2']['wins'];

                        $players[$player_id]['calibrationGamesRemaining'] = $value['dota2']['calibrationGamesRemaining'];
                        $players[$player_id]['competitiveRank'] = $value['dota2']['competitiveRank'];
                        $players[$player_id]['soloCalibrationGamesRemaining'] = $value['dota2']['soloCalibrationGamesRemaining'];
                        $players[$player_id]['soloCompetitiveRank'] = $value['dota2']['soloCompetitiveRank'];

                        $players[$player_id]['friendly'] = $value['dota2']['friendly'];
                        $players[$player_id]['leadership'] = $value['dota2']['leadership'];
                        $players[$player_id]['forgiving'] = $value['dota2']['forgiving'];
                        $players[$player_id]['teaching'] = $value['dota2']['teaching'];
                    }
                } else {
                    echo 'Empty data set!!!<br />';
                }
            }
        } else {
            echo 'No more pages!!!<br />';
        }

        ksort($players);

        /*
        echo '<pre>';
        print_r($players);
        echo '</pre>';
        */

        $players_total = array();
        foreach ($players as $key => $value) {
            $player_id = $key;

            $playerName = !empty($value['playerName'])
                ? $value['playerName']
                : 0;

            $privateProfile = !empty($value['privateProfile'])
                ? $value['privateProfile']
                : 0;
            $level = !empty($value['level'])
                ? $value['level']
                : 0;
            $wins = !empty($value['wins'])
                ? $value['wins']
                : 0;

            $calibrationGamesRemaining = !empty($value['calibrationGamesRemaining'])
                ? $value['calibrationGamesRemaining']
                : 0;
            $competitiveRank = !empty($value['competitiveRank'])
                ? $value['competitiveRank']
                : 0;
            $soloCalibrationGamesRemaining = !empty($value['soloCalibrationGamesRemaining'])
                ? $value['soloCalibrationGamesRemaining']
                : 0;
            $soloCompetitiveRank = !empty($value['soloCompetitiveRank'])
                ? $value['soloCompetitiveRank']
                : 0;

            $friendly = !empty($value['friendly'])
                ? $value['friendly']
                : 0;
            $leadership = !empty($value['leadership'])
                ? $value['leadership']
                : 0;
            $forgiving = !empty($value['forgiving'])
                ? $value['forgiving']
                : 0;
            $teaching = !empty($value['teaching'])
                ? $value['teaching']
                : 0;

            if ((!empty($calibrationGamesRemaining) || !empty($soloCalibrationGamesRemaining)) || (!empty($competitiveRank) || !empty($soloCompetitiveRank))) {
                $test = $db->q(
                    'INSERT INTO `mmr` (`steam_id`, `steam_name`, `private_profile`, `dota_level`, `dota_wins`, `rank_solo`, `rank_solo_calib`, `rank_team`, `rank_team_calib`, `commends_forgiving`, `commends_friendly`, `commends_leadership`, `commends_teaching`, `last_updated`)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE `steam_id` = VALUES(`steam_id`), `steam_name` = VALUES(`steam_name`), `private_profile` = VALUES(`private_profile`), `dota_level` = VALUES(`dota_level`), `dota_wins` = VALUES(`dota_wins`), `rank_solo` = VALUES(`rank_solo`), `rank_solo_calib` = VALUES(`rank_solo_calib`), `rank_team` = VALUES(`rank_team`), `rank_team_calib` = VALUES(`rank_team_calib`), `commends_forgiving` = VALUES(`commends_forgiving`), `commends_friendly` = VALUES(`commends_friendly`), `commends_leadership` = VALUES(`commends_leadership`), `commends_teaching` = VALUES(`commends_teaching`), `last_updated` = VALUES(`last_updated`);',
                    'isiiiiiiiiiiii',
                    $player_id, $playerName, $privateProfile, $level, $wins, $soloCompetitiveRank, $soloCalibrationGamesRemaining, $competitiveRank, $calibrationGamesRemaining, $forgiving, $friendly, $leadership, $teaching, time()
                );

                if ($test) {
                    $num_successes++;
                    //echo '<strong>Success</strong> for '.$player_id.' | '.$playerName.'<br />';
                } else {
                    $num_failures1++;
                    //echo 'Failure for '.$player_id.' | '.$playerName.'<br />';
                }
                unset($test);
            } else {
                $num_failures2++;
                //echo 'Not enough detail!'.$player_id.', \''.$playerName.'\', '.$privateProfile.', '.$level.', '.$wins.', '.$calibrationGamesRemaining.', '.$competitiveRank.', '.$soloCalibrationGamesRemaining.', '.$soloCompetitiveRank.', '.$friendly.', '.$leadership.', '.$forgiving.', '.$teaching.'<br />';

                $private_profile = '';
                if (empty($privateProfile)) {
                    $private_profile = ' [Public] ';
                } else {
                    $private_profile = ' <strong>[Private]</strong> ';
                }

                $commends = '';
                $level = '';
                $wins = '';

                if (empty($privateProfile)) {

//commends
                    if (!empty($friendly) || !empty($leadership) || !empty($forgiving) || !empty($teaching)) {
                        $commends = ' [Commends] ';
                    } else {
                        $commends = ' <strong>[No Commends]</strong> ';
                    }

//level
                    if (!empty($level)) {
                        $level = ' [Level] ';
                    } else {
                        $level = ' <strong>[No Level]</strong> ';
                    }

//wins
                    if (!empty($wins)) {
                        $wins = ' [Wins] ';
                    } else {
                        $wins = ' <strong>[No Wins]</strong> ';
                    }
                }
                echo 'Lacking match data for ' . $player_id . $private_profile . $commends . $level . $wins . ' | ' . $playerName . '<br />';
            }

            $players_total[] = $player_id . ', ' . $playerName . ' [' . $soloCompetitiveRank . ' , ' . $soloCalibrationGamesRemaining . '] [' . $competitiveRank . ' , ' . $calibrationGamesRemaining . ']<br />';
        }

        echo '<br /><strong>Total Successes</strong>: ' . $num_successes . '<br />';
        echo '<strong>Total Failures (SQL Failure)</strong>: ' . $num_failures1 . '<br />';
        echo '<strong>Total Failures (No Rank or Calibs)</strong>: ' . $num_failures2 . '<br />';

        echo '<br /><strong>Total Players (from SteamTracks report)</strong>: ' . count($players) . '<br />';
        echo '<pre>';
        print_r($players_total);
        echo '</pre>';
    } else {
        var_dump($users_response);
    }
} catch (Exception $e) {
    echo $e->getMessage();
}

?>