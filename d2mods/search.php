<?php
require_once('../global_functions.php');
require_once('./functions.php');
require_once('../connections/parameters.php');

try {
    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    echo '<h1>User and Match Search</h1>';

    echo '<p>This form allows users to search for specific users or matches, given their ID or custom steam URL.</p>';

    echo '<div class="container">
            <div class="row">
                <form id="searchForm" class="navbar-form navbar-left" role="search">
                    <div class="form-group">
                        <input name="user" type="text" class="form-control" placeholder="UserID or MatchID">
                    </div>
                    <button type="submit" class="btn btn-default">Search</button>
                </form>
            </div>
        </div>';

    echo '<script type="application/javascript">
                $("#searchForm").submit(function (event) {
                    event.preventDefault();
                    var searchTerm = $("input:first").val();

                    if (searchTerm.length == 32) {
                        loadPage("#d2mods__match?id=" + searchTerm, 1);
                        //window.location.replace("#d2mods__match?id=" + searchTerm);
                    }
                    else {
                        loadPage("#d2mods__profile?id=" + searchTerm, 1);
                        //window.location.replace("#d2mods__profile?id=" + searchTerm);
                    }
                });
            </script>
            ';


    echo '<span class="h4">&nbsp;</span>';

    echo '<div class="text-center">
            <a class="nav-clickable btn btn-default btn-lg" href="#s2__directory">Mod Directory</a>
            <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__recent_games">Recent Games</a>
           </div>';

    echo '<span class="h4">&nbsp;</span>';
} catch (Exception $e) {
    echo formatExceptionHandling($e);
} finally {
    if (isset($memcache)) $memcache->close();
}