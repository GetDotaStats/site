<?php
require_once('../global_functions.php');
require_once('../connections/parameters.php');

try {
    $db = new dbWrapper($hostname_gds_feeds, $username_gds_feeds, $password_gds_feeds, $database_gds_feeds, true);
    if ($db) {
        $feeds = $db->q('SELECT fl.`feed_id`, fl.`feed_title`, fl.`feed_url`, fl.`feed_enabled`, fl.`date_recorded`, fl.`feed_category`, fc.`category_name` FROM `feeds_list` fl LEFT JOIN `feeds_categories` fc ON fl.`feed_category` = fc.`category_id` WHERE fl.`feed_enabled` = 1;');

        if (!empty($feeds)) {

            echo '<table border="1" cellspacing="1">';
            echo '<tr>
                        <th>Category</th>
                        <th>Feed</th>
                        <th>Enabled</th>
                        <th>Date Added</th>
                    </tr>';

            foreach ($feeds as $key => $value) {
                $value['category_name'] = empty($value['category_name'])
                    ? 'Unknown!'
                    : $value['category_name'];

                echo '<tr>
                        <td>' . $value['category_name'] . '</td>
                        <td><a href="' . $value['feed_url'] . '" target="_new">' . $value['feed_title'] . '</a></td>
                        <td>' . $value['feed_enabled'] . '</td>
                        <td>' . $value['date_recorded'] . '</td>
                    </tr>';
            }

            echo '</table>';
        } else {
            echo 'No feeds!';
        }
    } else {
        echo 'No DB';
    }
} catch (Exception $e) {
    echo $e->getMessage();
}