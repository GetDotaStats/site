<?php
require_once('./connections/parameters.php');
is_numeric(@$_GET["match_id"]) ? $match_id = @$_GET["match_id"] : $match_id = NULL;

if(!empty($match_id)){
	$memcache = new Memcache;
	$memcache->connect("localhost",11211); # You might need to set "localhost" to "127.0.0.1"

	$match_results = $memcache->get("dbe_match_map".$match_id);
	if(!$match_results){
		$url="https://api.steampowered.com/IDOTA2Match_570/GetMatchDetails/V001/?match_id=" . $match_id . "&key=" . $api_key_dbe;

		$json = file_get_contents($url); 
		$data = json_decode($json, true);

		$match_results = '<!DOCTYPE html><html><body><div id="dotabuffextended">';

		if ( $data['result']['radiant_win'] == "true")
		  $match_results .= "1";
		else
		  $match_results .= "0";

		$match_results .= ',';
		
		$match_results .= sprintf("%011b", $data['result']['tower_status_radiant']);
		$match_results .= ',';
		$match_results .= sprintf("%011b", $data['result']['tower_status_dire']);
		$match_results .= ',';
		$match_results .= sprintf("%006b", $data['result']['barracks_status_radiant']);
		$match_results .= ',';
		$match_results .= sprintf("%006b", $data['result']['barracks_status_dire']);

		$match_results .= '</div></body></html>';

		$memcache->set("dbe_match_map".$match_id, $match_results, 0, 6*60*60);
	}

	echo $match_results;
}
else{
	echo 'Invalid match';
}
?>