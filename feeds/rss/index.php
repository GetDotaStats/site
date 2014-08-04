<?php
require_once('../../global_functions.php');
require_once('../../connections/parameters.php');

try {
    $db = new dbWrapper($hostname_gds_feeds, $username_gds_feeds, $password_gds_feeds, $database_gds_feeds, true);
    if ($db) {
        $feeds = $db->q('SELECT `item_guid`, `item_title`, `item_link`, `date_recorded` FROM `mega_feed` ORDER BY `date_recorded` DESC LIMIT 0,50;');

        if (!empty($feeds)) {
            header("Content-Type: application/rss+xml; charset=UTF-8");

            $rssfeed = '<?xml version="1.0" encoding="UTF-8"?>';
            $rssfeed = '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">';
            $rssfeed .= '<channel>';
            $rssfeed .= '<title>GetDotaStats Animu Feed</title>';
            $rssfeed .= '<atom:link href="http://getdotastats.com/feeds/rss" rel="self" type="application/rss+xml" />';
            $rssfeed .= '<link>http://getdotastats.com/feeds/rss</link>';
            $rssfeed .= '<description>This is a compilation of the latest anime</description>';
            $rssfeed .= '<language>en-us</language>';

            foreach($feeds as $key => $value){
                $rssfeed .= '<item>';
                $rssfeed .= '<title>' . htmlentities($value['item_title']) . '</title>';
                $rssfeed .= '<link>' . htmlentities($value['item_link']) . '</link>';
                $rssfeed .= '<guid>' . htmlentities($value['item_guid']) . '</guid>';
                $rssfeed .= '<pubDate>' . date("D, d M Y H:i:s O", strtotime($value['date_recorded'])) . '</pubDate>';
                $rssfeed .= '</item>';
            }

            $rssfeed .= '</channel>';
            $rssfeed .= '</rss>';

            echo $rssfeed;
        } else {
            echo 'No feeds!';
        }
    } else {
        echo 'No DB';
    }
} catch (Exception $e) {
    echo $e->getMessage();
}