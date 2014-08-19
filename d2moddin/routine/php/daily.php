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
        echo '<hr />';
        echo '<h1>' . $descriptor . '</h1>';

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
        echo '<hr />';
        echo '<h1>' . $descriptor . '</h1>';

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
        $time_start = time();
        $descriptor = 'Mod Items Breakdown';
        $temp_table = 'tbl_' . time();
        $query_name = 'stats_mods_items';
        echo '<hr />';
        echo '<h1>' . $descriptor . '</h1>';

        /*
                           CREATE TABLE IF NOT EXISTS `stats_mods_items1`
                           SELECT
                               ms.`mod` AS mod_name,
                               mp.`item1` as item,
                               COUNT( mp.`item1` ) AS purchased,
                               SUM(
                                   CASE
                                       WHEN `good_guys_win` = 1 AND `team_id` = 0 THEN 1
                                       WHEN `good_guys_win` = 0 AND `team_id` = 1 THEN 1
                                       ELSE 0
                                   END) AS wins
                           FROM `match_stats` ms
                           LEFT JOIN `match_players` mp ON ms.`match_id` = mp.`match_id`
                           GROUP BY ms.`mod` , item
                           ORDER BY 1 , 2;
                */
        for ($i = 1; $i <= 6; $i++) {
            $tblname = $query_name . $i;
            $time_start_sub = time();
            $sqlQuery = $db->q('DROP TABLE IF EXISTS `' . $tblname . '`;');
            $sqlQuery = $db->q("CREATE TABLE IF NOT EXISTS `" . $tblname . "` (
                    `mod_name` varchar(255) NOT NULL,
                    `item` int(255) NOT NULL,
                    `purchased` bigint(21) NOT NULL DEFAULT '0',
                    PRIMARY KEY (`mod_name`,`item`)
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1
                    SELECT
                        ms.`mod` AS mod_name,
                        mp.`item" . $i . "` as item,
                        COUNT( mp.`item" . $i . "` ) AS purchased
                    FROM `match_stats` ms
                    LEFT JOIN `match_players` mp ON ms.`match_id` = mp.`match_id`
                    GROUP BY ms.`mod` , item
                    ORDER BY 1 , 2;");
            $time_end_sub = time();
            echo $sqlQuery ? "[SUCCESS][CREATE] $tblname {" . secs_to_h($time_end_sub - $time_start_sub) . "}<br />" : "[FAILURE][CREATE] $tblname {" . secs_to_h($time_end_sub - $time_start_sub) . "}<br />";
        }

        $time_start_sub = time();
        /*
SELECT `mod_name`, `item`, SUM(`purchased`) AS purchased FROM (
SELECT `mod_name`, `item`, `purchased`, `wins` FROM stats_mods_items1
UNION ALL
SELECT `mod_name`, `item`, `purchased`, `wins` FROM stats_mods_items2
UNION ALL
SELECT `mod_name`, `item`, `purchased`, `wins` FROM stats_mods_items3
UNION ALL
SELECT `mod_name`, `item`, `purchased`, `wins` FROM stats_mods_items4
UNION ALL
SELECT `mod_name`, `item`, `purchased`, `wins` FROM stats_mods_items5
UNION ALL
SELECT `mod_name`, `item`, `purchased`, `wins` FROM stats_mods_items6
ORDER BY `mod_name`, `item`
) t1
WHERE item > 0
GROUP BY `mod_name`, `item`
ORDER BY `mod_name`, `item`
        */
        $db->q(
            "CREATE TABLE IF NOT EXISTS `$temp_table` (
				`mod_name` varchar(255) NOT NULL,
				`item` int(255) NOT NULL,
				`purchased` bigint(21) NOT NULL DEFAULT '0',
                PRIMARY KEY (`mod_name`,`item`)
			) ENGINE=InnoDB DEFAULT CHARSET=latin1;"
        );

        $sqlQuery = $db->q("INSERT INTO `$temp_table`
                SELECT `mod_name`, `item`, SUM(`purchased`) AS purchased FROM (
                    (SELECT `mod_name`, `item`, `purchased` FROM stats_mods_items1)
                    UNION ALL
                    (SELECT `mod_name`, `item`, `purchased` FROM stats_mods_items2)
                    UNION ALL
                    (SELECT `mod_name`, `item`, `purchased` FROM stats_mods_items3)
                    UNION ALL
                    (SELECT `mod_name`, `item`, `purchased` FROM stats_mods_items4)
                    UNION ALL
                    (SELECT `mod_name`, `item`, `purchased` FROM stats_mods_items5)
                    UNION ALL
                    (SELECT `mod_name`, `item`, `purchased` FROM stats_mods_items6)
                    ORDER BY `mod_name`, `item`
                ) t1
                WHERE item > 0
                GROUP BY `mod_name`, `item`
                ORDER BY `mod_name`, `item`");
        $time_end_sub = time();
        echo $sqlQuery ? "[SUCCESS][CREATE] $temp_table - Temporary Aggregation {" . secs_to_h($time_end_sub - $time_start_sub) . "}<br />" : "[FAILURE][CREATE] $temp_table Temporary Aggregation {" . secs_to_h($time_end_sub - $time_start_sub) . "}<br />";

        if ($sqlQuery) {
            $time_start_sub = time();
            $db->q("DROP TABLE IF EXISTS $query_name;");
            $db->q(
                "CREATE TABLE IF NOT EXISTS `$query_name` (
                    `mod_name` varchar(255) NOT NULL,
                    `item` int(255) NOT NULL,
                    `purchased` bigint(21) NOT NULL DEFAULT '0',
                    PRIMARY KEY (`mod_name`,`item`)
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1;"
            );

            $sqlQuery = $db->q("INSERT INTO `$query_name` SELECT * FROM $temp_table;");
            $time_end_sub = time();
            echo $sqlQuery ? "[SUCCESS][INSERT] $query_name {" . secs_to_h($time_end_sub - $time_start_sub) . "}<br />" : "[FAILURE][INSERT] $query_name {" . secs_to_h($time_end_sub - $time_start_sub) . "}<br />";
        }
        $db->q("DROP TABLE IF EXISTS `stats_mods_items1`;");
        $db->q("DROP TABLE IF EXISTS `stats_mods_items2`;");
        $db->q("DROP TABLE IF EXISTS `stats_mods_items3`;");
        $db->q("DROP TABLE IF EXISTS `stats_mods_items4`;");
        $db->q("DROP TABLE IF EXISTS `stats_mods_items5`;");
        $db->q("DROP TABLE IF EXISTS `stats_mods_items6`;");
        $db->q("DROP TABLE IF EXISTS $temp_table;");

        unset($$query_name);
        $time_end = time();
        echo '{' . $descriptor . '} took ' . secs_to_h($time_end - $time_start) . " to execute<br /><br />";
/////////////////////////////////////////////
        $time_start = time();
        $descriptor = 'Item Schema';
        $query_name = 'game_items';
        echo '<hr />';
        echo '<h1>' . $descriptor . '</h1>';

        $itemsList = getItems($api_key6);

        if ($itemsList['result']['status'] == 200 && !empty($itemsList['result']['items'])) {
            $db->q(
                "CREATE TABLE IF NOT EXISTS `$query_name` (
                    `item_id` int(255) NOT NULL,
                    `mod_name` varchar(255) NOT NULL,
                    `name` varchar(255) NOT NULL,
                    `cost` int(255) NOT NULL,
                    `secret_shop` tinyint(1) NOT NULL,
                    `side_shop` tinyint(1) NOT NULL,
                    `recipe` tinyint(1) NOT NULL,
                    `localized_name` varchar(255) NOT NULL,
                    PRIMARY KEY (`item_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1;"
            );
            $db->q("TRUNCATE `$query_name`;");


            foreach ($itemsList['result']['items'] as $key => $value) {
                if (isset($value['id']) && is_numeric($value['id'])) {
                    $modName = 'default';

                    $itemID = isset($value['id']) && is_numeric($value['id'])
                        ? $value['id']
                        : 9000;

                    $itemNPCname = !empty($value['name'])
                        ? $value['name']
                        : 'npc_unknown';

                    $itemCost = isset($value['cost']) && is_numeric($value['cost'])
                        ? $value['cost']
                        : 0;

                    $itemSecretS = isset($value['secret_shop']) && $value['secret_shop'] == 1
                        ? 1
                        : 0;

                    $itemSideS = isset($value['side_shop']) && $value['side_shop'] == 1
                        ? 1
                        : 0;

                    $itemRecipe = isset($value['recipe']) && $value['recipe'] == 1
                        ? 1
                        : 0;

                    $itemName = !empty($value['localized_name'])
                        ? $value['localized_name']
                        : 'Unknown Item';

                    $sqlQuery = $db->q("INSERT INTO `$query_name` (`item_id`, `mod_name`, `name`, `cost`, `secret_shop`, `side_shop`, `recipe`, `localized_name`) VALUES (?, ?, ?, ?, ?, ?, ?, ?);",
                        'issiiiis',
                        $itemID, $modName, $itemNPCname, $itemCost, $itemSecretS, $itemSideS, $itemRecipe, $itemName
                    );
                    echo $sqlQuery ? "[SUCCESS][INSERT] $itemName <br />" : "[FAILURE][INSERT] $itemName <br />";
                } else {
                    echo '[FAILURE][INSERT] No item ID! <br />';
                }
            }
        } else {
            $status = isset($itemsList['result']['status'])
                ? $itemsList['result']['status']
                : 'Unknown Error';
            echo '[FAILURE][INSERT] Item list empty! (Status: ' . $status . ')<br />';
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