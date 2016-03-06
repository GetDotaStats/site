<?php
try {
    require_once('../global_functions.php');
    require_once('../connections/parameters.php');

    if (!isset($_SESSION)) {
        session_start();
    }

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcached = new Cache(NULL, NULL, $localDev);

    checkLogin_v2();
    if (empty($_SESSION['user_id64'])) throw new Exception('Not logged in!');

    $adminCheck = adminCheck($_SESSION['user_id64'], 'animufeed');
    if (empty($adminCheck)) {
        throw new Exception('Not an admin!');
    }

    $db = new dbWrapper_v3($hostname_gds_feeds, $username_gds_feeds, $password_gds_feeds, $database_gds_feeds, true);
    if (empty($db)) throw new Exception('No DB!');
    ?>

    <h1 class="text-center"><a href="./feeds/rss/" target="_blank">GetDotaStats Animu Feed</a></h1>

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
                        try {
                            $feeds = $db->q('SELECT `category_id`, `category_name`, `date_recorded` FROM `feeds_categories` ORDER BY `category_name` DESC;');

                            if (!empty($feeds)) {
                                foreach ($feeds as $key => $value) {
                                    echo '<option value="' . $value['category_id'] . '">' . $value['category_name'] . '</option>';
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

            echo '<tr>
                        <td>' . $value['category_name'] . '</td>
                        <td><a href="' . $value['feed_url'] . '" target="_new">' . $value['feed_title'] . '</a></td>
                        <td>' . $value['date_recorded'] . '</td>
                    </tr>';
        }

        echo '</table></div>';
    } else {
        echo bootstrapMessage('Oh Snap', 'No feeds!');
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
?>

<script type="application/javascript">
    $("#myForm").submit(function (event) {
        event.preventDefault();

        $.post("./feeds/feeds_insert.php", $("#myForm").serialize(), function (data) {
            $("#myForm :input").each(function () {
                $(this).val('');
            });

            $('#result').html(data);

            loadPage(document.getElementById("abcd").getAttribute("href"));
        }, 'text');
    });
</script>
