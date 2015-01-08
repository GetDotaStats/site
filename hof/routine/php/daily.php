#!/usr/bin/php -q
<?php
require_once('../../functions.php');
require_once('../../../global_functions.php');
require_once('../../../connections/parameters.php');

$db = new dbWrapper($hostname_gds_cron, $username_gds_cron, $password_gds_cron, $database_gds_cron, true);

if ($db) {
    //GOLDEN PROFILES GROUP
    //https://steamcommunity.com/groups/golden_profiles/memberslistxml
    {
        $feedRAW = curl('steamcommunity.com/groups/golden_profiles/memberslistxml');

        $xml = simplexml_load_string($feedRAW);

        /*echo '<pre>';
        print_r($xml);
        echo '</pre>';
        exit();*/

        if (!empty($xml)) {
            $db->q("UPDATE `hof_golden_profiles` SET `isInGroup` = 0;");

            foreach ($xml->members->steamID64 as $key => $value) {
                $sqlResult = $db->q("UPDATE `hof_golden_profiles` SET `isInGroup` = 1 WHERE `user_id64` = ?;",
                    's',
                    $value
                );

                echo '<br />';

                echo $sqlResult
                    ? "[SUCCESS] User '.$value.' found!"
                    : "[FAILURE] User '.$value.' not found!";
            }
        }
    }
}