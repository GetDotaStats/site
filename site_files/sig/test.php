<?php
try {
    require_once('../global_functions.php');
    require_once('../connections/parameters.php');
    require_once('./functions_v2.php');

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    $account_id = !empty($_GET["aid"]) && is_numeric($_GET["aid"])
        ? $_GET["aid"]
        : 28755155;
    $flush_DB_stats = !empty($_GET["flush_acc"]) && $_GET["flush_acc"] == 1
        ? true
        : false;
    $required_hero_min_play = 14;
    $cacheTimeHours = 2;

    $dotabuff_stats = get_account_details($account_id, 4, $required_hero_min_play, $flush_DB_stats, $cacheTimeHours);

    echo '<h1>db_stats</h1>';
    echo '<pre>';
    print_r($dotabuff_stats);
    echo '</pre>';

    echo '<hr />';

    $steamID = new SteamID($account_id);
    if (empty($steamID->getSteamID32()) || empty($steamID->getSteamID64())) throw new Exception('Bad steamID!');

    $mmr_stats = $db->q(
        /*'SELECT
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
            LIMIT 0,1;',*/
        'SELECT
                *
            FROM `gds_users_mmr`
            WHERE `user_id32` = ?
            ORDER BY `date_recorded` DESC
            LIMIT 0,1;',
        's',
        $steamID->getsteamID32()
    );

    echo '<h1>LX data:</h1>';
    echo '<pre>';
    print_r($mmr_stats);
    echo '</pre>';

    echo '<hr />';

    /*$timeSinceUpdated = !empty($mmr_stats[0]['date_recorded'])
        ? time() - strtotime($mmr_stats[0]['date_recorded'])
        : 0;*/

    $timeSinceUpdated = relative_time_v3($mmr_stats[0]['date_recorded']);

    echo $timeSinceUpdated . '<br />';

    $memcache->close();
} catch (Exception $e) {
    echo formatExceptionHandling($e);
}