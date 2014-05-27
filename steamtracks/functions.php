<?php
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

if(!class_exists('user')){
class user 
{
	public $apikey;
	public $domain;

	public function GetPlayerSummaries ($steamid)
	{
		$response = file_get_contents('http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=' . $this->apikey . '&steamids=' . $steamid);
		$json = json_decode($response);
		return $json->response->players[0];
	}

	public function signIn ($relocate = NULL)
	{
		require_once './openid.php';
		$openid = new LightOpenID($this->domain);// put your domain
		if(!$openid->mode)
		{
			$openid->identity = 'http://steamcommunity.com/openid';
			header('Location: ' . $openid->authUrl());
		}
		elseif($openid->mode == 'cancel')
		{
			print ('User has canceled authentication!');
		}
		else
		{
			if($openid->validate())
			{
				preg_match("/^http:\/\/steamcommunity\.com\/openid\/id\/(7[0-9]{15,25}+)$/", $openid->identity, $matches); // steamID: $matches[1]
				//setcookie('steamID', $matches[1], time()+(60*60*24*7), '/'); // 1 week
				$_SESSION['user_id'] = $matches[1];
				$_SESSION['user_details'] = $this->GetPlayerSummaries($_SESSION['user_id']);
				
				if($relocate){
					header('Location: '.$relocate);
				}
				else{
					header('Location: ./');
				}
				exit;
			}
			else
			{
				print ('fail');
			}
		}
	}
	
	public function signOut ($relocate = NULL)
	{
		unset($_SESSION['user_id']);
		unset($_SESSION['user_details']);

		if($relocate){
			header('Location: '.$relocate);
		}
		else{
			header('Location: ./');
		}
	}
}
}

if (!function_exists("convert_id")) {
function convert_steamid($id, $required_output = '32'){
	if(empty($id)) return false;
	
    if(strlen($id) === 17 && $required_output == '32'){
        $converted = substr($id, 3) - 61197960265728;
    }
    else if(strlen($id) != 17 && $required_output == '64'){
        $converted = '765'.($id + 61197960265728);
    }
 
    return (string) $converted;
}
}


