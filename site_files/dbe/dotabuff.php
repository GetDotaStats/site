<?php
require_once('../connections/parameters.php');
is_numeric(@$_GET["match_id"]) ? $match_id = @$_GET["match_id"] : $match_id = NULL;

if(!empty($match_id)){
	$memcached = new Cache(NULL, NULL, $localDev);

	$match_results = $memcached->get("dbe_match_results".$match_id);
	if(!$match_results){
		$url="https://api.steampowered.com/IDOTA2Match_570/GetMatchDetails/V001/?match_id=" . $match_id . "&key=" . $api_key_dbe;
		$json = file_get_contents($url); 
		$data = json_decode($json, true);

		$match_results = '<!DOCTYPE html><html><body><div id="dotabuffextended">';
		for ($i = 0; $i < 10; $i++){
		  $match_results .= $data['result']['players'][$i]['hero_damage'] . "," . $data['result']['players'][$i]['tower_damage'] . "," . $data['result']['players'][$i]['hero_healing'];
		  if ($i < 9) $match_results .= ",";
		}
		$match_results .= '</div></body></html>';
		
		$memcached->set("dbe_match_results".$match_id, $match_results, 6*60*60);
	}

	echo $match_results;
}
else{
	echo 'Invalid match';
}
?>