<?php
require_once('../../global_functions.php');
require_once('../../connections/parameters.php');

try {
    if (!isset($_SESSION)) {
        session_start();
    }

    $s2_response = array();

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcached = new Cache(NULL, NULL, $localDev);

    checkLogin_v2();
    if (empty($_SESSION['user_id64'])) throw new Exception('Not logged in!');

    //Check if logged in user is an admin
    $adminCheck = adminCheck($_SESSION['user_id64'], 'admin');

    $steamIDmanipulator = new SteamID($_SESSION['user_id64']);
    $steamID32 = $steamIDmanipulator->getsteamID32();
    $steamID64 = $steamIDmanipulator->getsteamID64();

    if (
        empty($_POST['mod_workshop_link']) ||
        empty($_POST['mod_contact_address'])
    ) {
        throw new Exception('Missing or invalid required parameter(s)!');
    }

    if (!filter_var($_POST['mod_contact_address'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address!');
    } else {
        $db->q(
            'INSERT INTO `gds_users_options`
                    (
                        `user_id32`,
                        `user_id64`,
                        `user_email`,
                        `sub_dev_news`,
                        `date_updated`,
                        `date_recorded`
                    )
                VALUES (
                    ?,
                    ?,
                    ?,
                    1,
                    NULL,
                    NULL
                )
                ON DUPLICATE KEY UPDATE
                    `user_email` = VALUES(`user_email`),
                    `sub_dev_news` = VALUES(`sub_dev_news`);',
            'sss',
            array(
                $steamID32,
                $steamID64,
                $_POST['mod_contact_address']
            )
        );
    }

    $steamAPI = new steam_webapi($api_key1);

    if (!stristr($_POST['mod_workshop_link'], 'steamcommunity.com/sharedfiles/filedetails/?id=')) {
        throw new Exception('Bad workshop link');
    }

    $modWork = htmlentities(rtrim(rtrim(cut_str($_POST['mod_workshop_link'], 'steamcommunity.com/sharedfiles/filedetails/?id='), '/'), '&searchtext='));

    $mod_details = $steamAPI->GetPublishedFileDetails($modWork);

    if ($mod_details['response']['result'] != 1) {
        throw new Exception('Bad steam response. API probably down.');
    }

    $modName = !empty($mod_details['response']['publishedfiledetails'][0]['title'])
        ? htmlentities($mod_details['response']['publishedfiledetails'][0]['title'])
        : 'UNKNOWN MOD NAME';

    $modDesc = !empty($mod_details['response']['publishedfiledetails'][0]['description'])
        ? htmlentities($mod_details['response']['publishedfiledetails'][0]['description'])
        : 'UNKNOWN MOD DESCRIPTION';

    $modOwner = !empty($mod_details['response']['publishedfiledetails'][0]['creator'])
        ? htmlentities($mod_details['response']['publishedfiledetails'][0]['creator'])
        : '-1';

    $modApp = !empty($mod_details['response']['publishedfiledetails'][0]['consumer_app_id'])
        ? htmlentities($mod_details['response']['publishedfiledetails'][0]['consumer_app_id'])
        : '-1';

    if ($_SESSION['user_id64'] != $modOwner && !$adminCheck) {
        throw new Exception('Insufficient privilege to add this mod. Login as the mod developer or contact admins via <strong><a class="boldGreenText" href="https://github.com/GetDotaStats/stat-collection/issues" target="_blank">issue tracker</a></strong>!');
    }

    if ($modApp != 570) {
        throw new Exception('Mod is not for Dota2.');
    }

    if (!empty($_POST['mod_steam_group']) && stristr($_POST['mod_steam_group'], 'steamcommunity.com/groups/')) {
        $modGroup = htmlentities(rtrim(cut_str($_POST['mod_steam_group'], 'groups/'), '/'));
    } else {
        $modGroup = NULL;
    }

    $insertSQL = $db->q(
        'INSERT INTO `mod_list` (`steam_id64`, `mod_identifier`, `mod_name`, `mod_description`, `mod_workshop_link`, `mod_steam_group`)
            VALUES (?, ?, ?, ?, ?, ?);',
        'ssssss', //STUPID x64 windows PHP is actually x86
        array(
            $modOwner,
            md5($modName . time()),
            $modName,
            $modDesc,
            $modWork,
            $modGroup,
        )
    );

    if ($insertSQL) {
        $modID = $db->last_index();
        $json_response['result'] = 'Success! Found mod and added to DB for approval as #' . $modID;

        $db->q(
            'INSERT INTO `mod_list_owners` (`mod_id`, `steam_id64`)
            VALUES (?, ?);',
            'is', //STUPID x64 windows PHP is actually x86
            array(
                $modID,
                $modOwner,
            )
        );
		
		updateUserDetails($modOwner, $api_key2);

        $irc_message = new irc_message($webhook_gds_site_normal);

        $message = array(
            array(
                $irc_message->colour_generator('red'),
                '[ADMIN]',
                $irc_message->colour_generator(NULL),
            ),
            array(
                $irc_message->colour_generator('green'),
                '[MOD]',
                $irc_message->colour_generator(NULL),
            ),
            array(
                $irc_message->colour_generator('bold'),
                $irc_message->colour_generator('blue'),
                'Pending approval:',
                $irc_message->colour_generator(NULL),
                $irc_message->colour_generator('bold'),
            ),
            array(
                $irc_message->colour_generator('orange'),
                '{' . $modID . '}',
                $irc_message->colour_generator(NULL),
            ),
            array($modName),
            array(' || http://getdotastats.com/#admin__mod_approve'),
        );

        $message = $irc_message->combine_message($message);
        $irc_message->post_message($message, array('localDev' => $localDev));
    } else {
        $json_response['error'] = 'Mod not added to database. Failed to add mod for approval.';
    }
} catch (Exception $e) {
    $json_response['error'] = 'Caught Exception: ' . $e->getMessage();
} finally {
    if (isset($memcached)) $memcached->close();
    if (!isset($json_response)) $json_response = array('error' => 'Unknown exception');
}

try {
    header('Content-Type: application/json');
    echo utf8_encode(json_encode($json_response));
} catch (Exception $e) {
    unset($json_response);
    $json_response['error'] = 'Caught Exception: ' . $e->getMessage();
    echo utf8_encode(json_encode($json_response));
}