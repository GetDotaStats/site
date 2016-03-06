<?php
try {
    require_once('../../global_functions.php');
    require_once('../../connections/parameters.php');

    if (!isset($_SESSION)) {
        session_start();
    }

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcached = new Cache(NULL, NULL, $localDev);

    checkLogin_v2();
    if (empty($_SESSION['user_id64'])) throw new Exception('Not logged in!');

    $adminCheck = adminCheck($_SESSION['user_id64'], 'admin');
    if (empty($adminCheck)) throw new Exception('Not an admin!');

    //Do handling of steamID
    {
        if (empty($_POST['user_id'])) {
            throw new Exception('Invalid userID!');
        }

        $userID = $_POST['user_id'];

        if (is_numeric($userID)) {
            $steamIDcheck = new SteamID($userID);
            $userIDTreated = $steamIDcheck->getSteamID64();
        } else {
            if (stristr($userID, 'steamcommunity.com/id/')) {
                $userID = str_replace('/', '', cut_str($userID, 'steamcommunity.com/id/'));
            }

            if (stristr($userID, 'steamcommunity.com/profiles/')) {
                $userID = str_replace('/', '', cut_str($userID, 'steamcommunity.com/profiles/'));
            }

            if (is_numeric($userID)) {
                $steamIDcheck = new SteamID($userID);
                $userIDTreated = $steamIDcheck->getSteamID64();
            } else {
                $steamWebAPI = new steam_webapi($api_key2);

                //Do webapi request
                $vanityAPIcheck = $steamWebAPI->ResolveVanityURL($userID);

                if (!empty($vanityAPIcheck)) {
                    if ($vanityAPIcheck['response']['success'] == 1 && !empty($vanityAPIcheck['response']['steamid'])) {
                        $userIDTreated = $vanityAPIcheck['response']['steamid'];
                    }
                }
            }
        }

        if (empty($userIDTreated)) {
            throw new Exception('Invalid userID!');
        }
    }

    $webAPI = new steam_webapi($api_key1);
    $userIDdetails = grabAndUpdateSteamUserDetails($userIDTreated);

    if (!empty($userIDdetails) && isset($userIDdetails[0]['user_name']) && !empty($userIDdetails[0]['user_id64'])) {
        $json_response['result'] = "Success! UserID: {$userIDdetails[0]['user_name']} added to site!";

        $irc_message = new irc_message($webhook_gds_site_normal);

        $message = array(
            array(
                $irc_message->colour_generator('red'),
                '[ADMIN]',
                $irc_message->colour_generator(NULL),
            ),
            array(
                $irc_message->colour_generator('green'),
                '[SITE USER]',
                $irc_message->colour_generator(NULL),
            ),
            array(
                $irc_message->colour_generator('bold'),
                $irc_message->colour_generator('blue'),
                'Added or Updated Cache for:',
                $irc_message->colour_generator(NULL),
                $irc_message->colour_generator('bold'),
            ),
            array($userIDdetails[0]['user_name']),
            array(
                '[',
                $irc_message->colour_generator('orange'),
                $userIDdetails[0]['user_id64'],
                $irc_message->colour_generator(NULL),
                ']',
            ),
            array(' || http://getdotastats.com/#s2__user?id=' . $userIDdetails[0]['user_id64']),
        );

        $message = $irc_message->combine_message($message);
        $irc_message->post_message($message, array('localDev' => $localDev));
    } else {
        throw new Exception('Empty user details returned by API!');
    }

} catch (Exception $e) {
    $json_response['error'] = 'Caught Exception: ' . $e->getMessage();
} finally {
    if (isset($memcached)) $memcached->close();
    if (!isset($json_response)) $json_response = array('error' => 'Unknown exception');
}

try {
    echo utf8_encode(json_encode($json_response));
} catch (Exception $e) {
    unset($json_response);
    $json_response['error'] = 'Caught Exception: ' . $e->getMessage();
    echo utf8_encode(json_encode($json_response));
}