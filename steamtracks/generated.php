<?php
require_once('./functions.php');
require_once('../connections/parameters.php');
try {
    if (!isset($_SESSION)) {
        session_start();
    }

    $db = new dbWrapper($hostname_steamtracks, $username_steamtracks, $password_steamtracks, $database_steamtracks, false);
    $steamtracks = new steamtracks($steamtracks_api_key, $steamtracks_api_secret, false);

    $token = $_GET['token'];
    $steam_id = $_GET['steamid32'];

    if (!empty($token)) {
        if (!empty($steam_id) && is_numeric($_GET['steamid32'])) {
            $accept_request = $steamtracks->signup_ack($token, $steam_id);

            if ($accept_request['result']['status'] == 'OK') {
                $steam_id = !empty($accept_request['result']['userinfo']['steamid32'])
                    ? $accept_request['result']['userinfo']['steamid32']
                    : 0;
                $steam_name = !empty($accept_request['result']['userinfo']['playerName'])
                    ? $accept_request['result']['userinfo']['playerName']
                    : 0;
                $private_profile = !empty($accept_request['result']['userinfo']['privateProfile'])
                    ? $accept_request['result']['userinfo']['privateProfile']
                    : 0;

                $dota_level = !empty($accept_request['result']['userinfo']['dota2']['level'])
                    ? $accept_request['result']['userinfo']['dota2']['level']
                    : 0;
                $dota_wins = !empty($accept_request['result']['userinfo']['dota2']['wins'])
                    ? $accept_request['result']['userinfo']['dota2']['wins']
                    : 0;

                //$rank_solo_gamesleft = $accept_request['result']['userinfo']['dota2']['soloCalibrationGamesRemaining'];
                $rank_solo = !empty($accept_request['result']['userinfo']['dota2']['soloCompetitiveRank'])
                    ? $accept_request['result']['userinfo']['dota2']['soloCompetitiveRank']
                    : 0;
                $rank_solo_calib = !empty($accept_request['result']['userinfo']['dota2']['soloCalibrationGamesRemaining'])
                    ? $accept_request['result']['userinfo']['dota2']['soloCalibrationGamesRemaining']
                    : 0;
                //$rank_team_gamesleft = $accept_request['result']['userinfo']['dota2']['calibrationGamesRemaining'];
                $rank_team = !empty($accept_request['result']['userinfo']['dota2']['competitiveRank'])
                    ? $accept_request['result']['userinfo']['dota2']['competitiveRank']
                    : 0;
                $rank_team_calib = !empty($accept_request['result']['userinfo']['dota2']['calibrationGamesRemaining'])
                    ? $accept_request['result']['userinfo']['dota2']['calibrationGamesRemaining']
                    : 0;

                $commends_forgiving = !empty($accept_request['result']['userinfo']['dota2']['forgiving'])
                    ? $accept_request['result']['userinfo']['dota2']['forgiving']
                    : 0;
                $commends_friendly = !empty($accept_request['result']['userinfo']['dota2']['friendly'])
                    ? $accept_request['result']['userinfo']['dota2']['friendly']
                    : 0;
                $commends_leadership = !empty($accept_request['result']['userinfo']['dota2']['leadership'])
                    ? $accept_request['result']['userinfo']['dota2']['leadership']
                    : 0;
                $commends_teaching = !empty($accept_request['result']['userinfo']['dota2']['teaching'])
                    ? $accept_request['result']['userinfo']['dota2']['teaching']
                    : 0;

                if (!empty($steam_id) && !empty($steam_name)) {
                    $insert_sql = $db->q(
                        "INSERT INTO `mmr`(
                            `steam_id`,
                            `steam_name`,
                            `private_profile`,
                            `dota_level`,
                            `dota_wins`,
                            `rank_solo`,
                            `rank_solo_calib`,
                            `rank_team`,
                            `rank_team_calib`,
                            `commends_forgiving`,
                            `commends_friendly`,
                            `commends_leadership`,
                            `commends_teaching`
                        )
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);",
                        'isiiiiiiiiiii',
                        $steam_id, $steam_name, $private_profile, $dota_level, $dota_wins, $rank_solo, $rank_solo_calib, $rank_team, $rank_team_calib, $commends_forgiving, $commends_friendly, $commends_leadership, $commends_teaching
                    );

                    if ($insert_sql) {
                        //echo 'Sucessfully stored your data!';
                        header('../#steamtracks/?status=success');
                    } else {
                        //echo 'Failed to store your data! We will try again later.';
                        header('../#steamtracks/?status=sqlfailure');
                    }
                } else {
                    echo 'Failure parsing steam_id or steam_name from results.<br />';
                }
            } else {
                //echo 'Failure receiving account stats. If you signed up correctly, we will retry grabbing your stats automatically at a later date.<br />';
                header('../#steamtracks/?status=apifailure');
            }
        } else {
            //echo 'Bad steam id.<br />';
            header('../#steamtracks/?status=sidfailure');
        }
    } else {
        //echo 'Missing steam_id or token!!<br />';
        header('../#steamtracks/?status=missingidtoken');
    }
} catch (Exception $e) {
    header('../#steamtracks/?status=apifailure');
    //echo $e->getMessage();
}
?>
