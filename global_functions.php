<?php
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

if (!class_exists("dbWrapper_v2")) {
    Class dbWrapper_v2
    {
        protected $_mysqli;
        protected $_debug;

        public function __construct($host, $username, $password, $database, $debug = true)
        {
            $this->_mysqli = new mysqli($host, $username, $password, $database);
            $this->_debug = (bool)$debug;
            if (mysqli_connect_errno()) {
                if ($this->_debug) {
                    //echo mysqli_connect_error();
                    //debug_print_backtrace();
                    throw new Exception(mysqli_connect_error());
                }
                return false;
            }
            return true;
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
                        //echo mysqli_error($this->_mysqli);
                        //debug_print_backtrace();
                        throw new Exception(mysqli_error($this->_mysqli));
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
                    //echo mysqli_connect_error();
                    //debug_print_backtrace();
                    throw new Exception(mysqli_connect_error());
                }
                return false;
            }
            $this->q('SET NAMES utf8;');
            return true;
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
                            $args_ref[$k] = & $arg;
                        }
                    } else {
                        $args_ref = array();
                        $args_ref[] = func_get_arg(1);
                        foreach ($x[2] as $k => &$arg) {
                            $args_ref[] = & $arg;
                        }
                    }

                    call_user_func_array(array($query, 'bind_param'), $args_ref);
                }
                $query->execute();

                if ($query->errno) {
                    if ($this->_debug) {
                        //echo mysqli_error($this->_mysqli);
                        //debug_print_backtrace();
                        throw new Exception(mysqli_error($this->_mysqli));
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
        if ($timeout) {
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
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

        if (!$page) {
            $page = false;
        }

        curl_close($ch);
        return $page;
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
    function simple_cached_query($memcached_name, $sql = '', $cache_time_secs = 600)
    {
        global $memcache, $db;

        $variable = $memcache->get($memcached_name);
        if (!$variable) {
            if ($sql) {
                $variable = $db->q($sql);
                $memcache->set($memcached_name, $variable, 0, $cache_time_secs);
            } else {
                return 'No sql provided!!!';
            }
        }
        return $variable;
    }
}

if (!function_exists("cached_query")) {
    function cached_query($memcached_name, $sqlQuery, $declarationString = NULL, $parameterArray = NULL, $cache_time_secs = 600)
    {
        global $memcache, $db;

        if ($memcache) {
            $variable = $memcache->get($memcached_name);
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

                    $memcache->set($memcached_name, $variable, 0, $cache_time_secs);
                } else {
                    return 'No DB provided!!!';
                }
            }
            return $variable;
        } else {
            return 'No memcached provided!!!';
        }
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
        if (isset($_COOKIE['session'])) {
            global $_COOKIE;
            global $hostname_gds_site;
            global $username_gds_site;
            global $password_gds_site;
            global $database_gds_site;

            $db = new dbWrapper($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, false);
            $db->q('SET NAMES utf8;');

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
                    $_SESSION['access_feeds'] = $accountDetails[0]['access_feeds'];
                    $_SESSION['isAdmin'] = $accountDetails[0]['isAdmin'];
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
                unset($_SESSION['access_feeds']);
                unset($_SESSION['isAdmin']);

                $domain = ($_SERVER['HTTP_HOST'] != 'localhost') ? '.' . $_SERVER['HTTP_HOST'] : false;
                setcookie('session', '', time() - 3600, '/', $domain);

                return false;
            }
        } else {
            return false;
        }
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
            $APIresult = curl('http://api.steampowered.com/ISteamUser/ResolveVanityURL/v0001/?key=' . $this->steamAPIKey . '&vanityurl=' . $vanityURL);

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

                return $APIresult;
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