<?php
try {
    require_once('../../global_functions.php');
    require_once('../../connections/parameters.php');

    if (!isset($_SESSION)) {
        session_start();
    }

    echo '<h2>Add User to Site Cache</h2>';
    echo '<p>This tool is used to add users to the site cache. If you find yourself routinely using this tool,
        please open an issue describing why... as there is likely a deficient process that can be improved.</p>';

    echo '<form id="userAdd">';
    echo '<div class="row">
                <div class="col-md-5"><input class="formTextArea boxsizingBorder" name="user_id" type="text" maxlength="100" placeholder="URL or steamID64 or steamID32"></div>
                <div class="col-md-2"><button id="sub" class="btn btn-success">Add</button></div>
            </div>';
    echo '</form>';

    echo '<span class="h5">&nbsp;</span>';

    echo '<span id="userAddAJAXResult" class="labelWarnings label label-danger"></span>';

    echo '<script type="application/javascript">
            $("#userAdd").submit(function (event) {
                event.preventDefault();

                $.post("./admin/tools/add_site_user_ajax.php", $("#userAdd").serialize(), function (data) {
                    try {
                        if(data){
                            var response = JSON.parse(data);
                            if(response && response.error){
                                $("#userAddAJAXResult").html(response.error);
                            }
                            else if(response && response.result){
                                $("#userAddAJAXResult").html(response.result);
                            }
                            else{
                                $("#userAddAJAXResult").html(data);
                            }
                        }
                    }
                    catch(err) {
                        $("#modDetailsAddAJAXResult").html("Parsing Error: " + err.message + "<br />" + data);
                    }
                }, "text");
            });
        </script>';

} catch (Exception $e) {
    echo formatExceptionHandling($e);
} finally {
    if (isset($memcache)) $memcache->close();
}