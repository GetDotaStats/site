<?php
//////////////////////
//Common Variables
//////////////////////

$CDN_generic = '//getdotastats.com';
$CDN_image = '//getdotastats.com';

//////////////////////
// javascript
//////////////////////

$path_lib_jQuery = '/bootstrap/js/';
$path_lib_jQuery_name = 'jquery-1-3-2.min.js?20';
$path_lib_jQuery_full = $CDN_generic . $path_lib_jQuery . $path_lib_jQuery_name;

$path_lib_jQuery2 = '/bootstrap/js/';
$path_lib_jQuery2_name = 'jquery-1-11-0.min.js?20';
$path_lib_jQuery2_full = $CDN_generic . $path_lib_jQuery2 . $path_lib_jQuery2_name;

$path_lib_jQuery3 = '/bootstrap/js/';
$path_lib_jQuery3_name = 'jquery-2-1-3.min.js?1';
$path_lib_jQuery3_full = $CDN_generic . $path_lib_jQuery3 . $path_lib_jQuery3_name;

$path_lib_bootstrap = '/bootstrap/js/';
$path_lib_bootstrap_name = 'bootstrap.min.js?20';
$path_lib_bootstrap_full = $CDN_generic . $path_lib_bootstrap . $path_lib_bootstrap_name;

$path_lib_respondJS = '/bootstrap/js/';
$path_lib_respondJS_name = 'respond-1-4-2.min.js?20';
$path_lib_respondJS_full = $CDN_generic . $path_lib_respondJS . $path_lib_respondJS_name;

$path_lib_html5shivJS = '/bootstrap/js/';
$path_lib_html5shivJS_name = 'html5shiv-3-7-0.js?20';
$path_lib_html5shivJS_full = $CDN_generic . $path_lib_html5shivJS . $path_lib_html5shivJS_name;

$path_lib_siteJS = '/';
$path_lib_siteJS_name = 'getdotastats.js?38';
$path_lib_siteJS_full = $CDN_generic . $path_lib_siteJS . $path_lib_siteJS_name;
//$path_lib_siteJS_full = '.' . $path_lib_siteJS . $path_lib_siteJS_name;

$path_lib_highcharts = '/bootstrap/js/';
$path_lib_highcharts_name = 'highcharts-4-1-8.min.js?1';
$path_lib_highcharts_full = $CDN_generic . $path_lib_highcharts . $path_lib_highcharts_name;

//////////////////////
// CSS
//////////////////////

$path_css_site = '/';
$path_css_site_name = 'getdotastats.min.css?51';
//$path_css_site_name = 'getdotastats.css?51';
$path_css_site_full = $CDN_generic . $path_css_site . $path_css_site_name;
//$path_css_site_full = '.' . $path_css_site . $path_css_site_name;

$path_css_bootstrap = '/bootstrap/css/';
$path_css_bootstrap_name = 'bootstrap.min.css?1';
$path_css_bootstrap_full = '.' . $path_css_bootstrap . $path_css_bootstrap_name;

//////////////////////

if (!function_exists('exceptions_error_handler')) {
    function exceptions_error_handler($severity, $message, $filename, $lineno)
    {
        if (0 == error_reporting()) {
            // Error reporting is currently turned off or suppressed with @
            return;
        } else {
            throw new ErrorException($message, 0, $severity, $filename, $lineno);
        }
    }
}

set_error_handler('exceptions_error_handler');

if (!class_exists('Cache')) {
    class Cache
    {
        private $id;
        private $obj;
        private $memcache;

        function __construct($host = 'localhost', $port = 11211, $useMemcache = false, $id = NULL)
        {
            $host = empty($host) ? 'localhost' : $host;
            $port = empty($port) ? 11211 : $port;

            $this->id = $id;
            $this->memcache = $useMemcache;

            if ($this->memcache) {
                $this->obj = new Memcache();
            } else {
                $this->obj = new Memcached($id);
            }

            return $this->connect($host, $port);
        }

        public function connect($host, $port)
        {
            if ($this->memcache) {
                return $this->obj->addServer($host, $port);
            } else {
                $servers = $this->obj->getServerList();
                if (is_array($servers)) {
                    foreach ($servers as $server)
                        if ($server['host'] == $host and $server['port'] == $port)
                            return true;
                }
                return $this->obj->addServer($host, $port);
            }
        }

        public function clear_servers()
        {
            if (!$this->memcache) {
                return $this->obj->resetServerList();
            }
            return false;
        }

        public function close()
        {
            if ($this->memcache) {
                return $this->obj->close();
            } else {
                //we don't need to close the connection if we are just gonna open them again immediately
                //return $this->obj->quit();
                return true;
            }
        }

        public function get($cache_name)
        {
            return $this->obj->get($cache_name);
        }

        public function set($cache_name, $cache_value, $cache_expiration_secs = 15)
        {
            if ($this->memcache) {
                return $this->obj->set($cache_name, $cache_value, 0, $cache_expiration_secs);
            } else {
                return $this->obj->set($cache_name, $cache_value, $cache_expiration_secs);
            }
        }

        public function delete($cache_name, $cache_refusal_secs = 0)
        {
            if ($this->memcache) {
                return $this->obj->delete($cache_name);
            } else {
                return $this->obj->delete($cache_name, $cache_refusal_secs);
            }
        }

        public function replace($cache_name, $cache_value, $cache_expiration_secs = 15)
        {
            if ($this->memcache) {
                return $this->obj->replace($cache_name, $cache_value, 0, $cache_expiration_secs);
            } else {
                return $this->obj->replace($cache_name, $cache_value, $cache_expiration_secs);
            }
        }

        public function touch($cache_name, $cache_expiration_secs = 15)
        {
            if ($this->memcache) {
                $cache_value = $this->obj->get($cache_name);
                return $this->obj->set($cache_name, $cache_value, 0, $cache_expiration_secs);
            } else {
                return $this->obj->touch($cache_name, $cache_expiration_secs);
            }
        }

        public function decrement($cache_name, $cache_offset = 1, $cache_initial_value = 0, $cache_expiration_secs = 15)
        {
            if ($this->memcache) {
                return $this->obj->decrement($cache_name, $cache_offset);
            } else {
                return $this->obj->decrement($cache_name, $cache_offset, $cache_initial_value, $cache_expiration_secs);
            }
        }

        public function increment($cache_name, $cache_offset = 1, $cache_initial_value = 0, $cache_expiration_secs = 15)
        {
            if ($this->memcache) {
                return $this->obj->increment($cache_name, $cache_offset);
            } else {
                return $this->obj->increment($cache_name, $cache_offset, $cache_initial_value, $cache_expiration_secs);
            }
        }

        public function flush($delay = 0)
        {
            if ($this->memcache) {
                return $this->obj->flush();
            } else {
                return $this->obj->flush($delay);
            }
        }
    }
}

