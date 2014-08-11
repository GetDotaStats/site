<?php
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