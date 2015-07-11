<?php
require_once('../global_functions.php');
require_once('../connections/parameters.php');

try {
    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    echo '<h2>Highscores</h2>';

    echo '<p>Welcome to the GetDotaStats Highscore leaderboards! Here is where we recognise outstanding performances by members of
    the community, in the field of mod excellence. Below is a list of mods and their respective leaderboards.</p>';

    echo '<div class="alert alert-info" role="alert"><p><strong>Note</strong>: The leaderboards are updated every 10minutes. New scores are highlighted for 2 hours.</p></div>';

    echo '<div class="alert alert-warning" role="alert"><p><strong>Note</strong>: This system is in Beta, so very few mod developers have been exposed to it so far, which is why there are not many leaderboards.</p></div>';

    echo '<span class="h3">&nbsp;</span>';

    $mods = cached_query(
        'mod_hs_schema',
        'SELECT
                shms.`highscoreID`,
                shms.`highscoreIdentifier`,
                shms.`modID`,
                shms.`highscoreName`,
                shms.`highscoreDescription`,
                shms.`highscoreActive`,
                shms.`highscoreObjective`,
                shms.`highscoreOperator`,
                shms.`highscoreFactor`,
                shms.`highscoreDecimals`,
                shms.`date_recorded`,
                ml.`mod_name`
            FROM `stat_highscore_mods_schema` shms
            JOIN `mod_list` ml ON shms.`modID` = ml.`mod_identifier`
            WHERE `highscoreActive` = 1
            ORDER BY shms.`date_recorded`;',
        NULL,
        NULL,
        15
    );

    if (!empty($mods)) {
        foreach ($mods as $key => $value) {
            $highscoreDescription = !empty($value['highscoreDescription'])
                ? $value['highscoreDescription']
                : 'No description given.';

            if ($key % 2 == 0) {
                echo '<div class="row">';
            }

            echo '
                    <div class="col-md-6">
                        <div class="text-center">
                            <span class="h3">' . $value['mod_name'] . '<br /><small>' . $value['highscoreName'] . '</small></span>
                        </div>

                        <span class="h4">&nbsp;</span>

                        <div class="text-center">
                            <a class="nav-clickable btn btn-warning btn-lg" href="#d2mods__mod_leaderboard?lid=' . $value['highscoreIdentifier'] . '">Leaderboard</a>
                        </div>

                        <span class="h4">&nbsp;</span>

                        <p>' . $highscoreDescription . '</p>
                    </div>
                ';

            if ($key % 2 != 0) {
                echo '
                        </div>
                        <span class="h1">&nbsp;</span>
                    ';
            }
        }

        if ($key % 2 == 0) {
            echo '<div class="col-md-6">&nbsp;</div>
                    </div>
                    <span class="h1">&nbsp;</span>';
        }
    } else {
        echo bootstrapMessage('Oh Snap', 'No active mods with highscores!', 'danger');
    }

    $memcache->close();
} catch (Exception $e) {
    echo formatExceptionHandling($e);
}