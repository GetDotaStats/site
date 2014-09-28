<?php
if(file_exists('./development_environment')){
	$opts = array('http' => array('proxy' => 'tcp://lsaubne001.anglo.local:8080', 'request_fulluri' => true));
	$default = stream_context_set_default($opts);
}

if (!class_exists("dbWrapper")) {
Class dbWrapper {
    protected $_mysqli;
    protected $_debug;
	public $row_cnt;
	public $row_cnt_affected;
 
    public function __construct($host, $username, $password, $database, $debug) {
        $this->_mysqli = new mysqli($host, $username, $password, $database);
        $this->_debug = (bool) $debug;
        if (mysqli_connect_errno()) {
            if ($this->_debug) {
                echo mysqli_connect_error();
                debug_print_backtrace();
            }
            return false;
        }
        return true;
    }

	public function escape($query){
		return $this->_mysqli->real_escape_string($query);
	}
	
	public function multi_query($query){
		if(is_array($query)){
			$exploded = implode(';', $query);
		}
		else{
			$exploded = $query;
		}
		
		if($query = $this->_mysqli->multi_query($exploded)){
			$i = 0; 
			do { 
				$i++; 
			} 
			while ($this->_mysqli->more_results() && $this->_mysqli->next_result()); 
		}
		
		if($this->_mysqli->errno){
			if ($this->_debug) {
				echo mysqli_error($this->_mysqli);
				debug_print_backtrace();
			}
			return false;
		}
		else{
			return true;
		}
	}
	 
    public function q($query) {
        if ($query = $this->_mysqli->prepare($query)) {
            if (func_num_args() > 1) {
                $x = func_get_args(); //grab all of the arguments
                $args = array_merge(array(func_get_arg(1)), 
                    array_slice($x, 2)); //filter out the query part, leaving the type declaration and parameters
                $args_ref = array();
                foreach($args as $k => &$arg) { //not sure what this step is doing
                    $args_ref[$k] = &$arg; 
                }
                call_user_func_array(array($query, 'bind_param'), $args_ref); // bind each parameter in the form of: $query bind_param (param1, param2, etc.)
            }
            $query->execute();
 
            if ($query->errno) {
              if ($this->_debug) {
                echo mysqli_error($this->_mysqli);
                debug_print_backtrace();
              }
              return false;
            }
 
            if ($query->affected_rows > -1) {
                return $query->affected_rows;
            }
            $params = array();
            $meta = $query->result_metadata();
            while ($field = $meta->fetch_field()) {
                $params[] = &$row[$field->name];
            }
            call_user_func_array(array($query, 'bind_result'), $params);
 
            $result = array();
            while ($query->fetch()) {
                $r = array();
                foreach ($row as $key => $val) {
                    $r[$key] = $val;
                }
                $result[] = $r;
            }
			
			$this->row_cnt = $query->num_rows;				//num rows
			$this->row_cnt_affected = $query->affected_rows;	//affected rows
			
            $query->close(); 
            return $result;
        } else {
            if ($this->_debug) {
                echo $this->_mysqli->error;
                debug_print_backtrace();
            }
            return false;
        }
    }
 
    public function handle() {
        return $this->_mysqli;
    }
	
	public function last_index() {
		return $this->_mysqli->insert_id;
	}
}
}

if (!function_exists("curl")) {
function curl($link, $postfields = '', $cookie = '', $refer = '', $user_agent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1'){
	$ch = curl_init($link);
	
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
	curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
	if($refer){
		curl_setopt($ch, CURLOPT_REFERER, $refer);
	}
	if($postfields){
		curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
	}
	if($cookie){
		curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
	}
	$page = curl_exec($ch);
	curl_close($ch);
	return $page;
}
}

if (!function_exists("cut_str")) {
function cut_str($str, $left, $right){
	$str = substr ( stristr ( $str, $left ), strlen ( $left ));
	$leftLen = strlen ( stristr ( $str, $right ) );
	$leftLen = $leftLen ? - ($leftLen) : strlen ( $str );
	$str = substr ( $str, 0, $leftLen);
	
	return $str;
}
}

//GIVEN A UNIX TIMESTAMP RETURNS A RELATIVE DISTANCE TO DATE (23.4 days ago)
//PUTTING ANY VALUE IN 2ND VARIABLE MAKES IT RETURN RAW HOURS APART
if(!function_exists('relative_time')){
function relative_time($time, $output = 'default'){
	if(!is_numeric($time)){
		if(strtotime($time)){
			$time = strtotime($time);
		}
		else{
			return FALSE;
		}
	}

	if($output == 'default'){
		if((time() - $time) >= 2592000){
			$time_adj = round(((time() - $time)/2592000), 1) . ' months ago';
		}
		else if((time() - $time) >= 86400){
			$time_adj = round(((time() - $time)/86400), 1) . ' days ago';
		}
		else if((time() - $time) >= 3600){
			$time_adj = round(((time() - $time)/3600), 1) . ' hours ago';
		} 
		else {
			$time_adj = round(((time() - $time)/60), 0) . ' mins ago';
		}
	}
	else{
		$time_adj = round(((time() - $time)/3600), 1);
	}
	
	return $time_adj;
}
}

