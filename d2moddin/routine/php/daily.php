#!/usr/bin/php -q
<?php
require_once('./functions.php');
require_once('../../../global_functions.php');
require_once('../../../connections/parameters.php');

try {
    $db = new dbWrapper($hostname_d2moddin, $username_d2moddin, $password_d2moddin, $database_d2moddin, true);
    if ($db) {

/////////////////////////////////////////////
        $time_start = time();
        $descriptor = 'Mod Hero Breakdown';
        $temp_table = 'tbl_' . time();
        $query_name = 'stats_mods_heroes';

        /*
                        CREATE TABLE IF NOT EXISTS `stats_mods_heroes` (
                            `mod_name` varchar(255) NOT NULL,
                            `hero_id` int(255) NOT NULL,
                            `picked` bigint(21) NOT NULL DEFAULT '0',
                            `wins` bigint(21) NOT NULL DEFAULT '0',
                            PRIMARY KEY (`mod_name`,`hero_id`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=latin1
                            SELECT
                                ms.`mod` AS mod_name,
                                mp.`hero_id`,
                                COUNT( mp.`hero_id` ) AS picked,
                                SUM(
                                    CASE
                                        WHEN `good_guys_win` = 1 AND `team_id` = 0 THEN 1
                                        WHEN `good_guys_win` = 0 AND `team_id` = 1 THEN 1
                                        ELSE 0
                                    END) AS wins
                            FROM  `match_players` mp
                            LEFT JOIN  `match_stats` ms ON mp.`match_id` = ms.`match_id`
                            GROUP BY ms.`mod` , mp.`hero_id`
                            ORDER BY 1 , 2;
         */
        $q5_cluster_breakdown = $db->q("CREATE TABLE IF NOT EXISTS `$temp_table` (
                    `mod_name` varchar(255) NOT NULL,
                    `hero_id` int(255) NOT NULL,
                    `picked` bigint(21) NOT NULL DEFAULT '0',
                    `wins` bigint(21) NOT NULL DEFAULT '0',
                    PRIMARY KEY (`mod_name`,`hero_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1
                    SELECT
                        ms.`mod` AS mod_name,
                        mp.`hero_id`,
                        COUNT( mp.`hero_id` ) AS picked,
                        SUM(
                            CASE
                                WHEN `good_guys_win` = 1 AND `team_id` = 0 THEN 1
                                WHEN `good_guys_win` = 0 AND `team_id` = 1 THEN 1
                                ELSE 0
                            END) AS wins
                    FROM  `match_players` mp
                    LEFT JOIN  `match_stats` ms ON mp.`match_id` = ms.`match_id`
                    GROUP BY ms.`mod` , mp.`hero_id`
                    ORDER BY 1 , 2;");
        echo $q5_cluster_breakdown ? "[SUCCESS][CREATE] $descriptor <br />" : "[FAILURE][CREATE] $descriptor <br />";

        if ($q5_cluster_breakdown) {
            $db->q(
                "CREATE TABLE IF NOT EXISTS `$query_name` (
				`mod_name` varchar(255) NOT NULL,
				`hero_id` int(255) NOT NULL,
				`picked` bigint(21) NOT NULL DEFAULT '0',
				`wins` bigint(21) NOT NULL DEFAULT '0',
				PRIMARY KEY (`mod_name`,`hero_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=latin1;"
            );

            $db->q("TRUNCATE `$query_name`;");

            $q5_cluster_breakdown = $db->q("INSERT INTO `$query_name` SELECT * FROM $temp_table;");
            echo $q5_cluster_breakdown ? "[SUCCESS][INSERT] $descriptor <br />" : "[FAILURE][INSERT] $descriptor <br />";
        }
        $db->q("DROP TABLE $temp_table;");

        unset($$query_name);
        $time_end = time();
        echo '{' . $descriptor . '} took ' . secs_to_h($time_end - $time_start) . " to execute<br /><br />";

/////////////////////////////////////////////
        $time_start = time();
        $descriptor = 'Hero Schema';
        $query_name = 'game_heroes';

        $heroesList = getHeroes($api_key6);
        //RIP 108 -- npc_dota_hero_abyssal_underlord -- Abyssal Underlord

        if ($heroesList['result']['status'] == 200 && !empty($heroesList['result']['heroes'])) {
            $db->q(
                "CREATE TABLE IF NOT EXISTS `$query_name` (
                    `hero_id` int(255) NOT NULL,
                    `name` varchar(255) NOT NULL,
                    `localized_name` varchar(255) NOT NULL,
                    PRIMARY KEY (`hero_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1;"
            );
            $db->q("TRUNCATE `$query_name`;");


            foreach ($heroesList['result']['heroes'] as $key => $value) {
                if (isset($value['id']) && is_numeric($value['id'])) {
                    $heroID = isset($value['id']) && is_numeric($value['id'])
                        ? $value['id']
                        : 9000;

                    $heroNPCname = !empty($value['name'])
                        ? $value['name']
                        : 'npc_unknown';

                    $heroName = !empty($value['localized_name'])
                        ? $value['localized_name']
                        : 'Unknown Hero';


                    $sqlQuery = $db->q("INSERT INTO `$query_name` (`hero_id`, `name`, `localized_name`) VALUES (?, ?, ?);",
                        'iss',
                        $heroID, $heroNPCname, $heroName
                    );
                    echo $sqlQuery ? "[SUCCESS][INSERT] $heroName <br />" : "[FAILURE][INSERT] $heroName <br />";
                } else {
                    echo '[FAILURE][INSERT] No hero ID! <br />';
                }
            }
        } else {
            $status = isset($heroesList['result']['status'])
                ? $heroesList['result']['status']
                : 'Unknown Error';
            echo '[FAILURE][INSERT] Hero list empty! (Status: ' . $status . ')<br />';
        }

        $time_end = time();
        echo '{' . $descriptor . '} took ' . secs_to_h($time_end - $time_start) . " to execute<br /><br />";
/////////////////////////////////////////////

    } else {
        echo 'No DB';
    }
} catch (Exception $e) {
    echo $e->getMessage();
}