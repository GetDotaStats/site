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

        $value['stage_name'] = !empty($value['stage_name'])
            ? str_replace(
                '#DOTA_TournamentBracket_LB',
                'LB ',
                str_replace(
                    '#DOTA_TournamentBracket_Grand',
                    'Grand  ',
                    str_replace(
                        '#DOTA_TournamentBracket_UB',
                        'UB ',
                        str_replace(
                            '#DOTA_TournamentBracket_LBR',
                            'LB R',
                            $value['stage_name']
                        )
                    )
                )
            )
            : '??';

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