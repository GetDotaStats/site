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

    $adminCheck = adminCheck($_SESSION['user_id64'], 'admin');
    if (empty($adminCheck)) throw new Exception('Not an admin!');

    echo '<h2>Mod Version Check</h2>';
    echo '<p>This is the admin section dedicated to the overview of mod versions from the last 1000 games.</p>';


    try {
        echo '<h3>Versions</h3>';

        $modVersions = cached_query(
            'admin_last_30_service_runs',
            'SELECT
                    DISTINCT t1.`modID`,
                    ml.`mod_name`,
                    t1.`schemaVersion`
                FROM (
                    SELECT
                        s2m.`modID`,
                        s2m.`schemaVersion`
                    FROM `s2_match` s2m
                    ORDER BY s2m.`matchID` DESC
                    LIMIT 0,1000
                ) t1
                LEFT JOIN `mod_list` ml ON t1.`modID` = ml.`mod_id` ',
            NULL,
            NULL,
            30
        );

        if (empty($modVersions)) throw new Exception('No data to use!');

        echo "<div class='row'>
                    <div class='col-md-4'><strong>Mod</strong></div>
                    <div class='col-md-2'><strong>Version</strong></div>
                </div>";

        echo '<span class="h4">&nbsp;</span>';

        foreach ($modVersions as $key => $value) {
            $modID = $value['modID'];
            $modName = $value['mod_name'];
            $modVersion = $value['schemaVersion'];

            $modName = "<a class='nav-clickable' href='#s2__mod?id={$modID}'>{$modName}</a>";

            echo "<div class='row'>
                    <div class='col-md-4'>{$modName}</div>
                    <div class='col-md-2'>{$modVersion}</div>
                </div>";
        }
    } catch (Exception $e) {
        echo formatExceptionHandling($e);
    }


    echo '<span class="h4">&nbsp;</span>';

} catch (Exception $e) {
    echo formatExceptionHandling($e);
} finally {
    if (isset($memcache)) $memcache->close();
}