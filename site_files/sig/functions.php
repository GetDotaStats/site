<?php

$localDev = false;

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
        global $localDev;

        $memcached = new Cache(NULL, NULL, $localDev);

        if ($flush == 1) {
            $memcached->delete("d2_accountstats" . $account_id . '-' . $limit_result . '-' . $min_games . '-HighestWinRate');
        }

        $big_array = $memcached->get("d2_accountstats" . $account_id . '-' . $limit_result . '-' . $min_games . '-HighestWinRate');
        if (!$big_array) {
            $page = curl('http://dotabuff.com/players/' . $account_id . '/heroes?metric=winning&date=&game_mode=&match_type=real', NULL, NULL, NULL, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1', 10);

            if ($page == false) {
                return 'Timeout';
            } else if (stristr($page, 'DOTABUFF - Not Found') || !$page) {
                return false;
            } else if (stristr($page, 'DOTABUFF - Too Many Requests')) {
                return 'Rate-limited';
            }

            $big_array = array();

            $big_array['user_pic'] = str_replace('full', 'medium', cut_str(cut_str($page, '<div class="image-container image-container-player image-container-avatar">', '</div>'), 'src="', '"'));

            //$big_array['username'] = cut_str($page, '<h1>', '<small>');
            $big_array['username'] = cut_str($page, '<img alt="', '"');

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

            $memcached->set("d2_accountstats" . $account_id . '-' . $limit_result . '-' . $min_games . '-HighestWinRate', $big_array, 60 * 60);
        }

        $memcached->close();

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
        global $localDev;

        $memcached = new Cache(NULL, NULL, $localDev);

        if ($flush == 1) {
            $memcached->delete("d2_accountstats" . $account_id . '-' . $limit_result . '-MostPlayed');
        }

        $big_array = $memcached->get("d2_accountstats" . $account_id . '-' . $limit_result . '-MostPlayed');
        if (!$big_array) {
            $page = curl('http://dotabuff.com/players/' . $account_id . '/heroes?metric=played', NULL, NULL, NULL, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1', 10);

            if ($page == false) {
                return 'Timeout';
            } else if (stristr($page, 'DOTABUFF - Not Found') || !$page) {
                return false;
            } else if (stristr($page, 'DOTABUFF - Too Many Requests')) {
                return 'Rate-limited';
            }

            $big_array = array();

            $big_array['user_pic'] = str_replace('full', 'medium', cut_str(cut_str($page, '<div class="image-container image-container-player image-container-avatar">', '</div>'), 'src="', '"'));

            //$big_array['username'] = cut_str($page, '<h1>', '<small>');
            $big_array['username'] = cut_str($page, '<img alt="', '"');

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

            $memcached->set("d2_accountstats" . $account_id . '-' . $limit_result . '-MostPlayed', $big_array, 60 * 60);
        }

        $memcached->close();

        if (empty($big_array['username'])) {
            return false;
        } else {
            return $big_array;

        }
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