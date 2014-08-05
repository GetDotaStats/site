<!--<link rel="alternate" href="./rss/" title="GetDotaStats Animu Feed" type="application/rss+xml"/>-->
<?php
if (!isset($_SESSION)) {
    session_start();
}

if (empty($_SESSION['user_id64'])) {
    header("Location: ./");
}

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

<br />

<span id="result" class="label label-danger"></span>

<hr />

<div id="feedsList"></div>

<script type="application/javascript">
    $(document).ready(function () {
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
    }

    $("#myForm").submit(function (event) {
        event.preventDefault();

        $.post("./feeds/feeds_insert.php", $("#myForm").serialize(), function (data) {
            $("#myForm :input").each(function () {
                $(this).val('');
            });
            $('#result').html(data);
            testFunction();
        }, 'text');
    });
</script>
