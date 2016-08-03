<?php
try {
    require_once('../../connections/parameters.php');
    require_once('../../global_functions.php');
    //require_once('../../global_variables.php');
    require_once './google-api-php-client-1.1.7/src/Google/autoload.php';

    if (!isset($_SESSION)) {
        session_start();
    }

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB `gds_site`!');

    $memcached = new Cache(NULL, NULL, $localDev);

    {//do auth stuff
        checkLogin_v2();
        if (empty($_SESSION['user_id64'])) throw new Exception('Not logged in!');

        $adminCheck = adminCheck($_SESSION['user_id64'], 'admin');
        if (empty($adminCheck)) throw new Exception('Do not have `admin` privileges!');
    }

    define('APPLICATION_NAME', 'GDS TI6 Calendar Helper');
    define('CLIENT_SECRET_PATH', '../../../www-secrets/google_ti6_calendar_client_secret.json');
    define('SCOPES', implode(' ', array(
            Google_Service_Calendar::CALENDAR,
        )
    ));

    /**
     * Returns an authorized API client.
     * @return Google_Client the authorized client object
     */
    function getClient()
    {
        $client = new Google_Client();

        $client->setApplicationName(APPLICATION_NAME);
        $client->setScopes(SCOPES);
        $client->setAuthConfigFile(CLIENT_SECRET_PATH);
        $client->setAccessType('offline');

        if (!empty($_GET['action']) && $_GET['action'] == 'nuke') {
            // Nuke previous auth if asked to
            if (!empty($_SESSION['google_ti6_calendar_auth'])) unset($_SESSION['google_ti6_calendar_auth']);
        }

        if (!empty($_SESSION['google_ti6_calendar_auth'])) {
            // Load previously authorized credentials from a file.
            $accessToken = $_SESSION['google_ti6_calendar_auth'];
        } else {
            // Request authorization from the user.
            $authUrl = $client->createAuthUrl();

            echo "Open the following link in your browser to authorise app: <a href='{$authUrl}'>here</a><br />";

            $authCode = !empty($_GET['code'])
                ? $_GET['code']
                : NULL;

            if (empty($authCode)) throw new Exception("No auth code entered yet!");

            // Exchange authorization code for an access token.
            $accessToken = $client->authenticate($authCode);

            $_SESSION['google_ti6_calendar_auth'] = $accessToken;
        }
        $client->setAccessToken($accessToken);

        // Refresh the token if it's expired.
        if ($client->isAccessTokenExpired()) {
            $client->refreshToken($client->getRefreshToken());
            $_SESSION['google_ti6_calendar_auth'] = $client->getAccessToken();
        }
        return $client;
    }

    echo "<h2>Calendar Management</h2>";

    echo "<p>If your token expires, or you get stuck for any reason, press the big red nuke button to start again.</p>";

    echo "<a class='btn btn-danger' href='#admin__ti6__calendar?action=nuke'>NUKE SESSION DATA</a>";

    echo '<span class="h4">&nbsp;</span>';
    echo "<hr />";

    // Get the API client and construct the service object.
    $client = getClient();
    $googleCalendar = new Google_Service_Calendar($client);

    $calendarId = 'qaj486fiijdu3qmpl6141qmjuk@group.calendar.google.com';
    $optParams = array(
        //'maxResults' => 10,
        'orderBy' => 'startTime',
        'singleEvents' => TRUE,
        'timeMin' => date('c', mktime(0, 0, 0, 8, 1, 2016)),
        //'timeMin' => date('c'),
    );
    $results = $googleCalendar->events->listEvents($calendarId, $optParams);

    $calendarEventsArray = array();

    echo "<h3>Google Calendar:</h3>";
    if (empty(count($results->getItems()))) {
        echo "<strong>No upcoming events found.</strong><br />";
    } else {
        foreach ($results->getItems() as $event) {
            $eventStart = $event->start->dateTime;
            if (empty($eventStart)) {
                $eventStart = $event->start->date;
            }

            $eventID = $event->getId();
            $eventName = $event->getSummary();
            $eventDesc = $event->getDescription();

            if (stristr($eventDesc, 'Match: ')) {
                $matchID = str_replace('Match: ', '', $eventDesc);

                $calendarEventsArray[$matchID]['eventID'] = $eventID;
                $calendarEventsArray[$matchID]['eventStart'] = $eventStart;
                $calendarEventsArray[$matchID]['eventName'] = $eventName;
                $calendarEventsArray[$matchID]['eventDesc'] = $eventDesc;
            }
        }
    }

    ksort($calendarEventsArray);
    /*echo "<pre>";
    print_r($calendarEventsArray);
    echo "</pre>";*/

    echo '<span class="h4">&nbsp;</span>';
    echo "<hr />";

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    echo "<h3>Dota 2 API</h3>";

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

    $apiEventsArray = array();

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
            //if (date("jS \a\\t H:i", $value['start_time']) != $lastTime) echo "<hr />";
            $lastTime = date("jS \a\\t H:i", $value['start_time']);
            //echo $lastTime . ' -- ';
        }
        //echo " {$value['team1_name']} vs. {$value['team2_name']} [{$value['stage_name']}] -- Match: {$value['id']}";
        //echo "<br />";

        $apiEventsArray[$value['id']]['eventStart'] = $lastTime;
        $apiEventsArray[$value['id']]['eventName'] = "{$value['team1_name']} vs. {$value['team2_name']} [{$value['stage_name']}]";
        $apiEventsArray[$value['id']]['eventDesc'] = "Match: {$value['id']}";
    }

    ksort($apiEventsArray);
    /*echo "<pre>";
    print_r($apiEventsArray);
    echo "</pre>";*/

    echo '<span class="h4">&nbsp;</span>';
    echo "<hr />";

    //Testing for Diffs
    //$apiEventsArray[1003]['eventName'] = 'NaVi vs. Sekret [Qualification #1]';
    //$apiEventsArray[2050]['eventName'] = 'MVP Phoenix vs. Team DK [Group B]';
    //$apiEventsArray[2056]['eventName'] = 'Vici_Gaming Reborn vs. Void Guys [Group B]';


    //////////////////////////////////////////////////////////

    echo "<h3>Calendar vs API</h3>";

    //$calendarEventsArray
    //$apiEventsArray

    $eventArrayDifference = array();

    if ($apiEventsArray) {
        foreach ($apiEventsArray as $key => $value) {
            if (!empty($calendarEventsArray[$key])) {
                if ($value['eventName'] != $calendarEventsArray[$key]['eventName']) {
                    $eventArrayDifference[$key]['eventID'] = $calendarEventsArray[$key]['eventID'];
                    $eventArrayDifference[$key]['eventStart'] = $calendarEventsArray[$key]['eventStart'];
                    $eventArrayDifference[$key]['eventNameNew'] = $value['eventName'];
                    $eventArrayDifference[$key]['eventNameOld'] = $calendarEventsArray[$key]['eventName'];
                    $eventArrayDifference[$key]['eventDesc'] = $calendarEventsArray[$key]['eventDesc'];
                }
            }
        }

        if (!empty($eventArrayDifference[$key])) {
            ksort($eventArrayDifference);
            echo "<pre>";
            print_r($eventArrayDifference);
            echo "</pre>";
        } else {
            echo "No differences between calendar and API!";
        }
    } else {
        echo "Nothing in the API to compare against the calendar!";
    }

    echo '<span class="h4">&nbsp;</span>';
    echo "<hr />";

    //////////////////////////////////////////////////////////

    echo "<h3>Make Calendar Changes</h3>";

    if (!empty($eventArrayDifference)) {
        foreach ($eventArrayDifference as $key => $value) {
            $calendarEvent = $googleCalendar->events->get($calendarId, $value['eventID']);

            $calendarEvent->setSummary($value['eventNameNew']);
            //$calendarEvent->setDescription($value['eventDesc']);
            //$calendarEvent->setStart($value['eventStart']);

            $updateCalendar = $googleCalendar->events->update($calendarId, $value['eventID'], $calendarEvent);

            echo "Probably fixed up event {$value['eventID']} for match {$key}.<br />";
            echo "{$value['eventNameOld']} <strong>to</strong> {$value['eventNameNew']}<br /><br />";
        }
    } else {
        echo "No calendar events to update!";
    }

    echo '<span class="h4">&nbsp;</span>';
    echo "<hr />";

} catch (Exception $e) {
    echo formatExceptionHandling($e);
} finally {
    if (isset($memcached)) $memcached->close();
}