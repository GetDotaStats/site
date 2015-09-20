<?php
require_once('../global_functions.php');
require_once('../connections/parameters.php');

try {
    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    echo '<h1>User and Match Search</h1>';

    echo '<p>This form allows users to search for specific users or matches, given their ID or custom steam URL. Usernames must start with the search term.</p>';


    echo '<form id="searchForm">';
    echo '<div class="row">
                <div class="col-md-5"><input class="formTextArea boxsizingBorder" name="search_term" type="text" maxlength="100" placeholder="MatchID or Username or SteamProfile"></div>
                <div class="col-md-1"><button id="sub" class="btn btn-success">Search</button></div>
            </div>';
    echo '</form>';

    echo '<span class="h5">&nbsp;</span>';

    echo '<span id="AJAXResult" class="labelWarnings label label-danger"></span>';

    echo '<span class="h5">&nbsp;</span>';

    echo '<span id="searchResult" class="labelWarnings label label-danger"></span>';

    echo '<span class="h5">&nbsp;</span>';


    echo '<script type="application/javascript">
                $("#searchForm").submit(function (event) {
                    event.preventDefault();

                    $.post("./s2/search_ajax.php", $("#searchForm").serialize(), function (data) {
                        try {
                            if(data){
                                var response = JSON.parse(data);
                                if(response && response.error){
                                    $("#AJAXResult").html(response.error);
                                }
                                else{
                                    $("#AJAXResult").html(data);
                                }
                            }
                        }
                        catch(err) {
                            $("#AJAXResult").html("Parsing Error: " + err.message + "<br />" + data);
                        }
                    }, "text");
                });
            </script>
            ';
} catch (Exception $e) {
    echo formatExceptionHandling($e);
} finally {
    if (isset($memcache)) $memcache->close();
}