if(!function_exists('LoadJPEG')){
function LoadJPEG ($imgURL) {

    ##-- Get Image file from Port 80 --##
    $fp = fopen($imgURL, "r");
    $imageFile = fread ($fp, 3000000);
    fclose($fp);

    ##-- Create a temporary file on disk --##
    $tmpfname = tempnam ("/temp", "IMG");

    ##-- Put image data into the temp file --##
    $fp = fopen($tmpfname, "w");
    fwrite($fp, $imageFile);
    fclose($fp);

    ##-- Load Image from Disk with GD library --##
    $im = imagecreatefromjpeg ($tmpfname);

    ##-- Delete Temporary File --##
    unlink($tmpfname);

    ##-- Check for errors --##
    if (!$im) {
        print "Could not create JPEG image $imgURL";
    }

    return $im;
}
}

if (!function_exists("grab_image")) {
function grab_image($img_url, $destination = './images/schema/'){
	if(!empty($img_url) && substr($img_url, (strlen($img_url) - 4)) == '.png'){
		$file = $destination . basename($img_url);

		if(!file_exists($file)){
			##-- Get Image file from Port 80 --##

			$handle = fopen($img_url, "rb");
			$imageFile = '';
			while (!feof($handle) && $handle) {
			  $imageFile .= fread($handle, 8192);
			}
			fclose($handle);

			##-- Put image data into the temp file --##
			$fp = fopen($file, "w");
			fwrite($fp, $imageFile);
			fclose($fp);

			unset($imageFile);
			
			return $file;
		}
		else{
			return $file;
		}
	}
	else{
		return false;
	}
}
}

if (!function_exists("get_d2_player_backpack")) {
function get_d2_player_backpack($account_id = '76561197989020883', $flush = 0, $steam_api_key ){
	$memcache = new Memcache;
	$memcache->connect("localhost",11211); # You might need to set "localhost" to "127.0.0.1"
	
	if($flush == 1){
		$memcache->delete("get_d2_player_backpack".$account_id);
	}
	
	$url = 'http://api.steampowered.com/IEconItems_570/GetPlayerItems/v0001/?language=en&key='.$steam_api_key.'&steamid='.$account_id;

	$player_items = $memcache->get("get_d2_player_backpack".$account_id);
	if(!$player_items){
		$player_items = json_decode(curl($url), true);
	
		if(empty($player_items)){
			sleep(1);
			$player_items = json_decode(curl($url), true);
		}

		$memcache->set("get_d2_player_backpack".$account_id, $player_items, 0, 60*15);
	}
	
	$memcache->close();

	return $player_items;
}
}

if (!function_exists("get_d2_schema")) {
function get_d2_item_schema($flush = 0, $steam_api_key){
	$memcache = new Memcache;
	$memcache->connect("localhost",11211); # You might need to set "localhost" to "127.0.0.1"
	
	if($flush == 1){
		$memcache->delete("get_d2_item_schema");
	}

	$schema = $memcache->get("get_d2_item_schema");
	if(!$schema){
		$schema = json_decode(curl('http://api.steampowered.com/IEconItems_570/GetSchema/v0001/?language=en&key='.$steam_api_key), true);

		$memcache->set("get_d2_item_schema", $schema, 0, 60*60);
	}
	
	$memcache->close();

	return $schema;
}
}

if (!function_exists("get_d2_rarities")) {
function get_d2_rarities($flush = 0, $steam_api_key ){
	$memcache = new Memcache;
	$memcache->connect("localhost",11211); # You might need to set "localhost" to "127.0.0.1"
	
	if($flush == 1){
		$memcache->delete("get_d2_rarities");
	}

	$schema = $memcache->get("get_d2_rarities");
	if(!$schema){
		$schema = json_decode(curl('http://api.steampowered.com/IEconDOTA2_816/GetRarities/v1/?language=en&key='.$steam_api_key), true);

		$memcache->set("get_d2_rarities", $schema, 0, 60*60);
	}
	
	$memcache->close();

	return $schema;
}
}

if (!function_exists("GetMatchHistory")) {
function GetMatchHistory($startinggame = NULL, $date_max = NULL, $debug = false, $num_games = NULL, $steam_api_key ){
	$parameters = NULL;
	
	if(!empty($startinggame)){
		$parameters .= '&start_at_match_id='.$startinggame;
	}
	if(!empty($num_games)){
		$parameters .= '&matches_requested='.$num_games;
	}
	if(!empty($date_max)){
		$parameters .= '&date_max='.$date_max;
	}

	$url = 'http://api.steampowered.com/IDOTA2Match_570/GetMatchHistory/v001/?language=en&min_players=10&key='.$steam_api_key.$parameters;

	if($debug === true){
		echo $url;
	}
	
	$matches = json_decode(curl($url), true);

	if(empty($matches)){
		sleep(1);
		$matches = json_decode(curl($url), true);
	}

	return $matches;
}
}

if (!function_exists("GetMatchHistoryBySequenceNum")) {
function GetMatchHistoryBySequenceNum($start_at_match_seq_num = NULL, $matches_requested = NULL, $debug = false, $steam_api_key ){
	$parameters = NULL;
	
	if(!empty($start_at_match_seq_num)){
		$parameters .= '&start_at_match_seq_num='.$start_at_match_seq_num;
	}
	if(!empty($matches_requested)){
		$parameters .= '&matches_requested='.$matches_requested;
	}
	
	$url = 'http://api.steampowered.com/IDOTA2Match_570/GetMatchHistoryBySequenceNum/v0001/?language=en&min_players=10&key='.$steam_api_key.$parameters;

	if($debug === true){
		echo $url;
	}
	
	$matches = json_decode(curl($url), true);

	if(empty($matches)){
		sleep(1);
		$matches = json_decode(curl($url), true);
	}

	return $matches;
}
}

