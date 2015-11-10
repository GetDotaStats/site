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
    echo '<p>This is the admin section dedicated to the overview of mod versions for active mods.</p>';
    echo '<p>We should be harassing mods to update to the most recent version of the library.</p>';


    try {
        echo '<h3>Versions</h3>';

        $modVersions = cached_query(
            'admin_version_check',
            'SELECT
                  ml.`mod_id`,
                  ml.`mod_name`,
                  (SELECT `schemaVersion` FROM `s2_match` WHERE `matchID` = (SELECT MAX(`matchID`) FROM `s2_match` WHERE `modID` = ml.`mod_id` LIMIT 0,1) LIMIT 0,1) AS `libraryVersion`
                FROM `mod_list` ml
                WHERE ml.`mod_active` = 1
                ORDER BY `libraryVersion` DESC, ml.`mod_name` ASC;',
            NULL,
            NULL,
            30
        );

        if (empty($modVersions)) throw new Exception('No data to use!');

        echo "<div class='row'>
                    <div class='col-md-4'><strong>Mod</strong></div>
                    <div class='col-md-1 text-center'><strong>Ver.</strong></div>
                </div>";

        echo '<span class="h5">&nbsp;</span>';

        foreach ($modVersions as $key => $value) {
            $modID = $value['mod_id'];
            $modName = $value['mod_name'];
            $libraryVersion = $value['libraryVersion'];

            $modName = "<a class='nav-clickable' href='#s2__mod?id={$modID}'>{$modName}</a>";

            echo "<div class='row'>
                    <div class='col-md-4'>{$modName}</div>
                    <div class='col-md-1 text-center'>{$libraryVersion}</div>
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