<?php
require_once('../connections/parameters.php');
require_once('./functions.php');

$db = new dbWrapper($hostname_sig, $username_sig, $password_sig, $database_sig, false);

$account_id = !empty($_GET["aid"]) && is_numeric($_GET["aid"])
    ? $_GET["aid"]
    : 28755155;
$required_hero_min_play = 14;

$sig_stats_winrate = get_account_char_winrate($account_id, 4, $required_hero_min_play, 1);
$sig_stats_most_played = get_account_char_mostplayed($account_id, 4, $required_hero_min_play, 1);

/////////////////////////////
//ACCOUNT WIN %
/////////////////////////////
/*$mmr_stats = $db->q(
    'SELECT `rank_solo`, `rank_team`, `dota_wins` FROM `mmr` WHERE `steam_id` = ? LIMIT 0,1;',
    'i',
    $account_id
);*/

echo '<h1>sig_stats_winrate</h1>';
print_r($sig_stats_winrate);

echo '<hr />';

echo '<h1>sig_stats_most_played</h1>';
print_r($sig_stats_most_played);

echo '<hr />';

$mmr_stats = $db->q(
    'SELECT `rank_solo`, `rank_team`, `dota_wins`, `last_updated` FROM `mmr` WHERE `steam_id` = ? LIMIT 0,1;',
    'i',
    $account_id
);

echo '<h1>SteamTracks data:</h1>';
print_r($mmr_stats);

echo '<hr />';

$timeSinceUpdated = !empty($mmr_stats[0]['last_updated'])
    ? time() - $mmr_stats[0]['last_updated']
    : 0;

echo $timeSinceUpdated . '<br />';

if (!empty($mmr_stats[0]['dota_wins']) && $timeSinceUpdated != 0 && $timeSinceUpdated <= 1209600) {
    echo $mmr_stats[0]['dota_wins'] . ' - SteamTracks';
} else if (!empty($sig_stats_winrate['account_win'])) {
    echo $sig_stats_winrate['account_win'] . ' - DB';
} else {
    echo '???' . ' - Unknown';
}

/*echo '<hr />';

echo '<h1>mmr_stats</h1>';
print_r($mmr_stats);*/