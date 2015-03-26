<?php
try {
    //////////////////////////////////
    // STATIC DECLARATIONS                   <----- ALWAYS CHECK
    //////////////////////////////////
    $areWeLocalDevEnv = false; // Windows is weird and requires slightly different logic
    $required_hero_min_play = 14; // Minimum number of games for a hero to count
    $cacheTimeHours = 2; //cache things for 2hours

    $base_img_name = 'base2.png';
    $font_norm = 'arial.ttf';
    $font_bold = 'arialbd.ttf';

    $account_id = !empty($_GET["aid"]) && is_numeric($_GET["aid"])
        ? $_GET["aid"]
        : 28755155;

    $cacheTimeSeconds = $cacheTimeHours * 60 * 60;

    $file_name_location = $areWeLocalDevEnv
        ? '.\images\generated\\' . $account_id . '_main.png'
        : './images/generated/' . $account_id . '_main.png';
    //////////////////////////////////

    require_once('../connections/parameters.php');
    require_once('../global_functions.php');
    require_once('./functions_v2.php');
    set_time_limit(60);

    header("Content-type: image/png");
    header("Pragma: public");
    header("Cache-Control: maxage=$cacheTimeSeconds");
    header("Expires: " . date(DATE_RFC822, strtotime(" $cacheTimeHours hours")));

    //////////////////////////////////
    // INTERNALS
    //////////////////////////////////
    putenv('GDFONTPATH=' . realpath('.'));
    $banned = false;
    $areWeRegenning = false;
    $caughtException = false;

    $steamID = new SteamID($account_id);
    if (empty($steamID->getSteamID32()) || empty($steamID->getSteamID64())) throw new Exception('Bad steamID!');

    $file_name_location = $areWeLocalDevEnv
        ? '.\images\generated\\' . $steamID->getsteamID32() . '_main.png'
        : './images/generated/' . $steamID->getsteamID32() . '_main.png';

    $webAPI = new steam_webapi($api_key1);

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, false);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    /*!empty($_GET["base"]) && is_file($base_img_dir_sig . $_GET["base"])
        ? $base_img_name = $_GET["base"]
        : $base_img_name = 'base2.png';*/

    $flush_file = !empty($_GET["flush"]) && $_GET["flush"] == 1
        ? true
        : false;

    $flush_DB_stats = !empty($_GET["flush_acc"]) && $_GET["flush_acc"] == 1
        ? true
        : false;

    empty($_SERVER['HTTP_REFERER'])
        ? $_SERVER['HTTP_REFERER'] = NULL
        : NULL;
    //////////////////////////////////

    $db->q(
        "INSERT INTO `sigs_access_log`
            (`user_id32`, `user_id64`, `remote_ip`, `referer`)
            VALUES (?, ?, ?, ?)",
        "ssss",
        array(
            $steamID->getsteamID32(),
            $steamID->getsteamID64(),
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_REFERER']
        )
    );

    $lx_user_details = cached_query(
        'sigs_lx_user_details' . $steamID->getsteamID32(),
        'SELECT
                `user_id32`,
                `user_id64`,
                `user_name`,
                `user_games`,
                `user_mmr_solo`,
                `user_mmr_party`,
                `user_stats_disabled`,
                `date_recorded`
            FROM `gds_users_mmr`
            WHERE `user_id32` = ?
            ORDER BY `date_recorded` DESC
            LIMIT 0,1;',
        's',
        $steamID->getsteamID32(),
        1
    );

    $steam_user_details = cached_query(
        'sigs_steam_user_details' . $steamID->getsteamID32(),
        'SELECT
              `user_id64`,
              `user_id32`,
              `user_name`,
              `user_avatar`,
              `user_avatar_medium`,
              `user_avatar_large`,
              `date_recorded`
            FROM `gds_users`
            WHERE `user_id32` = ?
            LIMIT 0,1;',
        's',
        $steamID->getsteamID32(),
        1
    );

    if (empty($steam_user_details)) $steam_user_details = grabAndUpdateSteamUserDetails($steamID->getsteamID32());
    if (empty($steam_user_details)) throw new Exception('Couldn\'t get Steam user details');

    $steamUserName = htmlentitiesdecode_custom($steam_user_details[0]['user_name']);

    if (!empty($flush_file)) {
        @unlink($file_name_location);
    }

    if (!file_exists($file_name_location) || (filemtime($file_name_location) <= strtotime("-$cacheTimeHours hours"))) {
        $areWeRegenning = true;
        @unlink($file_name_location);

        $user_details = get_account_details($steamID->getsteamID32(), 4, $required_hero_min_play, $flush_DB_stats, $cacheTimeHours);

        $base_img = imagecreatefrompng($base_img_dir_sig . $base_img_name);
        list($src_width, $src_height, $src_type, $src_attr) = getimagesize($base_img_dir_sig . $base_img_name);

        //////////////////////////////////
        // EXCEPTIONS
        //////////////////////////////////
        if (empty($user_details)) throw new Exception('User does not exist or has not shared history');
        //////////////////////////////////

        $overlay_initial_spacing_x = 5;
        $overlay_initial_spacing_y = 5;

        $overlay_default_spacing_x = 5;
        $overlay_default_spacing_y = 5;

        $overlay_width = 54;
        $overlay_height = 30;

        //////////////////////////////////////////////
        // Apply the overlays for mostplayed
        //////////////////////////////////////////////
        if (!empty($user_details['mostPlayedHeroes'])) {
            $x = $y = 0;
            for ($i = 0; $i < count($user_details['mostPlayedHeroes']); $i++) {
                $image_file = './images/' . $user_details['mostPlayedHeroes'][$i]['pic'] . '.png';

                if (file_exists($image_file)) {
                    $image = imagecreatefrompng($image_file);
                } else {
                    $image = './images/bases/hero_default.png';
                }

                //list($overlay_width, $overlay_height) = getimagesize($image_file);

                if ($i > 0) {
                    $x = $x + $overlay_width + 5;
                } else {
                    $x = 18;
                }

                $y = 56;

                imagecopy($base_img,
                    $image,
                    $x,
                    $y,
                    0,
                    0,
                    $overlay_width,
                    $overlay_height);
                imagedestroy($image);

                ///////////////////////////////////
                //APPLY TEXT FOR OVERLAY
                ///////////////////////////////////
                $overlay_text = $user_details['mostPlayedHeroes'][$i]['gamesplayed'] . ' (' . round($user_details['mostPlayedHeroes'][$i]['winrate'], 0) . '%)';

                $text_colour = imagecolorallocate($base_img, 255, 255, 0);

                strlen($overlay_text) > 10 ? $font_size = 7 : $font_size = 8; //SMALLER FONT IF TOO MANY NUMBERS
                $tb = imagettfbbox($font_size, 0, $font_norm, $overlay_text);

                $text_box_height = abs($tb[1] - $tb[7]);

                $overlay_text_offset_x = 2;
                $overlay_text_offset_y = 2;

                imagettftext($base_img,
                    $font_size,
                    0,
                    ($x + $overlay_text_offset_x),
                    ($y + $overlay_height + $text_box_height + 5),
                    $text_colour,
                    $font_norm,
                    $overlay_text);
            }
        }

        //////////////////////////////////////////////
        // Apply the overlays for winrate
        //////////////////////////////////////////////
        if (!empty($user_details['winRateHeroes'])) {
            $x = $y = 0;
            for ($i = 0; $i < count($user_details['winRateHeroes']); $i++) {
                $image_file = './images/' . $user_details['winRateHeroes'][$i]['pic'] . '.png';

                if (file_exists($image_file)) {
                    $image = imagecreatefrompng($image_file);
                } else {
                    $image = './images/bases/hero_default.png';
                }

                //list($overlay_width, $overlay_height) = getimagesize($image_file);

                if ($i > 0) {
                    $x = $x + $overlay_width + 5;
                } else {
                    $x = 301;
                }

                $y = 56;

                imagecopy($base_img,
                    $image,
                    $x,
                    $y,
                    0,
                    0,
                    $overlay_width,
                    $overlay_height);
                imagedestroy($image);

                ///////////////////////////////////
                //APPLY TEXT FOR OVERLAY
                ///////////////////////////////////
                $overlay_text = round($user_details['winRateHeroes'][$i]['winrate'], 0) . '% (' . $user_details['winRateHeroes'][$i]['gamesplayed'] . ')';

                $text_colour = imagecolorallocate($base_img, 255, 255, 0);

                strlen($overlay_text) > 10 ? $font_size = 7 : $font_size = 8; //SMALLER FONT IF TOO MANY NUMBERS
                $tb = imagettfbbox($font_size, 0, $font_norm, $overlay_text);

                $text_box_height = abs($tb[1] - $tb[7]);

                $overlay_text_offset_x = 2;
                $overlay_text_offset_y = 2;

                imagettftext($base_img,
                    $font_size,
                    0,
                    ($x + $overlay_text_offset_x),
                    ($y + $overlay_height + $text_box_height + 5),
                    $text_colour,
                    $font_norm,
                    $overlay_text);
            }
        }

        ////////////////////////////
        //ADD Steam Avatar
        ////////////////////////////
        $image_file = $steam_user_details[0]['user_avatar_medium'];

        if (!empty($image_file)) {
            $image_file = LoadJPEG($image_file);
        } else {
            $image_file = imagecreatefrompng('./images/bases/steam_overlay.png');
        }

        //list($overlay_width, $overlay_height) = getimagesize($image_file);
        $overlay_width = imagesx($image_file);
        $overlay_height = imagesy($image_file);

        $steam_avatar_offset_x = 10;
        $steam_avatar_offset_y = 10;

        $steam_avatar_dimension = 30;

        imagecopyresampled($base_img,
            $image_file,
            $steam_avatar_offset_x,
            $steam_avatar_offset_y,
            0,
            0,
            $steam_avatar_dimension,
            $steam_avatar_dimension,
            64,
            64);

        imagedestroy($image_file);

        /////////////////////
        //USERNAME
        /////////////////////
        $list_of_mods = array();
        $list_of_mods[] = '28755155'; //jimmydorry

        if (in_array($account_id, $list_of_mods)) {
            $text_colour = imagecolorallocate($base_img, 228, 114, 151);
        } else {
            $text_colour = imagecolorallocate($base_img, 78, 213, 84);
        }

        $font_size = 15;
        strlen($steamUserName) > 40 ? $username = substr($steamUserName, 0, 40) . '...' : $username = $steamUserName;
        $tb = imagettfbbox($font_size, 0, $font_bold, $username);

        $overlay_text_offset_x = 7;
        $overlay_text_offset_y = 10;

        $x_a_un = $steam_avatar_dimension + $steam_avatar_offset_x + $overlay_text_offset_x;
        $y_a_un = (abs($tb[7]) - $tb[1]) / 2 + ($steam_avatar_dimension / 2) + $steam_avatar_offset_y;

        imagettftext($base_img,
            $font_size,
            0,
            $x_a_un,
            $y_a_un,
            $text_colour,
            $font_bold,
            $username);

        /////////////////////////////
        //ACCOUNT WIN %
        /////////////////////////////

        $dota_wins = !empty($user_details['account_win'])
            ? $user_details['account_win']
            : '???';

        $font_size = 16;
        $text_colour = imagecolorallocate($base_img, 78, 213, 84);
        $overlay_text = $user_details['account_percent'] . '% (' . $dota_wins . ' wins)';
        $tb = imagettfbbox($font_size, 0, $font_bold, $overlay_text);

        $overlay_text_offset_x = 10;
        $overlay_text_offset_y = 10;

        $x_a_wr = $src_width - $tb[2] - $overlay_text_offset_x;
        $y_a_wr = abs($tb[7]) - $tb[1] + $overlay_text_offset_y;

        imagettftext($base_img,
            $font_size,
            0,
            $x_a_wr,
            $y_a_wr,
            $text_colour,
            $font_bold,
            $overlay_text);

        //////////////////////////
        //ACCOUNT MMR
        //////////////////////////

        if (!empty($lx_user_details) && empty($lx_user_details[0]['user_stats_disabled'])) {
            $rank_solo = !empty($lx_user_details[0]['user_mmr_solo'])
                ? $lx_user_details[0]['user_mmr_solo']
                : '???';
            $rank_team = !empty($lx_user_details[0]['user_mmr_party'])
                ? $lx_user_details[0]['user_mmr_party']
                : '???';

            $font_size = 9;
            $overlay_text = $rank_solo . ' | ' . $rank_team;
        } else {
            $font_size = 9;
            $overlay_text = 'Visit our webpage to add MMR';
        }

        $text_colour = imagecolorallocate($base_img, 78, 213, 84);
        //$overlay_text = $user_details['account_win'] . ' wins';
        $tb = imagettfbbox($font_size, 0, $font_bold, $overlay_text);

        $overlay_text_offset_x = 10;
        $overlay_text_offset_y = 10;

        $x_a_wn = $src_width - $tb[2] - $overlay_text_offset_x;
        $y_a_wn = abs($tb[7]) - $tb[1] + $overlay_text_offset_y + $y_a_wr;

        imagettftext($base_img,
            $font_size,
            0,
            $x_a_wn,
            $y_a_wn,
            $text_colour,
            $font_bold,
            $overlay_text);

        //////////////////////////
        //VALVE MMR LOGO
        //////////////////////////
        $image_file_src = './images/bases/mmr_logo_v2.png';
        $image_file = imagecreatefrompng($image_file_src);

        list($overlay_width, $overlay_height) = getimagesize($image_file_src);

        $steam_avatar_offset_x = 10;
        $steam_avatar_offset_y = 5;

        $mmr_overlay_final_pos_x = $x_a_wn - $overlay_width;
        $mmr_overlay_final_pos_y = $y_a_wn - ($overlay_height / 2);

        $steam_avatar_dimension = 16;

        imagecopyresampled($base_img,
            $image_file,
            $mmr_overlay_final_pos_x,
            $mmr_overlay_final_pos_y,
            0,
            0,
            $steam_avatar_dimension,
            $steam_avatar_dimension,
            24,
            24);

        imagedestroy($image_file);

        $db->q("INSERT INTO `sigs_generated` (`user_id32`, `user_id64`, `date_modified`)
				  VALUES (?, ?, FROM_UNIXTIME(?))
				ON DUPLICATE KEY UPDATE
				  `date_modified` = VALUES(`date_modified`)",
            "sss",
            array(
                $steamID->getsteamID32(),
                $steamID->getsteamID64(),
                time()
            )
        );
    }
} catch (Exception $e) {
    $caughtException = true;

    if (file_exists($file_name_location)) @unlink($file_name_location);

    if (!isset($base_img)) {
        $base_img = imagecreatefrompng($base_img_dir_sig . $base_img_name);
        list($src_width, $src_height, $src_type, $src_attr) = getimagesize($base_img_dir_sig . $base_img_name);
    }

    $font_size = 14;
    $text_colour = imagecolorallocate($base_img, 78, 213, 84);
    $overlay_text = $e->getMessage();
    $tb = imagettfbbox($font_size, 0, $font_bold, $overlay_text);

    $overlay_text_offset_x = 0;
    $overlay_text_offset_y = 0;

    $x_a_nu = ($src_width - $tb[2]) / 2;
    $y_a_nu = abs($tb[7] - $tb[1]) + 10;

    imagettftext($base_img,
        $font_size,
        0,
        $x_a_nu,
        $y_a_nu,
        $text_colour,
        $font_bold,
        $overlay_text);

    imagepng($base_img, $file_name_location);
    imagedestroy($base_img);

    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($file_name_location)) . ' GMT', true, 200);
    header('Content-Length: ' . filesize($file_name_location));
} finally {
    if (isset($memcache)) $memcache->close();

    if ($areWeRegenning && !$caughtException) {
        imagepng($base_img, $file_name_location);
        imagedestroy($base_img);

        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($file_name_location)) . ' GMT', true, 200);
        header('Content-Length: ' . filesize($file_name_location));
    }

    if (!$areWeLocalDevEnv) {
        $headers = apache_request_headers();
    }

    if (file_exists($file_name_location) && isset($headers['If-Modified-Since']) && (strtotime($headers['If-Modified-Since']) == filemtime($file_name_location))) {
        // Client's cache IS current, so we just respond '304 Not Modified'.
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($file_name_location)) . ' GMT', true, 304);
        header('Content-Length: ' . filesize($file_name_location));
    }

    readfile($file_name_location);
}