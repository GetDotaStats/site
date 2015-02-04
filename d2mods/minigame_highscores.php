<?php
require_once('../global_functions.php');
require_once('../connections/parameters.php');

try {
    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    echo '<h2>Highscores</h2>';

    echo '<p>Welcome to the GetDotaStats Highscore leaderboards! Here is where we recognise outstanding performances by members of
    the community, in the field of mini game excellence.</p>';

    echo '<span class="h3">&nbsp;</span>';

    if ($db) {
        $minigames = cached_query(
            'minigames_list1',
            'SELECT
                    `minigameID`,
                    `minigameIdentifier`,
                    `minigameName`,
                    `minigameDeveloper`,
                    `minigameDescription`,
                    `minigameSteamGroup`,
                    `minigameActive`,
                    `minigameObjective`,
                    `minigameOperator`,
                    `minigameFactor`,
                    `date_recorded`
                FROM `stat_highscore_minigames`
                WHERE `minigameActive` = 1
                ORDER BY date_recorded;',
            NULL,
            NULL,
            15
        );

        if (!empty($minigames)) {
            foreach ($minigames as $key => $value) {
                $minigameDescription = !empty($value['minigameDescription'])
                    ? $value['minigameDescription']
                    : 'No description given.';

                if ($key % 2 == 0) {
                    echo '<div class="row">';
                }

                echo '
                    <div class="col-md-6">
                        <div class="text-center">
                            <a class="nav-clickable link_no_decoration" href="#d2mods__minigame_leaderboard?lid=' . $value['minigameIdentifier'] . '">
                                <span class="h3">' . $value['minigameName'] . '</span>
                            </a>
                        </div>

                        <span class="h4">&nbsp;</span>

                        <div class="text-center">
                            <a class="nav-clickable btn btn-warning btn-lg" href="#d2mods__minigame_leaderboard?lid=' . $value['minigameIdentifier'] . '">Leaderboard</a>
                        </div>

                        <span class="h4">&nbsp;</span>

                        <p>' . $minigameDescription . '</p>
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
            echo bootstrapMessage('Oh Snap', 'No active mini games!', 'danger');
        }
    } else {
        echo bootstrapMessage('Oh Snap', 'No DB!', 'danger');
    }

    $memcache->close();
} catch (Exception $e) {
    $message = 'Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage();
    echo bootstrapMessage('Oh Snap', $message, 'danger');
}