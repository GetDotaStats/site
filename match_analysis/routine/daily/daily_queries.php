#!/usr/bin/php -q
<?php
require_once('../../functions.php');
require_once('../../../connections/parameters.php');

$db = new dbWrapper($hostname_match_analysis_routine, $username_match_analysis_routine, $password_match_analysis_routine, $database_match_analysis_routine, true);

try{
//////////////////////////////////////////////
/*	$time_start = time();
	$descriptor = 'Leaver Breakdown';
	$temp_table = 'tbl_' . time();
	$query_name = 'q1_leaver_breakdown';
	
	$q1_leavers_aggregate = $db -> q(
			"SELECT gls.`leaver_status`, gls.`nice_name`, gls.`name`, COUNT(*) as total_leavers 
				FROM `players` p 
				LEFT JOIN `game_leaver_status` gls ON p.`leaver_status` = gls.`leaver_status` 
				GROUP BY p.`leaver_status` 
				ORDER BY total_leavers DESC;");
	echo $q1_leavers_aggregate ? "[SUCCESS][SELECT] $descriptor \n" : "[FAILURE][SELECT] $descriptor \n";

	if($q1_leavers_aggregate){
		$total_players_for_leavers = 0;
		foreach($q1_leavers_aggregate as $key => $value){
			$total_players_for_leavers += $value['total_leavers'];
		}
		foreach($q1_leavers_aggregate as $key => $value){
			$q1_leavers_aggregate[$key]['percent'] = round( $value['total_leavers'] / $total_players_for_leavers * 100 , 2);
		}
		unset($total_players_for_leavers);
		
		$db -> q(
			"CREATE TABLE IF NOT EXISTS `$query_name` (
				`leaver_status` tinyint(2) NOT NULL,
				`nice_name` varchar(255) NOT NULL,
				`name` varchar(255) NOT NULL,
				`total_leavers` bigint(255) NOT NULL,
				`percent` decimal(5,2) NOT NULL,
				PRIMARY KEY (`leaver_status`)
			) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

		$db -> q("TRUNCATE `$query_name`;");
		
		foreach($q1_leavers_aggregate as $key => $value){
			$db->q("INSERT INTO $query_name (leaver_status, nice_name, name, total_leavers, percent) VALUES (?, ?, ?, ?, ?)",
				'issid',
				$value['leaver_status'], $value['nice_name'], $value['name'], $value['total_leavers'], $value['percent']);
		}
		echo "[?SUCCESS?][INSERT] $descriptor \n";
	}
	
	unset($$query_name);
	$time_end = time();
	echo '{'.$descriptor . '} took ' . secs_to_h($time_end - $time_start) . " to execute\n\n";*/

/////////////////////////////////////////////
	$time_start = time();
	$descriptor = 'Player Match DB Details';
	$temp_table = 'tbl_' . time();
	$query_name = 'q2_player_match_db_details';
	
	echo 'Starting: ' . $descriptor . " \n";
	
	$q2_db_details_multi = array();
	
	$q2_db_details_multi[0]['query_name'] = 'match_count';
	$q2_db_details_multi[0]['query'] = 
		"SELECT COUNT(*) AS query_response 
			FROM `q2_player_match_db_details__m1` 
			LIMIT 0 , 1;";
	
	$q2_db_details_multi[1]['query_name'] = 'player_count_total';
	$q2_db_details_multi[1]['query'] = 
		"SELECT COUNT(`account_id`) AS query_response
			FROM `q2_player_match_db_details__m1p1` 
			LIMIT 0 , 1;";
	
	$q2_db_details_multi[2]['query_name'] = 'player_count_registered';
	$q2_db_details_multi[2]['query'] = 
		"SELECT COUNT(`account_id`) AS query_response 
			FROM `q2_player_match_db_details__m1p1` 
			WHERE `account_id` IS NOT NULL 
			LIMIT 0,1;";
	
	$q2_db_details_multi[3]['query_name'] = 'player_count_distinct';
	$q2_db_details_multi[3]['query'] = 
		"SELECT COUNT(DISTINCT `account_id`) AS query_response 
			FROM `q2_player_match_db_details__m1p1` 
			WHERE `account_id` IS NOT NULL 
			LIMIT 0,1;";
	
	$q2_db_details_multi[4]['query_name'] = 'heroes_played';
	$q2_db_details_multi[4]['query'] = 
		"SELECT COUNT(`hero_id`) AS query_response 
			FROM `q2_player_match_db_details__m1p1` 
			WHERE `hero_id` > 0 
			LIMIT 0,1;";

	$q2_db_details_multi[5]['query_name'] = 'date_oldest';
	$q2_db_details_multi[5]['query'] = 
		"SELECT MIN(m1.`start_time`) AS query_response 
			FROM `q2_player_match_db_details__m1` m1 
			LIMIT 0,1;";
	
	$q2_db_details_multi[6]['query_name'] = 'match_oldest';
	$q2_db_details_multi[6]['query'] = 
		"SELECT MIN(m1.`match_id`) AS query_response 
			FROM `q2_player_match_db_details__m1` m1 
			LIMIT 0,1;";
	
	$q2_db_details_multi[7]['query_name'] = 'date_recent';
	$q2_db_details_multi[7]['query'] = 
		"SELECT MAX(m1.`start_time`) AS query_response 
			FROM `q2_player_match_db_details__m1` m1 
			LIMIT 0,1;";
	
	$q2_db_details_multi[8]['query_name'] = 'match_recent';
	$q2_db_details_multi[8]['query'] = 
		"SELECT MAX(m1.`match_id`) AS query_response 
			FROM `q2_player_match_db_details__m1` m1 
			LIMIT 0,1;";

	
	$q2_db_details_results = array();
	
	$db -> q(
		"CREATE TABLE IF NOT EXISTS `$query_name` (
			`date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			`match_count` bigint(255) DEFAULT NULL,
			`player_count_total` bigint(255) DEFAULT NULL,
			`player_count_registered` bigint(255) DEFAULT NULL,
			`player_count_distinct` bigint(255) DEFAULT NULL,
			`heroes_played` bigint(255) DEFAULT NULL,
			`date_oldest` bigint(20) DEFAULT NULL,
			`match_oldest` bigint(255) DEFAULT NULL,
			`date_recent` bigint(20) DEFAULT NULL,
			`match_recent` bigint(255) DEFAULT NULL,
			PRIMARY KEY (`date_added`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1");
	
	$db -> q('DROP TABLE IF EXISTS `q2_player_match_db_details__m1`, `q2_player_match_db_details__m1p1`;'); //GET RID OF OUR HEAP TABLES IF THEY EXIST

	$time_start2 = time();
	$q2_db_details = $db -> q(
		"CREATE TEMPORARY TABLE `q2_player_match_db_details__m1` (INDEX(`match_id`))
			SELECT `match_id`, `start_time` 
			FROM `matches` 
			WHERE `game_mode` NOT IN ('7', '9', '15');");
	echo $q2_db_details ? "[SUCCESS][CREATE] $descriptor" : "[FAILURE][CREATE] $descriptor";
	$time_end2 = time();
	echo ' - [' . secs_to_h($time_end2 - $time_start2) . "]\n";

	$time_start2 = time();
	$q2_db_details = $db -> q(
		"CREATE TEMPORARY TABLE `q2_player_match_db_details__m1p1` (INDEX(`account_id`), INDEX(`hero_id`))
			SELECT m1.`match_id`, p1.`account_id`, p1.`hero_id` 
			FROM `q2_player_match_db_details__m1` m1
			INNER JOIN `players` p1 ON m1.`match_id` = p1.`match_id`;");
	echo $q2_db_details ? "[SUCCESS][CREATE] $descriptor" : "[FAILURE][CREATE] $descriptor";
	$time_end2 = time();
	echo ' - [' . secs_to_h($time_end2 - $time_start2) . "]\n";

	$q2_db_details_values = array();

	foreach($q2_db_details_multi as $key => $value){
		$time_start2 = time();
		
		$q2_db_details = $db -> q($value['query']);
		echo $q2_db_details ? "[SUCCESS][SELECT] ".$value['query_name'] : "[FAILURE][SELECT] $descriptor - ".$value['query_name'];

		if(!empty($q2_db_details[0]['query_response'])){
			$q2_db_details_values[$value['query_name']] = $q2_db_details[0]['query_response'];
		}
		
		$time_end2 = time();
		echo ' - [' . secs_to_h($time_end2 - $time_start2) . "]\n";
	}

	if(!empty($q2_db_details_values['match_count']) && !empty($q2_db_details_values['player_count_total']) && !empty($q2_db_details_values['player_count_registered']) && !empty($q2_db_details_values['player_count_distinct']) && !empty($q2_db_details_values['heroes_played']) && !empty($q2_db_details_values['date_oldest']) && !empty($q2_db_details_values['match_oldest']) && !empty($q2_db_details_values['date_recent']) && !empty($q2_db_details_values['match_recent'])){
		$q2_db_details = $db -> q(
			"INSERT INTO `$query_name` (`match_count`, `player_count_total`, `player_count_registered`, `player_count_distinct`, `heroes_played`, `date_oldest`, `match_oldest`, `date_recent`, `match_recent`) 
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) 
			ON DUPLICATE KEY UPDATE `match_count` = VALUES(`match_count`), `player_count_total` = VALUES(`player_count_total`), `player_count_registered` = VALUES(`player_count_registered`), `player_count_distinct` = VALUES(`player_count_distinct`), `heroes_played` = VALUES(`heroes_played`), `date_oldest` = VALUES(`date_oldest`), `match_oldest` = VALUES(`match_oldest`), `date_recent` = VALUES(`date_recent`), `match_recent` = VALUES(`match_recent`);", 
			'iiiiiiiii', 
			$q2_db_details_values['match_count'], $q2_db_details_values['player_count_total'], $q2_db_details_values['player_count_registered'], $q2_db_details_values['player_count_distinct'], $q2_db_details_values['heroes_played'], $q2_db_details_values['date_oldest'], $q2_db_details_values['match_oldest'], $q2_db_details_values['date_recent'], $q2_db_details_values['match_recent']);
			
		echo $q2_db_details ? "[SUCCESS][INSERT] $descriptor \n" : "[FAILURE][INSERT] $descriptor \n";
	}
	else{
		echo "[FAILURE][INSERT] $descriptor \n";
	}

			
	unset($$query_name);
	$time_end = time();
	echo '{'.$descriptor . '} took ' . secs_to_h($time_end - $time_start) . " to execute\n\n";

/////////////////////////////////////////////
	$time_start = time();
	$descriptor = 'Game Mode Aggregate Breakdown';
	$temp_table = 'tbl_' . time();
	$query_name = 'q3_game_mode_breakdown';
	
	$q3_game_mode_breakdown = $db -> q("CREATE TEMPORARY TABLE $temp_table 
		SELECT gm.`game_mode`, gm.`nice_name`, gm.`name`, COUNT(m.`match_id`) as total
			FROM `game_modes` gm
			LEFT JOIN `matches` m ON gm.`game_mode` = m.`game_mode`
			GROUP BY gm.`game_mode`;"
	);
	echo $q3_game_mode_breakdown ? "[SUCCESS][CREATE] $descriptor \n" : "[FAILURE][CREATE] $descriptor \n";

	if($q3_game_mode_breakdown){
		$db -> q(
			"CREATE TABLE IF NOT EXISTS `$query_name` (
				`game_mode` tinyint(2) NOT NULL,
				`nice_name` varchar(50) NOT NULL,
				`name` varchar(50) NOT NULL,
				`total` bigint(21) NOT NULL DEFAULT '0',
				PRIMARY KEY (`game_mode`)
			) ENGINE=InnoDB DEFAULT CHARSET=latin1;"
		);

		$db -> q("TRUNCATE `$query_name`;");
	
		$q3_game_mode_breakdown = $db -> q("INSERT INTO `$query_name` SELECT * FROM $temp_table;");
		echo $q3_game_mode_breakdown ? "[SUCCESS][INSERT] $descriptor \n" : "[FAILURE][INSERT] $descriptor \n";
	}
	$db -> q("DROP TABLE $temp_table;");
	
	unset($$query_name);
	$time_end = time();
	echo '{'.$descriptor . '} took ' . secs_to_h($time_end - $time_start) . " to execute\n\n";

/////////////////////////////////////////////
	$time_start = time();
	$descriptor = 'Game Mode Hero Picks';
	$temp_table = 'tbl_' . time();
	$query_name = 'q4_game_mode_hero_picks';
	
	$q4_game_mode_hero_picks = $db -> q("CREATE TEMPORARY TABLE $temp_table
		SELECT 
			m.`game_mode`, 
			gh.`hero_id`,
			gh.`localized_name`, 
			COUNT(*) as games_total, 
			SUM(radiant_win) as radiant_wins, 
			(COUNT(*) - SUM(radiant_win)) as dire_wins
		FROM `players` p 
		LEFT JOIN `game_heroes` gh ON p.`hero_id` = gh.`hero_id` 
		JOIN `matches` m ON p.`match_id` = m.`match_id` 
		WHERE p.`hero_id` > 0 
		GROUP BY m.`game_mode`, p.`hero_id` 
		ORDER BY m.`game_mode`, p.`hero_id`;"
	);
	echo $q4_game_mode_hero_picks ? "[SUCCESS][CREATE] $descriptor \n" : "[FAILURE][CREATE] $descriptor \n";

	if($q4_game_mode_hero_picks){
		$db -> q(
			"CREATE TABLE IF NOT EXISTS `$query_name` (
				`game_mode` tinyint(2) NOT NULL,
				`hero_id` int(255) NOT NULL DEFAULT '0',
				`localized_name` varchar(255) DEFAULT NULL,
				`games_total` bigint(255) NOT NULL DEFAULT '0',
				`radiant_wins` bigint(255) DEFAULT NULL,
				`dire_wins` bigint(255) DEFAULT NULL,
				PRIMARY KEY (`game_mode`,`hero_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=latin1;"
		);

		$db -> q("TRUNCATE `$query_name`;");
	
		$q4_game_mode_hero_picks = $db -> q("INSERT INTO `$query_name` SELECT * FROM $temp_table;");
		echo $q4_game_mode_hero_picks ? "[SUCCESS][INSERT] $descriptor \n" : "[FAILURE][INSERT] $descriptor \n";
	}
	$db -> q("DROP TABLE $temp_table;");
	
	unset($$query_name);
	$time_end = time();
	echo '{'.$descriptor . '} took ' . secs_to_h($time_end - $time_start) . " to execute\n\n";

