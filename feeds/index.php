<!--<link rel="alternate" href="./rss/" title="GetDotaStats Animu Feed" type="application/rss+xml"/>-->
<?php
require_once('../global_functions.php');
require_once('../connections/parameters.php');

if (!isset($_SESSION)) {
    session_start();
}

if (isset($_COOKIE['session']) && empty($_SESSION['user_id64'])) {
    checkLogin_v2();
}

if (empty($_SESSION['user_id64'])) {
    header("Location: ./");
}

echo '<h1 class="text-center"><a href="./feeds/rss/" target="_blank">GetDotaStats Animu Feed</a></h1>';

try {
    if (!empty($_SESSION['user_id64'])) {
        $db = new dbWrapper($hostname_gds_feeds, $username_gds_feeds, $password_gds_feeds, $database_gds_feeds, true);
        if ($db) {
            $accessCheck = $db->q('SELECT * FROM `access_list` WHERE `steam_id64` = ? LIMIT 0,1;',
                'i',
                $_SESSION['user_id64']);

            if (!empty($accessCheck)) {

                ?>


                <form id="myForm">
                    <table border="1" cellspacing="1">
                        <tr>
                            <th align="left">URL</th>
                            <td><input name="feed_url" type="text" required></td>
                        </tr>
                        <tr>
                            <th align="left">Title</th>
                            <td><input name="feed_title" type="text" required></td>
                        </tr>
                        <tr>
                            <th align="left">Category</th>
                            <td>
                                <!--<input name="feed_category" type="text" required>-->
                                <select id="feed_category" name="feed_category" required>  <!--Call run() function-->
                                    <option value="">--- Select ---</option>
                                    <?php
                                    require_once('../global_functions.php');
                                    require_once('../connections/parameters.php');

                                    try {
                                        $db = new dbWrapper($hostname_gds_feeds, $username_gds_feeds, $password_gds_feeds, $database_gds_feeds, false);
                                        if ($db) {
                                            $feeds = $db->q('SELECT `category_id`, `category_name`, `date_recorded` FROM `feeds_categories`;');

                                            if (!empty($feeds)) {
                                                foreach ($feeds as $key => $value) {
                                                    echo '<option value="' . $value['category_id'] . '">' . $value['category_name'] . '</option>';
                                                }
                                            }
                                        }
                                    } catch (Exception $e) {
                                        echo $e->getMessage();
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" align="center">
                                <button id="sub">Save</button>
                            </td>
                        </tr>
                    </table>
                </form>

                <br/>

                <span id="result" class="label label-danger"></span>

                <hr/>

                <?php
                $feeds = $db->q('SELECT fl.`feed_id`, fl.`feed_title`, fl.`feed_url`, fl.`feed_enabled`, fl.`date_recorded`, fl.`feed_category`, fc.`category_name` FROM `feeds_list` fl LEFT JOIN `feeds_categories` fc ON fl.`feed_category` = fc.`category_id` WHERE fl.`feed_enabled` = 1 ORDER BY fc.`category_name` DESC, fl.`date_recorded` DESC ;');

                if (!empty($feeds)) {

                    echo '<div id="feedsList" class="table-responsive">
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
?>

<script type="application/javascript">
    /*$(document).ready(function () {
     testFunction();
     });

     function testFunction() {
     $.ajax({
     type: "GET",
     url: "./feeds/feeds.php",
     dataType: "html",
     success: function (msg) {
     if (parseInt(msg) != 0) {
     $('#feedsList').html(msg);
     }
     },
     error: function (jqXHR, textStatus, errorThrown) {
     $('#feedsList').html('Failed to load page. Try again later.');
     }
     });
     }*/

    $("#myForm").submit(function (event) {
        event.preventDefault();

        $.post("./feeds/feeds_insert.php", $("#myForm").serialize(), function (data) {
            $("#myForm :input").each(function () {
                $(this).val('');
            });
            $('#result').html(data);
            //testFunction();
        }, 'text');
    });
</script>
