#!/usr/bin/php -q
<?php
require_once('../../functions.php');
require_once('../../../connections/parameters.php');

$db = new dbWrapper($hostname_match_analysis_routine, $username_match_analysis_routine, $password_match_analysis_routine, $database_match_analysis_routine, true);

try{
//////////////////////////////////////////////
	$matches = '`matches`'; //`matches_test2`
	$players = '`players`'; //`players_test2`
		
	$matches_temp = '`matches_temp`';
	$players_temp = '`players_temp`';
	
	$start = '0'; //up to 700000
	$limit = '1000000';

	$time_start = time();
	$descriptor = 'Econometrics Data';
	
	$db -> q("DROP TABLE IF EXISTS players_econ_1;");
	$db -> q("DROP TABLE IF EXISTS players_econ_2;");
	$db -> q("DROP TABLE IF EXISTS players_econ_3;");
	$db -> q("DROP TABLE IF EXISTS players_econ_4;");
	$db -> q("DROP TABLE IF EXISTS $matches_temp;");
	$db -> q("DROP TABLE IF EXISTS $players_temp;");
	
	$time_start2 = time();
	$query = $db -> q("CREATE TABLE IF NOT EXISTS $matches_temp
		SELECT * 
		FROM $matches
		LIMIT $start, $limit;");
	echo $query ? "[SUCCESS][CREATE] $matches_temp \n" : "[FAILURE][CREATE] $matches_temp \n";
	$time_end = time();
	echo '{'. $matches_temp . '} took ' . secs_to_h($time_end - $time_start2) . " to execute\n\n";
	
	if($query){
		$time_start2 = time();
		$query = $db -> q("CREATE TABLE IF NOT EXISTS $players_temp
			SELECT * 
			FROM $players
			LIMIT " . ($start * 10) . ", " . ($limit * 10) . ";");
		echo $query ? "[SUCCESS][CREATE] $players_temp \n" : "[FAILURE][CREATE] $players_temp \n";
		$time_end = time();
		echo '{'. $players_temp . '} took ' . secs_to_h($time_end - $time_start2) . " to execute\n\n";
		
		if($query){
			$time_start2 = time();
			$query_name = '`players_econ_1`';
			$query = $db -> q("CREATE TABLE IF NOT EXISTS `players_econ_1`
				SELECT p2.match_id
				FROM $players_temp p2
				RIGHT JOIN $matches_temp m2 ON p2.match_id = m2.match_id
				WHERE p2.leaver_status < 2
				GROUP BY p2.`match_id`
				HAVING COUNT(*) >= 10;");
			echo $query ? "[SUCCESS][CREATE] $query_name \n" : "[FAILURE][CREATE] $query_name \n";
			$time_end = time();
			echo '{'.$query_name . '} took ' . secs_to_h($time_end - $time_start2) . " to execute\n\n";
			
			if($query){
				$time_start2 = time();
				$query_name = '`players_econ_2`';
				$query = $db -> q(
					"CREATE TABLE IF NOT EXISTS `players_econ_2`
						SELECT match_id, lobby_type,  game_mode, radiant_win, duration, cluster, tower_status_radiant, tower_status_dire, barracks_status_radiant, barracks_status_dire
						FROM $matches_temp
						WHERE match_id IN (SELECT `match_id` FROM `players_econ_1`);"
				);
				echo $query ? "[SUCCESS][CREATE] $query_name \n" : "[FAILURE][CREATE] $query_name \n";
				$time_end = time();
				echo '{'.$query_name . '} took ' . secs_to_h($time_end - $time_start2) . " to execute\n\n";

				if($query){
					$time_start2 = time();
					$query_name = '`players_econ_3`';
					$query = $db -> q(
						"CREATE TABLE IF NOT EXISTS `players_econ_3`
							SELECT match_id, IF(player_slot < 128, 1, 0) as radiant, SUM(kills), SUM(deaths), SUM(assists), (SUM(gold) + SUM(gold_spent)) as total_gold, SUM(last_hits), AVG(gold_per_min), AVG(xp_per_min)
							FROM $players_temp
							WHERE match_id IN (SELECT `match_id` FROM `players_econ_1`)
							GROUP BY `match_id`, `radiant`;");
					echo $query ? "[SUCCESS][CREATE] $query_name \n" : "[FAILURE][CREATE] $query_name \n";
					$time_end = time();
					echo '{'.$query_name . '} took ' . secs_to_h($time_end - $time_start2) . " to execute\n\n";
				
					if($query){
						$db -> q("DROP TABLE IF EXISTS players_econ_1;");
						
						$time_start2 = time();
						$query_name = '`players_econ_4`';
						$query = $db -> q(
						"CREATE TABLE IF NOT EXISTS `players_econ_4`
							SELECT
								pe2.match_id, pe2.lobby_type, pe2.game_mode, pe2.radiant_win, pe2.duration, pe2.cluster, pe2.tower_status_radiant, pe2.tower_status_dire, pe2.barracks_status_radiant, pe2.barracks_status_dire, 
								MAX(CASE WHEN radiant = 0 THEN `SUM(kills)` END) AS 'radiant_kills',
								MAX(CASE WHEN radiant = 0 THEN `SUM(deaths)` END) AS 'radiant_deaths',
								MAX(CASE WHEN radiant = 0 THEN `SUM(assists)` END) AS 'radiant_assists',
								MAX(CASE WHEN radiant = 0 THEN `total_gold` END) AS 'radiant_totalgold',
								MAX(CASE WHEN radiant = 0 THEN `SUM(last_hits)` END) AS 'radiant_last_hits',
								MAX(CASE WHEN radiant = 0 THEN `AVG(gold_per_min)` END) AS 'radiant_gold_per_min',
								MAX(CASE WHEN radiant = 0 THEN `AVG(xp_per_min)` END) AS 'radiant_xp_per_min',
								MAX(CASE WHEN radiant = 1 THEN `SUM(kills)` END) AS 'dire_kills',
								MAX(CASE WHEN radiant = 1 THEN `SUM(deaths)` END) AS 'dire_deaths',
								MAX(CASE WHEN radiant = 1 THEN `SUM(assists)` END) AS 'dire_assists',
								MAX(CASE WHEN radiant = 1 THEN `total_gold` END) AS 'dire_totalgold',
								MAX(CASE WHEN radiant = 1 THEN `SUM(last_hits)` END) AS 'dire_last_hits',
								MAX(CASE WHEN radiant = 1 THEN `AVG(gold_per_min)` END) AS 'dire_gold_per_min',
								MAX(CASE WHEN radiant = 1 THEN `AVG(xp_per_min)` END) AS 'dire_xp_per_min'
							FROM `players_econ_3` pe3
							INNER JOIN `players_econ_2` pe2 ON pe3.match_id = pe2.match_id
							GROUP BY pe3.match_id;");
						echo $query ? "[SUCCESS][CREATE] $query_name \n" : "[FAILURE][CREATE] $query_name \n";
						$time_end = time();
						echo '{'.$query_name . '} took ' . secs_to_h($time_end - $time_start2) . " to execute\n\n";
						
						if($query){
							$db -> q("DROP TABLE IF EXISTS players_econ_2;");
							$db -> q("DROP TABLE IF EXISTS players_econ_3;");
							
							$time_start2 = time();
							$query_name = '`xml_export`';
							$query = $db -> q(
								"SELECT * FROM players_econ_4 INTO OUTFILE '/tmp/econ".time().".csv' 
									FIELDS TERMINATED BY ',' 
									LINES TERMINATED BY \"\\n\";");
							echo $query ? "[SUCCESS][CREATE] $query_name \n" : "[FAILURE][CREATE] $query_name \n";
							$time_end = time();
							echo '{'.$query_name . '} took ' . secs_to_h($time_end - $time_start2) . " to execute\n\n";
						}
					}
				}
			}
		}
	}

	unset($query);
	$time_end = time();
	echo '{'.$descriptor . '} took ' . secs_to_h($time_end - $time_start) . " to execute\n\n";

/////////////////////////////////////////////
	
}
catch (Exception $e){
	echo $e->getMessage();
}

?>