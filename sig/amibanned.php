<?php
require_once('../connections/parameters.php');
require_once('./functions.php');

$google_start = time();
$google = curl('http://google.com');
$google_end = time();
echo 'Google: ' . ($google_end - $google_start) . 's<br />';

ob_flush();
flush();

$yahoo_start = time();
$yahoo = curl('http://yahoo.com');
$yahoo_end = time();
echo 'Yahoo: ' . ($yahoo_end - $yahoo_start) . 's<br />';

ob_flush();
flush();

$dotabuff_start = time();
$dotabuff = curl('http://dotabuff.com');
$dotabuff_end = time();
echo 'Dotabuff: ' . ($dotabuff_end - $dotabuff_start) . 's<br />';

ob_flush();
flush();

//echo $google . '<hr />';
//echo $yahoo . '<hr />';
echo $dotabuff . '<hr />';