if (!function_exists("GetMatchDetails")) {
function GetMatchDetails($match_id = NULL, $debug = false, $steam_api_key ){
	$parameters = NULL;
	
	if(!empty($match_id)){
		$parameters .= '&match_id='.$match_id;
	}

	$url = 'http://api.steampowered.com/IDOTA2Match_570/GetMatchDetails/V001/?language=en&min_players=10&key='.$steam_api_key.$parameters;

	if($debug === true){
		echo $url;
	}
	
	$matches = json_decode(curl($url), true);

	if(empty($matches)){
		sleep(1);
		$matches = json_decode(curl($url), true);
	}

	return $matches;
}
}

if (!function_exists("GetHeroes")) {
function GetHeroes($debug = false, $steam_api_key){

	$url = 'http://api.steampowered.com/IEconDOTA2_570/GetHeroes/v0001/?language=en&key='.$steam_api_key;

	if($debug === true){
		echo $url;
	}
	
	$matches = json_decode(curl($url), true);

	if(empty($matches)){
		sleep(1);
		$matches = json_decode(curl($url), true);
	}

	return $matches;
}
}

if (!function_exists("convert_id")) {
function convert_id($id){
	if(empty($id)) return false;
	
    if (strlen($id) === 17){
        $converted = substr($id, 3) - 61197960265728;
    }
    else{
        $converted = '765'.($id + 61197960265728);
    }
 
    return (string) $converted;
}
}

//FOR DAEMONS TO MAKE BATCH MYSQLI QUERIES. LOOK AT d2_match_fix and d2_grab_items for usage.
if (!function_exists("send_query")) {
function send_query($db, $query_values_array, $query_start, $query_end, $debug = false){
	if(count($query_values_array) > 0){
		$query_values = implode(', ', $query_values_array);

		$query = 
			$query_start 
			. $query_values . 
			$query_end;
		
		if($debug){
			echo $query.'<br />';
		}

		if($db->multi_query($query)){
			return true;
		}
		else{
			if($db->multi_query($query)){
				return true;
			}
		}
	}

	return false;
}
}

//FOR DAEMONS TO MAKE BATCH MYSQLI QUERIES. LOOK AT d2_match_fix and d2_grab_items for usage.
if (!function_exists("check_then_send_query")) {
function check_then_send_query($db, $query_array, $max_query_size, $log = false, $development = false, $debug = false){
	if(!empty($query_array)){
		foreach($query_array as $key => $value){	//CHECK IF QUERIES WILL BE TOO BIG
			if(isset($value['raw_values']) && count($value['raw_values']) >= $max_query_size){
				foreach($query_array as $key2 => $value2){
					if(count($value2['raw_values']) > 0){
						if($query_result = send_query($db, $value2['raw_values'], $value2['sql_start'], $value2['sql_end'], $debug)){
							
							if($log){
								if($development){
									echo "[SUCCESS][$key2] Q: ".count($value2['raw_values'])."\n";
								}
								else{
									//System_Daemon::info('{appName} Failure: %s',
									//	$e->getMessage()
									//);
								}
							}
	
							$query_array[$key2]['raw_values'] = array();
						}
						else{
							if($log){
								if($development){
									echo "[FAILURE][$key2] Q: ".count($value2['raw_values'])."\n";
								}
								else{
									System_Daemon::info('{appName} %s',
										"[FAILURE][$key2] Q: ".count($value2['raw_values'])
									);
								}
							}
						}
						sleep(0.001);
					}
				}
				break;
			}
		}
	}
	else{
		if($debug){
			if($development){
				echo 'Failure: Nothing in query array!<br />';
			}
			else{
				System_Daemon::info('{appName} Failure: %s',
					"Nothing in query array!"
				);
			}
		}
	}
	return $query_array;
}
}

if (!function_exists("escape_array")) {
function escape_array($db, $array){
	if(!empty($array)){
		foreach($array as $key => $value){	//ESCAPE ALL OF THE SQL VALUES
			if($value == '0' || (isset($value) && $value != '')){
				$array[$key] = "'".$db->escape($value)."'";
			}
			else{
				$array[$key] = 'NULL';
			}
		}
	}
	return $array;
}
}

