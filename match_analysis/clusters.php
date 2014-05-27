<?php
	require_once('./functions.php');
	require_once('./connections/parameters.php');
?>

				<?php
try{
	if(!isset($_GET['r']) || !is_numeric($_GET['r'])){
		header("Location: ./clusters.php?r=-1");
	}
	else{
		$region = $_GET['r'];
	}

	if(isset($_GET['nofun']) && $_GET['nofun'] == 1){
		$nofun = 1;
	}
	else{
		$nofun = 0;
	}
	
	$db = new dbWrapper($hostname, $username, $password, $database, false);

	if($db){
		$memcache = new Memcache;
		$memcache->connect("localhost",11211); # You might need to set "localhost" to "127.0.0.1"
		
		$match_db_details = get_match_db_details($db);
		
		$match_cluster_breakdown = simple_cached_query('d2match_cluster_breakdown', 
				'SELECT 
					`region`,
					`region_name`, 
					COUNT(DISTINCT `cluster`) as clusters, 
					SUM(`games`) as games
				FROM `q5_cluster_breakdown` 
				GROUP BY `region`
				ORDER BY games DESC', 
			5);
		
		$addon_sql = '';

		if($region == -1){
			if($nofun){
				$addon_sql = " WHERE q5.`game_mode` NOT IN ('7', '9', '15') ";
			}

			$match_cluster_breakdown_gamemode = $db->q(
					'SELECT 
						q5.`game_mode`, 
						gm.`nice_name`, 
						SUM(q5.`games`) as games
					FROM `q5_cluster_breakdown` q5
					LEFT JOIN `game_modes` gm ON q5.`game_mode` = gm.`game_mode`
					' . $addon_sql . '
					GROUP BY q5.`game_mode`
					ORDER BY games DESC;');
		}
		else{
			if($nofun){
				$addon_sql = " AND q5.`game_mode` NOT IN ('7', '9', '15') ";
			}

			$match_cluster_breakdown_gamemode = $db->q(
					'SELECT 
						q5.`game_mode`, 
						gm.`nice_name`, 
						SUM(q5.`games`) as games
					FROM `q5_cluster_breakdown` q5
					LEFT JOIN `game_modes` gm ON q5.`game_mode` = gm.`game_mode`
					WHERE q5.`region` = ?
					' . $addon_sql . '
					GROUP BY q5.`region`, q5.`game_mode`
					ORDER BY games DESC;',
				'i',
				$region);
		}

        $nofun_abbr = '<abbr title="No fun modes like diretide and frostivus" class="initialism">NF</abbr>';

		echo '<h1>Clusters</h1>';
		echo '<table class="table table-bordered table-hover table-condensed">';
		echo '
			<tr>
				<th colspan="3">Region</th>
				<th>Clusters</th>
				<th>Percentage</th>
				<th>Games</th>
			</tr>';

        $total_games = 0;
        $total_clusters = 0;
        $active_region_name = 'World Wide';

		foreach($match_cluster_breakdown as $key => $value){
            $total_games += $value['games'];
            $total_clusters += $value['clusters'];

			$css_class1 = '';
			if($region == $value['region'] && !$nofun){
                $active_region_name = $value['region_name'];
				$css_class1 = ' class="active"';
			}

			$css_class2 = '';
			if($region == $value['region'] && $nofun){
                $active_region_name = $value['region_name'];
				$css_class2 = ' class="active"';
			}
			
			empty($value['region_name']) 
				? $value['region_name'] = 'Unknown Clusters!'
					: NULL;

			echo '
				<tr>
					<td>'. ($key + 1) .'</td>
					<td'.$css_class1.'><a class="nav-clickable" href="#match_analysis__clusters?r='.$value['region'].'">'. $value['region_name'] .'</a></td>
					<td'.$css_class2.'><a class="nav-clickable" href="#match_analysis__clusters?r='.$value['region'].'&nofun=1">'.$nofun_abbr.'</a></td>
					<td>'. $value['clusters'] .'</td>
					<td>'. number_format($value['games'] / $match_db_details['match_count'] * 100, 2) .'%</td>
					<td>'. number_format($value['games']) .'</td>
				</tr>';
		}


        $css_class1 = '';
        if($region == -1 && !$nofun){
            $css_class1 = ' class="active"';
        }

        $css_class2 = '';
        if($region == -1 && $nofun){
            $css_class2 = ' class="active"';
        }

        echo '
			<tr>
				<td>&nbsp;</td>
				<td'.$css_class1.'><a class="nav-clickable" href="#match_analysis__clusters?r=-1">Aggregate</a></td>
				<td'.$css_class2.'><a class="nav-clickable" href="#match_analysis__clusters?r=-1&nofun=1">'.$nofun_abbr.'</a></td>
				<td>'.number_format($total_clusters).'</td>
				<td>100%</td>
                <td>'.number_format($total_games).'</td>
			</tr>';

        echo '</table>';

        //////////////////////////////////////////////////
        $game_mode_name = 'test';
		echo '<h1>Game Modes <small>' . $active_region_name . '</small></h1>';
		if(!empty($match_cluster_breakdown_gamemode)){
			$total_games_mode = 0;
			foreach($match_cluster_breakdown_gamemode as $key => $value){
				$total_games_mode += $value['games'];
			}
			
			echo '<div class="table-responsive">
		        <table class="table table-striped">';
			echo '
				<tr>
					<th>#</th>
					<th>Mode</th>
					<th>Percentage</th>
					<th>Games</th>
				</tr>';
			foreach($match_cluster_breakdown_gamemode as $key => $value){
				echo '
					<tr>
						<td>' . ($key + 1) . '</td>
						<td><a class="nav-clickable" href="#match_analysis__game_modes?gm='.$value['game_mode'].'">'.$value['nice_name'].'</a></td>
						<td>'. number_format($value['games'] / $total_games_mode * 100, 2) .'%</td>
						<td>'. number_format($value['games']) .'</td>
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
