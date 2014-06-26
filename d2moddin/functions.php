<?php
if (!class_exists("dbWrapper")) {
    Class dbWrapper
    {
        protected $_mysqli;
        protected $_debug;

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

        public function q($query)
        {
            if ($query = $this->_mysqli->prepare($query)) {
                if (func_num_args() > 1) {
                    $x = func_get_args();
                    $args = array_merge(array(func_get_arg(1)),
                        array_slice($x, 2));
                    $args_ref = array();
                    foreach ($args as $k => &$arg) {
                        $args_ref[$k] = & $arg;
                    }
                    call_user_func_array(array($query, 'bind_param'), $args_ref);
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
    function curl($link, $postfields = '', $cookie = '', $refer = '', $user_agent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1', $timeout = false)
    {
        empty($user_agent)
            ? $user_agent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1'
            : null;

        $ch = curl_init($link);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        if($timeout){
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , $timeout);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout); //timeout in seconds
        }
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

        if(!$page){
            $page = false;
        }

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