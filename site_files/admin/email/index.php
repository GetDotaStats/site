<?php
try {
    require_once('../../connections/parameters.php');
    require_once('../../global_functions.php');

    if (!isset($_SESSION)) {
        session_start();
    }

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB `gds_site`!');

    $memcached = new Cache(NULL, NULL, $localDev);

    {//do auth stuff
        checkLogin_v2();
        if (empty($_SESSION['user_id64'])) throw new Exception('Not logged in!');

        $adminCheck = adminCheck($_SESSION['user_id64'], 'admin');
        if (empty($adminCheck)) throw new Exception('Do not have `admin` privileges!');

        $adminCheck = adminCheck($_SESSION['user_id64'], 'email');
        if (empty($adminCheck)) throw new Exception('Do not have `email` privileges!');
    }
    unset($db);

    //switch to email DB
    $db = new dbWrapper_v3($hostname_jimmydorry_email, $username_jimmydorry_email, $password_jimmydorry_email, $database_jimmydorry_email, true);
    if (empty($db)) throw new Exception('No DB `jimmydorry_email`!');

    echo '<h2>Create a email alias pair</h2>';
    echo '<p>This form allows admins to create a new email alias pair.</p>';

    /*echo '<div class="text-center">
                <a class="nav-clickable btn btn-default btn-lg" href="#admin__mod_schema">Schema List</a>
                <a class="nav-clickable btn btn-default btn-lg" href="#admin__mod_schema_edit">Edit Schema</a>
           </div>';*/

    echo '<hr />';

    echo '<form id="addEmailAlias">';

    echo '<div id="custom_email_master">';
    {
        echo '<div class="row">
                    <div class="col-md-2"><strong>ID</strong></div>
                    <div class="col-md-6"><input class="formTextArea boxsizingBorder" name="email_id" type="text" maxlength="14" size="20"></div>
                </div>';
        echo '<div class="row">
                    <div class="col-md-2"><strong>Service</strong></div>
                    <div class="col-md-6"><input class="formTextArea boxsizingBorder" name="service_name" type="text" maxlength="100" size="45"></div>
                </div>';
        echo '<div class="row">
                    <div class="col-md-2"><strong>Username</strong></div>
                    <div class="col-md-6"><input class="formTextArea boxsizingBorder" name="user_name" type="text" maxlength="50" size="45"></div>
                </div>';
        echo '<div class="row">
                    <div class="col-md-2"><strong>Main URL</strong></div>
                    <div class="col-md-6"><input class="formTextArea boxsizingBorder" name="service_main_url" type="text" maxlength="255" size="45"></div>
                </div>';
        echo '<div class="row">
                    <div class="col-md-2"><strong>Login URL</strong></div>
                    <div class="col-md-6"><input class="formTextArea boxsizingBorder" name="service_login_url" type="text" maxlength="255" size="45"></div>
                </div>';

        echo '<span class="h5">&nbsp;</span>';

        echo '<div class="row">
                <div class="col-md-7 text-center">
                    <button id="sub" class="btn btn-success">Create</button>
                </div>
            </div>';
    }
    echo '</div>';
    echo '<span class="h5">&nbsp;</span>';

    echo '</form>';

    echo '<span id="schemaCustomAJAXResult" class="labelWarnings label label-danger"></span>';

    echo '<script type="application/javascript">
            $("#addEmailAlias").submit(function (event) {
                event.preventDefault();

                $.post("./admin/email/create_ajax.php", $("#addEmailAlias").serialize(), function (data) {
                    try {
                        if(data){
                            var response = JSON.parse(data);
                            if(response && response.error){
                                $("#schemaCustomAJAXResult").html(response.error);
                            }
                            else if(response && response.result){
                                loadPage("#admin__email/",1);
                            }
                            else{
                                $("#schemaCustomAJAXResult").html(data);
                            }
                        }
                    }
                    catch(err) {
                        $("#schemaCustomAJAXResult").html("Parsing Error: " + err.message + "<br />" + data);
                    }
                }, "text");
            });
        </script>';

    echo '<span class="h4">&nbsp;</span>';

    $emailPairs = cached_query(
        'admin_email_pairs',
        "SELECT
                `email_id`,
                `service_name`,
                `user_name`,
                `service_main_url`,
                `service_login_url`,
                `date_updated`,
                `date_recorded`
            FROM `email_lookup`
            ORDER BY `date_updated` DESC;"
    );
    if (empty($emailPairs)) throw new Exception("No email pairs to list!");

    echo '<div class="row">
                    <div class="col-md-2"><strong>ID</strong></div>
                    <div class="col-md-3"><strong>Service</strong></div>
                    <div class="col-md-4"><strong>Username</strong></div>
                    <div class="col-md-1">
                        <div class="row">
                            <div class="col-md-6 text-center"><strong>M</strong></div>
                            <div class="col-md-6 text-center"><strong>L</strong></div>
                        </div>
                    </div>
                    <div class="col-md-2 text-center"><strong>Updated</strong></div>
                </div>';
    echo "<hr />";

    foreach ($emailPairs as $key => $emailPairsData) {
        $emailID = $emailPairsData['email_id'];
        $emailServiceName = $emailPairsData['service_name'];
        $emailUserName = !empty($emailPairsData['user_name'])
            ? $emailPairsData['user_name']
            : '-';
        $emailServiceMainURL = "<a target='_blank' href='{$emailPairsData['service_main_url']}'><span class='glyphicon glyphicon-new-window'>&nbsp;</span></a>";
        $emailLoginMainURL = !empty($emailPairsData['service_login_url'])
            ? "<a target='_blank' href='{$emailPairsData['service_login_url']}'><span class='glyphicon glyphicon-new-window'>&nbsp;</span></a>"
            : '-';
        $emailDateUpdated = relative_time_v3($emailPairsData['date_updated']);
        $emailDateRecorded = relative_time_v3($emailPairsData['date_recorded']);

        echo "<div class='row'>
                    <div class='col-md-2'><div>{$emailID}</div></div>
                    <div class='col-md-3'><div>{$emailServiceName}</div></div>
                    <div class='col-md-4'><div>{$emailUserName}</div></div>
                    <div class='col-md-1'>
                        <div class='row'>
                            <div class='col-md-6 text-center'>{$emailServiceMainURL}</div>
                            <div class='col-md-6 text-center'>{$emailLoginMainURL}</div>
                        </div>
                    </div>
                    <div class='col-md-2 text-right'>{$emailDateUpdated}</div>
                </div>";
    }

    echo "<span class='h4'>&nbsp;</span>";

} catch (Exception $e) {
    echo formatExceptionHandling($e, true);
} finally {
    if (isset($memcached)) $memcached->close();
}