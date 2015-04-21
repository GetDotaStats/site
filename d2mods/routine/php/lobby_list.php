#!/usr/bin/php -q
<?php
require_once('../../functions.php');
require_once('../../../global_functions.php');
require_once('../../../connections/parameters.php');

$db = new dbWrapper($hostname_gds_cron, $username_gds_cron, $password_gds_cron, $database_gds_cron, true);

if ($db) {
    //EXPIRE ACTIVE LOBBIES
    {
        $sqlResult = $db->q(
            "UPDATE `lobby_list` SET `lobby_active` = 0 WHERE `lobby_active` = 1 AND (`date_recorded` < now() - INTERVAL `lobby_ttl` MINUTE OR `date_keep_alive` < now() - INTERVAL 2 MINUTE);"
        );

        echo $sqlResult
            ? "[SUCCESS] $sqlResult lobbies closed!<br />"
            : "[FAILURE] Table not updated!<br />";
    }
}