/////////////////////////////////////////////
	$time_start = time();
	$descriptor = 'Cluster Breakdown';
	$temp_table = 'tbl_' . time();
	$query_name = 'q5_cluster_breakdown';
	
	$q5_cluster_breakdown = $db -> q("CREATE TEMPORARY TABLE $temp_table
		SELECT 
			m.`game_mode`, 
			m.`cluster`, 
			gc.`region`, 
			gr.`region_name`, 
			COUNT(*) AS games
		FROM  `matches` m
		LEFT JOIN  `game_clusters` gc ON m.`cluster` = gc.`cluster` 
		LEFT JOIN  `game_regions` gr ON gc.`region` = gr.`region` 
		GROUP BY gr.`region`, m.`cluster`, m.`game_mode` 
		ORDER BY gr.`region`, m.`cluster`, m.`game_mode`;"
	);
	echo $q5_cluster_breakdown ? "[SUCCESS][CREATE] $descriptor \n" : "[FAILURE][CREATE] $descriptor \n";

	if($q5_cluster_breakdown){
		$db -> q(
			"CREATE TABLE IF NOT EXISTS `$query_name` (
				`game_mode` tinyint(2) NOT NULL,
				`cluster` int(10) NOT NULL,
				`region` tinyint(2) NOT NULL,
				`region_name` varchar(255),
				`games` bigint(21) NOT NULL DEFAULT '0',
				PRIMARY KEY (`region`,`cluster`,`game_mode`)
			) ENGINE=InnoDB DEFAULT CHARSET=latin1;"
		);

		$db -> q("TRUNCATE `$query_name`;");
	
		$q5_cluster_breakdown = $db -> q("INSERT INTO `$query_name` SELECT * FROM $temp_table;");
		echo $q5_cluster_breakdown ? "[SUCCESS][INSERT] $descriptor \n" : "[FAILURE][INSERT] $descriptor \n";
	}
	$db -> q("DROP TABLE $temp_table;");
	
	unset($$query_name);
	$time_end = time();
	echo '{'.$descriptor . '} took ' . secs_to_h($time_end - $time_start) . " to execute\n\n";

