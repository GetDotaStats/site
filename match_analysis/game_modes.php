<?php
	require_once('./functions.php');
	require_once('../connections/parameters.php');
?>

				<?php
try{
	if(!isset($_GET['gm']) || !is_numeric($_GET['gm'])){
		header("Location: ./game_modes.php?gm=-1");
	}
	else{
		$game_mode = $_GET['gm'];
	}

    $db = new dbWrapper($hostname_match_analysis, $username_match_analysis, $password_match_analysis, $database_match_analysis, false);

	if($db){
		$memcache = new Memcache;
		$memcache->connect("localhost",11211); # You might need to set "localhost" to "127.0.0.1"
		
		$match_db_details = get_match_db_details($db);
		
		$game_modes = simple_cached_query('d2_game_modes', 
				"SELECT 
					`game_mode`, 
					`nice_name`, 
					`name`, 
					`total` 
				FROM `q3_game_mode_breakdown`", 
			30);
		
		$game_mode_array = array();
		foreach($game_modes as $key => $value){
			$game_mode_array[$value['game_mode']] = $value['total'];
		}

		if($game_mode == -1){
			$heroes_played = $memcache->get('d2_heroes_played_gm'.$game_mode);
			if(!$heroes_played){
				//IGNORE TRASH GAMES (XMAS AND HALLOWEEN AND CUSTOM)
				$heroes_played = $db->q(
					"SELECT 
						`hero_id`, 
						`localized_name`, 
						SUM(`games_total`) as games_total, 
						SUM(`radiant_wins`) as radiant_wins, 
						SUM(`dire_wins`) as dire_wins 
					FROM `q4_game_mode_hero_picks`
					WHERE `game_mode` NOT IN ('7', '9', '15')
					GROUP BY `hero_id`
					ORDER BY games_total DESC"
				);
				
				foreach($heroes_played as $key => $value){
					$heroes_played[$key]['total_percentage'] = number_format($value['games_total'] / $match_db_details['match_count'] * 100, 2);
				}

				$memcache->set('d2_heroes_played_gm'.$game_mode, $heroes_played, 0, 30);
			}
		}
		else{
			$heroes_played = $memcache->get('d2_heroes_played_gm'.$game_mode);
			if(!$heroes_played){
				$heroes_played = $db->q(
					"SELECT 
						`game_mode`, 
						`hero_id`, 
						`localized_name`, 
						SUM(`games_total`) as games_total, 
						SUM(`radiant_wins`) as radiant_wins, 
						SUM(`dire_wins`) as dire_wins 
					FROM `q4_game_mode_hero_picks`
					WHERE `game_mode` = ?
					GROUP BY `hero_id`
					ORDER BY games_total DESC",
				'i',
				$game_mode
				);

				foreach($heroes_played as $key => $value){
					$heroes_played[$key]['total_percentage'] = number_format($value['games_total'] / $game_mode_array[$value['game_mode']] * 100, 2);
				}

				$memcache->set('d2_heroes_played_gm'.$game_mode, $heroes_played, 0, 30);
			}
		}
		
		echo '<h1>Game Modes <small>World Wide</small></h1>';
		echo '<table class="table table-bordered table-hover table-condensed">';
		echo '
			<tr>
				<th colspan="2">Mode</th>
				<th>Percentage</th>
				<th>Games</th>
			</tr>';

		foreach($game_modes as $key => $value){
			$css_class1 = '';
			if($value['game_mode'] == $game_mode){
				$css_class1 = ' class="active"';
			}
			
			if($value['total'] > 0){
				$link = '<a class="nav-clickable" href="#match_analysis__game_modes?gm='.$value['game_mode'].'">' . $value['nice_name'] . '</a>';
			}
            else{
                $link = $value['nice_name'];
            }
	
			echo '
				<tr'.$css_class1.'>
					<td>'. $key .'</td>
					<td>'.$link.'</td>
					<td>'. number_format($value['total'] / $match_db_details['match_count'] * 100, 2) .'%</td>
					<td>'. number_format($value['total']) .'</td>
				</tr>';
		}

        $css_class1 = '';
        if($game_mode == -1){
            $css_class1 = ' class="active"';
        }

        echo '
			<tr'.$css_class1.'>
				<td>&nbsp;</td>
				<td><a class="nav-clickable" href="#match_analysis__game_modes?gm=-1">Aggregate</a></td>
				<td>100%</td>
				<td>'. number_format($match_db_details['match_count']) .'</td>
			</tr>';

		echo '</table>';
		
		/////////////////////////////////////////

        echo '<h1>Hero Picks</h1>';
		if(!empty($heroes_played)){
			echo '<div class="table-responsive">
		        <table class="table table-striped">';
				echo '
				<tr>
					<td></td>
					<th>Hero</th>
					<th>Percentage</th>
					<th>Games Played</th>
				</tr>';
			foreach($heroes_played as $key => $value){
				echo '<tr>
					<td>'. ($key + 1) .'</td>
					<td>'.$value['localized_name'].'</td>
					<td>'.number_format($value['total_percentage'], 2).'%</td>
					<td>'.number_format($value['games_total']).'</td>
				</tr>';
			}
			echo '</table>
		    </div>';
		}
		else{
			echo 'Hang on! The stats must be regenerating or there are no stats to show!';
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
