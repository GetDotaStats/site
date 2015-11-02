<?php
try {
    require_once('../global_functions.php');
    require_once('../connections/parameters.php');

    if (!isset($_SESSION)) {
        session_start();
    }

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    checkLogin_v2();
    if (empty($_SESSION['user_id64'])) throw new Exception('Not logged in!');

    $adminCheck = adminCheck($_SESSION['user_id64'], 'animufeed');
    if (empty($adminCheck)) {
        throw new Exception('Not an admin!');
    }

    $db = new dbWrapper_v3($hostname_gds_feeds, $username_gds_feeds, $password_gds_feeds, $database_gds_feeds, true);
    if (empty($db)) throw new Exception('No DB!');

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
} catch (Exception $e) {
    echo $e->getMessage();
}