/////////////////////////////////////////////
	$time_start = time();
	$descriptor = 'Aggregate Winrate Breakdown';
	$temp_table = 'tbl_' . time();
	$query_name = 'q6_aggregate_winrate_breakdown';
	
	$q6_aggregate_winrate_breakdown = $db -> q("CREATE TEMPORARY TABLE $temp_table
		SELECT 
			300 * floor(`duration` / 300) as `range_start`, 
			300 * floor(`duration` / 300) + 300 as `range_end`, 
			SUM(radiant_win) as radiant_wins, 
			(COUNT(*) - SUM(radiant_win)) as dire_wins 
		FROM `matches` 
		WHERE `game_mode` NOT IN ('7', '9', '15')
		GROUP BY 2  
		ORDER BY 2;"
	);
	echo $q6_aggregate_winrate_breakdown ? "[SUCCESS][CREATE] $descriptor \n" : "[FAILURE][CREATE] $descriptor \n";

	if($q6_aggregate_winrate_breakdown){
		$db -> q(
			"CREATE TABLE IF NOT EXISTS `$query_name` (
				`range_start` bigint(20) DEFAULT NULL,
				`range_end` bigint(20) DEFAULT NULL,
				`radiant_wins` bigint(30) DEFAULT NULL,
				`dire_wins` bigint(30) DEFAULT NULL,
				PRIMARY KEY (`range_end`)
			) ENGINE=InnoDB DEFAULT CHARSET=latin1;"
		);

		$db -> q("TRUNCATE `$query_name`;");
	
		$q6_aggregate_winrate_breakdown = $db -> q("INSERT INTO `$query_name` SELECT * FROM $temp_table;");
		echo $q6_aggregate_winrate_breakdown ? "[SUCCESS][INSERT] $descriptor \n" : "[FAILURE][INSERT] $descriptor \n";
	}
	$db -> q("DROP TABLE $temp_table;");
	
	unset($$query_name);
	$time_end = time();
	echo '{'.$descriptor . '} took ' . secs_to_h($time_end - $time_start) . " to execute\n\n";