//MUST PASS DATASET INTO ARRAY AS array('x' => ?, 'y' => ?) FOR bar/line/radar
//MUST PASS DATASET INTO ARRAY AS array('value' => ?, 'colour' => ?) FOR pies
if (!function_exists("gen_graphjs")) {
function gen_graphjs($graphelement, $dataset, $charttype = 'Line', $fillcolour = 'rgba(151,187,205,0.5)', $strokecolour = 'rgba(151,187,205,1)', $pointcolour = 'rgba(151,187,205,1)', $pointstrokecolour = '#fff'){
	if(!empty($dataset)){
		if($charttype == 'PolarArea' || $charttype == 'Pie' || $charttype == 'Doughnut'){
			$datajson = json_encode($dataset);
			$optionsjson = '[{
				segmentShowStroke : true,
				segmentStrokeColor : "#fff",
				segmentStrokeWidth : 2,
				percentageInnerCutout : 70,
				animation : false,
				animationSteps : 100,
				animationEasing : "easeOutBounce",
				animateRotate : false,
				animateScale : false}];
			';
		}
		else{
			$labels = array();
			$data = array();
			
			foreach($dataset as $key => $value){
				$labels[] = $value['x'];
				$data[] = $value['y'];
			}
	
			$labels = json_encode($labels);
			$data = json_encode($data);

			$datajson = '{
					labels : ' . $labels . ',
					datasets : [
						{
							fillColor : "' . $fillcolour . '",
							strokeColor : "' . $pointcolour . '",
							pointColor : "' . $pointcolour . '",
							pointStrokeColor : "' . $pointstrokecolour . '",
							data : ' . $data . '
						}
					]
				}';

			$optionsjson = '\'\';';
		}
		
		echo '
			<script type="text/javascript">
				var data = ' . $datajson . '
				
				var options = ' . $optionsjson . '
			
				var ctx = document.getElementById("' . $graphelement . '").getContext("2d");
				var myNewChart = new Chart(ctx).' . $charttype . '(data, options);
			</script>
		';
	}
}
}

if (!function_exists("hex2rgb")) {
function hex2rgb($hex) {
   $hex = str_replace("#", "", $hex);

   if(strlen($hex) == 3) {
      $r = hexdec(substr($hex,0,1).substr($hex,0,1));
      $g = hexdec(substr($hex,1,1).substr($hex,1,1));
      $b = hexdec(substr($hex,2,1).substr($hex,2,1));
   } else {
      $r = hexdec(substr($hex,0,2));
      $g = hexdec(substr($hex,2,2));
      $b = hexdec(substr($hex,4,2));
   }
   $rgb = $r.', '.$g.', '.$b;
   //return implode(",", $rgb); // returns the rgb values separated by commas
   return $rgb; // returns an array with the rgb values
}
}

if (!function_exists("hex2name")) {
function hex2name($hex){
	$colour_name = NULL;
	switch($hex){
		case 'FFFFFF':
			$colour_name = 'None';
			break;
		case 'B7CF33':
			$colour_name = 'Light Green';
			break;
		
		case '32A6CF':
			$colour_name = 'Light Blue';
			break;
		
		case 'D07733':
			$colour_name = 'Orange';
			break;
		
		case 'C439C6':
			$colour_name = 'Magenta';
			break;
		
		/*case '8232ED':
			$colour_name = 'Purple';
			break;*/
		
		case '2924CB':
			$colour_name = 'Persian';
			break;
		
		case '51B350':
			$colour_name = 'Green';
			break;
		
		case '3D68C4':
			$colour_name = 'Indigo';
			break;
		
		case '0097CE':
			$colour_name = 'Blue';
			break;
		
		case '8232CF':
			$colour_name = 'Violet';
			break;
		
		case 'CFAB31':
			$colour_name = 'Gold';
			break;
		
		case '4AB78D':
			$colour_name = 'Teal';
			break;
		
		case 'D03D33':
			$colour_name = 'Red';
			break;
	}
return $colour_name;
	
	/*
		Light Green		183,207,51		B7CF33
		Light Blue		50,166,207		32A6CF
		Orange			208,119,51		D07733
		Magenta			196,57,198		C439C6
		Purple	 		130,50,237		8232ED
		Persian			41,36,203		2924CB
		Green			81,179,80		51B350
		Indigo			61,104,196		3D68C4
		Blue			0,151,206		0097CE
		Violet			130,50,207		8232CF
		Gold			207,171,49		CFAB31
		Teal			74,183,141		4AB78D
		Red				208,61,51		D03D33
	*/
}
}

if (!function_exists("quality2colour")) {
function quality2colour($quality){
	$colour = NULL;
	switch($quality){
		case 0: //base
			$colour = '#FFFFFF';
			break;
		case 1: //genuine
			$colour = '#4D7455';
			break;
		case 2: //vintage
			$colour = '#476291';
			break;
		case 3: //unusual
			$colour = '#8650AC';
			break;
		case 4: //standard
			$colour = '#FFFFFF';
			break;
		case 5: //community
			$colour = '#70B04A';
			break;
		case 7: //self-made
			$colour = '#70B04A';
			break;
		case 9: //strange
			$colour = '#CF6A32';
			break;
		case 12: //tournament
			$colour = '#D46FF9';
			break;
		case 13: //favoured
			$colour = '#FFFF01';
			break;
	}
	return $colour;
}
}

