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

if (!function_exists("get_account_char_winrate")) {
    function get_account_char_winrate($account_id = '28755155', $limit_result = NULL, $min_games = 15, $flush = 0)
    {
        $memcache = new Memcache;
        $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

        if ($flush == 1) {
            $memcache->delete("d2_accountstats" . $account_id . '-' . $limit_result . '-' . $min_games . '-HighestWinRate');
        }

        $big_array = $memcache->get("d2_accountstats" . $account_id . '-' . $limit_result . '-' . $min_games . '-HighestWinRate');
        if (!$big_array) {
            $page = curl('http://dotabuff.com/players/' . $account_id . '/heroes?metric=winning&date=&game_mode=&match_type=real');

            if (stristr($page, 'DOTABUFF - Not Found') || !$page) {
                return false;
            }

            $big_array = array();

            $big_array['user_pic'] = str_replace('full', 'medium', cut_str(cut_str($page, '<div class="image-container image-container-avatar image-container-player">', '</div>'), 'src="', '"'));

            $big_array['username'] = cut_str($page, '<h1>', '<small>');

            $page_stats = cut_str($page, '<div id="content-header-secondary">', '</div><div id="content-interactive">');

            $page_stats = explode('<dl', $page_stats);

            $big_array['last_match'] = cut_str($page_stats[1], 'datetime="', '"');
            $big_array['account_win'] = cut_str($page_stats[2], '<span class="wins">', '</span>');
            $big_array['account_loss'] = cut_str($page_stats[2], '<span class="losses">', '</span>');
            $big_array['account_abandons'] = cut_str($page_stats[2], '<span class="abandons">', '</span>');
            $big_array['account_percent'] = cut_str($page_stats[3], '<dd>', '</dd>');

            $page = cut_str($page, '<tbody>', '</tbody>');

            $page_array = explode('<tr>', $page);
            empty($limit_result) ? $limit_result = count($page_array) : NULL;

            $i = 0;

            foreach ($page_array as $key => $value) {
                if ($key > 0 && $i < $limit_result) {
                    $page_array_test = explode('<td', $value);

                    $games_played = cut_str($page_array_test[4], '>', '<div');

                    if ($games_played > $min_games) {
                        $big_array['heroes'][$i]['name'] = cut_str($page_array_test[1], '<img alt="', '"');
                        $big_array['heroes'][$i]['pic'] = cut_str($page_array_test[2], '<a href="', '"');
                        $big_array['heroes'][$i]['winrate'] = cut_str($page_array_test[3], '>', '<div');
                        $big_array['heroes'][$i]['gamesplayed'] = $games_played;

                        $i++;
                    }
                }
            }

            $memcache->set("d2_accountstats" . $account_id . '-' . $limit_result . '-' . $min_games . '-HighestWinRate', $big_array, 0, 60 * 60);
        }

        $memcache->close();

        if (empty($big_array['username'])) {
            return false;
        } else {
            return $big_array;

        }
    }
}

if (!function_exists("get_account_char_mostplayed")) {
    function get_account_char_mostplayed($account_id = '28755155', $limit_result = NULL, $flush = 0)
    {
        $memcache = new Memcache;
        $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

        if ($flush == 1) {
            $memcache->delete("d2_accountstats" . $account_id . '-' . $limit_result . '-MostPlayed');
        }

        $big_array = $memcache->get("d2_accountstats" . $account_id . '-' . $limit_result . '-MostPlayed');
        if (!$big_array) {
            $page = curl('http://dotabuff.com/players/' . $account_id . '/heroes');

            if (stristr($page, '<h2 id="status">Not Found</h2>') || !$page) {
                return false;
            }

            $big_array = array();

            $big_array['user_pic'] = str_replace('full', 'medium', cut_str(cut_str($page, '<div class="image-container image-container-avatar image-container-player">', '</div>'), 'src="', '"'));

            $big_array['username'] = cut_str($page, '<h1>', '<small>');

            $page_stats = cut_str($page, '<div id="content-header-secondary">', '</div><div id="content-interactive">');

            $page_stats = explode('<dl', $page_stats);

            $big_array['last_match'] = cut_str($page_stats[1], 'datetime="', '"');
            $big_array['account_win'] = cut_str($page_stats[2], '<span class="wins">', '</span>');
            $big_array['account_loss'] = cut_str($page_stats[2], '<span class="losses">', '</span>');
            $big_array['account_abandons'] = cut_str($page_stats[2], '<span class="abandons">', '</span>');
            $big_array['account_percent'] = cut_str($page_stats[3], '<dd>', '</dd>');

            $page = cut_str($page, '<tbody>', '</tbody>');

            $page_array = explode('<tr>', $page);
            empty($limit_result) ? $limit_result = count($page_array) : NULL;

            $i = 0;

            foreach ($page_array as $key => $value) {
                if ($key > 0 && $i < $limit_result) {
                    $page_array_test = explode('<td', $value);

                    $games_played = cut_str($page_array_test[3], '>', '<div');

                    $big_array['heroes'][$i]['name'] = cut_str($page_array_test[1], '<img alt="', '"');
                    $big_array['heroes'][$i]['pic'] = cut_str($page_array_test[2], '<a href="', '"');
                    $big_array['heroes'][$i]['winrate'] = cut_str($page_array_test[4], '>', '<div');
                    $big_array['heroes'][$i]['gamesplayed'] = $games_played;

                    $i++;
                }
            }

            $memcache->set("d2_accountstats" . $account_id . '-' . $limit_result . '-MostPlayed', $big_array, 0, 60 * 60);
        }

        $memcache->close();

        if (empty($big_array['username'])) {
            return false;
        } else {
            return $big_array;

        }
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

if (!function_exists('LoadJPEG')) {
    function LoadJPEG($imgURL)
    {

        ##-- Get Image file from Port 80 --##
        $fp = fopen($imgURL, "r");
        $imageFile = fread($fp, 3000000);
        fclose($fp);

        ##-- Create a temporary file on disk --##
        $tmpfname = tempnam("/temp", "IMG");

        ##-- Put image data into the temp file --##
        $fp = fopen($tmpfname, "w");
        fwrite($fp, $imageFile);
        fclose($fp);

        ##-- Load Image from Disk with GD library --##
        $im = imagecreatefromjpeg($tmpfname);

        ##-- Delete Temporary File --##
        unlink($tmpfname);

        ##-- Check for errors --##
        if (!$im) {
            print "Could not create JPEG image $imgURL";
        }

        return $im;
    }
}