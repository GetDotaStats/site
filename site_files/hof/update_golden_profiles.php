<?php
try {
    require_once('../connections/parameters.php');
    require_once('../global_functions.php');

    if (!isset($_SESSION)) {
        session_start();
    }

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcached = new Cache(NULL, NULL, $localDev);

    $webAPI = new steam_webapi($api_key1);

    //UPDATE GOLDEN PROFILES
    {
        set_time_limit(0);

        //WHERE `isParsed` = 0
        $hofDetails = $db->q(
            'SELECT
                  hof_gp.`auction_rank`,
                  hof_gp.`user_id64`,
                  hof_gp.`user_id32`
                FROM `hof_golden_profiles` hof_gp
                ORDER BY auction_rank ASC;'
        );

        if (empty($hofDetails)) throw new Exception('No golden profile to check!');

        foreach ($hofDetails as $key => $value) {
            if (!empty($value['user_id64']) && $value['user_id64'] != '-1') {
                echo 'Updating: ' . $value['user_id64'];

                $playerSummary = grabAndUpdateSteamUserDetails($value['user_id64']);

                echo ' (' . $playerSummary[0]['user_name'] . ')';

                echo '<br />';
            } else {
                echo 'Skipping: ' . $value['user_id64'];
            }
        }
    }
} catch (Exception $e) {
    $message = 'Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage();
    echo bootstrapMessage('Oh Snap', $message, 'danger');
} finally {
    if (isset($memcached)) $memcached->close();
}