if (!function_exists("gen_match_detail_table")) {
function gen_match_detail_table($match_history, $log = false, $max_query_size = 10000){
try{
	global $db;
	$table = '';

	$query_array['matches']['raw_values'] = array();
	$query_array['matches']['sql_start'] = 'INSERT INTO `matches`(`match_id`, `match_seq_num`, `lobby_type`, `game_mode`, `radiant_win`, `duration`, `start_time`, `cluster`, `first_blood_time`, `league_id`, `tower_status_radiant`, `tower_status_dire`, `barracks_status_radiant`, `barracks_status_dire`, `series_id`, `series_type`) VALUES ';
	$query_array['matches']['sql_end'] = ' ON DUPLICATE KEY UPDATE `match_id` = VALUES(`match_id`), `match_seq_num` = VALUES(`match_seq_num`), `lobby_type` = VALUES(`lobby_type`), `game_mode` = VALUES(`game_mode`), `radiant_win` = VALUES(`radiant_win`), `duration` = VALUES(`duration`), `start_time` = VALUES(`start_time`), `cluster` = VALUES(`cluster`), `first_blood_time` = VALUES(`first_blood_time`), `league_id` = VALUES(`league_id`), `series_id` = VALUES(`series_id`), `series_type` = VALUES(`series_type`);';
	
	$query_array['picks_bans']['raw_values'] = array();
	$query_array['picks_bans']['sql_start'] = 'INSERT INTO `picks_bans`(`match_id`, `order`, `team`, `is_pick`, `hero_id`) VALUES ';
	$query_array['picks_bans']['sql_end'] = ' ON DUPLICATE KEY UPDATE `match_id` = VALUES(`match_id`), `order` = VALUES(`order`), `team` = VALUES(`team`), `is_pick` = VALUES(`is_pick`), `hero_id` = VALUES(`hero_id`);';
	
	$query_array['players']['raw_values'] = array();
	$query_array['players']['sql_start'] = 'INSERT INTO `players`(`match_id`, `account_id`, `player_slot`, `hero_id`, `item_0`, `item_1`, `item_2`, `item_3`, `item_4`, `item_5`, `kills`, `deaths`, `assists`, `leaver_status`, `gold`, `last_hits`, `denies`, `gold_per_min`, `xp_per_min`, `gold_spent`, `hero_damage`, `tower_damage`, `hero_healing`, `level`) VALUES ';
	$query_array['players']['sql_end'] = ' ON DUPLICATE KEY UPDATE `match_id` = VALUES(`match_id`), `account_id` = VALUES(`account_id`), `player_slot` = VALUES(`player_slot`), `hero_id` = VALUES(`hero_id`), `item_0` = VALUES(`item_0`), `item_1` = VALUES(`item_1`), `item_2` = VALUES(`item_2`), `item_3` = VALUES(`item_3`), `item_4` = VALUES(`item_4`), `item_5` = VALUES(`item_5`), `kills` = VALUES(`kills`), `deaths` = VALUES(`deaths`), `assists` = VALUES(`assists`), `leaver_status` = VALUES(`leaver_status`), `gold` = VALUES(`gold`), `last_hits` = VALUES(`last_hits`), `denies` = VALUES(`denies`), `gold_per_min` = VALUES(`gold_per_min`), `xp_per_min` = VALUES(`xp_per_min`), `gold_spent` = VALUES(`gold_spent`), `hero_damage` = VALUES(`hero_damage`), `tower_damage` = VALUES(`tower_damage`), `hero_healing` = VALUES(`hero_healing`), `level` = VALUES(`level`);';
	
	$query_array['additional_units']['raw_values'] = array();
	$query_array['additional_units']['sql_start'] = 'INSERT INTO `additional_units`(`match_id`, `hero_id`, `unitname`, `item_0`, `item_1`, `item_2`, `item_3`, `item_4`, `item_5`) VALUES ';
	$query_array['additional_units']['sql_end'] = ' ON DUPLICATE KEY UPDATE `match_id` = VALUES(`match_id`), `hero_id` = VALUES(`hero_id`), `unitname` = VALUES(`unitname`), `item_0` = VALUES(`item_0`), `item_1` = VALUES(`item_1`), `item_2` = VALUES(`item_2`), `item_3` = VALUES(`item_3`), `item_4` = VALUES(`item_4`), `item_5` = VALUES(`item_5`);';
	
	$query_array['ability_upgrades']['raw_values'] = array();
	$query_array['ability_upgrades']['sql_start'] = 'INSERT INTO `ability_upgrades`(`match_id`, `hero_id`, `ability`, `time`, `level`) VALUES ';
	$query_array['ability_upgrades']['sql_end'] = ' ON DUPLICATE KEY UPDATE `match_id` = VALUES(`match_id`), `hero_id` = VALUES(`hero_id`), `ability` = VALUES(`ability`), `time` = VALUES(`time`), `level` = VALUES(`level`);';
	
	if(isset($match_history['result']['matches']) && !empty($match_history['result']['matches'])){
		foreach($match_history['result']['matches'] as $key => $value){
				//INSERT MATCH DETAILS INTO DB
				unset($match_stats);
				$match_stats = $value;
				
				if(!isset($match_stats['leagueid'])) $match_stats['leagueid'] = NULL;
				if(!isset($match_stats['series_id'])) $match_stats['series_id'] = NULL;
				if(!isset($match_stats['series_type'])) $match_stats['series_type'] = NULL;
		
				unset($match_stats['players']);
				unset($match_stats['picks_bans']);
				$match_stats = escape_array($db, $match_stats);
		
				if(isset($match_stats['match_id']) && isset($match_stats['match_seq_num']) && isset($match_stats['lobby_type']) && isset($match_stats['game_mode']) && isset($match_stats['radiant_win']) && isset($match_stats['duration']) && isset($match_stats['start_time']) && isset($match_stats['cluster']) && isset($match_stats['first_blood_time']) && isset($match_stats['leagueid']) && isset($match_stats['tower_status_radiant']) && isset($match_stats['tower_status_dire']) && isset($match_stats['barracks_status_radiant']) && isset($match_stats['barracks_status_dire']) && isset($match_stats['series_id']) && isset($match_stats['series_type'])){
					$query_array['matches']['raw_values'][] = sprintf("(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
							$match_stats['match_id'], 
							$match_stats['match_seq_num'], 
							$match_stats['lobby_type'], 
							$match_stats['game_mode'], 
							$match_stats['radiant_win'], 
							$match_stats['duration'], 
							$match_stats['start_time'], 
							$match_stats['cluster'], 
							$match_stats['first_blood_time'], 
							$match_stats['leagueid'], 
							$match_stats['tower_status_radiant'], 
							$match_stats['tower_status_dire'], 
							$match_stats['barracks_status_radiant'], 
							$match_stats['barracks_status_dire'], 
							$match_stats['series_id'], 
							$match_stats['series_type']
					);
				}
		
				//PICKS AND BANS
				if(isset($value['picks_bans'])){
					foreach($value['picks_bans'] as $key2 => $value2){
						//INSERT PICK BAN DETAILS INTO DB
						unset($match_stats);
						$match_stats = $value2;

						if(!isset($match_stats['is_pick'])) $match_stats['is_pick'] = 0; 

						if(isset($match_stats['order']) && isset($match_stats['team']) && isset($match_stats['hero_id'])){
							$match_stats = escape_array($db, $match_stats);

				
							$query_array['picks_bans']['raw_values'][] = sprintf("(%s, %s, %s, %s, %s)",
									$value['match_id'], 
									$match_stats['order'], 
									$match_stats['team'], 
									$match_stats['is_pick'], 
									$match_stats['hero_id']
							);
						}
					}
				}
		
				//PLAYER DETAILS
				foreach($value['players'] as $key2 => $value2){
					//ADDITIONAL UNITS
					if(isset($value2['additional_units'])){
						foreach($value2['additional_units'] as $key3 => $value3){
							//INSERT ADDITIONAL UNITS DETAILS INTO DB
							unset($match_stats);
							$match_stats = $value3;
							
							if(!isset($match_stats['unitname'])) $match_stats['unitname'] = 'Unknown'; 
							if(!isset($match_stats['item_0'])) $match_stats['item_0'] = 0; 
							if(!isset($match_stats['item_1'])) $match_stats['item_1'] = 0; 
							if(!isset($match_stats['item_2'])) $match_stats['item_2'] = 0; 
							if(!isset($match_stats['item_3'])) $match_stats['item_3'] = 0; 
							if(!isset($match_stats['item_4'])) $match_stats['item_4'] = 0; 
							if(!isset($match_stats['item_5'])) $match_stats['item_5'] = 0; 

							$match_stats = escape_array($db, $match_stats);
							
							//`match_id`, `hero_id`, `unitname`, `item_0`, `item_1`, `item_2`, `item_3`, `item_4`, `item_5`
							$query_array['additional_units']['raw_values'][] = sprintf("(%s, %s, %s, %s, %s, %s, %s, %s, %s)",
									$value['match_id'], 
									$value2['hero_id'], 
									$match_stats['unitname'], 
									$match_stats['item_0'], 
									$match_stats['item_1'], 
									$match_stats['item_2'], 
									$match_stats['item_3'], 
									$match_stats['item_4'], 
									$match_stats['item_5']
							);
						}
					}
		
					//ABILITY UPGRADES
					if(isset($value2['ability_upgrades'])){
						foreach($value2['ability_upgrades'] as $key3 => $value3){
							//INSERT ABILITY UPGRADES DETAILS INTO DB
							unset($match_stats);
							$match_stats = $value3;
		
							$match_stats = escape_array($db, $match_stats);
							
							//`match_id`, `hero_id`, `ability`, `time`, `level`
							$query_array['ability_upgrades']['raw_values'][] = sprintf("(%s, %s, %s, %s, %s)",
								$value['match_id'], $value2['hero_id'], $match_stats['ability'], $match_stats['time'], $match_stats['level']
							);
							
						}
					}
				
					//INSERT PLAYER DETAILS INTO DB
					unset($match_stats);
					$match_stats = $value2;
					
					if(!isset($match_stats['account_id']) || $match_stats['account_id'] == '4294967295') $match_stats['account_id'] = NULL;
					if(!isset($match_stats['hero_id'])) $match_stats['hero_id'] = NULL;
					if(!isset($match_stats['leaver_status'])) $match_stats['leaver_status'] = 0;

					if(!isset($match_stats['item_0'])) $match_stats['item_0'] = 0;
					if(!isset($match_stats['item_1'])) $match_stats['item_1'] = 0;
					if(!isset($match_stats['item_2'])) $match_stats['item_2'] = 0;
					if(!isset($match_stats['item_3'])) $match_stats['item_3'] = 0;
					if(!isset($match_stats['item_4'])) $match_stats['item_4'] = 0;
					if(!isset($match_stats['item_5'])) $match_stats['item_5'] = 0;

					if(!isset($match_stats['kills'])) $match_stats['kills'] = 0;
					if(!isset($match_stats['deaths'])) $match_stats['deaths'] = 0;
					if(!isset($match_stats['assists'])) $match_stats['assists'] = 0;
					if(!isset($match_stats['gold'])) $match_stats['gold'] = 0;
					if(!isset($match_stats['last_hits'])) $match_stats['last_hits'] = 0;
					if(!isset($match_stats['denies'])) $match_stats['denies'] = 0;
					if(!isset($match_stats['gold_per_min'])) $match_stats['gold_per_min'] = 0;
					if(!isset($match_stats['xp_per_min'])) $match_stats['xp_per_min'] = 0;
					if(!isset($match_stats['gold_spent'])) $match_stats['gold_spent'] = 0;
					if(!isset($match_stats['hero_damage'])) $match_stats['hero_damage'] = 0;
					if(!isset($match_stats['tower_damage'])) $match_stats['tower_damage'] = 0;
					if(!isset($match_stats['hero_healing'])) $match_stats['hero_healing'] = 0;
					if(!isset($match_stats['level'])) $match_stats['level'] = 0;
		
					unset($match_stats['additional_units']);
					unset($match_stats['ability_upgrades']);
					$match_stats = escape_array($db, $match_stats);
					
					//`match_id`, `account_id`, `player_slot`, `hero_id`, `item_0`, `item_1`, `item_2`, `item_3`, `item_4`, `item_5`, `kills`, `deaths`, `assists`, `leaver_status`, `gold`, `last_hits`, `denies`, `gold_per_min`, `xp_per_min`, `gold_spent`, `hero_damage`, `tower_damage`, `hero_healing`, `level`
					$query_array['players']['raw_values'][] = sprintf("(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
						$value['match_id'], 
						$match_stats['account_id'], 
						$match_stats['player_slot'], 
						$match_stats['hero_id'], 
						$match_stats['item_0'], 
						$match_stats['item_1'], 
						$match_stats['item_2'], 
						$match_stats['item_3'], 
						$match_stats['item_4'], 
						$match_stats['item_5'], 
						$match_stats['kills'], 
						$match_stats['deaths'], 
						$match_stats['assists'], 
						$match_stats['leaver_status'], 
						$match_stats['gold'], 
						$match_stats['last_hits'], 
						$match_stats['denies'], 
						$match_stats['gold_per_min'], 
						$match_stats['xp_per_min'], 
						$match_stats['gold_spent'], 
						$match_stats['hero_damage'], 
						$match_stats['tower_damage'], 
						$match_stats['hero_healing'], 
						$match_stats['level']
					);
				}
		
				/////////////
				//END LOOP
				////////////
		
				$query_array = check_then_send_query($db, $query_array, $max_query_size, true, false, false);
		}
		$query_array = check_then_send_query($db, $query_array, 0, true, false, false);
		
		if($log){
			return $table;
		}
		else{
			return true;
		}
	}
	else{
		return false;
	}
}
catch (Exception $e){
//	echo $e->getMessage();
	System_Daemon::info('{appName} Failure: %s',
		$e->getMessage()
	);
}
}
}

