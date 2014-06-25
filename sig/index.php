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

$base_img_name = 'base2.png';
$file_name_location = './images/generated/' . $account_id . '.png';

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

    $base_img = imagecreatefrompng($base_img_dir_sig . $base_img_name);

    // We need to know the width and height of the overlay
    list($src_width, $src_height, $src_type, $src_attr) = getimagesize($base_img_dir_sig . $base_img_name);

    //NO USERACCOUNT FOUND
    $font_size = 14;
    $text_colour = imagecolorallocate($base_img, 78, 213, 84);
    $overlay_text = 'No stats while site is banned.';
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