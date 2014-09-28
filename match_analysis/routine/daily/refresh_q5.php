#!/usr/bin/php -q
<?php
require_once('../../functions.php');
require_once('../../../connections/parameters.php');

$db = new dbWrapper($hostname_match_analysis_routine, $username_match_analysis_routine, $password_match_analysis_routine, $database_match_analysis_routine, true);

try{
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
}
catch (Exception $e){
	echo $e->getMessage();
}

?>