if(!class_exists('SteamID')){
class SteamID
{
	private $steamID32 = '';

	private $steamID64 = '';

	public function __construct($steam_id)
	{
		if(empty($steam_id))
		{
			$this->steamID32 = $this->steamID64 = '';
		}
		elseif(ctype_digit($steam_id))
		{
			$this->steamID64 = $steam_id;
			$this->steamID32 = $this->convert64to32($steam_id);
		}
		elseif(preg_match('/^STEAM_0:[01]:[0-9]+/', $steam_id))
		{
			$this->steamID32 = $steam_id;
			$this->steamID64 = $this->convert32to64($steam_id);
		}
		else
		{
			throw new RuntimeException('Invalid data provided; data is not a valid steamid32 or steamid64');
		}
	}

	private function convert32to64($steam_id)
	{
		list( , $m1, $m2) = explode(':', $steam_id, 3);
		list($steam_cid, ) = explode('.', bcadd((((int) $m2 * 2) + $m1), '76561197960265728'), 2);
		return $steam_cid;
	}

	private function convert64to32($steam_cid)
	{
		$id = array('STEAM_0');
		$id[1] = substr($steam_cid, -1, 1) % 2 == 0 ? 0 : 1;
		$id[2] = bcsub($steam_cid, '76561197960265728');
		if(bccomp($id[2], '0') != 1)
		{
			return false;
		}
		$id[2] = bcsub($id[2], $id[1]);
		list($id[2], ) = explode('.', bcdiv($id[2], 2), 2);
		return implode(':', $id);
	}

	public function getSteamID32()
	{
		return $this->steamID32;
	}

	public function getSteamID64()
	{
		return $this->steamID64;
	}
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

if(!class_exists('steamtracks')){
class steamtracks{
	private $steamtracks_api_key;
	private $steamtracks_api_secret;
	private $verify_ssl;
	private $debug;
	
	private $api_url = "https://steamtracks.com/api/v1/";
	
	function __construct($steamtracks_api_key, $steamtracks_api_secret, $verify_ssl = FALSE, $debug = FALSE){
		$this->steamtracks_api_key = $steamtracks_api_key;
		$this->steamtracks_api_secret = $steamtracks_api_secret;
		$this->verify_ssl = $verify_ssl;
		$this->debug = $debug;
	}
	
	//request is either GET or POST
	private function curl_do($request_type, $method, $data_string){
		$api_url = $this->api_url . $method;
		if($request_type == 'GET'){
			$api_url .= '?payload=' . urlencode($data_string);
		}

		$api_sig = urlencode(base64_encode(hash_hmac('sha1', $data_string, $this->steamtracks_api_secret, 1)));

		if($this->debug){
			echo 'API url: '.$api_url.'<br />';
			echo 'Payload: '.$data_string.'<br />';
		}

		//SET THE REQUIRED HEADERS
		$headers = array( 
			"SteamTracks-Key: " . $this->steamtracks_api_key,
			"SteamTracks-Signature: " . $api_sig,
			"ACCEPT: application/json",
			"Content-Type: application/json" 
		);
		
		//SET THE REQUIRED CURL OPTIONS
		$ch = curl_init(); 
		curl_setopt($ch, CURLOPT_URL, $api_url); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		curl_setopt($ch, CURLOPT_TIMEOUT, 60); 
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
		curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
		
		//DONT CHECK SSL CERT BY DEFAULT
		if(!$this->verify_ssl){ 
			curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0); 
			curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
		}
		
		//HANDLE THE POST FIELDS
		if($request_type == 'POST'){
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
		}
		
		//SEND OFF THE CURL REQUEST
		$data = curl_exec($ch); 
		
		//CATCH ERROR OR RETURN ARRAY
		if (curl_errno($ch)) { 
			$data = "Error: " . curl_error($ch); 
		}
		else{
			$data = json_decode($data, 1);
		}
	
		curl_close($ch);
		
		return $data;
	}
	
	function notify($message, $to = NULL, $broadcast = FALSE, $exclude_offline = FALSE){
		if(!empty($to) || !empty($broadcast)){
			$parameters = array();
			$parameters['t'] = time();
			if(!empty($to)) $parameters['to'] = $to;
			if(!empty($broadcast)) $parameters['broadcast'] = (string) $broadcast;
			if(!empty($exclude_offline)) $parameters['exclude_offline'] = (string) $exclude_offline;
			
			$data = $this->curl_do('GET', 'signup/token', json_encode($parameters));
		}
		else{
			$data = 'Must have either a "to" set or "brodcast" set to true.<br />';
		}
		return $data;
	}
	
	function signup_token($steamid32 = NULL, $return_steamid32 = FALSE){
		$parameters = array();
		$parameters['t'] = time();
		if(!empty($steamid32)) $parameters['steamid32'] = $steamid32;
		if(!empty($return_steamid32)) $parameters['return_steamid32'] = (string) $return_steamid32;
		
		$data = $this->curl_do('GET', 'signup/token', json_encode($parameters));
		return $data;
	}
	
	function signup_status($token){
		if(!empty($token)){
			$parameters = array();
			$parameters['t'] = time();
			$parameters['token'] = $token;
	
			$data = $this->curl_do('GET', 'signup/status', json_encode($parameters));
		}
		else{
			$data = 'No token given!<br />';
		}
		return $data;
	}
	
	function signup_ack($token, $user = NULL){
		if(!empty($token)){
			$parameters = array();
			$parameters['t'] = time();
			$parameters['token'] = $token;
			if(!empty($user)) $parameters['user'] = (string) $user;
	
			$data = $this->curl_do('POST', 'signup/ack', json_encode($parameters));
		}
		else{
			$data = 'No token given!<br />';
		}
		return $data;
	}

	function users($page = NULL){
		$parameters = array();
		$parameters['t'] = time();
		if(!empty($page)) $parameters['page'] = (string) $page;

		$data = $this->curl_do('GET', 'users', json_encode($parameters));
		return $data;
	}

	function users_count(){
		$parameters = array();
		$parameters['t'] = time();

		$data = $this->curl_do('GET', 'users/count', json_encode($parameters));
		return $data;
	}
	
	function users_info($user){
		if(!empty($user)){
			$parameters = array();
			$parameters['t'] = time();
			$parameters['user'] = (string) $user;
	
			$data = $this->curl_do('GET', 'users/info', json_encode($parameters));
		}
		else{
			$data = 'No user given!<br />';
		}
		return $data;
	}
	
	function users_states(){
		$parameters = array();
		$parameters['t'] = time();
	
		$data = $this->curl_do('GET', 'users/states', json_encode($parameters));
		return $data;
	}
	
	function users_games(){
		$parameters = array();
		$parameters['t'] = time();
	
		$data = $this->curl_do('GET', 'users/games', json_encode($parameters));
		return $data;
	}
	
	function users_leavers(){
		$parameters = array();
		$parameters['t'] = time();
	
		$data = $this->curl_do('GET', 'users/leavers', json_encode($parameters));
		return $data;
	}
	
	function users_flushleavers(){
		$parameters = array();
		$parameters['t'] = time();
	
		$data = $this->curl_do('POST', 'users/flushleavers', json_encode($parameters));
		return $data;
	}
	
	function users_changes($from_timestamp = NULL, $fields = array()){
		$parameters = array();
		$parameters['t'] = time();
		if(!empty($from_timestamp)) $parameters['from_timestamp'] = (string) $from_timestamp;
		if(!empty($fields)) $parameters['fields'] = $fields;
	
		$data = $this->curl_do('GET', 'users/changes', json_encode($parameters));
		return $data;
	}
}
}

if (!function_exists("steamtracks_curl")) {
function steamtracks_curl($method, $request_type = 'GET', $parameters = array(), $verify_ssl = FALSE, $debug = FALSE){
	//WE HAVE TO TURN SSL VERIFICATION OFF BECAUSE FOR SOME REASON THE SSL ON THE API DOES NOT MATCH
	
	global $steamtracks_api_key;
	global $steamtracks_api_secret;
	
	$url = "https://steamtracks.com/api/v1/"; 
	$api_url = $url . $method;
	
	//WE REQUIRE A CHANGING BASE FOR OUR SIGNATURE
	$parameters['t'] = time();
	
	//TURN ARRAY INTO JSON STRING
	$data_string = json_encode($parameters);
	
	//WE NEED A API SIGNATURE
	$api_sig = urlencode(base64_encode(hash_hmac('sha1', $data_string, $steamtracks_api_secret, 1)));
	
	//CONSTRUCT THE FINAL URL
	if($request_type == 'GET'){
		$api_url = $api_url . '?payload=' . urlencode($data_string);
	}
	
	if($debug){
		echo 'API url: '.$api_url.'<br />';
		echo 'Payload: '.$data_string.'<br />';
	}
	
	//SET THE REQUIRED HEADERS
	$headers = array( 
		"SteamTracks-Key: " . $steamtracks_api_key,
		"SteamTracks-Signature: " . $api_sig,
		"ACCEPT: application/json",
		"Content-Type: application/json" 
	); 
	
	//SET THE REQUIRED CURL OPTIONS
	$ch = curl_init(); 
	curl_setopt($ch, CURLOPT_URL, $api_url); 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
	curl_setopt($ch, CURLOPT_TIMEOUT, 60); 
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
	curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
	
	//DONT CHECK SSL CERT BY DEFAULT
	if(!$verify_ssl){ 
		curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0); 
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
	}
	
	//HANDLE THE POST FIELDS
	if($request_type == 'POST'){
		curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
	}
	
	//SEND OFF THE CURL REQUEST
	$data = curl_exec($ch); 
	
	//CATCH ERROR OR RETURN ARRAY
	if (curl_errno($ch)) { 
		$data = "Error: " . curl_error($ch); 
	}
	else{
		$data = json_decode($data, 1);
	}

	curl_close($ch);
	
	return $data;
}
}
?>