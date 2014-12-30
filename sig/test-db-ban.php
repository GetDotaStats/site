<?php
require_once('../connections/parameters.php');
require_once('./functions.php');
set_time_limit(60);

!empty($_GET["aid"]) && is_numeric($_GET["aid"]) ? $account_id = $_GET["aid"] : $account_id = 28755155;
@$_GET["flush_acc"] == 1 ? $flush_acc = 1 : $flush_acc = 0;

$db = new dbWrapper($hostname_sig, $username_sig, $password_sig, $database_sig, false);

$required_hero_min_play = 14;
$sig_stats_winrate = get_account_char_winrate($account_id, 4, $required_hero_min_play, $flush_acc);
$sig_stats_most_played = get_account_char_mostplayed($account_id, 4, $required_hero_min_play, $flush_acc);

echo '<pre>';
print_r($sig_stats_winrate);
echo '</pre>';

echo '<hr />';

echo '<pre>';
print_r($sig_stats_winrate);
echo '</pre>';
