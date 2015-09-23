<?php
require_once('../../global_functions.php');
require_once('../../connections/parameters.php');

if (!function_exists("getModDetails")) {
    function getModDetails($memcache, $db, $modID)
    {
        global $memcache, $db, $hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site;

        $rememberToClose = false;
        if (!$memcache) {
            $memcache = new Memcache;
            $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"
            $rememberToClose = true;
        }

        if (!$db) {
            $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, false);
        }

        $modDetails = $memcache->get('api_mod_details' . $modID);
        if (!$modDetails) {
            $modDetails = $db->q(
                'SELECT
                        `mod_id`,
                        `steam_id64`,
                        `mod_identifier` AS mod_guid,
                        `mod_name`,
                        `mod_description`,
                        `mod_workshop_link`,
                        `mod_steam_group`,
                        `mod_public_key`,
                        `mod_private_key`,
                        `mod_active`,
                        `date_recorded`
                    FROM `mod_list`
                    WHERE `mod_id` = ?
                    LIMIT 0,1;',
                'i',
                $modID
            );

            if (!empty($modDetails)) {
                $modDetails = $modDetails[0];
            } else {
                $modDetails = false;
            }
            $memcache->set('api_mod_details' . $modID, $modDetails, 0, 5 * 60);
        }

        if ($rememberToClose) $memcache->close();

        return $modDetails;
    }
}