if (!function_exists('getallheaders')) {
    function getallheaders()
    {
        if (!is_array($_SERVER)) {
            return array();
        }

        $headers = array();
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

if (!class_exists("dbWrapper_v3")) {
    Class dbWrapper_v3
    {
        protected $_mysqli;
        protected $_debug;

        public function __construct($host, $username, $password, $database, $debug = true)
        {
            $this->_mysqli = new mysqli($host, $username, $password, $database);
            $this->_debug = (bool)$debug;
            if (mysqli_connect_errno()) {
                if ($this->_debug) {
                    throw new Exception(mysqli_connect_error());
                }
                return false;
            }
            $this->q('SET NAMES utf8;');
            return true;
        }

        public function __destruct()
        {
            $this->close();
        }

        public function escape($query)
        {
            return $this->_mysqli->real_escape_string($query);
        }

        public function ping()
        {
            if ($this->_mysqli->ping()) {
                return true;
            } else {
                throw new Exception($this->_mysqli->error);
            }
        }

        public function q($query)
        {
            if ($query = $this->_mysqli->prepare($query)) {
                if (func_num_args() > 1) {
                    $x = func_get_args();

                    if (!is_array($x[2])) {
                        $args = array_merge(array(func_get_arg(1)),
                            array_slice($x, 2));
                        $args_ref = array();
                        foreach ($args as $k => &$arg) {
                            $args_ref[$k] = &$arg;
                        }
                    } else {
                        $args_ref = array();
                        $args_ref[] = func_get_arg(1);
                        foreach ($x[2] as $k => &$arg) {
                            $args_ref[] = &$arg;
                        }
                    }

                    call_user_func_array(array($query, 'bind_param'), $args_ref);
                }
                $query->execute();

                if ($query->errno) {
                    if ($this->_debug) {
                        throw new Exception($query->error);
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
                $query->close();
                return $result;
            } else {
                if ($this->_debug) {
                    //echo $this->_mysqli->error;
                    //debug_print_backtrace();
                    throw new Exception($this->_mysqli->error);
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

        public function affected_rows()
        {
            return $this->_mysqli->affected_rows;
        }

        public function close()
        {
            if (!empty($this->_mysqli)) {
                if (gettype($this->_mysqli == 'object')) {
                    $this->_mysqli->close();
                } else {
                    throw new Exception("Couldn't close DB cleanly! Not a object!");
                }
            } else {
                throw new Exception("Couldn't close DB cleanly! Empty!");
            }
        }
    }
}

if (!function_exists("curl")) {
    function curl($link, $postfields = '', $cookie = '', $refer = '', $user_agent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1', $timeoutConnect = false, $timeoutExecute = false)
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
        if ($timeoutConnect) {
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeoutConnect);
        }
        if ($timeoutExecute) {
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeoutExecute); //timeout in seconds
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

        if (!$page) {
            $page = false;
        }

        curl_close($ch);
        return $page;
    }
}

if (!function_exists("curl_download")) {
    function curl_download($url, $savePath = '../../images/', $saveName = 'test.png')
    {
        if (
            empty($url) ||
            empty($savePath) || $savePath == '../../images/' ||
            empty($saveName) || $saveName == 'test.png'
        ) {
            throw new Exception('Missing required parameter!');
        }

        if (!is_dir($savePath)) throw new Exception('Save path does not exist!');

        $image = curl($url, NULL, NULL, NULL, NULL, 30, 120);

        if (empty($image)) throw new Exception('No image downloaded!');

        if (is_file($savePath . $saveName)) {
            $saveNameFinal = $saveName . '2';
        } else {
            $saveNameFinal = $saveName;
        }

        $saveFileHandler = fopen($savePath . $saveNameFinal, 'w');
        fwrite($saveFileHandler, $image);
        fclose($saveFileHandler);
        unset($saveFileHandler);

        if ($saveName != $saveNameFinal) {
            $saveFileHandler = unlink($savePath . $saveName);
            if ($saveFileHandler) {
                $saveFileHandler = rename($savePath . $saveNameFinal, $savePath . $saveName);
                if ($saveFileHandler) {
                    if (!is_file($savePath . $saveName)) {
                        throw new Exception('New version of file not found!');
                    } else {
                        return true;
                    }
                } else {
                    throw new Exception('New version of file was not renamed!');
                }
            } else {
                throw new Exception('Old version of file was not deleted!');
            }
        } else if (!is_file($savePath . $saveNameFinal)) {
            throw new Exception('No image downloaded!');
        } else {
            return true;
        }
    }
}

if (!class_exists('irc_message')) {
    class irc_message
    {
        private $webhook_url = '';

        public function __construct($webhook_gds_site)
        {
            $this->set_webhook($webhook_gds_site);
        }

        public function set_webhook($webhook)
        {
            if (!empty($webhook)) {
                $this->webhook_url = $webhook;
            } else {
                throw new Exception('Empty webhook!');
            }
        }

        public function post_message($message, $options = array('localDev' => false, 'useExceptions' => false, 'timeoutConnect' => 5, 'timeoutExecute' => 10,))
        {
            if (!empty($options['localDev'])) {
                return false;
            }

            $ch = curl_init($this->webhook_url);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
            curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('payload' => $message)));
            if (!empty($options['timeoutConnect'])) {
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $options['timeoutConnect']);
            }
            if (!empty($options['timeoutExecute'])) {
                curl_setopt($ch, CURLOPT_TIMEOUT, $options['timeoutExecute']); //timeout in seconds
            }

            curl_exec($ch);
            $response = curl_getinfo($ch);
            curl_close($ch);

            if ($response['http_code'] != 200) {
                if (!empty($options['useExceptions'])) {
                    throw new Exception('Bad header of #' . $response['http_code'] . ' returned!');
                } else {
                    return false;
                }
            }

            return true;
        }

        public function colour_generator($colour)
        {
            if ($colour == 'bold') return chr(2);

            $code = chr(3);

            switch ($colour) {
                case '0':
                case 'black':
                    $code .= '0';
                    break;
                case '1':
                case 'white':
                    $code .= '1';
                    break;
                case '2':
                case 'blue':
                    $code .= '2';
                    break;
                case '3':
                case 'green':
                    $code .= '3';
                    break;
                case '4':
                case 'red':
                    $code .= '4';
                    break;
                case '5':
                case 'brown':
                    $code .= '5';
                    break;
                case '6':
                case 'purple':
                    $code .= '6';
                    break;
                case '7':
                case 'orange':
                    $code .= '7';
                    break;
                case '8':
                case 'yellow':
                    $code .= '8';
                    break;
                case '9':
                case 'limegreen':
                    $code .= '9';
                    break;
                case '10':
                case 'turquise':
                    $code .= '10';
                    break;
                case '11':
                case 'cyan':
                    $code .= '11';
                    break;
                case '12':
                case 'lightblue':
                    $code .= '12';
                    break;
                case '13':
                case 'pink':
                    $code .= '13';
                    break;
                case '14':
                case 'grey':
                    $code .= '14';
                    break;
                case '15':
                case 'lightgrey':
                    $code .= '15';
                    break;
            }

            return $code;
        }

        public function combine_message($message_array)
        {
            if (!is_array($message_array)) throw new Exception('combine_message() requires an array!');

            $message = '';
            foreach ($message_array as $key => $value) {
                foreach ($value as $key2 => $value2) {
                    $message .= $value2;
                }

                $message .= ' ';
            }

            return $message;
        }
    }
}

if (!function_exists("cut_str")) {
    function cut_str($str, $left, $right = NULL)
    {
        try {
            $str = substr(stristr($str, $left), strlen($left));

            if ($right) {
                $leftLen = strlen(stristr($str, $right));
                $leftLen = $leftLen ? -($leftLen) : strlen($str);
                $str = substr($str, 0, $leftLen);
            }
        } catch (Exception $e) {
            return false;
        }

        return $str;
    }
}

//GIVEN A UNIX TIMESTAMP RETURNS A RELATIVE DISTANCE TO DATE (23.4 days ago)
//A STRING DENOMINATOR OF SINGLE TIME (SECOND, MINUTE, etc) WILL FORCE FORMATTED OUTPUT
//RETURNARRAY WILL RETURN ARRAY INSTEAD OF STRING
if (!function_exists('relative_time_v3')) {
    function relative_time_v3($time, $decimals = 1, $output = NULL, $returnArray = false, $returnFormattedNumber = true, $returnCompactString = false)
    {
        if (!is_numeric($time)) {
            if (strtotime($time)) {
                $time = strtotime($time);
            } else {
                throw new Exception('Not a parseable time string');
            }
        }

        if (!is_numeric($decimals)) {
            throw new Exception('Decimal parameter not numeric');
        }

        if (empty($output)) {
            switch ($time) {
                case ((time() - $time) >= 31536000):
                    $number = number_format(((time() - $time) / 31536000), $decimals);
                    $timeString = !$returnCompactString ? 'year' : 'yr';
                    break;
                case ((time() - $time) >= 2592000):
                    $number = number_format(((time() - $time) / 2592000), $decimals);
                    $timeString = !$returnCompactString ? 'month' : 'mo';
                    break;
                case ((time() - $time) >= 86400):
                    $number = number_format(((time() - $time) / 86400), $decimals);
                    $timeString = !$returnCompactString ? 'day' : 'day';
                    break;
                case ((time() - $time) >= 3600):
                    $number = number_format(((time() - $time) / 3600), $decimals);
                    $timeString = !$returnCompactString ? 'hour' : 'hr';
                    break;
                default:
                    $number = number_format(((time() - $time) / 60), 0);
                    $timeString = !$returnCompactString ? 'minute' : 'min';
                    break;
            }
        } else {
            switch ($output) {
                case 'year':
                    $number = number_format(((time() - $time) / 31536000), $decimals);
                    $timeString = !$returnCompactString ? 'year' : 'yr';
                    break;
                case 'month':
                    $number = number_format(((time() - $time) / 2592000), $decimals);
                    $timeString = !$returnCompactString ? 'month' : 'mo';
                    break;
                case 'day':
                    $number = number_format(((time() - $time) / 86400), $decimals);
                    $timeString = !$returnCompactString ? 'day' : 'day';
                    break;
                case 'hour':
                    $number = number_format(((time() - $time) / 3600), $decimals);
                    $timeString = !$returnCompactString ? 'hour' : 'hr';
                    break;
                case 'minute':
                    $number = number_format(((time() - $time) / 60), 0);
                    $timeString = !$returnCompactString ? 'minute' : 'min';
                    break;
                case 'second':
                    $number = number_format(((time() - $time)), 0);
                    $timeString = !$returnCompactString ? 'second' : 'sec';
                    break;
                default:
                    $number = number_format(((time() - $time)), $decimals);
                    $timeString = !$returnCompactString ? 'second' : 'sec';
                    break;
            }
        }

        if ($number == 1) {
            $timeString = !$returnCompactString ? $timeString . ' ago' : $timeString . '. ago';
        } else {
            $timeString = !$returnCompactString ? $timeString . 's ago' : $timeString . 's. ago';
        }

        if ($returnFormattedNumber == false) {
            $number = str_replace(',', '', $number);
        }

        if (empty($returnArray)) {
            $time_adj = $number . ' ' . $timeString;
        } else {
            $time_adj = array(
                'number' => $number,
                'time_string' => $timeString,
            );
        }

        return $time_adj;
    }
}

if (!function_exists('filesize_human_readable')) {
    /**
     * @param int $size
     * @param int $decimals number of decimal places the file size is rounded to
     * @param string $output (B | KB | MB | GB | TB)
     * @param bool $returnArray
     * @return array|string formatted file size or array (number | string)
     * @throws Exception
     */
    function filesize_human_readable($size, $decimals = 1, $output = NULL, $returnArray = false)
    {
        if (!is_numeric($size)) {
            throw new Exception('Size parameter not numeric');
        }

        if (!is_numeric($decimals)) {
            throw new Exception('Decimal parameter not numeric');
        }

        if (empty($output)) {
            switch ($size) {
                case ($size >= 1099511627776):
                    $number = number_format(($size / 1099511627776), $decimals);
                    $string = 'TB';
                    break;
                case ($size >= 1073741824):
                    $number = number_format(($size / 1073741824), $decimals);
                    $string = 'GB';
                    break;
                case ($size >= 1048576):
                    $number = number_format(($size / 1048576), $decimals);
                    $string = 'MB';
                    break;
                case ($size >= 1024):
                    $number = number_format(($size / 1024), $decimals);
                    $string = 'KB';
                    break;
                default:
                    $number = number_format($size, 0);
                    $string = 'B';
                    break;
            }
        } else {
            switch ($output) {
                case 'TB':
                    $number = number_format(($size / 1099511627776), $decimals);
                    $string = 'TB';
                    break;
                case 'GB':
                    $number = number_format(($size / 1073741824), $decimals);
                    $string = 'GB';
                    break;
                case 'MB':
                    $number = number_format(($size / 1048576), $decimals);
                    $string = 'MB';
                    break;
                case 'KB':
                    $number = number_format(($size / 1024), $decimals);
                    $string = 'KB';
                    break;
                default:
                    $number = number_format($size, $decimals);
                    $string = 'B';
                    break;
            }
        }

        if (!$returnArray) {
            $formatted = $number . ' ' . $string;
        } else {
            $formatted = array(
                'number' => $number,
                'string' => $string,
            );
        }

        return $formatted;
    }
}

if (!function_exists("simple_cached_query")) {
    function simple_cached_query($memcached_name, $sql = '', $cache_time_secs = 600)
    {
        global $memcached, $db;

        $variable = $memcached->get($memcached_name);
        if (!$variable) {
            if ($sql) {
                $variable = $db->q($sql);
                $memcached->set($memcached_name, $variable, $cache_time_secs);
            } else {
                return 'No sql provided!!!';
            }
        }
        return $variable;
    }
}

if (!function_exists("cached_query")) {
    function cached_query($memcached_name, $sqlQuery, $declarationString = NULL, $parameterArray = NULL, $cache_time_secs = 15, $db_specific = NULL, $memcache_specific = NULL)
    {
        if (!empty($db_specific)) {
            $db = $db_specific;
        } else {
            global $db;
        }

        if (!empty($memcache_specific)) {
            $memcached = $memcache_specific;
        } else {
            global $memcached;
        }

        if (empty($memcached) || empty($db)) throw new Exception('No DB or memcache connection specified!');

        $variable = $memcached->get($memcached_name);
        if (!$variable) {
            if ($sqlQuery) {
                if (!empty($declarationString) && !empty($parameterArray)) {
                    $variable = $db->q(
                        $sqlQuery,
                        $declarationString,
                        $parameterArray
                    );
                } else {
                    $variable = $db->q(
                        $sqlQuery
                    );
                }

                $memcached->set($memcached_name, $variable, $cache_time_secs);
            } else {
                throw new Exception('No DB provided!!!');
            }
        }
        return $variable;
    }
}

if (!function_exists("guid")) {
    function guid()
    {
        if (function_exists('com_create_guid')) {
            return com_create_guid();
        } else {
            mt_srand((double)microtime() * 10000); //optional for php 4.2.0 and up.
            $charid = strtoupper(md5(uniqid(rand(), true)));
            $hyphen = chr(45); // "-"
            $uuid = //chr(123).// "{"
                substr($charid, 0, 8) . $hyphen
                . substr($charid, 8, 4) . $hyphen
                . substr($charid, 12, 4) . $hyphen
                . substr($charid, 16, 4) . $hyphen
                . substr($charid, 20, 12);
            //.chr(125);// "}"
            return $uuid;
        }
    }
}

if (!function_exists("checkLogin_v2")) {
    function checkLogin_v2()
    {
        global $_COOKIE;
        if (isset($_COOKIE['session'])) {
            global $db;
            if (empty($db)) throw new Exception('No DB defined!');

            $auth = $db->q('SELECT * FROM `gds_users_sessions` WHERE `user_cookie` = ? ORDER BY `date_recorded` DESC LIMIT 0,1;',
                's',
                $_COOKIE['session']);

            if (!empty($auth)) {
                $steamID64 = $auth[0]['user_id64'];
                $accountDetails = $db->q('SELECT * FROM `gds_users` WHERE `user_id64` = ? LIMIT 0,1;',
                    's', //STUPID x64 windows PHP is actually x86
                    $steamID64);

                if (!empty($accountDetails)) {
                    $_SESSION['user_id32'] = $accountDetails[0]['user_id32'];
                    $_SESSION['user_id64'] = $accountDetails[0]['user_id64'];
                    $_SESSION['user_name'] = $accountDetails[0]['user_name'];
                    $_SESSION['user_avatar'] = $accountDetails[0]['user_avatar'];
                } else {
                    //KILL BAD COOKIE
                    $domain = ($_SERVER['HTTP_HOST'] != 'localhost') ? '.' . $_SERVER['HTTP_HOST'] : false;
                    setcookie('session', '', time() - 3600, '/', $domain);
                }

                return true;
            } else {
                //KILL BAD COOKIE
                unset($_SESSION['user_id32']);
                unset($_SESSION['user_id64']);
                unset($_SESSION['user_name']);
                unset($_SESSION['user_avatar']);

                $domain = ($_SERVER['HTTP_HOST'] != 'localhost') ? '.' . $_SERVER['HTTP_HOST'] : false;
                setcookie('session', '', time() - 3600, '/', $domain);

                return false;
            }
        } else {
            return false;
        }
    }
}

if (!function_exists("adminCheck")) {
    function adminCheck($userID64, $group)
    {
        global $db;
        if (empty($db)) throw new Exception('No DB defined!');

        $adminCheck = cached_query(
            'admin_userID_check2' . $userID64 . '_' . $group,
            'SELECT * FROM `gds_power_users` WHERE `user_id64` = ? AND `user_group` = ? LIMIT 0,1;',
            'ss',
            [$userID64, $group],
            5 * 60
        );

        if (!empty($adminCheck)) {
            return true;
        }

        return false;
    }
}

if (!function_exists("secs_to_h")) {
    function secs_to_h($secs)
    {
        $units = array(
            "week" => 7 * 24 * 3600,
            "day" => 24 * 3600,
            "hour" => 3600,
            "minute" => 60,
            "second" => 1,
        );

        // specifically handle zero
        if ($secs == 0) return "0 seconds";

        $s = "";

        foreach ($units as $name => $divisor) {
            if ($quot = intval($secs / $divisor)) {
                $s .= "$quot $name";
                $s .= (abs($quot) > 1 ? "s" : "") . ", ";
                $secs -= $quot * $divisor;
            }
        }

        return substr($s, 0, -2);
    }
}

// default, primary, success, info, warning, danger, link
if (!function_exists("bootstrapMessage")) {
    function bootstrapMessage($errorHeading = 'Oh Snap', $errorMessage, $type = 'danger')
    {
        switch ($type) {
            case 'primary':
                $type = 'primary';
                break;
            case 'success':
                $type = 'success';
                break;
            case 'info':
                $type = 'info';
                break;
            case 'warning':
                $type = 'warning';
                break;
            case 'danger':
                $type = 'danger';
                break;
            default:
                $type = 'danger';
                break;
        }

        $formatted = '<div class="page-header"><div class="alert alert-' . $type . '" role="alert"><strong>' . $errorHeading . ':</strong> ' . $errorMessage . '</div></div>';

        return $formatted;
    }
}

if (!function_exists("formatExceptionHandling")) {
    function formatExceptionHandling($e, $debug = false)
    {
        if (!$debug) {
            $message = $e->getMessage();
            $messageFormatted = bootstrapMessage('Oh Snap', $message, 'danger');
        } else {
            $message = $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')';
            $messageFormatted = bootstrapMessage('Caught Exception', $message, 'danger');
        }

        return $messageFormatted;
    }
}

if (!function_exists('dota2TeamName')) {
    function dota2TeamName($teamID)
    {
        switch ($teamID) {
            case -1:
                $teamName = 'No Winner';
                break;
            case 2:
                $teamName = 'Radiant';
                break;
            case 3:
                $teamName = 'Dire';
                break;
            default:
                $teamName = '#' . $teamID;
                break;
        }
        return $teamName;
    }
}

if (!function_exists("secs_to_clock")) {
    function secs_to_clock($seconds)
    {
        $hours = str_pad(floor($seconds / 3600), 2, '0', STR_PAD_LEFT);
        $mins = str_pad(floor(($seconds - ($hours * 3600)) / 60), 2, '0', STR_PAD_LEFT);
        $secs = str_pad(floor($seconds % 60), 2, '0', STR_PAD_LEFT);

        if ($hours > 0) {
            return $hours . ':' . $mins . ':' . $secs;
        } else {
            return $mins . ':' . $secs;
        }
    }
}

if (!function_exists("array_map_recursive")) {
    function array_map_recursive($callback, $array)
    {
        foreach ($array as $key => $value) {
            if (is_array($array[$key])) {
                $array[$key] = array_map_recursive($callback, $array[$key]);
            } else {
                $array[$key] = call_user_func($callback, $array[$key]);
            }
        }
        return $array;
    }
}

if (!function_exists("unicodeToUTF_8")) {
    function unicodeToUTF_8($string)
    {
        if (!empty($string)) {
            return preg_replace("/%u([0-9a-f]{3,4})/i", "&#x\\1;", $string);
        }
        return false;
    }
}

if (!function_exists("handlingUnicodeFromFlashWithURLencoding")) {
    function handlingUnicodeFromFlashWithURLencoding($string)
    {
        if (!empty($string)) {
            $string = unicodeToUTF_8($string);
            $string = htmlentities_custom($string);

            return $string;
        }
        return false;
    }
}

if (!function_exists("htmlentities_custom")) {
    function htmlentities_custom($string)
    {
        if (!empty($string)) {
            $string = htmlentities($string, ENT_QUOTES | ENT_HTML5 | ENT_IGNORE, 'UTF-8', false);

            return $string;
        }
        return false;
    }
}

if (!function_exists("htmlentitiesdecode_custom")) {
    function htmlentitiesdecode_custom($string)
    {
        if (!empty($string)) {
            $string = html_entity_decode($string, ENT_QUOTES | ENT_HTML5 | ENT_IGNORE, 'UTF-8');

            return $string;
        }
        return false;
    }
}

if (!function_exists("br2nl")) {
    function br2nl($string)
    {
        return preg_replace('/\<br(\s*)?\/?\>/i', PHP_EOL, $string);
    }
}

if (!class_exists('steam_webapi')) {
    class steam_webapi
    {
        private $steamAPIKey = NULL;

        public function __construct($steamAPIKey)
        {
            if (empty($steamAPIKey)) {
                throw new RuntimeException('No Steam Key Provided!');
            } else {
                $this->steamAPIKey = $steamAPIKey;
            }
        }

        function ResolveVanityURL($vanityURL)
        {
            $APIresult = curl('http://api.steampowered.com/ISteamUser/ResolveVanityURL/v0001/?key=' . $this->steamAPIKey . '&vanityurl=' . $vanityURL, NULL, NULL, NULL, NULL, 5, 5);

            $APIresult = !empty($APIresult)
                ? json_decode($APIresult, 1)
                : false;

            return $APIresult;
        }

        function GetFriendList($steamID, $relationshipFilter = 'friend')
        {
            //Relationship filter. Possibles values: all, friend
            $APIresult = curl('http://api.steampowered.com/ISteamUser/GetFriendList/v0001/?key=' . $this->steamAPIKey . '&steamid=' . $steamID . '&relationship=' . $relationshipFilter);

            $APIresult = !empty($APIresult)
                ? json_decode($APIresult, 1)
                : false;

            return $APIresult;
        }

        function GetPlayerSummariesV2($steamID)
        {
            /*
             Array
            (
                [response] => Array
                    (
                        [players] => Array
                            (
                                [0] => Array
                                    (
                                        [steamid] => 76561198005952231
                                        [communityvisibilitystate] => 3
                                        [profilestate] => 1
                                        [personaname] => D Jexah
                                        [lastlogoff] => 1422384134
                                        [profileurl] => http://steamcommunity.com/id/Jexah/
                                        [avatar] => http://cdn.akamai.steamstatic.com/steamcommunity/public/images/avatars/39/397a93607b4292485b3181c564096e9731bf69b6.jpg
                                        [avatarmedium] => http://cdn.akamai.steamstatic.com/steamcommunity/public/images/avatars/39/397a93607b4292485b3181c564096e9731bf69b6_medium.jpg
                                        [avatarfull] => http://cdn.akamai.steamstatic.com/steamcommunity/public/images/avatars/39/397a93607b4292485b3181c564096e9731bf69b6_full.jpg
                                        [personastate] => 3
                                        [primaryclanid] => 103582791433015252
                                        [timecreated] => 1233904234
                                        [personastateflags] => 0
                                        [gameextrainfo] => Dota 2
                                        [gameid] => 570
                                        [loccountrycode] => AU
                                        [locstatecode] => ACT
                                    )

                            )

                    )

            )
             */
            try {
                $APIresult = curl('http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v2/?key=' . $this->steamAPIKey . '&steamids=' . $steamID);

                $APIresult = !empty($APIresult)
                    ? json_decode($APIresult, 1)
                    : false;

                if (!empty($APIresult)) {
                    return $APIresult;
                }
                return false;
            } catch (Exception $e) {
                return $e->getMessage();
            }
        }

        function GetPublishedFileDetails($wid)
        {
            try {
                $postFields = array(
                    'key' => $this->steamAPIKey,
                    'itemcount' => 1,
                    'format' => 'json',
                    'publishedfileids[0]' => $wid
                );
                $postFields = http_build_query($postFields);

                $APIresult = curl('http://api.steampowered.com/ISteamRemoteStorage/GetPublishedFileDetails/v1/', $postFields);

                $APIresult = !empty($APIresult)
                    ? json_decode($APIresult, 1)
                    : false;

                return $APIresult;
            } catch (Exception $e) {
                return $e->getMessage();
            }
        }
    }
}

if (!class_exists('dota2_webapi')) {
    class dota2_webapi
    {
        private $steamAPIKey = NULL;

        public function __construct($steamAPIKey)
        {
            if (empty($steamAPIKey)) {
                throw new RuntimeException('No Steam Key Provided!');
            } else {
                $this->steamAPIKey = $steamAPIKey;
            }
        }

        function GetGameItems($language = 'en')
        {
            $APIresult = curl('http://api.steampowered.com/IEconDOTA2_570/GetGameItems/v1/?key=' . $this->steamAPIKey . '&format=json&language=' . $language);

            $APIresult = !empty($APIresult)
                ? json_decode($APIresult, 1)
                : false;

            return $APIresult;
        }
    }
}

if (!class_exists('SteamID')) {
    class SteamID
    {
        private $steamID = '';
        private $steamID32 = '';
        private $steamID64 = '';

        public function __construct($steam_id = NULL)
        {
            if (!empty($steam_id)) {
                $this->setSteamID($steam_id);
            }
        }

        public function setSteamID($steam_id)
        {
            if (empty($steam_id) || !is_numeric($steam_id)) {
                throw new Exception('Invalid data provided; data is not a valid steamID or steamID32 or steamID64');
            } elseif (strlen($steam_id) === 17) {
                $this->steamID64 = $steam_id;
                $this->steamID32 = $this->convert64to32($steam_id);
                $this->steamID = $this->convert64toID($steam_id);
            } elseif (strlen($steam_id) != 17) {
                $this->steamID64 = $this->convert32to64($steam_id);
                $this->steamID32 = $steam_id;
                $this->steamID = $this->convert32toID($steam_id);
            } elseif (preg_match('/^STEAM_0:[01]:[0-9]+/', $steam_id)) {
                $this->steamID64 = $this->convertIDto64($steam_id);
                $this->steamID32 = $this->convertIDto32($steam_id);
                $this->steamID = $steam_id;
            } else {
                throw new Exception('Invalid data provided; data is not a valid steamID or steamID32 or steamID64');
            }
        }

        private function convert64to32($steam_id)
        {
            $steam_cid = substr($steam_id, 3) - 61197960265728;
            return $steam_cid;
        }

        private function convert32to64($steam_id)
        {
            $steam_cid = '765' . ($steam_id + 61197960265728);
            return $steam_cid;
        }

        private function convert32toID($steam_id)
        {
            $steam_cid = '765' . ($steam_id + 61197960265728);
            $steam_cid = $this->convert64toID($steam_cid);
            return $steam_cid;
        }

        private function convert64toID($steam_cid)
        {
            $id = array('STEAM_0');
            $id[1] = substr($steam_cid, -1, 1) % 2 == 0 ? 0 : 1;
            $id[2] = bcsub($steam_cid, '76561197960265728');
            if (bccomp($id[2], '0') != 1) {
                return false;
            }
            $id[2] = bcsub($id[2], $id[1]);
            list($id[2],) = explode('.', bcdiv($id[2], 2), 2);
            return implode(':', $id);
        }

        private function convertIDto64($steam_id)
        {
            list(, $m1, $m2) = explode(':', $steam_id, 3);
            list($steam_cid,) = explode('.', bcadd((((int)$m2 * 2) + $m1), '76561197960265728'), 2);
            return $steam_cid;
        }

        private function convertIDto32($steam_id)
        {
            list(, $m1, $m2) = explode(':', $steam_id, 3);
            list($steam_cid,) = explode('.', bcadd((((int)$m2 * 2) + $m1), '76561197960265728'), 2);
            $steam_cid = $this->convert64to32($steam_cid);
            return $steam_cid;
        }

        public function getSteamID()
        {
            return $this->steamID;
        }

        public function getsteamID32()
        {
            return $this->steamID32;
        }

        public function getSteamID64()
        {
            return $this->steamID64;
        }
    }
}

if (!function_exists('updateUserDetails')) {
    function updateUserDetails($steamID64, $api_key)
    {
        global $memcached, $db;

        if (!$memcached) {
            throw new Exception("No memcached instance to use!");
        }

        if (!$db) {
            throw new Exception("No DB instance to use!");
        }

        $steamWebAPI = new steam_webapi($api_key);
        $playerID = new SteamID($steamID64);

        $playerDetails = $memcached->get('cron_user_details' . $steamID64);
        if (empty($playerDetails)) {
            sleep(0.5);
            $playerDetails_tmp = $steamWebAPI->GetPlayerSummariesV2($playerID->getSteamID64());

            if (!empty($playerDetails_tmp)) {
                $playerDetails[0]['user_id64'] = $playerID->getSteamID64();
                $playerDetails[0]['user_id32'] = $playerID->getSteamID32();
                $playerDetails[0]['user_name'] = htmlentities_custom($playerDetails_tmp['response']['players'][0]['personaname']);
                $playerDetails[0]['user_avatar'] = $playerDetails_tmp['response']['players'][0]['avatar'];
                $playerDetails[0]['user_avatar_medium'] = $playerDetails_tmp['response']['players'][0]['avatarmedium'];
                $playerDetails[0]['user_avatar_large'] = $playerDetails_tmp['response']['players'][0]['avatarfull'];


                $sqlResult = $db->q(
                    'INSERT INTO `gds_users`
                        (`user_id64`, `user_id32`, `user_name`, `user_avatar`, `user_avatar_medium`, `user_avatar_large`)
                        VALUES (?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                          `user_name` = VALUES(`user_name`),
                          `user_avatar` = VALUES(`user_avatar`),
                          `user_avatar_medium` = VALUES(`user_avatar_medium`),
                          `user_avatar_large` = VALUES(`user_avatar_large`);',
                    'ssssss',
                    array(
                        $playerDetails[0]['user_id64'],
                        $playerDetails[0]['user_id32'],
                        $playerDetails[0]['user_name'],
                        $playerDetails[0]['user_avatar'],
                        $playerDetails[0]['user_avatar_medium'],
                        $playerDetails[0]['user_avatar_large']
                    )
                );

                if ($sqlResult) {
                    return true;
                }

                $memcached->set('cron_user_details' . $steamID64, $playerDetails, 15 * 60);

                unset($playerDetails_tmp);
                unset($playerDetails);
            }
        }
        return false;
    }
}

if (!function_exists('grabAndUpdateSteamUserDetails')) {
    function grabAndUpdateSteamUserDetails($steamID32, $localDevOverride = false)
    {
        global $db, $memcached, $webAPI, $localDev;
        if (!$localDevOverride && $localDev) throw new Exception('We working locally!');
        if (!isset($db)) throw new Exception('No DB defined!');
        if (!isset($memcached)) throw new Exception('No memcache defined!');
        if (!isset($webAPI)) throw new Exception('webAPI not defined!');

        $steamID = new SteamID($steamID32);

        $web_api_user_details_temp = $webAPI->GetPlayerSummariesV2($steamID->getSteamID64());

        if (empty($web_api_user_details_temp)) throw new Exception('No Steam user found');
        if (empty($web_api_user_details_temp['response']['players'])) throw new Exception('Bad response from webAPI');

        $mg_lb_user_details = array();

        $mg_lb_user_details[0]['user_id64'] = $steamID->getSteamID64();
        $mg_lb_user_details[0]['user_id32'] = $steamID->getSteamID32();
        $mg_lb_user_details[0]['user_name'] = htmlentities_custom($web_api_user_details_temp['response']['players'][0]['personaname']);
        $mg_lb_user_details[0]['user_avatar'] = $web_api_user_details_temp['response']['players'][0]['avatar'];
        $mg_lb_user_details[0]['user_avatar_medium'] = $web_api_user_details_temp['response']['players'][0]['avatarmedium'];
        $mg_lb_user_details[0]['user_avatar_large'] = $web_api_user_details_temp['response']['players'][0]['avatarfull'];
        $memcached->set('mg_lb_user_details_' . $steamID->getSteamID64(), $mg_lb_user_details, 10 * 60);

        $db->q(
            'INSERT INTO `gds_users`
                (`user_id64`, `user_id32`, `user_name`, `user_avatar`, `user_avatar_medium`, `user_avatar_large`)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                  `user_name` = VALUES(`user_name`),
                  `user_avatar` = VALUES(`user_avatar`),
                  `user_avatar_medium` = VALUES(`user_avatar_medium`),
                  `user_avatar_large` = VALUES(`user_avatar_large`);',
            'ssssss',
            array(
                $mg_lb_user_details[0]['user_id64'],
                $mg_lb_user_details[0]['user_id32'],
                $mg_lb_user_details[0]['user_name'],
                $mg_lb_user_details[0]['user_avatar'],
                $mg_lb_user_details[0]['user_avatar_medium'],
                $mg_lb_user_details[0]['user_avatar_large']
            )
        );

        return $mg_lb_user_details;
    }
}

if (!function_exists('makePieChart')) {
    function makePieChart($dataArray, $div_container = 'container', $chartTitle, $chartSubtitle = NULL)
    {
        require_once(__DIR__ . "/bootstrap/highcharts/Highchart.php");
        require_once(__DIR__ . "/bootstrap/highcharts/HighchartJsExpr.php");
        require_once(__DIR__ . "/bootstrap/highcharts/HighchartOption.php");
        require_once(__DIR__ . "/bootstrap/highcharts/HighchartOptionRenderer.php");

        $chart = new Highchart();

        $chart->chart->renderTo = $div_container;
        //$chart->chart->type = "pie";
        $chart->chart->plotBackgroundColor = null;
        $chart->chart->plotBorderWidth = null;
        $chart->chart->plotShadow = false;
        $chart->title->text = $chartTitle;
        $chart->subtitle->text = $chartSubtitle;
        $chart->tooltip->formatter = new HighchartJsExpr(
            "function() {
                return '<b>' + this.point.name + '</b>: ' + Math.round(this.percentage*10)/10 + ' %';
            }"
        );
        $chart->chart->plotOptions->pie->allowPointSelect = 1;
        $chart->chart->plotOptions->pie->cursor = 'pointer';
        //$chart->chart->plotOptions->pie->dataLabels->enabled = true;
        //$chart->chart->plotOptions->pie->showInLegend = 0;
        $chart->credits->enabled = false;

        $chart->series[0]->type = 'pie';
        $chart->series[0]->name = 'test';
        $chart->series[0]->data = $dataArray;

        return $chart->render("chart1", NULL, true);
    }
}

if (!function_exists('makeLineChart')) {
    //$seriesOptions -- Add series name as key, and all options in array attached to it will be applied to series in chart
    function makeLineChart($dataArray, $div_container = 'container', $chartTitle, $chartSubtitle = NULL, $series1Options = array('title' => 'Games', 'min' => 0), $seriesNOptions = array('Series2' => array('title' => 'Test', 'yAxis' => 1, 'opposite' => true)), $seriesTooltipFormat = array('pointFormat' => 'Value: {point.y:.2f} mm'))
    {
        require_once(__DIR__ . "/bootstrap/highcharts/Highchart.php");
        require_once(__DIR__ . "/bootstrap/highcharts/HighchartJsExpr.php");
        require_once(__DIR__ . "/bootstrap/highcharts/HighchartOption.php");
        require_once(__DIR__ . "/bootstrap/highcharts/HighchartOptionRenderer.php");

        $chart = new Highchart();

        $chart->chart->renderTo = $div_container;
        $chart->chart->type = "spline";
        $chart->chart->zoomType = "x";
        $chart->title->text = $chartTitle;
        $chart->subtitle->text = $chartSubtitle;
        $chart->xAxis->type = "datetime";
        $chart->yAxis[0]->title->text = $series1Options['title']
            ? $series1Options['title']
            : 'Games';
        $chart->yAxis[0]->min = isset($series1Options['min'])
            ? $series1Options['min']
            : 0;
        /*$chart->tooltip->formatter = new HighchartJsExpr(
            "function() {
                return '<b>'+ this.series.name +'</b><br/>'+
                this.y +' games';
            }"
        );*/

        if ($seriesTooltipFormat == array('pointFormat' => 'Value: {point.y:.2f} mm') || empty($seriesTooltipFormat)) {
            $chart->tooltip->formatter = new HighchartJsExpr(
                "function() {
                var order = [], i, j, temp = [],
                    points = this.points;

                for(i=0; i<points.length; i++)
                {
                    j=0;
                    if( order.length )
                    {
                        while( points[order[j]] && points[order[j]].y > points[i].y )
                            j++;
                    }
                    temp = order.splice(0, j);
                    temp.push(i);
                    order = temp.concat(order);
                }
                console.log(order);
                temp = '';
                $(order).each(function(i,j){
                    temp += '<b>' + points[j].series.name + ': ' + points[j].y + '</b><br/>';
                });
                return temp;
            }"
            );
        } else {
            $chart->tooltip = $seriesTooltipFormat;
        }

        $chart->tooltip->crosshairs = array(true, true);
        $chart->tooltip->shared = true;
        $chart->credits->enabled = false;
        $chart->legend->maxHeight = 60;

        $i = 0;
        foreach ($dataArray as $key => $value) {
            $chart->series[$i]->name = $key;
            $chart->series[$i]->data = $value;

            if (isset($seriesNOptions[$key])) {
                if (isset($seriesNOptions[$key]['yAxis'])) {
                    $chart->series[$i]->yAxis = $seriesNOptions[$key]['yAxis'];

                    $chart->yAxis[$seriesNOptions[$key]['yAxis']]->title->text = $seriesNOptions[$key]['title'];
                    if (isset($seriesNOptions[$key]['opposite'])) {
                        $chart->yAxis[$seriesNOptions[$key]['yAxis']]->opposite = $seriesNOptions[$key]['opposite'];
                    }
                    if (isset($seriesNOptions[$key]['min'])) {
                        $chart->yAxis[$seriesNOptions[$key]['yAxis']]->min = $seriesNOptions[$key]['min'];
                    }
                }
            }

            $i++;
        }

        return $chart->render("chart1", NULL, true);
    }
}

if (!class_exists('basicStatsForArrays')) {
    class basicStatsForArrays
    {
        private $numericArray = array();

        public function __construct($numericArray = array())
        {
            if (!empty($numericArray)) {
                $this->setNumericArray($numericArray);
            }
        }

        public function setNumericArray($numericArray)
        {
            if (empty($numericArray)) {
                throw new Exception('Empty numeric array!');
            } else if (!is_array($numericArray)) {
                throw new Exception('Invalid numeric array!');
            } else {
                $this->numericArray = $numericArray;
            }
        }

        public function Count()
        {
            return count($this->numericArray);
        }

        public function Min()
        {
            return min($this->numericArray);
        }

        public function Max()
        {
            return max($this->numericArray);
        }

        public function Sum()
        {
            return array_sum($this->numericArray);
        }

        public function Quartile($Quartile)
        {
            $pos = (count($this->numericArray) - 1) * $Quartile;

            $base = floor($pos);
            $rest = $pos - $base;

            if (isset($this->numericArray[$base + 1])) {
                return $this->numericArray[$base] + $rest * ($this->numericArray[$base + 1] - $this->numericArray[$base]);
            } else {
                return $this->numericArray[$base];
            }
        }

        public function Quartile_25()
        {
            return $this->Quartile(0.25);
        }

        public function Quartile_50()
        {
            return $this->Quartile(0.5);
        }

        public function Quartile_75()
        {
            return $this->Quartile(0.75);
        }

        public function Median()
        {
            return $this->Quartile_50();
        }

        public function Average()
        {
            return $this->Sum() / $this->Count();
        }

        public function StdDev()
        {
            if (count($this->numericArray) < 2) {
                return false;
            }

            $avg = $this->Average();

            $sum = 0;
            foreach ($this->numericArray as $value) {
                $sum += pow($value - $avg, 2);
            }

            return sqrt((1 / (count($this->numericArray) - 1)) * $sum);
        }
    }
}

if (!class_exists('serviceReporting')) {
    class serviceReporting
    {
        private $db = null;
        private $lastServiceLog = null;

        public function __construct($db)
        {
            if (empty($db)) {
                throw new Exception('No DB connection provided!');
            }

            $this->db = $db;
        }

        //log service stats
        public function log($serviceName, $runTime, $performanceIndex1, $performanceIndex2 = NULL, $performanceIndex3 = NULL, $isSub = FALSE)
        {
            $isSub = !empty($isSub)
                ? 1
                : 0;

            $sqlResult = $this->db->q('INSERT INTO `cron_services`
            (
                `service_name`,
                `is_sub`,
                `execution_time`,
                `performance_index1`,
                `performance_index2`,
                `performance_index3`
            ) VALUES (?, ?, ?, ?, ?, ?);',
                'siiiii',
                array($serviceName, $isSub, $runTime, $performanceIndex1, $performanceIndex2, $performanceIndex3)
            );

            if ($sqlResult) {
                return true;
            }

            throw new Exception('Service report not lodged!');
        }

        public function logAndCompareOld(
            $serviceName,
            $runTime = array('value' => NULL, 'min' => 20, 'growth' => 0.5),
            $performanceIndex1 = array('value' => NULL, 'min' => 0, 'growth' => 0.1, 'unit' => 'games'),
            $performanceIndex2 = array('value' => NULL, 'min' => 0, 'growth' => 0.1, 'unit' => 'games'),
            $performanceIndex3 = array('value' => NULL, 'min' => 0, 'growth' => 0.1, 'unit' => 'games'),
            $isSub = FALSE,
            $subName = NULL
        )
        {
            if (!isset($runTime['value'])) throw new Exception('Missing runTime value!');
            if (!isset($performanceIndex1['value'])) throw new Exception('Missing performanceIndex1 value!');
            if (!empty($performanceIndex2) && !isset($performanceIndex2['value'])) throw new Exception('PerformanceIndex2 array defined, but missing value!');
            if (!empty($performanceIndex3) && !isset($performanceIndex3['value'])) throw new Exception('PerformanceIndex3 array defined, but missing value!');

            if (!isset($performanceIndex2['value'])) $performanceIndex2['value'] = NULL;
            if (!isset($performanceIndex3['value'])) $performanceIndex3['value'] = NULL;

            //GRAB old report data
            $oldServiceReport = cached_query(
                $serviceName . '_old_service_report',
                'SELECT
                        `instance_id`,
                        `service_name`,
                        `execution_time`,
                        `performance_index1`,
                        `performance_index2`,
                        `performance_index3`,
                        `date_recorded`
                    FROM `cron_services`
                    WHERE `service_name` = ?
                    ORDER BY `date_recorded` DESC
                    LIMIT 0,1;',
                's',
                array($serviceName),
                1,
                $this->db
            );

            //LOG new report data
            $this->log($serviceName, $runTime['value'], $performanceIndex1['value'], $performanceIndex2['value'], $performanceIndex3['value'], $isSub);

            if (empty($oldServiceReport)) {
                $cronName = !empty($subName)
                    ? $subName
                    : $serviceName;
                throw new Exception("First time running cron `{$cronName}`! It may be new!");
            }
            $oldServiceReport = $oldServiceReport[0];

            //Check if first time it's had data
            if (
                $isSub &&
                (
                    (empty($oldServiceReport['performance_index1']) && !empty($performanceIndex1['value'])) ||
                    (empty($oldServiceReport['performance_index2']) && !empty($performanceIndex2['value'])) ||
                    (empty($oldServiceReport['performance_index3']) && !empty($performanceIndex3['value']))
                )
            ) {
                $cronName = !empty($subName)
                    ? $subName
                    : $serviceName;
                throw new Exception("Mod `{$cronName}` has values to use since the last report! It may be new!");
            }

            //Check if it had data, but now does not
            if (
                $isSub &&
                (
                    (!empty($oldServiceReport['performance_index1']) && (!empty($performanceIndex1 && empty($performanceIndex1['value'])))) ||
                    (!empty($oldServiceReport['performance_index2']) && (!empty($performanceIndex2 && empty($performanceIndex2['value'])))) ||
                    (!empty($oldServiceReport['performance_index3']) && (!empty($performanceIndex3 && empty($performanceIndex3['value']))))
                )
            ) {
                $cronName = !empty($subName)
                    ? $subName
                    : $serviceName;
                throw new Exception("Mod `{$cronName}` had values in the last report, but now does not! It may be broken!");
            }

            //Check if the run-time increased majorly
            if (isset($runTime['min']) && !empty($runTime['growth'])) {
                if ($runTime['value'] > $runTime['min'] && ($runTime['value'] > ($oldServiceReport['execution_time'] * (1 + $runTime['growth'])))) {
                    $prettyGrowth = $runTime['growth'] * 100;
                    $cronName = !empty($subName)
                        ? $subName
                        : $serviceName;
                    $extraContext = !empty($isSub)
                        ? ' for `' . $cronName . '`'
                        : '';
                    throw new Exception("Major increase (>{$prettyGrowth}%) in execution time{$extraContext}! {$oldServiceReport['execution_time']}secs to {$runTime['value']}secs");
                }
            }

            //Check if the performance_index1 increased majorly
            if (isset($performanceIndex1['min']) && !empty($performanceIndex1['growth'])) {
                if ($performanceIndex1['value'] > $performanceIndex1['min'] && ($performanceIndex1['value'] > ($oldServiceReport['performance_index1'] * (1 + $performanceIndex1['growth'])))) {
                    $prettyGrowth = $performanceIndex1['growth'] * 100;
                    $prettyUnits = !empty($performanceIndex1['unit'])
                        ? $performanceIndex1['unit']
                        : 'values';
                    $cronName = !empty($subName)
                        ? $subName
                        : $serviceName;
                    $extraContext = !empty($isSub)
                        ? ' for `' . $cronName . '`'
                        : '';
                    throw new Exception("Major increase (>{$prettyGrowth}%) in performance index #1{$extraContext}! {$oldServiceReport['performance_index1']} {$prettyUnits} to {$performanceIndex1['value']} {$prettyUnits}");
                }
            }

            //Check if the performance_index2 increased majorly
            if (isset($performanceIndex2['min']) && !empty($performanceIndex2['growth'])) {
                if ($performanceIndex2['value'] > $performanceIndex2['min'] && ($performanceIndex2['value'] > ($oldServiceReport['performance_index2'] * (1 + $performanceIndex2['growth'])))) {
                    $prettyGrowth = $performanceIndex2['growth'] * 100;
                    $prettyUnits = !empty($performanceIndex2['unit'])
                        ? $performanceIndex2['unit']
                        : 'values';
                    $cronName = !empty($subName)
                        ? $subName
                        : $serviceName;
                    $extraContext = !empty($isSub)
                        ? ' for `' . $cronName . '`'
                        : '';
                    throw new Exception("Major increase (>{$prettyGrowth}%) in performance index #2{$extraContext}! {$oldServiceReport['performance_index2']} {$prettyUnits} to {$performanceIndex2['value']} {$prettyUnits}");
                }
            }

            //Check if the performance_index3 increased majorly
            if (isset($performanceIndex3['min']) && !empty($performanceIndex3['growth'])) {
                if ($performanceIndex3['value'] > $performanceIndex3['min'] && ($performanceIndex3['value'] > ($oldServiceReport['performance_index3'] * (1 + $performanceIndex3['growth'])))) {
                    $prettyGrowth = $performanceIndex3['growth'] * 100;
                    $prettyUnits = !empty($performanceIndex3['unit'])
                        ? $performanceIndex3['unit']
                        : 'values';
                    $cronName = !empty($subName)
                        ? $subName
                        : $serviceName;
                    $extraContext = !empty($isSub)
                        ? ' for `' . $cronName . '`'
                        : '';
                    throw new Exception("Major increase (>{$prettyGrowth}%) in performance index #3{$extraContext}! {$oldServiceReport['performance_index3']} {$prettyUnits} to {$performanceIndex3['value']} {$prettyUnits}");
                }
            }
        }

        //Grabs a service log entry
        public function getServiceLog($serviceName)
        {
            $sqlResult = $this->db->q(
                'SELECT
                    `instance_id`,
                    `service_name`,
                    `execution_time`,
                    `performance_index1`,
                    `performance_index2`,
                    `performance_index3`,
                    `date_recorded`
                  FROM `cron_services`
                  WHERE `service_name` = ?
                  ORDER BY `date_recorded` DESC
                  LIMIT 0,1;',
                's',
                array($serviceName)
            );

            if (!empty($sqlResult)) {
                $sqlResult = $sqlResult[0];

                $this->lastServiceLog = $sqlResult;

                return $this->lastServiceLog;
            }

            throw new Exception('No service log found!');
        }

        //Grabs run-time from last Service Log that was obtained
        public function getServiceLogRunTime($decimals = 1, $output = NULL, $returnArray = false, $returnFormattedNumber = true, $returnCompactString = false)
        {
            if (!empty($this->lastServiceLog)) {
                return relative_time_v3($this->lastServiceLog['date_recorded'], $decimals, $output, $returnArray, $returnFormattedNumber, $returnCompactString);
            }

            throw new Exception('No service log loaded! Run getServiceLog() first!');
        }

        //Grabs execution-time from last Service Log that was obtained
        public function getServiceLogExecutionTime()
        {
            if (!empty($this->lastServiceLog)) {
                return secs_to_h($this->lastServiceLog['execution_time']);
            }

            throw new Exception('No service log loaded! Run getServiceLog() first!');
        }
    }
}

if (!function_exists('adminWrapText')) {
    function adminWrapText($text)
    {
        if (empty($text)) throw new Exception('No text to wrap!');
        $text = '<span class="boldRedText">' . $text . '</span> <span class="db_link glyphicon glyphicon-magnet" title="ADMIN viewable only"></span>';
        return $text;
    }
}

if (!function_exists('matchPhaseToGlyhpicon')) {
    function matchPhaseToGlyhpicon($phase)
    {
        if (empty($phase)) throw new Exception('No phase to convert!');

        switch ($phase) {
            case 1:
                $phaseGlyphicon = '<span class="glyphicon glyphicon-time boldRedText" title="Players loaded"></span>';
                break;
            case 2:
                $phaseGlyphicon = '<span class="glyphicon glyphicon-time boldOrangeText" title="Game started"></span>';
                break;
            case 3:
                $phaseGlyphicon = '<span class="glyphicon glyphicon-time boldGreenText" title="Game ended"></span>';
                break;
            default:
                $phaseGlyphicon = '<span class="glyphicon glyphicon-time" title="Players loaded"></span>';
                break;
        }

        return $phaseGlyphicon;
    }
}

if (!function_exists('matchConnectionStatusToGlyhpicon')) {
    function matchConnectionStatusToGlyhpicon($connectionStatus)
    {
        if (empty($connectionStatus)) throw new Exception('No connection status to convert!');

        switch ($connectionStatus) {
            case 0:
                $connectionStateGlyphicon = '<span class="glyphicon glyphicon-ok boldOrangeText" title="Unknown"></span>';
                break;
            case 1:
                $connectionStateGlyphicon = '<span class="glyphicon glyphicon-ok boldGreenText" title="Has not Connected"></span>';
                break;
            case 2:
                $connectionStateGlyphicon = '<span class="glyphicon glyphicon-ok boldGreenText" title="Connected"></span>';
                break;
            case 3:
                $connectionStateGlyphicon = '<span class="glyphicon glyphicon-ok boldOrangeText" title="Disconnected"></span>';
                break;
            case 4:
                $connectionStateGlyphicon = '<span class="glyphicon glyphicon-remove boldRedText" title="Abandoned"></span>';
                break;
            case 5:
                $connectionStateGlyphicon = '<span class="glyphicon glyphicon-ok boldOrangeText" title="Loading"></span>';
                break;
            case 6:
                $connectionStateGlyphicon = '<span class="glyphicon glyphicon-remove boldRedText" title="Failed"></span>';
                break;
            default:
                $connectionStateGlyphicon = '<span class="glyphicon glyphicon-ok boldOrangeText" title="Unknown"></span>';
                break;
        }

        return $connectionStateGlyphicon;
    }
}

if (!function_exists('makePagination')) {
    function makePagination($currentPage, $maxPage, $linkBase, $GET_Variable = 'n')
    {
        $startPage = ($currentPage < 6) ? 1 : $currentPage - 5;
        $endPage = 9 + $startPage;
        $endPage = ($maxPage < $endPage) ? $maxPage : $endPage;
        $diff = ($startPage != $endPage)
            ? $startPage - $endPage + 9
            : 0;
        $startPage -= ($startPage - $diff > 0) ? $diff : 1;

        $paginationDisplay = '';

        $paginationDisplay .= '<div class="row">';

        //Render the "FIRST" or not
        if ($startPage > 1) {
            //If our first number is greater than one, let's add a first
            $paginationDisplay .= "<div class='col-sm-1'><a class='nav-clickable' href='{$linkBase}{$GET_Variable}=1'>First</a></div>";
        } else if ($startPage == 1) {
            if ($currentPage == 1) {
                //Bold if we are on page 1
                $paginationDisplay .= "<div class='col-sm-1 text-center'><a class='nav-clickable' href='{$linkBase}{$GET_Variable}=1'><strong>1</strong></a></div>";
            } else {
                //Don't bold if we are not on page one
                $paginationDisplay .= "<div class='col-sm-1 text-center'><a class='nav-clickable' href='{$linkBase}{$GET_Variable}=1'>1</a></div>";
            }

            //If we already at the end, finish here
            if ($startPage == $endPage) {
                $paginationDisplay .= '</div>';

                return $paginationDisplay;
            }

            //We want to render the same number of pages, so let's push the window along
            $startPage++;
            $endPage++;
        }
        if ($endPage >= $maxPage) {
            $startPage--;
        }

        //Render the middle numbers or not
        for ($i = $startPage; $i <= $endPage; $i++) {
            $prettyPageNumber = number_format($i);
            if ($i == $currentPage) {
                $paginationDisplay .= "<div class='col-sm-1 text-center'><a class='nav-clickable' href='{$linkBase}{$GET_Variable}={$i}'><strong>{$prettyPageNumber}</strong></a></div>";
            } else {
                $paginationDisplay .= "<div class='col-sm-1 text-center'><a class='nav-clickable' href='{$linkBase}{$GET_Variable}={$i}'>{$prettyPageNumber}</a></div>";
            }
        }

        //Render the "LAST" or not
        if ($endPage < $maxPage) {
            $paginationDisplay .= "<div class='col-sm-1 text-right'><a class='nav-clickable' href='{$linkBase}{$GET_Variable}={$maxPage}'>Last</a></div>";
        }

        $paginationDisplay .= '</div>';

        return $paginationDisplay;
    }
}

if (!function_exists('generate_csp')) {
    function generate_csp(array $csp):string
    {
        if (empty($csp)) throw new Exception("Empty CSP declaration!");

        $csp_parts = array();
        foreach ($csp as $key => $value) {
            $csp_parts[] = $key . ' ' . implode(' ', $value);
        }

        $csp_string = implode('; ', $csp_parts);

        return $csp_string;
    }
}

if (!class_exists('curl_improved')) {
    class curl_improved
    {
        private $ch = null;
        private $isBehindProxy = null;
        private $hasEnabledProxy = false;

        public function __construct(bool $behindProxy, string $link = null)
        {
            $this->isBehindProxy = $behindProxy;

            $this->ch = curl_init();
            $this->setOptions();
            $this->setUserAgent();
            $this->setTimeOuts();

            if (!empty($link)) {
                $this->setLink($link);
            }
        }

        public function __destruct()
        {
            $this->closeLink();
        }

        public function setLink(string $link, $GET_fields = array())
        {
            if (!empty($GET_fields) && is_array($GET_fields)) {
                $fields = $this->fieldArrayToString($GET_fields);
                $link .= $fields;
            }

            curl_setopt($this->ch, CURLOPT_URL, $link);
        }

        public function getPage()
        {
            if ($this->isBehindProxy === true) {
                if ($this->hasEnabledProxy === false) {
                    throw new Exception('Config says we are behind proxy! We must setProxyDetails() before attempting to grab page!');
                }
            }

            $page = curl_exec($this->ch);

            if (empty($page) || !$page) {
                $page = curl_errno($this->ch);
                $page .= ' - ' . curl_error($this->ch);
            }

            $this->closeLink();

            return $page;
        }

        public function closeLink()
        {
            if (gettype($this->ch) == 'resource') {
                curl_close($this->ch);
            }
        }

        public function setOptions(array $options = array(CURLOPT_RETURNTRANSFER => 1, CURLOPT_HEADER => 0, CURLOPT_FOLLOWLOCATION => 1, CURLOPT_AUTOREFERER => 1, CURLOPT_HTTPHEADER => array('Expect:')))
        {

            if (empty($options) || !isset($options[CURLOPT_RETURNTRANSFER])) {
                curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
            }

            if (empty($options) || !isset($options[CURLOPT_HEADER])) {
                curl_setopt($this->ch, CURLOPT_HEADER, 0);
            }

            if (empty($options) || !isset($options[CURLOPT_FOLLOWLOCATION])) {
                curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 1);
            }

            if (empty($options) || !isset($options[CURLOPT_AUTOREFERER])) {
                curl_setopt($this->ch, CURLOPT_AUTOREFERER, 1);
            }

            if (empty($options) || !isset($options[CURLOPT_HTTPHEADER])) {
                curl_setopt($this->ch, CURLOPT_HTTPHEADER, array('Expect:'));
            }

            if (!empty($options) && is_array($options)) {
                curl_setopt_array($this->ch, $options);
            }
        }

        public function setUserAgent(string $userAgent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1')
        {
            empty($userAgent)
                ? $userAgent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1'
                : null;

            $this->setOptions(array(CURLOPT_USERAGENT => $userAgent));
        }

        public function setTimeOuts(int $connectTimeout = 5, int $executeTimeout = 10)
        {
            empty($connectTimeout) || !is_numeric($connectTimeout)
                ? $connectTimeout = 5
                : null;

            empty($executeTimeout) || !is_numeric($executeTimeout)
                ? $executeTimeout = 10
                : null;

            $this->setOptions(array(CURLOPT_CONNECTTIMEOUT => $connectTimeout, CURLOPT_TIMEOUT => $executeTimeout));
        }

        public function setReferrer(string $referrer = 'https://google.com')
        {
            if (isset($referrer)) {
                $this->setOptions(array(CURLOPT_REFERER => $referrer));
            } else {
                throw new Exception('Attempted to set empty referrer!');
            }
        }

        public function setPostFields($postFields = array())
        {
            $fields = $this->fieldArrayToString($postFields);
            $this->setOptions(array(CURLOPT_POST => 1, CURLOPT_POSTFIELDS => $fields));
        }

        public function fieldArrayToString($fields = array())
        {
            if (!empty($fields) && is_array($fields)) {
                $fields_string = '';
                foreach ($fields as $key => $value) {
                    $value = rtrim($value);
                    $fields_string .= $key . '=' . $value . '&';
                }
                //rtrim($fields_string, '&');
                $fields_string = substr($fields_string, 0, -1);

                $fields = $fields_string;
                unset($fields_string);
            } else {
                throw new Exception('Invalid fields array! Not an array or empty!');
            }

            if (!empty($fields)) {
                return $fields;
            } else {
                throw new Exception('Invalid fields array! Empty!');
            }
        }

        public function setCookie(string $cookie)
        {
            if (!empty($cookie)) {
                $this->setOptions(array(CURLOPT_COOKIEJAR => $cookie, CURLOPT_COOKIEFILE => $cookie));
            } else {
                throw new Exception('Empty cookie path!');
            }
        }

        public function setSSLcert(string $SSLcertPath)
        {
            if (!empty($SSLcertPath)) {
                if (is_file($SSLcertPath)) {
                    $this->setOptions(
                        array(
                            CURLOPT_SSL_VERIFYPEER => true,
                            CURLOPT_SSL_VERIFYHOST => 2,
                            CURLOPT_CAINFO => getcwd() . '/' . $SSLcertPath
                        )
                    );
                } else {
                    throw new Exception('Invalid SSL cert path!');
                }
            } else {
                throw new Exception('Empty SSL cert path!');
            }
        }

        public function setProxyDetails(string $proxyAddress, string $proxyPort = '8080', string $proxyType = 'HTTP', string $proxyUser = NULL, string $proxyPass = NULL, bool $debug = false)
        {
            if ($this->isBehindProxy === false) {
                return false;
            }

            if (!empty($proxyAddress)) {
                //PORT
                if ($debug) echo 'PORT: ' . $proxyPort . '<br />';
                if (!empty($proxyPort)) {
                    $this->setOptions(array(CURLOPT_PROXYPORT => $proxyPort));
                } else {
                    $this->setOptions(array(CURLOPT_PROXYPORT => '8080'));
                }

                //TYPE
                if ($debug) echo 'TYPE: ' . $proxyType . '<br />';
                if (!empty($proxyType)) {
                    $this->setOptions(array(CURLOPT_PROXYTYPE => $proxyType));
                } else {
                    $this->setOptions(array(CURLOPT_PROXYTYPE => 'HTTP'));
                }

                //ADDRESS
                if ($debug) echo 'ADDRESS: ' . $proxyAddress . '<br />';
                $this->setOptions(array(CURLOPT_PROXY => $proxyAddress));

                //AUTH
                if (!empty($proxyUser) || !empty($proxyPass)) {
                    $proxyAuthCredentials = $proxyUser . ':' . $proxyPass;
                    if ($debug) echo 'AUTH: ' . $proxyAuthCredentials . '<br />';
                    $this->setOptions(array(CURLOPT_PROXYUSERPWD => $proxyAuthCredentials));
                } else {
                    $proxyAuthCredentials = NULL;
                }

                //curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
                //curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, false);

                $this->hasEnabledProxy = true;
            } else {
                throw new Exception('Attempted to user Proxy with empty proxy address!');
            }
        }
    }
}

if (!function_exists('convert_array_bools')) {
    function convert_array_bools(Array $data)
    {
        function converter(&$value, $key)
        {
            if (is_bool($value)) {
                $value = ($value ? 1 : 0);
            }
        }

        array_walk_recursive($data, 'converter');
        return $data;
    }
}

if (!function_exists('generateUUIDv4')) {
    function generateUUIDv4()
    {
        if (version_compare(PHP_VERSION, '7.0.0', '<')) {
            return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

                // 32 bits for "time_low"
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),

                // 16 bits for "time_mid"
                mt_rand(0, 0xffff),

                // 16 bits for "time_hi_and_version",
                // four most significant bits holds version number 4
                mt_rand(0, 0x0fff) | 0x4000,

                // 16 bits, 8 bits for "clk_seq_hi_res",
                // 8 bits for "clk_seq_low",
                // two most significant bits holds zero and one for variant DCE1.1
                mt_rand(0, 0x3fff) | 0x8000,

                // 48 bits for "node"
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
        } else {
            return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

                // 32 bits for "time_low"
                random_int(0, 0xffff), random_int(0, 0xffff),

                // 16 bits for "time_mid"
                random_int(0, 0xffff),

                // 16 bits for "time_hi_and_version",
                // four most significant bits holds version number 4
                random_int(0, 0x0fff) | 0x4000,

                // 16 bits, 8 bits for "clk_seq_hi_res",
                // 8 bits for "clk_seq_low",
                // two most significant bits holds zero and one for variant DCE1.1
                random_int(0, 0x3fff) | 0x8000,

                // 48 bits for "node"
                random_int(0, 0xffff), random_int(0, 0xffff), random_int(0, 0xffff)
            );
        }
    }
}

if (!function_exists('random_string')) {
    function random_string(int $length = 12)
    {
        if (!empty($length) && is_numeric($length)) {
            return bin2hex(random_bytes($length));
        } else {
            throw new Exception("Invalid random string length!");
        }
    }
}