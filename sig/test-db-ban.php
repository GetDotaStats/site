<?php
require_once('../connections/parameters.php');
require_once('../global_functions.php');
require_once('./functions_v2.php');
set_time_limit(60);

$account_id = !empty($_GET["aid"]) && is_numeric($_GET["aid"]) ? $_GET["aid"] : 28755155;
//$flush_acc = !empty($_GET["flush_acc"]) && $_GET["flush_acc"] == 1 ? 1 : 0;
$flush_acc = 1;

$db = new dbWrapper_v3($hostname_sig, $username_sig, $password_sig, $database_sig, false);

$memcache = new Memcache;
$memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

$required_hero_min_play = 14;
$sig_stats_winrate = get_account_char_winrate($account_id, 4, $required_hero_min_play, $flush_acc);
$sig_stats_most_played = get_account_char_mostplayed($account_id, 4, $required_hero_min_play, $flush_acc);

$memcache->close();

echo '<pre>';
print_r($sig_stats_winrate);
echo '</pre>';

echo '<hr />';

echo '<pre>';
print_r($sig_stats_most_played);
echo '</pre>';

echo '<hr />';

echo htmlentities(curl('http://www.dotabuff.com/players/' . $account_id.'/heroes?metric=winning&date=&game_mode=&match_type=real', NULL, NULL, NULL, NULL, 10));