if (!function_exists("seq_starting_point")) {
function seq_starting_point(){
	global $db;
	
	$starting_seq = $db->q('SELECT MAX(match_seq_num) AS match_seq_num FROM `matches`');
	if($starting_seq){
		$starting_seq = $starting_seq[0]['match_seq_num'] > '373670012'
			? $starting_seq[0]['match_seq_num']
				//: '320641385'; //random game from a few months ago I was in
				: '373670012'; //3spirits released

		return $starting_seq;
	}
	else{
		return false;
	}
}
}

if (!function_exists("grab_matches_by_seq")) {
function grab_matches_by_seq($starting_seq, $api_key, $time_to_store_secs = 600, $games_to_grab = 100){
	global $memcache;

	$match_history = $memcache->get("d2_seq_match_hist".$starting_seq);
	if(!$match_history){
		$match_history = GetMatchHistoryBySequenceNum($starting_seq, 100, false, $api_key); //197748708
		
		if($match_history){
			$memcache->set("d2_seq_match_hist".$starting_seq, $match_history, 0, $time_to_store_secs);
		}
	}

	return $match_history;
}
}

if (!function_exists("grab_heroes")) {
function grab_heroes($api_key, $time_to_store_secs = 600){
	global $memcache;

	$heroes = $memcache->get("d2_heroes");
	if(!$heroes){
		$heroes = GetHeroes(false, $api_key);
		
		if($heroes){
			$memcache->set("d2_heroes", $heroes, 0, $time_to_store_secs);
		}
	}

	return $heroes;
}
}

