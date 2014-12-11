#!/usr/bin/php -q
<?php
require_once('../../functions.php');
require_once('../../../global_functions.php');
require_once('../../../connections/parameters.php');

$db = new dbWrapper($hostname_gds_cron, $username_gds_cron, $password_gds_cron, $database_gds_cron, true);

if ($db) {
    //MOD-HERO BREAKDOWN
    {
        $db->q("DROP TABLE IF EXISTS `cron_mod_heroes_temp`;");

        $sqlResult = $db->q(
            "CREATE TABLE IF NOT EXISTS `cron_mod_heroes_temp`
                SELECT
                  `mod_id`,
                  `hero_id`,
                  COUNT(*) AS numPicks
                FROM `mod_match_heroes`
                GROUP BY `mod_id`, `hero_id`
                ORDER BY `mod_id`, `hero_id`;"
        );

        echo $sqlResult
            ? "[SUCCESS] Table created!<br />"
            : "[FAILURE] Table not created!<br />";

        if ($sqlResult) {
            $db->q(
                "CREATE TABLE IF NOT EXISTS `cron_mod_heroes` (
                  `mod_id` varchar(255) NOT NULL,
                  `player_hero_id` int(255) NOT NULL,
                  `numPicks` bigint(21) NOT NULL DEFAULT '0',
                  PRIMARY KEY (`mod_id`,`player_hero_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1;"
            );

            $db->q(
                "TRUNCATE TABLE `cron_mod_heroes`;"
            );

            $sqlResult = $db->q(
                "INSERT INTO `cron_mod_heroes`
                    SELECT * FROM `cron_mod_heroes_temp`;"
            );

            echo $sqlResult
                ? "[SUCCESS] Final table populated!<br />"
                : "[FAILURE] Final table not populated!<br />";

            $db->q("DROP TABLE IF EXISTS `cron_mod_heroes_temp`;");
        }
    }
}