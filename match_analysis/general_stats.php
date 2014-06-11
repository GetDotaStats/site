<?php
	require_once('./functions.php');
	require_once('../connections/parameters.php');
?>

					<?php
try{
    $db = new dbWrapper($hostname_match_analysis, $username_match_analysis, $password_match_analysis, $database_match_analysis, false);
	
	if($db){
		$memcache = new Memcache;
		$memcache->connect("localhost",11211); # You might need to set "localhost" to "127.0.0.1"
		
		$match_db_details = get_match_db_details($db);
		
		$match_radiant_wins_date_duration = simple_cached_query('d2_match_wins_date_duration', 
			"SELECT
				`match_date`,
				`range_start`,
				`range_end`,
				(`radiant_wins` / (`radiant_wins` + `dire_wins`)) as radiant_percentage, 
				`radiant_wins`,
				(`dire_wins` / (`radiant_wins` + `dire_wins`)) as dire_percentage, 
				`dire_wins` 
			FROM `q7_winrate_breakdown_duration_date` 
			WHERE `range_end` <= 7200
			ORDER BY match_date, range_end;",
		30);

		$match_radiant_wins_aggregate = simple_cached_query('d2_match_radiant_wins_aggregate', 
			"SELECT 
				`range_start`, 
				`range_end`, 
				(`radiant_wins` / (`radiant_wins` + `dire_wins`)) as radiant_percentage, 
				`radiant_wins`, 
				(`dire_wins` / (`radiant_wins` + `dire_wins`)) as dire_percentage, 
				`dire_wins`,
				(`radiant_wins` + `dire_wins`) as total_games
			FROM `q6_aggregate_winrate_breakdown` 
			WHERE (`radiant_wins` + `dire_wins`) > 5
			ORDER BY `range_end`;",
		30);

		echo '<h1>Winrate by Duration per Date</h1>';

		$big_array = array();
		foreach($match_radiant_wins_date_duration as $key => $value){
			$duration = number_format($value['range_start']/60).' - '.number_format($value['range_end']/60);
			$big_array[$value['match_date']][$duration]['games'] = $value['radiant_wins'] + $value['dire_wins'];
			$big_array[$value['match_date']][$duration]['radiant'] = number_format($value['radiant_percentage'] * 100, 1) . '%';
			$big_array[$value['match_date']][$duration]['dire'] = number_format($value['dire_percentage'] * 100, 1) . '%';

		}
		
		$biggest_sub = 0;
		foreach($big_array as $key => $value){
			if(count($value) > $biggest_sub){
				$biggest_sub = count($value);
			}
		}
		
		if(!empty($big_array)){
			echo '<table class="table table-bordered table-hover table-condensed">';
				echo '<tr>';
					echo '<th>&nbsp;</th>';
					echo '<th colspan="'.$biggest_sub.'">Duration (Radiant - Dire)</th>';
				echo '</tr>';
				echo '<tr>';
					echo '<th>Date</th>';
					for($i=0;$i<$biggest_sub;$i++){
						echo '<th>' . ($i * 30) .' - ' . (($i + 1) * 30) .'mins</th>';;
					}
				echo '</tr>';
			foreach($big_array as $key => $value){
				echo '<tr>';
					echo '<td>' . $key . '</td>';
					
					foreach($value as $key2 => $value2){
						echo '<td>' . $value2['radiant'] . ' - ' . $value2['dire'] . ' (' . $value2['games'] . ')</td>';
					}
				echo '</tr>';
			}
			echo '</table>';
	
			echo '<br /><br />';
		}
		else{
			echo 'Hang on! The stats must be regenerating!<br /><br />';
		}

		/////////////////////////////////////////
		
		echo '<h1>Aggregate Winrate Analysis</h1>';
		if(!empty($match_radiant_wins_aggregate)){
			echo '<div class="table-responsive">
		        <table class="table table-striped">';
				echo '
				<tr>
					<th>Game Length (mins)</th>
					<th>Total Games</th>
					<th>Radiant Winrate</th>
					<th>Dire Winrate</th>
				</tr>';
			foreach($match_radiant_wins_aggregate as $key => $value){
				echo '<tr>
					<td>'.number_format($value['range_start']/60).' - '.number_format($value['range_end']/60).'</td>
					<td>'.number_format($value['total_games']).'</td>
					<td>'.number_format($value['radiant_percentage'] * 100, 2).'%</td>
					<td>'.number_format($value['dire_percentage'] * 100, 2).'%</td>
				</tr>';
			}
			echo '</table>
			</div>';
		}
		else{
			echo 'Hang on! The stats must be regenerating!<br /><br />';
		}
	}
	else{
		echo 'No DB';
	}
}
catch (Exception $e){
	echo $e->getMessage();
}
					?>
