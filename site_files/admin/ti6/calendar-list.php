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

    echo "<a href='#admin__ti6__calendar?action=nuke'>NUKE SESSION DATA</a><br /><br />";

    // Get the API client and construct the service object.
    $client = getClient();
    $googleCalendar = new Google_Service_Calendar($client);

    $calendarId = 'qaj486fiijdu3qmpl6141qmjuk@group.calendar.google.com';
    $optParams = array(
        //'maxResults' => 10,
        'orderBy' => 'startTime',
        'singleEvents' => TRUE,
        'timeMin' => date('c'),
    );
    $results = $googleCalendar->events->listEvents($calendarId, $optParams);

    $calendarEventsArray = array();

    echo "<h3>Upcoming events:</h3>";
    if (empty(count($results->getItems()))) {
        echo "<strong>No upcoming events found.</strong><br />";
    } else {
        echo "<table style='padding: 5px; border: 3px black solid;'>";
        echo "<tr style='border: 2px black solid;'>
                    <th><center>ID</center></th>
                    <th><center>Time</center></th>
                    <th><center>Event</center></th>
                    <th><center>Description</center></th>
                </tr>";

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

            echo "<tr>
                        <td style='padding: 3px; border: 1px black solid;'>{$eventID}</td>
                        <td style='padding: 3px; border: 1px black solid;'>{$eventStart}</td>
                        <td style='padding: 3px; border: 1px black solid;'>{$eventName}</td>
                        <td style='padding: 3px; border: 1px black solid;'>{$eventDesc}</td>
                    </tr>";
        }

        echo "</table>";
    }

    echo '<span class="h4">&nbsp;</span>';

    echo "<pre>";
    print_r($calendarEventsArray);
    echo "</pre>";

    echo '<span class="h4">&nbsp;</span>';
} catch (Exception $e) {
    echo formatExceptionHandling($e);
} finally {
    if (isset($memcached)) $memcached->close();
}