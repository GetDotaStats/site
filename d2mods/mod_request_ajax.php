<?php
require_once('./functions.php');
require_once('../global_functions.php');
require_once('../connections/parameters.php');

try {
    if (!isset($_SESSION)) {
        session_start();
    }

    $json_response = array();

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    checkLogin_v2();
    if (empty($_SESSION['user_id64'])) throw new Exception('Not logged in!');

    $steamAPI = new steam_webapi($api_key1);

    if (!empty($_POST['mod_workshop_link'])) {
        if (stristr($_POST['mod_workshop_link'], 'steamcommunity.com/sharedfiles/filedetails/?id=')) {
            $modWork = htmlentities(rtrim(rtrim(cut_str($_POST['mod_workshop_link'], 'steamcommunity.com/sharedfiles/filedetails/?id='), '/'), '&searchtext='));

            $mod_details = $steamAPI->GetPublishedFileDetails($modWork);

            if ($mod_details['response']['result'] == 1) {
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

                if ($_SESSION['user_id64'] == $modOwner) {
                    if ($modApp == 570) {
                        if (!empty($_POST['mod_steam_group']) && stristr($_POST['mod_steam_group'], 'steamcommunity.com/groups/')) {
                            $modGroup = htmlentities(rtrim(cut_str($_POST['mod_steam_group'], 'groups/'), '/'));
                        } else {
                            $modGroup = NULL;
                        }

                        if (!empty($_POST['mod_maps']) && $_POST['mod_maps'] != 'One map per line') {
                            $modMaps = json_encode(array_map('trim', explode("\n", htmlentities($_POST['mod_maps']))));
                        } else {
                            $modMaps = 'No maps given.';
                        }

                        $config = array(
                            "digest_alg" => "sha512",
                            "private_key_bits" => 1024,
                            "private_key_type" => OPENSSL_KEYTYPE_RSA,
                        );
                        $res = openssl_pkey_new($config); // Create the private and public key
                        openssl_pkey_export($res, $privKey); // Extract the private key from $res to $privKey
                        $pubKey = openssl_pkey_get_details($res); // Extract the public key from $res to $pubKey
                        $pubKey = $pubKey["key"];


                        $insertSQL = $db->q(
                            'INSERT INTO `mod_list` (`steam_id64`, `mod_identifier`, `mod_name`, `mod_description`, `mod_workshop_link`, `mod_steam_group`, `mod_public_key`, `mod_private_key`, `mod_maps`)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?);',
                            'sssssssss', //STUPID x64 windows PHP is actually x86
                            $modOwner, md5($modName . time()), $modName, $modDesc, $modWork, $modGroup, $pubKey, $privKey, $modMaps
                        );

                        if ($insertSQL) {
                            $modID = $db->last_index();
                            $json_response['result'] = 'Success! Found mod and added to DB for approval as #' . $modID;

                            $irc_message = new irc_message($webhook_gds_site_live);

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
                    } else {
                        $json_response['error'] = 'Mod is not for Dota2.';
                    }
                } else {
                    $json_response['error'] = 'Insufficient privilege to add this mod. Login as the mod developer.';
                }
            } else {
                $json_response['error'] = 'Bad steam response. API probably down.';
            }
        } else {
            $json_response['error'] = 'Bad workshop link';
        }
    } else {
        $json_response['error'] = 'No workshop link given';
    }
} catch (Exception $e) {
    $json_response['error'] = 'Caught Exception: ' . $e->getMessage() . ' || Contact getdotastats.com';
}

try {
    echo utf8_encode(json_encode($json_response));
} catch (Exception $e) {
    unset($json_response);
    $json_response['error'] = 'Caught Exception: ' . $e->getMessage() . ' || Contact getdotastats.com';
    echo utf8_encode(json_encode($json_response));
}