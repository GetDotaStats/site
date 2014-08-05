<?php
require_once('../global_functions.php');
require_once('../connections/parameters.php');

if (!isset($_SESSION)) {
    session_start();
}

try {
    if (!empty($_SESSION['user_id64'])) {
        $db = new dbWrapper($hostname_gds_feeds, $username_gds_feeds, $password_gds_feeds, $database_gds_feeds, true);
        if ($db) {
            $accessCheck = $db->q('SELECT * FROM `access_list` WHERE `steam_id64` = ? LIMIT 0,1;',
                'i',
                $_SESSION['user_id64']);

            if (!empty($accessCheck)) {
                $feeds = $db->q('SELECT fl.`feed_id`, fl.`feed_title`, fl.`feed_url`, fl.`feed_enabled`, fl.`date_recorded`, fl.`feed_category`, fc.`category_name` FROM `feeds_list` fl LEFT JOIN `feeds_categories` fc ON fl.`feed_category` = fc.`category_id` WHERE fl.`feed_enabled` = 1 ORDER BY fc.`category_name` DESC, fl.`date_recorded` DESC ;');

                if (!empty($feeds)) {

                    echo '<div class="table-responsive">
		        <table class="table table-striped">';
                    echo '<tr>
                        <th>Category</th>
                        <th>Feed</th>
                        <th>Date Added</th>
                    </tr>';

                    foreach ($feeds as $key => $value) {
                        $value['category_name'] = empty($value['category_name'])
                            ? 'Unknown!'
                            : $value['category_name'];

                        /*$enabledCheckmark = empty($value['feed_enabled'])
                            ? '<span class="glyphicon glyphicon-remove"></span>'
                            : '<span class="glyphicon glyphicon-ok"></span>';*/

                        echo '<tr>
                        <td>' . $value['category_name'] . '</td>
                        <td><a href="' . $value['feed_url'] . '" target="_new">' . $value['feed_title'] . '</a></td>
                        <td>' . $value['date_recorded'] . '</td>
                    </tr>';
                    }

                    echo '</table></div>';
                } else {
                    echo 'No feeds!';
                }
            } else {
                echo 'This user account does not have access!';
            }

        } else {
            echo 'No DB';
        }
    } else {
        echo 'Not logged in!';
    }
} catch (Exception $e) {
    echo $e->getMessage();
}