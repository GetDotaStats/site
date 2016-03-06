<?php
try {
    require_once('../connections/parameters.php');
    require_once('../global_functions.php');
    require_once('./functions_v3.php');
    set_time_limit(60);

    $account_id = !empty($_GET["aid"]) && is_numeric($_GET["aid"]) ? $_GET["aid"] : 28755155;
    //$flush_acc = !empty($_GET["flush_acc"]) && $_GET["flush_acc"] == 1 ? 1 : 0;
    $flush_acc = 1;

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, false);
    if (empty($db)) throw new Exception('No DB!');

    $memcached = new Cache(NULL, NULL, $localDev);

    $required_hero_min_play = 14;
    $sig_stats_winrate = get_account_char_winrate($account_id, 4, $required_hero_min_play, $flush_acc);
    $sig_stats_most_played = get_account_char_mostplayed($account_id, 4, $required_hero_min_play, $flush_acc);
    $combined_method = getAndUpdateDBDetails($account_id, 4, $required_hero_min_play, $flush_acc);

    echo '<pre>';
    echo '<strong>get_account_char_winrate():</strong><br />';
    print_r($sig_stats_winrate);
    echo '</pre>';

    echo '<hr />';
    echo '<strong>get_account_char_mostplayed():</strong><br />';

    echo '<pre>';
    print_r($sig_stats_most_played);
    echo '</pre>';

    echo '<hr />';
    echo '<strong>getAndUpdateDBDetails():</strong><br />';

    echo '<pre>';
    print_r($combined_method);
    echo '</pre>';

    echo '<hr />';
    echo '<strong>Manual construction:</strong><br />';

    {
        $userDetails = cached_query(
            'sigs_db_user_details' . $account_id,
            'SELECT
                    `user_id32`,
                    `user_id64`,
                    `last_match`,
                    `account_win`,
                    `account_loss`,
                    `account_abandons`,
                    `account_percent`,
                    `winRateHeroes`,
                    `mostPlayedHeroes`,
                    `date_updated`,
                    `date_recorded`
                FROM `sigs_dotabuff_info`
                WHERE `user_id32` = ?
                LIMIT 0,1;',
            's',
            $account_id,
            1
        );

        if (!empty($userDetails)) {
            $bigArray = array(
                'last_match' => $userDetails[0]['last_match'],
                'account_win' => $userDetails[0]['account_win'],
                'account_loss' => $userDetails[0]['account_loss'],
                'account_abandons' => $userDetails[0]['account_abandons'],
                'account_percent' => $userDetails[0]['account_percent'],
                'winRateHeroes' => json_decode($userDetails[0]['winRateHeroes'], true),
                'mostPlayedHeroes' => json_decode($userDetails[0]['mostPlayedHeroes'], true),
            );

            echo '<pre>';
            print_r($bigArray);
            echo '</pre>';

            echo '<hr />';
        }
    }


    echo '<hr />?db_raw=1 for raw Dotabuff dump';

    if (!empty($_GET["db_raw"]) && $_GET["db_raw"] == 1) {
        echo '<br /><br />';
        echo htmlentities(curl('http://www.dotabuff.com/players/' . $account_id . '/heroes?metric=winning&date=&game_mode=&match_type=real', NULL, NULL, NULL, NULL, 10));
    }

} catch (Exception $e) {
    echo formatExceptionHandling($e);
} finally {
    if (isset($memcached)) $memcached->close();
}