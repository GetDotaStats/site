#!/usr/bin/php -q
<?php
require_once('../../../connections/parameters.php');
require_once('../../../global_functions.php');

try {
    $db = new dbWrapper($hostname_sig, $username_sig, $password_sig, $database_sig, true);

    ////////////////////////
    //GENERAL STATS
    ////////////////////////
    {
        /*$db->q(
            "CREATE TABLE IF NOT EXISTS `stats_1_count` (
                `hour` int(2) NOT NULL DEFAULT '0',
                `day` int(2) NOT NULL DEFAULT '0',
                `month` int(2) NOT NULL DEFAULT '0',
                `year` int(4) NOT NULL DEFAULT '0',
                `sig_views` bigint(21) NOT NULL DEFAULT '0',
                `date_accessed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`year`,`month`,`day`,`hour`)
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1;");*/


        $db->q('CREATE TABLE IF NOT EXISTS `stats_0_access_log` SELECT * FROM access_log LIMIT 0, 100;');
        $db->q("CREATE TABLE IF NOT EXISTS `stats_1_count` (
                `hour` int(2) NOT NULL DEFAULT '0',
                `day` int(2) NOT NULL DEFAULT '0',
                `month` int(2) NOT NULL DEFAULT '0',
                `year` int(4) NOT NULL DEFAULT '0',
                `sig_views` bigint(21) NOT NULL DEFAULT '0',
                `date_accessed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`year`,`month`,`day`,`hour`)
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

        $db->q('TRUNCATE `stats_0_access_log`;');

        $db->q('INSERT INTO `stats_0_access_log`
            SELECT * FROM `access_log`
            WHERE `date_accessed` >= (SELECT DATE_FORMAT( IF( MAX(  `date_accessed` ) >0, MAX(  `date_accessed` ) , (SELECT MIN(  `date_accessed` ) FROM  `access_log` ) ) ,  "%Y-%m-%d %H:00:00") FROM stats_1_count)
            LIMIT 0,200000;');

        $db->q(
            'INSERT INTO `stats_1_count`
                SELECT
                    HOUR(`date_accessed`) as `hour`,
                    DAY(`date_accessed`) as `day`,
                    MONTH(`date_accessed`) as `month`,
                    YEAR(`date_accessed`) as `year`,
                    COUNT(*) as `sig_views`,
                    DATE_FORMAT(MAX(`date_accessed`), "%Y-%m-%d %H:00:00") as `date_accessed`
                FROM `stats_0_access_log`
                GROUP BY 4,3,2,1
                ORDER BY 4 DESC,3 DESC,2 DESC,1 DESC
            ON DUPLICATE KEY UPDATE
                `sig_views` = VALUES(`sig_views`);');

        $db->q('DROP TABLE `stats_0_access_log`;');

        $last10_rows = $db->q('SELECT * FROM `stats_1_count` ORDER BY `date_accessed` DESC LIMIT 0,10;');

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