//OUTPUTS
// array('seq_start', 'seq_end')
if (!function_exists("seq_starting_point_v2")) {
function seq_starting_point_v2($parser_name){
	global $db;

	$test = $db->q("SELECT job_id, seq_start, seq_end, seq_current FROM parser_manager WHERE started = '1' AND completed = '0' AND parser = ? ORDER BY priority DESC, seq_start ASC LIMIT 0,1",
						's',
						$parser_name);
	if(empty($test)){
		$test = $db->q("SELECT job_id, seq_start, seq_end, seq_current FROM parser_manager WHERE started = '0' AND completed = '0' ORDER BY priority DESC, seq_start ASC LIMIT 0,1");
	}
	
	if(!empty($test)){
		$test = $test[0];
		if(isset($test['seq_current']) && ($test['seq_current'] > $test['seq_start'])){
			$test['seq_start'] = $test['seq_current'];
		}
		unset($test['seq_current']);

		return $test;
	}
	
	return false;
}
}

if (!function_exists("array_to_table")) {
function array_to_table($array, $offset_rows = true){
	if(is_array($array)){
		$table = '<table border=1>';
		foreach($array as $key1 => $value){
			if($key1 == 0){
				$table .= '<tr>';
				$table .= '<td>&nbsp;</td>';
				foreach($value as $key2 => $value2){
					$table .= '<th>' . $key2 . '</th>';
				}
				$table .= '</tr>';
			}
	
			$table .= '<tr>';
			$table .= $offset_rows
						? '<td>'. ($key1 + 1) .'</td>'
							: '<td>'. $key1 .'</td>';

			foreach($value as $key2 => $value2){
				if(!is_array($value2)){ 
					$table .= '<td>' . $value2 . '</td>';
				}
				else{
					$table .= '<td>!!'.$key2.'!!</td>';
				}
			}
			$table .= '</tr>';
		}
		$table .= '</table>';

		return $table;
	}
	else{
		return 'Not passed an array!!!!';
	}
}
}

