#!/usr/bin/php -q
<?php
require_once('../../../connections/parameters.php');
require_once('../../../global_functions.php');

try {
    $db = new dbWrapper_v2($hostname_sig, $username_sig, $password_sig, $database_sig);

    ////////////////////////
    //GEOIP STATS
    ////////////////////////
    {
        $db->q('CREATE TABLE IF NOT EXISTS `stats_2_temp_access_log` SELECT INET_ATON(`remote_ip`) as remote_ip, `date_accessed` FROM access_log LIMIT 0, 100;');
        $db->q("CREATE TABLE IF NOT EXISTS `stats_2_geoip_count` (
                `hour` int(2) NOT NULL DEFAULT '0',
                `day` int(2) NOT NULL DEFAULT '0',
                `month` int(2) NOT NULL DEFAULT '0',
                `year` int(4) NOT NULL DEFAULT '0',
                `sig_views` bigint(21) NOT NULL DEFAULT '0',
                `country_code` varchar(5) NOT NULL DEFAULT '0',
                `date_accessed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`year`,`month`,`day`,`hour`)
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

        $db->q('TRUNCATE `stats_2_temp_access_log`;');

        $db->q('INSERT INTO `stats_2_temp_access_log`
            SELECT INET_ATON(`remote_ip`) as remote_ip, `date_accessed` FROM `access_log`
            WHERE `date_accessed` >= (SELECT DATE_FORMAT( IF( MAX(  `date_accessed` ) >0, MAX(  `date_accessed` ) , (SELECT MIN(  `date_accessed` ) FROM  `access_log` ) ) ,  "%Y-%m-%d %H:00:00") FROM stats_2_geoip_count)
            LIMIT 0,100000;'); //used to be 200000

        /*
            INSERT INTO `stats_2_geoip_count`
            SELECT
                HOUR(`date_accessed`) as `hour`,
                DAY(`date_accessed`) as `day`,
                MONTH(`date_accessed`) as `month`,
                YEAR(`date_accessed`) as `year`,
                COUNT(*) AS total_views,
                (SELECT `country_code` FROM geoip WHERE `rep_end` >= t1.`remote_ip` ORDER BY rep_end ASC LIMIT 1) as country_code,
                DATE_FORMAT(MAX(`date_accessed`), "%Y-%m-%d %H:00:00") as `date_accessed`
            FROM stats_2_temp_access_log t1
            GROUP BY 4,3,2,1,6
            ORDER BY 4 DESC,3 DESC,2 DESC,1 DESC,6 DESC;
         */

        exit(); //////////////////////////////////////////////////////////////////////////////////////////////

        $db->q(
            'INSERT INTO `stats_2_geoip_count`
                SELECT
                    HOUR(`date_accessed`) as `hour`,
                    DAY(`date_accessed`) as `day`,
                    MONTH(`date_accessed`) as `month`,
                    YEAR(`date_accessed`) as `year`,
                    COUNT(*) as `sig_views`,
                    DATE_FORMAT(MAX(`date_accessed`), "%Y-%m-%d %H:00:00") as `date_accessed`
                FROM `stats_2_temp_access_log`
                GROUP BY 4,3,2,1
                ORDER BY 4 DESC,3 DESC,2 DESC,1 DESC
            ON DUPLICATE KEY UPDATE
                `sig_views` = VALUES(`sig_views`);');

        $db->q('DROP TABLE `stats_2_temp_access_log`;');

        $last10_rows = $db->q('SELECT * FROM `stats_2_geoip_count` ORDER BY `date_accessed` DESC LIMIT 0,10;');

        echo '<table border="1" cellspacing="1">';
        echo '<tr>
                <th>Views</th>
                <th>Date</th>
            </tr>';
        foreach($last10_rows as $key => $value){
            echo '<tr>
                    <td>'.$value['sig_views'].'</td>
                    <td>'.$value['date_accessed'].'</td>
                </tr>';
        }
        echo '</table>';

        /*echo '<pre>';
        print_r($last10_rows);
        echo '</pre>';*/
    }
} catch (Exception $e) {
    echo $e->getMessage();
}