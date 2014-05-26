<?php
require_once('./functions.php');
require_once('./connections/parameters.php');
try{
	if (!isset($_SESSION)) {
		session_start();
	}

	$db = new dbWrapper($hostname, $username, $password, $database, false);
	$steamtracks = new steamtracks($steamtracks_api_key, $steamtracks_api_secret, false);
	
	if(isset($_GET['token'])){
		$token = $_GET['token'];
	}
	else{
		echo 'No token in callback!';
		exit();
	}
	
	if(isset($_GET['steamid32']) && is_numeric($_GET['steamid32'])){
		$steam_id = $_GET['steamid32'];
	}
	else{
		//the callback is not returning the steam_id, so dirty hack added here
		echo 'Using hack to get steamID that should have been a callback parameter.<br />';
		
		$status_request = $steamtracks->signup_status($token);//GET TOKEN STATUS

		if(!empty($status_request['result']['status']) && $status_request['result']['status'] == 'accepted'){
			$steam_id = $status_request['result']['user'];
		}
		else{
			echo 'Failed to get steam ID!!<br />';
			exit();
		}
	}

	if(!empty($token) && !empty($steam_id)){
		$accept_request = $steamtracks->signup_ack($token, $steam_id);
		
		if($accept_request['result']['status'] == 'OK'){
			$steam_id = !empty($accept_request['result']['userinfo']['steamid32'])
				? $accept_request['result']['userinfo']['steamid32']
					: 0;
			$steam_name = !empty($accept_request['result']['userinfo']['playerName'])
				? $accept_request['result']['userinfo']['playerName']
					: 0;
			$private_profile = !empty($accept_request['result']['userinfo']['privateProfile'])
				? $accept_request['result']['userinfo']['privateProfile']
					: 0;
		
			$dota_level = !empty($accept_request['result']['userinfo']['dota2']['level'])
				? $accept_request['result']['userinfo']['dota2']['level']
					: 0;
			$dota_wins = !empty($accept_request['result']['userinfo']['dota2']['wins'])
				? $accept_request['result']['userinfo']['dota2']['wins']
					: 0;
		
			//$rank_solo_gamesleft = $accept_request['result']['userinfo']['dota2']['soloCalibrationGamesRemaining'];
			$rank_solo = !empty($accept_request['result']['userinfo']['dota2']['soloCompetitiveRank'])
				? $accept_request['result']['userinfo']['dota2']['soloCompetitiveRank']
					: 0;
			$rank_solo_calib = !empty($accept_request['result']['userinfo']['dota2']['soloCalibrationGamesRemaining'])
				? $accept_request['result']['userinfo']['dota2']['soloCalibrationGamesRemaining']
					: 0;
			//$rank_team_gamesleft = $accept_request['result']['userinfo']['dota2']['calibrationGamesRemaining'];
			$rank_team = !empty($accept_request['result']['userinfo']['dota2']['competitiveRank'])
				? $accept_request['result']['userinfo']['dota2']['competitiveRank']
					: 0;
			$rank_team_calib = !empty($accept_request['result']['userinfo']['dota2']['calibrationGamesRemaining'])
				? $accept_request['result']['userinfo']['dota2']['calibrationGamesRemaining']
					: 0;
		
			$commends_forgiving = !empty($accept_request['result']['userinfo']['dota2']['forgiving'])
				? $accept_request['result']['userinfo']['dota2']['forgiving']
					: 0;
			$commends_friendly = !empty($accept_request['result']['userinfo']['dota2']['friendly'])
				? $accept_request['result']['userinfo']['dota2']['friendly']
					: 0;
			$commends_leadership = !empty($accept_request['result']['userinfo']['dota2']['leadership'])
				? $accept_request['result']['userinfo']['dota2']['leadership']
					: 0;
			$commends_teaching = !empty($accept_request['result']['userinfo']['dota2']['teaching'])
				? $accept_request['result']['userinfo']['dota2']['teaching']
					: 0;
			
			if(!empty($steam_id) && !empty($steam_name)){
				$insert_sql = $db->q(
						"INSERT INTO `mmr`(
							`steam_id`, 
							`steam_name`,
							`private_profile`,  
							`dota_level`, 
							`dota_wins`, 
							`rank_solo`, 
							`rank_solo_calib`, 
							`rank_team`, 
							`rank_team_calib`, 
							`commends_forgiving`, 
							`commends_friendly`, 
							`commends_leadership`, 
							`commends_teaching`
						)
						VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);",
					'isiiiiiiiiiii',
					$steam_id, $steam_name, $private_profile, $dota_level, $dota_wins, $rank_solo, $rank_solo_calib, $rank_team, $rank_team_calib, $commends_forgiving, $commends_friendly, $commends_leadership, $commends_teaching
				);

				if($insert_sql){
					//echo 'Sucessfully stored your data!';
					header('./index.php?status=success');
				}
				else{
					//echo 'Failed to store your data! We will try again later.';
					header('./index.php?status=sqlfailure');
				}
			}
			else{
				echo 'Failure parsing steam_id or steam_name from results.<br />';
			}
		}
		else{
			//echo 'Failure receiving account stats. If you signed up correctly, we will retry grabbing your stats automatically at a later date.<br />';
			header('./index.php?status=apifailure');
		}
	}
	else{
		//echo 'Missing steam_id or token!!<br />';
		header('./index.php?status=missingidtoken');
	}
}
catch (Exception $e){
	header('./index.php?status=apifailure');
	//echo $e->getMessage();
}
?>