/////////////////////////////////////////////
	$time_start = time();
	$descriptor = 'Winrate Breakdown Per Duration and Date';
	$temp_table = 'tbl_' . time();
	$query_name = 'q7_winrate_breakdown_duration_date';
	
	$q7_winrate_breakdown_duration_date = $db -> q("CREATE TEMPORARY TABLE $temp_table
		SELECT
				date(FROM_UNIXTIME(`start_time`)) as match_date,
				1800 * floor(`duration` / 1800) as `range_start`,
				1800 * floor(`duration` / 1800) + 1800 as `range_end`,
				SUM(radiant_win) as radiant_wins,
				(COUNT(*) - SUM(radiant_win)) as dire_wins 
			FROM `matches`
			WHERE `game_mode` NOT IN ('7', '9', '15') AND `duration` <= 7200
			GROUP BY match_date, range_end 
			ORDER BY match_date, range_end;"
	);
	echo $q7_winrate_breakdown_duration_date ? "[SUCCESS][CREATE] $descriptor \n" : "[FAILURE][CREATE] $descriptor \n";

	if($q7_winrate_breakdown_duration_date){
		$db -> q(
			"CREATE TABLE IF NOT EXISTS `$query_name` (
				`match_date` date DEFAULT NULL,
				`range_start` bigint(20) DEFAULT NULL,
				`range_end` bigint(20) DEFAULT NULL,
				`radiant_wins` bigint(30) DEFAULT NULL,
				`dire_wins` bigint(30) DEFAULT NULL, 
				PRIMARY KEY (`match_date`, `range_end`)
			) ENGINE=InnoDB DEFAULT CHARSET=latin1;"
		);

		$db -> q("TRUNCATE `$query_name`;");
	
		$q7_winrate_breakdown_duration_date = $db -> q("INSERT INTO `$query_name` SELECT * FROM $temp_table;");
		echo $q7_winrate_breakdown_duration_date ? "[SUCCESS][INSERT] $descriptor \n" : "[FAILURE][INSERT] $descriptor \n";
	}
	$db -> q("DROP TABLE $temp_table;");
	
	unset($$query_name);
	$time_end = time();
	echo '{'.$descriptor . '} took ' . secs_to_h($time_end - $time_start) . " to execute\n\n";

