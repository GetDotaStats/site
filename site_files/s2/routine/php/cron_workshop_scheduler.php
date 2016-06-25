#!/usr/bin/php -q
<?php
require_once('../../../connections/parameters.php');
require_once('../../../global_functions.php');
require_once('../../../cron_functions.php');

try {
    $db = new dbWrapper_v3($hostname_gds_cron, $username_gds_cron, $password_gds_cron, $database_gds_cron, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcached = new Cache(NULL, NULL, $localDev);

    $cron_workshop = new cron_workshop($db, $memcached, $localDev, $allowWebhooks, $runningWindows, $behindProxy, $webhook_gds_site_admin, $api_key1);
    $cron_workshop->queue(2);

} catch (Exception $e) {
    echo '<br />Caught Exception (MAIN) -- ' . basename($e->getFile()) . ':' . $e->getLine() . '<br />' . $e->getMessage() . '<br /><br />';
} finally {
    if (isset($memcached)) $memcached->close();
}