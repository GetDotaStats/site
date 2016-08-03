<?php
try {
    require_once('../../connections/parameters.php');
    require_once('../../global_functions.php');
    //require_once('../../global_variables.php');

    /*if (!isset($_SESSION)) {
        session_start();
    }*/

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB `gds_site`!');

    $memcached = new Cache(NULL, NULL, $localDev);

    /*{//do auth stuff
        checkLogin_v2();
        if (empty($_SESSION['user_id64'])) throw new Exception('Not logged in!');

        $adminCheck = adminCheck($_SESSION['user_id64'], 'admin');
        if (empty($adminCheck)) throw new Exception('Do not have `admin` privileges!');
    }*/

    //Phases by phase_id=2
    $apiEndpoint = 'http://www.dota2.com/webapi/ITournaments/GetTournamentBrackets/v001?league_id=4664';

    $TI6matchTimes = $memcached->get('ti6_match_times');
    if (!$TI6matchTimes) {
        $curlObject = new curl_improved($behindProxy, $apiEndpoint);
        $curlObject->setProxyDetails($proxyDetails['address'], $proxyDetails['port'], $proxyDetails['type'], $proxyDetails['user'], $proxyDetails['pass'], false);
        $TI6matchTimes = $curlObject->getPage();

        $TI6matchTimes = json_decode($TI6matchTimes, true, NULL);

        if (empty($TI6matchTimes)) throw new Exception("Couldn't get TI6 match times!");

        $memcached->set('ti6_match_times', $TI6matchTimes, 1 * 60);
        $memcached->set('ti6_match_times_call_time', time(), 1 * 60);
    }
    $TI6matchTimescallTime = $memcached->get('ti6_match_times_call_time');

    $TI6matchTimesArray = array();

    date_default_timezone_set('America/Los_Angeles');

    $lastTime = '';
    foreach ($TI6matchTimes['matches'] as $key => $value) {
        $value['team1_name'] = !empty($value['team1_name'])
            ? $value['team1_name']
            : "TBD";

        $value['team2_name'] = !empty($value['team2_name'])
            ? $value['team2_name']
            : "TBD";

        switch ($value['stage_name']) {
            case '#DOTA_TournamentBracket_GrandFinals':
                $value['stage_name'] = str_replace(
                    '#DOTA_TournamentBracket_GrandFinals',
                    'Grand Finals',
                    $value['stage_name']);
                break;
            case '#DOTA_TournamentBracket_LBFinals':
                $value['stage_name'] = str_replace(
                    '#DOTA_TournamentBracket_LBFinals',
                    'Lower Bracket Finals',
                    $value['stage_name']);
                break;
            case '#DOTA_TournamentBracket_UBFinals':
                $value['stage_name'] = str_replace(
                    '#DOTA_TournamentBracket_UBFinals',
                    'Upper Bracket Finals',
                    $value['stage_name']);
                break;
            case '#DOTA_TournamentBracket_UBSemiFinals':
                $value['stage_name'] = str_replace(
                    '#DOTA_TournamentBracket_UBSemiFinals',
                    'Upper Bracket Semi Finals',
                    $value['stage_name']);
                break;
            case '#DOTA_TournamentBracket_UBQuarterFinals':
                $value['stage_name'] = str_replace(
                    '#DOTA_TournamentBracket_UBQuarterFinals',
                    'Upper Bracket Quarter Finals',
                    $value['stage_name']);
                break;
            case '#DOTA_TournamentBracket_LBR1':
                $value['stage_name'] = str_replace(
                    '#DOTA_TournamentBracket_LBR1',
                    'Lower Bracket Round 1',
                    $value['stage_name']);
                break;
            case '#DOTA_TournamentBracket_LBR2':
                $value['stage_name'] = str_replace(
                    '#DOTA_TournamentBracket_LBR2',
                    'Lower Bracket Round 2',
                    $value['stage_name']);
                break;
            case '#DOTA_TournamentBracket_LBR3':
                $value['stage_name'] = str_replace(
                    '#DOTA_TournamentBracket_LBR3',
                    'Lower Bracket Round 3',
                    $value['stage_name']);
                break;
            case '#DOTA_TournamentBracket_LBR4':
                $value['stage_name'] = str_replace(
                    '#DOTA_TournamentBracket_LBR4',
                    'Lower Bracket Round 4',
                    $value['stage_name']);
                break;
            case '#DOTA_TournamentBracket_LBR5':
                $value['stage_name'] = str_replace(
                    '#DOTA_TournamentBracket_LBR5',
                    'Lower Bracket Round 5',
                    $value['stage_name']);
                break;
            case '#DOTA_TournamentBracket_Group1':
                $value['stage_name'] = str_replace(
                    '#DOTA_TournamentBracket_Group1',
                    'Group A',
                    $value['stage_name']);
                break;
            case '#DOTA_TournamentBracket_Group2':
                $value['stage_name'] = str_replace(
                    '#DOTA_TournamentBracket_Group2',
                    'Group B',
                    $value['stage_name']);
                break;
            case '#DOTA_TournamentBracket_LosersMatchNew':
                $value['stage_name'] = str_replace(
                    '#DOTA_TournamentBracket_LosersMatchNew',
                    'WC Losers Match',
                    $value['stage_name']);
                break;
            case '#DOTA_TournamentBracket_Qualification1':
                $value['stage_name'] = str_replace(
                    '#DOTA_TournamentBracket_Qualification1',
                    'WC Qualification 1',
                    $value['stage_name']);
                break;
            case '#DOTA_TournamentBracket_Qualification2':
                $value['stage_name'] = str_replace(
                    '#DOTA_TournamentBracket_Qualification2',
                    'WC Qualification 2',
                    $value['stage_name']);
                break;
            case '#DOTA_TournamentBracket_GSL1':
                $value['stage_name'] = str_replace(
                    '#DOTA_TournamentBracket_GSL1',
                    'GSL Match 1',
                    $value['stage_name']);
                break;
            case '#DOTA_TournamentBracket_GSL2':
                $value['stage_name'] = str_replace(
                    '#DOTA_TournamentBracket_GSL2',
                    'GSL Match 2',
                    $value['stage_name']);
                break;
            default:
                $value['stage_name'] = 'Unknown Stage';
                break;
        }

        if (!empty($value['start_time'])) {
            if (date("jS \-\- H:i", $value['start_time']) != $lastTime) echo "<hr />";
            $lastTime = date("jS \-\- H:i", $value['start_time']);
            echo $lastTime . ' -- ';
        }
        echo " {$value['team1_name']} vs. {$value['team2_name']} [{$value['stage_name']}] -- Match: {$value['id']}";
        echo "<br />";
    }

    echo "<hr />";


    //echo '<pre>';
    //print_r($TI6matchTimes);
    //echo '</pre>';


} catch (Exception $e) {
    echo formatExceptionHandling($e);
} finally {
    if (isset($memcached)) $memcached->close();
}