/////////////////////////////////////////////


/*	$time_start = time();
	$descriptor = 'Player stats';
	$temp_table = 'tbl_' . time();
	
	$query_3_player_stats = $db -> q("CREATE TEMPORARY TABLE $temp_table SELECT 
			COUNT(*) AS total_users, 
			COUNT(DISTINCT `user_id`) as unique_users, 
			(SELECT COUNT(*) FROM `players` WHERE `user_id` IS NULL) AS total_anon_users 
			FROM `players` WHERE `user_id` IS NOT NULL LIMIT 0,1");
	echo $query_3_player_stats ? "[SUCCESS][CREATE] $descriptor \n" : "[FAILURE][CREATE] $descriptor \n";

	if($query_3_player_stats){
		$query_3_player_stats = $db -> q("TRUNCATE query_3_player_stats;");
	
		$query_3_player_stats = $db -> q("INSERT INTO query_3_player_stats SELECT * FROM $temp_table;");
		echo $query_3_player_stats ? "[SUCCESS][INSERT] $descriptor \n" : "[FAILURE][INSERT] $descriptor \n";
	}
	$query_3_player_stats = $db -> q("DROP TABLE $temp_table;");
	
	$time_end = time();
	echo '{'.$descriptor . '} took ' . ($time_end - $time_start) . "seconds to execute\n\n";

/////////////////////////////////////////////
	$time_start = time();
	$descriptor = 'Match type stats';
	$temp_table = 'tbl_' . time();
	
	$query_4_match_types_stats = $db -> q("CREATE TEMPORARY TABLE $temp_table SELECT 
			mt.`nice_name` AS lobby_type, COUNT(*) AS num_lobbies  
			FROM `matches` m
			NATURAL JOIN `matches_lobby_types` mt
			GROUP BY m.`lobby_type`");
	echo $query_4_match_types_stats ? "[SUCCESS][CREATE] $descriptor \n" : "[FAILURE][CREATE] $descriptor \n";

	if($query_4_match_types_stats){
		$query_4_match_types_stats = $db -> q("TRUNCATE query_4_match_types_stats;");
	
		$query_4_match_types_stats = $db -> q("INSERT INTO query_4_match_types_stats SELECT * FROM $temp_table;");
		echo $query_4_match_types_stats ? "[SUCCESS][INSERT] $descriptor \n" : "[FAILURE][INSERT] $descriptor \n";
	}
	$query_4_match_types_stats = $db -> q("DROP TABLE $temp_table;");
	
	$time_end = time();
	echo '{'.$descriptor . '} took ' . ($time_end - $time_start) . "seconds to execute\n\n";

/////////////////////////////////////////////
	$time_start = time();
	$descriptor = 'Match stats';
	$temp_table = 'tbl_' . time();
	
	$query_5_match_stats = $db -> q("CREATE TEMPORARY TABLE $temp_table SELECT 
			COUNT(`match_id`) AS matches_total, 
			MIN(`start_time`) AS oldest_match, 
			MAX(`start_time`) AS newest_match, 
			(SELECT COUNT(*) 
			FROM (
				SELECT COUNT(match_id) FROM `players` GROUP BY match_id HAVING COUNT(match_id) < 10
			) as test_table) AS matches_to_fix 
			FROM `matches` LIMIT 0,1;");
	echo $query_5_match_stats ? "[SUCCESS][CREATE] $descriptor \n" : "[FAILURE][CREATE] $descriptor \n";

	if($query_5_match_stats){
		$query_5_match_stats = $db -> q("TRUNCATE query_5_match_stats;");
	
		$query_5_match_stats = $db -> q("INSERT INTO query_5_match_stats SELECT * FROM $temp_table;");
		echo $query_5_match_stats ? "[SUCCESS][INSERT] $descriptor \n" : "[FAILURE][INSERT] $descriptor \n";
	}
	$query_5_match_stats = $db -> q("DROP TABLE $temp_table;");
	
	$time_end = time();
	echo '{'.$descriptor . '} took ' . ($time_end - $time_start) . "seconds to execute\n\n";
*/
/////////////////////////////////////////////
	
}
catch (Exception $e){
	echo $e->getMessage();
}

?>