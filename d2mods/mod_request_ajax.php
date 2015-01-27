<?php
require_once('./functions.php');
require_once('../global_functions.php');
require_once('../connections/parameters.php');

try {
    if (!isset($_SESSION)) {
        session_start();
    }

    checkLogin_v2();

    $json_response = array();

    if (!empty($_SESSION['user_id64'])) {
        $db = new dbWrapper_v2($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site);
        $db->q('SET NAMES utf8;');
        if ($db) {
            $steamAPI = new steam_webapi($api_key1);

            if (!empty($_POST['mod_workshop_link'])) {
                if (stristr($_POST['mod_workshop_link'], 'steamcommunity.com/sharedfiles/filedetails/?id=')) {
                    $modWork = htmlentities(rtrim(rtrim(cut_str($_POST['mod_workshop_link'], 'steamcommunity.com/sharedfiles/filedetails/?id='), '/'), '&searchtext='));

                    $mod_details = $steamAPI->GetPublishedFileDetails($modWork);

                    if ($mod_details['response']['result'] == 1) {
                        $modName = $mod_details['response']['publishedfiledetails'][0]['title'];
                        $modDesc = $mod_details['response']['publishedfiledetails'][0]['description'];
                        $modOwner = $mod_details['response']['publishedfiledetails'][0]['creator'];

                        if ($_SESSION['user_id64'] != $modOwner) {
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
                                $json_response['result'] = 'Success! Found mod and added to DB for approval.';
                            } else {
                                $json_response['error'] = 'Mod not added to database. Failed to add mod for approval.';
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
        } else {
            $json_response['error'] = 'No DB';
        }
    } else {
        $json_response['error'] = 'Not logged in';
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