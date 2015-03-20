#!/usr/bin/php -q
<?php
require_once('../../../connections/parameters.php');
require_once('../../../global_functions.php');

try {
    $db = new dbWrapper_v3($hostname_gds_cron, $username_gds_cron, $password_gds_cron, $database_gds_cron, true);
    if (empty($db)) throw new Exception('No DB!');

    ////////////////////////
    //GENERAL STATS
    ////////////////////////
    {
        $db->q('CREATE TABLE IF NOT EXISTS `cron_sig_access_temp` SELECT * FROM `sigs_access_log` LIMIT 0, 100;');
        $db->q("CREATE TABLE IF NOT EXISTS `cron_sig_access` (
                `hour` int(2) NOT NULL DEFAULT '0',
                `day` int(2) NOT NULL DEFAULT '0',
                `month` int(2) NOT NULL DEFAULT '0',
                `year` int(4) NOT NULL DEFAULT '0',
                `sig_views` bigint(21) NOT NULL DEFAULT '0',
                `date_recorded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`year`,`month`,`day`,`hour`)
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

        $db->q('TRUNCATE `cron_sig_access_temp`;');

        $db->q('INSERT INTO `cron_sig_access_temp`
            SELECT * FROM `sigs_access_log`
            WHERE `date_recorded` >= (SELECT DATE_FORMAT( IF( MAX(  `date_recorded` ) >0, MAX(  `date_recorded` ) , (SELECT MIN(  `date_recorded` ) FROM  `sigs_access_log` ) ) ,  "%Y-%m-%d %H:00:00") FROM cron_sig_access)
            LIMIT 0,200000;');

        $db->q(
            'INSERT INTO `cron_sig_access`
                SELECT
                    HOUR(`date_recorded`) as `hour`,
                    DAY(`date_recorded`) as `day`,
                    MONTH(`date_recorded`) as `month`,
                    YEAR(`date_recorded`) as `year`,
                    COUNT(*) as `sig_views`,
                    DATE_FORMAT(MAX(`date_recorded`), "%Y-%m-%d %H:00:00") as `date_recorded`
                FROM `cron_sig_access_temp`
                GROUP BY 4,3,2,1
                ORDER BY 4 DESC,3 DESC,2 DESC,1 DESC
            ON DUPLICATE KEY UPDATE
                `sig_views` = VALUES(`sig_views`);');

        $db->q('DROP TABLE `cron_sig_access_temp`;');

        $last10_rows = $db->q('SELECT * FROM `cron_sig_access` ORDER BY `date_recorded` DESC LIMIT 0,10;');

        echo '<table border="1" cellspacing="1">';
        echo '<tr>
                <th>Views</th>
                <th>Date</th>
            </tr>';
        foreach($last10_rows as $key => $value){
            echo '<tr>
                    <td>'.$value['sig_views'].'</td>
                    <td>'.$value['date_recorded'].'</td>
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