<?php
require_once('./functions.php');
require_once('../global_functions.php');
require_once('../connections/parameters.php');

if (!isset($_SESSION)) {
    session_start();
}

if (isset($_COOKIE['session']) && empty($_SESSION['user_id64'])) {
    checkLogin_v2();
}

try {
    if (!empty($_SESSION['user_id64'])) {
        $db = new dbWrapper_v2($hostname_gds_test, $username_gds_test, $password_gds_test, $database_gds_test, true);
        if ($db) {
            if (!empty($_POST['mod_name'])) {
                $modName = $db->escape($_POST['mod_name']);

                $modDesc = !empty($_POST['mod_description'])
                    ? $db->escape($_POST['mod_description'])
                    : NULL;

                $modWork = !empty($_POST['mod_workshop_link'])
                    ? $db->escape($_POST['mod_workshop_link'])
                    : NULL;

                $modGroup = !empty($_POST['mod_steam_group'])
                    ? $db->escape($_POST['mod_steam_group'])
                    : NULL;


                $config = array(
                    "digest_alg" => "sha512",
                    "private_key_bits" => 1024,
                    "private_key_type" => OPENSSL_KEYTYPE_RSA,
                );
                $res = openssl_pkey_new($config); // Create the private and public key
                openssl_pkey_export($res, $privKey); // Extract the private key from $res to $privKey
                $pubKey = openssl_pkey_get_details($res); // Extract the public key from $res to $pubKey
                $pubKey = $pubKey["key"];


                $insertSQL = $db->q('INSERT INTO `mod_list` (`steam_id64`, `mod_identifier`, `mod_name`, `mod_description`, `mod_workshop_link`, `mod_steam_group`, `mod_public_key`, `mod_private_key`)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?);',
                    'isssssss',
                    $_SESSION['user_id64'], md5($modName . time()), $modName, $modDesc, $modWork, $modGroup, $pubKey, $privKey);

                if ($insertSQL) {
                    echo 'Insert Success!';
                } else {
                    echo '<strong>Oh Snap:</strong> Insert Failure!';
                }
            } else {
                echo '<strong>Oh Snap:</strong> No mod name given!';
            }
        } else {
            echo '<strong>Oh Snap:</strong> No DB!';
        }
    } else {
        echo '<strong>Oh Snap:</strong> Not logged in!';
    }
} catch (Exception $e) {
    echo '<strong>Oh Snap:</strong> Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage();
}