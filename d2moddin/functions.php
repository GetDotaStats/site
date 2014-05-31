<?php
if (!class_exists("dbWrapper")) {
    Class dbWrapper
    {
        protected $_mysqli;
        protected $_debug;
        public $row_cnt;
        public $row_cnt_affected;

        public function __construct($host, $username, $password, $database, $debug)
        {
            $this->_mysqli = new mysqli($host, $username, $password, $database);
            $this->_debug = (bool)$debug;
            if (mysqli_connect_errno()) {
                if ($this->_debug) {
                    echo mysqli_connect_error();
                    debug_print_backtrace();
                }
                return false;
            }
            return true;
        }

        public function escape($query)
        {
            return $this->_mysqli->real_escape_string($query);
        }

        public function multi_query($query)
        {
            if (is_array($query)) {
                $exploded = implode(';', $query);
            } else {
                $exploded = $query;
            }

            if ($query = $this->_mysqli->multi_query($exploded)) {
                $i = 0;
                do {
                    $i++;
                } while ($this->_mysqli->more_results() && $this->_mysqli->next_result());
            }

            if ($this->_mysqli->errno) {
                if ($this->_debug) {
                    echo mysqli_error($this->_mysqli);
                    debug_print_backtrace();
                }
                return false;
            } else {
                return true;
            }
        }

        public function q($query)
        {
            if ($query = $this->_mysqli->prepare($query)) {
                if (func_num_args() > 1) {
                    $x = func_get_args(); //grab all of the arguments
                    $args = array_merge(array(func_get_arg(1)),
                        array_slice($x, 2)); //filter out the query part, leaving the type declaration and parameters
                    $args_ref = array();
                    foreach ($args as $k => &$arg) { //not sure what this step is doing
                        $args_ref[$k] = & $arg;
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
                    $params[] = & $row[$field->name];
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

                $this->row_cnt = $query->num_rows; //num rows
                $this->row_cnt_affected = $query->affected_rows; //affected rows

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

        public function handle()
        {
            return $this->_mysqli;
        }

        public function last_index()
        {
            return $this->_mysqli->insert_id;
        }
    }
}

if (!function_exists("curl")) {
    function curl($link, $postfields = '', $cookie = '', $refer = '', $user_agent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1')
    {
        $ch = curl_init($link);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        if ($refer) {
            curl_setopt($ch, CURLOPT_REFERER, $refer);
        }
        if ($postfields) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
        }
        if ($cookie) {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
        }
        $page = curl_exec($ch);
        curl_close($ch);
        return $page;
    }
}

if (!function_exists("cut_str")) {
    function cut_str($str, $left, $right)
    {
        $str = substr(stristr($str, $left), strlen($left));
        $leftLen = strlen(stristr($str, $right));
        $leftLen = $leftLen ? -($leftLen) : strlen($str);
        $str = substr($str, 0, $leftLen);

        return $str;
    }
}

//GIVEN A UNIX TIMESTAMP RETURNS A RELATIVE DISTANCE TO DATE (23.4 days ago)
//PUTTING ANY VALUE IN 2ND VARIABLE MAKES IT RETURN RAW HOURS APART
if (!function_exists('relative_time')) {
    function relative_time($time, $output = 'default')
    {
        if (!is_numeric($time)) {
            if (strtotime($time)) {
                $time = strtotime($time);
            } else {
                return FALSE;
            }
        }

        if ($output == 'default') {
            if ((time() - $time) >= 2592000) {
                $time_adj = round(((time() - $time) / 2592000), 1) . ' months ago';
            } else if ((time() - $time) >= 86400) {
                $time_adj = round(((time() - $time) / 86400), 1) . ' days ago';
            } else if ((time() - $time) >= 3600) {
                $time_adj = round(((time() - $time) / 3600), 1) . ' hours ago';
            } else {
                $time_adj = round(((time() - $time) / 60), 0) . ' mins ago';
            }
        } else {
            $time_adj = round(((time() - $time) / 3600), 1);
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