<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>

<form id="myForm">
    <table border="1" cellspacing="1">
        <tr>
            <th align="left">Title</th>
            <td><input name="feed_title" type="text" required></td>
        </tr>
        <tr>
            <th align="left">URL</th>
            <td><input name="feed_url" type="text" required></td>
        </tr>
        <tr>
            <th align="left">Category</th>
            <td><input name="feed_category" type="text" required></td>
        </tr>
        <tr>
            <td colspan="2" align="center">
                <button id="sub">Save</button>
            </td>
        </tr>
    </table>
</form>

<span id="result" class="label label-danger"></span>

<hr/>

<div id="feedsList"></div>

<script type="application/javascript">
    $(document).ready(function () {
        loadPage();
    });

    function loadPage() {
        $.ajax({
            type: "GET",
            url: "./feeds.php",
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

        $.post("test_insert.php", $("#myForm").serialize(), function (data) {
            $("#myForm :input").each(function () {
                $(this).val('');
            });
            $('#result').html(data);
            loadPage();
        }, 'text');
    });
</script>