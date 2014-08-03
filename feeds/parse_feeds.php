<?php
require_once('../global_functions.php');
require_once('../connections/parameters.php');

try {
    $db = new dbWrapper($hostname_gds_feeds, $username_gds_feeds, $password_gds_feeds, $database_gds_feeds, false);
    if ($db) {
        $feeds = $db->q('SELECT * FROM `feeds_list` WHERE `enabled` = 1');

        if (!empty($feeds)) {
            foreach ($feeds as $key => $value) {
                $feedRAW = curl($value['feed_url']);
                if (stristr($feedRAW, '<description />')) {
                    $feedRAW = str_replace('<description />', '', $feedRAW);
                }

                $xml = simplexml_load_string($feedRAW);
                $xml->registerXPathNamespace('prefix', 'http://www.w3.org/2005/Atom');

                /*echo '<pre>';
                print_r($xml);
                echo '</pre>';
                exit();*/

                foreach ($xml->channel->item as $key2 => $value2) {
                    echo '<h2>' . $value2->title . '</h2>';
                    echo $value2->link . '<br />';
                    echo $value2->guid . '<br />';
                    echo date('Y-m-d H:i:s', strtotime($value2->pubDate)) . '<br />';
                    echo $value2->pubDate . '<hr />';

                    $db->q('INSERT INTO `mega_feed` (`item_guid`, `item_title`, `item_link`, `date_recorded`) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE `item_guid` = VALUES(`item_guid`), `item_title` = VALUES(`item_title`), `item_link` = VALUES(`item_link`), `date_recorded` = VALUES(`date_recorded`)',
                        'ssss',
                        $value2->guid, $value2->title, $value2->link, date('Y-m-d H:i:s', strtotime($value2->pubDate)));
                }
            }
        } else {
            echo 'No feeds!';
        }
    } else {
        echo 'No DB';
    }
} catch (Exception $e) {
    echo $e->getMessage();
}