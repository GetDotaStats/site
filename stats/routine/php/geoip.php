<?php
require_once('../../../global_functions.php');
require_once('../../../connections/parameters.php');

$start = time();

if (!isset($_SESSION)) {
    session_start();
}

try {
    $db = new dbWrapper_v2($hostname_sig, $username_sig, $password_sig, $database_sig);
    if ($db) {
        $memcache = new Memcache;
        $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"


        /*
        SELECT `country_code`
        FROM `geoip`
        WHERE INET_ATON("4.2.2.1") BETWEEN `rep_start` AND `rep_end`;
         */


        $time_start = time();

        $i = 0;
        $count = 1000;
        $query1 = 'SELECT
                COUNT(*) AS total_views,
                (SELECT `country_code` FROM geoip WHERE t1.`remote_ip` BETWEEN `rep_start` AND `rep_end`) as country_code
            FROM(
                SELECT
                    INET_ATON(remote_ip) as remote_ip
                FROM `access_log`
                LIMIT ' . $i . ',' . $count . '
            ) as t1
            GROUP BY 2
            ORDER BY 1 DESC;';

        $query2 = 'SELECT
                COUNT(*) AS total_views,
                (SELECT `country_code` FROM geoip WHERE `rep_end` >= t1.`remote_ip` ORDER BY rep_end ASC LIMIT 1) as country_code
            FROM(
                SELECT
                    INET_ATON(remote_ip) as remote_ip
                FROM `access_log`
                LIMIT ' . $i . ',' . $count . '
            ) as t1
            GROUP BY 2
            ORDER BY 1 DESC;';

        //echo $query . '<hr />';

        $production_stats = $db->q($query1);

        $time_end = time();
        echo "[QUERY] took " . secs_to_h($time_end - $time_start) . " to execute<br /><br />";


        foreach ($production_stats as $key => $value) {
            echo $value['country_code'] . ' - ' . $value['total_views'] . '<br />';
        }


        $memcache->close();
    } else {
        echo '<div class="page-header"><div class="alert alert-danger" role="alert"><strong>Oh Snap:</strong> No DB!</div></div>';
    }
} catch (Exception $e) {
    echo '<div class="page-header"><div class="alert alert-danger" role="alert"><strong>Oh Snap:</strong> Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage() . '</div></div>';
}