<?php
require_once('../global_functions.php');
require_once('../connections/parameters.php');

try {
    if (!isset($_SESSION)) {
        session_start();
    }

    if (isset($_COOKIE['session']) && empty($_SESSION['user_id64'])) {
        checkLogin_v2();
    }
    if (!empty($_SESSION['user_id64'])) {
        $db = new dbWrapper_v2($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site);
        if ($db) {
            $messages = $db->q('SELECT * FROM `node_listener` ORDER BY date_recorded DESC;');

            foreach ($messages as $key => $value) {
                $parsed = json_decode($value['message'], 1);

                /////////////////////////////////////////////////////////////////////////////////////////

                /*$numPlayers = !empty($parsed['rounds']['players'])
                    ? count($parsed['rounds']['players'])
                    : NULL;*/

                /*echo $parsed['matchID'] . ' || ' . $numPlayers . '<br />';
                $db->q(
                    'INSERT INTO `mod_match_overview` (`match_id`, `match_num_players`)
                        VALUES (?, ?) ON DUPLICATE KEY UPDATE
                            `match_id` = VALUES(`match_id`),
                            `match_num_players` = VALUES(`match_num_players`);'
                    , 'si'
                    , $parsed['matchID'], $numPlayers
                );*/

                /////////////////////////////////////////////////////////////////////////////////////////

                /*echo $parsed['matchID'] . ' || ' . $parsed['modID'] . ' || ' . $parsed['duration'] . '||' . $value['date_recorded'] . '<br />';
                $db->q(
                    'INSERT INTO `node_listener` (`test_id`, `mod_id`)
                        VALUES (?, ?) ON DUPLICATE KEY UPDATE
                            `test_id` = VALUES(`test_id`),
                            `mod_id` = VALUES(`mod_id`);'
                    , 'is'
                    , $value['test_id'], $parsed['modID']
                );*/


                /////////////////////////////////////////////////////////////////////////////////////////

                $matchID = $parsed['matchID'];
                $modID = $parsed['modID'];

                if (!empty($parsed['rounds']['players'])) {
                    foreach ($parsed['rounds']['players'] as $key2 => $value2) {

                        $player_sid32 = !empty($value2['steamID32'])
                            ? $value2['steamID32']
                            : 0;

                        $player_name = !empty($value2['playerName'])
                            ? $value2['playerName']
                            : 'N/A';

                        $player_teamID = !empty($value2['teamID'])
                            ? $value2['teamID']
                            : 0;

                        $player_slotID = !empty($value2['slotID'])
                            ? $value2['slotID']
                            : 0;

                        $hero_heroID = !empty($value2['hero']['heroID'])
                            ? $value2['hero']['heroID']
                            : 0;

                        $hero_level = !empty($value2['hero']['level'])
                            ? $value2['hero']['level']
                            : 0;

                        $hero_kills = !empty($value2['hero']['kills'])
                            ? $value2['hero']['kills']
                            : 0;

                        $hero_assists = !empty($value2['hero']['assists'])
                            ? $value2['hero']['assists']
                            : 0;

                        $hero_deaths = !empty($value2['hero']['deaths'])
                            ? $value2['hero']['deaths']
                            : 0;

                        $hero_gold = !empty($value2['hero']['gold'])
                            ? $value2['hero']['gold']
                            : 0;

                        $hero_lastHits = !empty($value2['hero']['lastHits'])
                            ? $value2['hero']['lastHits']
                            : 0;

                        $hero_denies = !empty($value2['hero']['denies'])
                            ? $value2['hero']['denies']
                            : 0;

                        echo $matchID . ' || ' . $modID . ' || ' . $player_sid32 . '<br />';

                        $db->q(
                            'INSERT INTO `mod_match_players`
                                  (`match_id`,
                                  `mod_id`,
                                  `player_sid32`,
                                  `player_name`,
                                  `player_team_id`,
                                  `player_slot_id`,
                                  `player_hero_id`,
                                  `player_hero_level`,
                                  `player_hero_kills`,
                                  `player_hero_deaths`,
                                  `player_hero_assists`,
                                  `player_hero_gold`,
                                  `player_hero_lasthits`,
                                  `player_hero_denies`,
                                  `date_recorded`)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE
                                    `match_id` = VALUES(`match_id`),
                                    `player_sid32` = VALUES(`player_sid32`),
                                    `player_name` = VALUES(`player_name`),
                                    `player_team_id` = VALUES(`player_team_id`),
                                    `player_slot_id` = VALUES(`player_slot_id`),
                                    `date_recorded` = VALUES(`date_recorded`);',
                            'ssisiiiiiiiiiis',
                            $matchID,
                            $modID,
                            $player_sid32,
                            $player_name,
                            $player_teamID,
                            $player_slotID,
                            $hero_heroID,
                            $hero_level,
                            $hero_kills,
                            $hero_assists,
                            $hero_deaths,
                            $hero_gold,
                            $hero_lastHits,
                            $hero_denies,
                            $value['date_recorded']
                        );

                        $db->q(
                            'INSERT INTO `mod_match_players_names`
                                  (`player_sid32`,
                                  `player_name`,
                                  `date_recorded`)
                                VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE
                                    `player_name` = VALUES(`player_name`),
                                    `date_recorded` = VALUES(`date_recorded`);',
                            'iss',
                            $player_sid32,
                            $player_name,
                            $value['date_recorded']
                        );
                    }
                } else {
                    echo '<strong>NO PLAYERS!!</strong> ' . $matchID . ' || ' . $modID . '<br />';
                }

                flush();
            }
        } else {
            echo '<div class="page-header"><div class="alert alert-danger" role="alert"><strong>Oh Snap:</strong> No DB!</div></div>';
        }
    } else {
        echo '<div class="page-header"><div class="alert alert-danger" role="alert"><strong>Oh Snap:</strong> Not logged in!</div></div>';
        echo '<a href="../">Go back to main site</a>';
    }
} catch (Exception $e) {
    echo '<div class="page-header"><div class="alert alert-danger" role="alert"><strong>Oh Snap:</strong> Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage() . '</div></div>';
}