if (!function_exists("simple_cached_query")) {
function simple_cached_query($memcached_name, $sql = '', $cache_time_secs = 600){
	global $memcache, $db;
	
	$variable = $memcache->get($memcached_name);
	if(!$variable){
		if($sql){
			$variable = $db->q($sql);
			$memcache->set($memcached_name, $variable, 0, $cache_time_secs);
		}
		else{
			return 'No sql provided!!!';
		}
	}
	return $variable;
}
}

if (!function_exists("secs_to_h")) {
function secs_to_h($secs)
{
        $units = array(
                "week"   => 7*24*3600,
                "day"    =>   24*3600,
                "hour"   =>      3600,
                "minute" =>        60,
                "second" =>         1,
        );

	// specifically handle zero
        if ( $secs == 0 ) return "0 seconds";

        $s = "";

        foreach ( $units as $name => $divisor ) {
                if ( $quot = intval($secs / $divisor) ) {
                        $s .= "$quot $name";
                        $s .= (abs($quot) > 1 ? "s" : "") . ", ";
                        $secs -= $quot * $divisor;
                }
        }

        return substr($s, 0, -2);
}
}

if (!function_exists("generate_header")) {
function generate_header($match_db_details){
	$header = '<div id="header">';
	////////////////////////////////////////////////////
	if(!empty($match_db_details)){

		$header .= '<div id="player_db_summary"><span class="header4">Player DB Details</span>';
		$header .= '<table>';
		$header .= '<tr>
						<th>Players</th>
						<td>' . number_format($match_db_details['player_count_total']) . '</td>
					</tr>';
		$header .= '<tr>
						<th>Anon</th>
						<td>' . number_format($match_db_details['player_count_total'] - $match_db_details['player_count_registered']) . '</td>
					</tr>';
		$header .= '<tr>
						<th>Registered</th>
						<td>' . number_format($match_db_details['player_count_registered']) . '</td>
					</tr>';
		$header .= '<tr>
						<th>Distinct</th>
						<td>' . number_format($match_db_details['player_count_distinct']) . '</td>
					</tr>';
		$header .= '<tr>
						<th>Theoretical</th>
						<td><em>' . number_format(floor(($match_db_details['player_count_distinct'] / $match_db_details['player_count_registered']) * ($match_db_details['player_count_total'] - $match_db_details['player_count_registered']))) . '</em></td>
					</tr>';
		$header .= '</table>';
		$header .= '</div>';
		
	////////////////////////////////////////////////////
		
		$header .= '<div id="match_db_summary"><span class="header4">Match DB Details</span>';
		$header .= '<table>';
		$header .= '<tr>
						<th>Matches</th>
						<td colspan="3">' . number_format($match_db_details['match_count']) . '</td>
					</tr>';
		$header .= '<tr>
						<th>Heroes</th>
						<td colspan="3">' . number_format($match_db_details['heroes_played']) . '</td>
					</tr>';
		$header .= '<tr>
						<th colspan="3">Date</th>
						<th>Match ID</th>
					</tr>';
		$header .= '<tr>
						<th>Oldest</th>
						<td>' . relative_time($match_db_details['date_oldest']) . '</td>
						<td>' . gmdate("d/m/Y H:i:s", $match_db_details['date_oldest']) . '</td>
						<td>' . $match_db_details['match_oldest'] . '</td>
					</tr>';
		$header .= '<tr>
						<th>Newest</th>
						<td>' . relative_time($match_db_details['date_recent']) . '</td>
						<td>' . gmdate("d/m/Y H:i:s", $match_db_details['date_recent']) . '</td>
						<td>'.$match_db_details['match_recent'].'</td>
					</tr>';
		$header .= '</table>';
		$header .= '</div>';
	}
	else{
		$header .= '<div id="player_db_summary"><span class="header4">Player DB Details</span><br />';
		$header .= 'No data!';
		$header .= '</div>';
		$header .= '<div id="match_db_summary"><span class="header4">Match DB Details</span><br />';
		$header .= 'No data!';
		$header .= '</div>';
	}

	//////////
	$header .= '</div>';
	
	return $header;
}
}

if (!function_exists("get_match_db_details")) {
function get_match_db_details($db){
	if($db){
		$match_db_details = simple_cached_query('d2_player_match_db_details', 
				"SELECT 
					`match_count`, 
					`player_count_total`, 
					`player_count_registered`, 
					`player_count_distinct`, 
					`heroes_played`, 
					`date_oldest`, 
					`match_oldest`, 
					`date_recent`, 
					`match_recent` 
				FROM `q2_player_match_db_details` 
				ORDER BY `date_added` DESC
				LIMIT 0,1", 
			10);
		$match_db_details = $match_db_details[0];

		return $match_db_details;
	}
	else{
		return false;
	}
}
}

?>