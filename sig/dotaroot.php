<?php
require_once('../connections/parameters.php');
require_once('./functions.php');
header("Content-type: image/png");
header("Pragma: public");
header("Cache-Control: maxage=7200");
header("Expires: " . date(DATE_RFC822, strtotime(" 2 hours")));

!empty($_GET["aid"]) && is_numeric($_GET["aid"]) ? $account_id = $_GET["aid"] : $account_id = 28755155;
//!empty($_GET["base"]) && is_file($base_img_dir_sig . $_GET["base"]) ? $base_img_name = $_GET["base"] : $base_img_name = 'base2.png';
@$_GET["flush_acc"] == 1 ? $flush_acc = 1 : $flush_acc = 0;

$base_img_name = 'base_dotaroot.png';
$file_name_location = './images/generated/dr_' . $account_id . '.png';

$db = new dbWrapper($hostname_sig, $username_sig, $password_sig, $database_sig, false);

empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] = NULL : NULL;

$db->q("INSERT INTO `access_log` (`aid`, `remote_ip`, `referer`) VALUES (?, ?, ?)", "iss", $account_id, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_REFERER']);

if (isset($_GET['flush']) && $_GET['flush'] == 1) {
    if (file_exists($file_name_location)) {
        @unlink($file_name_location);
    }
}

if (!file_exists($file_name_location) || (filemtime($file_name_location) <= strtotime('-2 hours'))) {
//if(empty($find_account_id) || !file_exists('./images/generated/'.$account_id.'.png')){

    @unlink('./images/generated/' . $account_id . '.png');

    putenv('GDFONTPATH=' . realpath('.'));
    //$text_colour = imagecolorallocate( $base_img, 255, 255, 255 );
    $font_norm = 'arial.ttf';
    $font_bold = 'arialbd.ttf';
    $font = 'arialbd.ttf';

    $required_hero_min_play = 14;
    $sig_stats_winrate = get_account_char_winrate($account_id, 4, $required_hero_min_play, $flush_acc);
    $sig_stats_most_played = get_account_char_mostplayed($account_id, 4, $required_hero_min_play, $flush_acc);

    $base_img = imagecreatefrompng($base_img_dir_sig . $base_img_name);

    // We need to know the width and height of the overlay
    list($src_width, $src_height, $src_type, $src_attr) = getimagesize($base_img_dir_sig . $base_img_name);

    if ($sig_stats_winrate != 'Timeout' && $sig_stats_most_played != 'Timeout') {
        if ($sig_stats_winrate != 'Rate-limited' && $sig_stats_most_played != 'Rate-limited') {
            if (!empty($sig_stats_winrate) || !empty($sig_stats_most_played)) {
                $overlay_initial_spacing_x = 5;
                $overlay_initial_spacing_y = 5;

                $overlay_default_spacing_x = 5;
                $overlay_default_spacing_y = 5;

                $overlay_width = 54;
                $overlay_height = 30;

                //////////////////////////////////////////////
                // Apply the overlays for mostplayed
                //////////////////////////////////////////////
                if (isset($sig_stats_most_played['heroes'])) {
                    $x = $y = 0;
                    for ($i = 0; $i < count($sig_stats_most_played['heroes']); $i++) {
                        $image_file = './images/' . $sig_stats_most_played['heroes'][$i]['pic'] . '.png';

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
                        $overlay_text = $sig_stats_most_played['heroes'][$i]['gamesplayed'] . ' (' . round($sig_stats_most_played['heroes'][$i]['winrate'], 0) . '%)';

                        $text_colour = imagecolorallocate($base_img, 0, 0, 0);

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
                if (isset($sig_stats_winrate['heroes'])) {
                    $x = $y = 0;
                    for ($i = 0; $i < count($sig_stats_winrate['heroes']); $i++) {
                        $image_file = './images/' . $sig_stats_winrate['heroes'][$i]['pic'] . '.png';

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
                        $overlay_text = round($sig_stats_winrate['heroes'][$i]['winrate'], 0) . '% (' . $sig_stats_winrate['heroes'][$i]['gamesplayed'] . ')';

                        $text_colour = imagecolorallocate($base_img, 0, 0, 0);

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
                $image_file = $sig_stats_winrate['user_pic'];

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
                    $text_colour = imagecolorallocate($base_img, 47, 79, 79); //78, 213, 84 );
                }

                $font_size = 15;
                strlen($sig_stats_winrate['username']) > 40 ? $username = substr($sig_stats_winrate['username'], 0, 40) . '...' : $username = $sig_stats_winrate['username'];
                $tb = imagettfbbox($font_size, 0, $font, $username);

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
                    $font,
                    $username);

                /////////////////////////////
                //ACCOUNT WIN %
                /////////////////////////////
                $mmr_stats = $db->q(
                    'SELECT `rank_solo`, `rank_team`, `dota_wins` FROM `mmr` WHERE `steam_id` = ? LIMIT 0,1;',
                    'i',
                    $account_id
                );

                if (!empty($mmr_stats[0]['dota_wins'])) {
                    $dota_wins = $mmr_stats[0]['dota_wins'];
                } else if (!empty($sig_stats_winrate['account_win'])) {
                    $dota_wins = $sig_stats_winrate['account_win'];
                } else {
                    $dota_wins = '???';
                }

                $font_size = 16;
                $text_colour = imagecolorallocate($base_img, 119, 136, 153); //78, 213, 84 );
                $overlay_text = $sig_stats_winrate['account_percent'] . ' (' . $dota_wins . ' wins)';
                $tb = imagettfbbox($font_size, 0, $font, $overlay_text);

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
                    $font,
                    $overlay_text);

                //////////////////////////
                //ACCOUNT MMR
                //////////////////////////
                if (!empty($mmr_stats)) {
                    $rank_solo = !empty($mmr_stats[0]['rank_solo'])
                        ? $mmr_stats[0]['rank_solo']
                        : '???';
                    $rank_team = !empty($mmr_stats[0]['rank_team'])
                        ? $mmr_stats[0]['rank_team']
                        : '???';

                    $font_size = 9;
                    $overlay_text = $rank_solo . ' | ' . $rank_team;
                } else {
                    $font_size = 7;
                    $overlay_text = 'Visit our webpage to add MMR';
                }

                $text_colour = imagecolorallocate($base_img, 105, 105, 105); //78, 213, 84 );
                //$overlay_text = $sig_stats_winrate['account_win'] . ' wins';
                $tb = imagettfbbox($font_size, 0, $font, $overlay_text);

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
                    $font,
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


            } else {
                //NO USERACCOUNT FOUND
                $font_size = 14;
                $text_colour = imagecolorallocate($base_img, 78, 213, 84);
                $overlay_text = 'User does not exist or has not shared history.';
                $tb = imagettfbbox($font_size, 0, $font, $overlay_text);

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
                    $font,
                    $overlay_text);
            }
        } else {
            //RATE-LIMITED
            $font_size = 14;
            $text_colour = imagecolorallocate($base_img, 78, 213, 84);
            $overlay_text = 'Limited stats while site is rate-limited';
            $tb = imagettfbbox($font_size, 0, $font, $overlay_text);

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
                $font,
                $overlay_text);
        }
    } else {
        //BANNED?
        $font_size = 14;
        $text_colour = imagecolorallocate($base_img, 78, 213, 84);
        $overlay_text = 'No stats while site is banned';
        $tb = imagettfbbox($font_size, 0, $font, $overlay_text);

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
            $font,
            $overlay_text);
    }


    imagepng($base_img, $file_name_location);
    imagedestroy($base_img);

    $db->q("INSERT INTO `generated` (`account_id`, `time_generated`)
				VALUES (?, NOW()) 
				ON DUPLICATE KEY UPDATE `last_generated` = NOW()",
        "i", $account_id);

    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($file_name_location)) . ' GMT', true, 200);
    header('Content-Length: ' . filesize($file_name_location));
}

$headers = apache_request_headers(); //<=================== TURN OFF ON LOCALHOST
if (isset($headers['If-Modified-Since']) && (strtotime($headers['If-Modified-Since']) == filemtime($file_name_location))) {
    // Client's cache IS current, so we just respond '304 Not Modified'.
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($file_name_location)) . ' GMT', true, 304);
    header('Content-Length: ' . filesize($file_name_location));
}

readfile($file_name_location);

/*Array Example values of TB
(
    [0] => 0 // lower left X coordinate
    [1] => -1 // lower left Y coordinate
    [2] => 198 // lower right X coordinate
    [3] => -1 // lower right Y coordinate
    [4] => 198 // upper right X coordinate
    [5] => -20 // upper right Y coordinate
    [6] => 0 // upper left X coordinate
    [7] => -20 // upper left Y coordinate
)*/
?>