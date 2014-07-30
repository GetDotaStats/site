<?php
require_once('./functions.php');
require_once('../global_functions.php');
require_once('../connections/parameters.php');

$start = time();

if (!isset($_SESSION)) {
    session_start();
}

try {
    $db = new dbWrapper($hostname_d2moddin, $username_d2moddin, $password_d2moddin, $database_d2moddin, true);
    if ($db) {
        $memcache = new Memcache;
        $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"


        $line = 0;
        $handle = @fopen("./results-dump.json", "r");
        if ($handle) {
            while (($buffer = fgets($handle)) !== false) {
                $line++;
                //var_dump($buffer);

                $match = json_decode($buffer, true);

                //print_r($match);

                ////////////////////////
                // add `MATCH STATS`
                ////////////////////////
                {
                    $match['automatic_surrender'] = !isset($match['automatic_surrender']) || $match['automatic_surrender'] == false
                        ? 0
                        : 1;
                    $match['good_guys_win'] = !isset($match['good_guys_win']) || $match['good_guys_win'] == false
                        ? 0
                        : 1;
                    $match['mass_disconnect'] = !isset($match['mass_disconnect']) || $match['mass_disconnect'] == false
                        ? 0
                        : 1;


                    if (isset($match['match_id']) && isset($match['mod']) && !empty($match['teams'])) {
                        $db->q("INSERT INTO `match_stats` (`match_id`, `mod`, `automatic_surrender`, `match_date`, `duration`, `first_blood_time`, `good_guys_win`, `mass_disconnect`, `num_teams`, `num_players`, `server_addr`, `server_version`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE `mod` = VALUES(`mod`), `automatic_surrender` = VALUES(`automatic_surrender`), `match_date` = VALUES(`match_date`), `duration` = VALUES(`duration`), `first_blood_time` = VALUES(`first_blood_time`), `good_guys_win` = VALUES(`good_guys_win`), `mass_disconnect` = VALUES(`mass_disconnect`), `num_teams` = VALUES(`num_teams`), `num_players` = VALUES(`num_players`), `server_addr` = VALUES(`server_addr`), `server_version` = VALUES(`server_version`);",
                            "ssiiiiiiiisi",
                            $match['match_id'], $match['mod'], $match['automatic_surrender'], $match['date']['$numberLong'], $match['duration'], $match['first_blood_time'], $match['good_guys_win'], $match['mass_disconnect'], count($match['teams']), array_sum($match['num_players']), $match['server_addr'], $match['server_version']);


                        ////////////////////////
                        // add `MATCH PLAYERS`
                        ////////////////////////
                        foreach ($match['teams'] as $key => $value) {
                            if (!empty($value['players'])) {
                                $match_id = $match['match_id'];
                                $team_id = $key;

                                /*echo '<pre>';
                                print_r($value['players']);
                                echo '</pre>';
                                exit();*/

                                $sql = array();
                                foreach ($value['players'] as $key2 => $value2) {
                                    $player_slot = $key2;
                                    if (isset($value2['account_id']) && isset($value2['steam_id']) && isset($value2['user_id'])) {
                                        $item1 = empty($value2['items'][0])
                                            ? 0
                                            : 1;
                                        $item2 = empty($value2['items'][1])
                                            ? 0
                                            : 1;
                                        $item3 = empty($value2['items'][2])
                                            ? 0
                                            : 1;
                                        $item4 = empty($value2['items'][3])
                                            ? 0
                                            : 1;
                                        $item5 = empty($value2['items'][4])
                                            ? 0
                                            : 1;
                                        $item6 = empty($value2['items'][5])
                                            ? 0
                                            : 1;

                                        $sql[] = '(' .
                                            '\'' . $db->escape($match_id) . '\', ' .
                                            $db->escape($team_id) . ', ' .
                                            $db->escape($player_slot) . ', ' .
                                            $db->escape($value2['account_id']) . ', ' .
                                            $db->escape($value2['steam_id']) . ', ' .
                                            '\'' . $db->escape($value2['user_id']) . '\', ' .
                                            $db->escape($value2['kills']) . ', ' .
                                            $db->escape($value2['assists']) . ', ' .
                                            $db->escape($value2['deaths']) . ', ' .
                                            $db->escape($value2['claimed_denies']) . ', ' .
                                            $db->escape($value2['claimed_farm_gold']) . ', ' .
                                            $db->escape($value2['denies']) . ', ' .
                                            $db->escape($value2['gold']) . ', ' .
                                            $db->escape($value2['gold_per_min']) . ', ' .
                                            $db->escape($value2['hero_damage']) . ', ' .
                                            $db->escape($value2['hero_healing']) . ', ' .
                                            $db->escape($value2['hero_id']) . ', ' .
                                            $db->escape($value2['last_hits']) . ', ' .
                                            $db->escape($value2['leaver_status']) . ', ' .
                                            $db->escape($value2['level']) . ', ' .
                                            $db->escape($value2['tower_damage']) . ', ' .
                                            $db->escape($value2['xp_per_minute']) . ', ' .
                                            $db->escape($item1) . ', ' .
                                            $db->escape($item2) . ', ' .
                                            $db->escape($item3) . ', ' .
                                            $db->escape($item4) . ', ' .
                                            $db->escape($item5) . ', ' .
                                            $db->escape($item6) . ')';
                                    } else {
                                        echo 'Failed: ' . $value2['account_id'] . '<br />';
                                    }
                                }
                                $sql_values = implode(', ', $sql);


                                //unset($sql);

                                $sql = "INSERT INTO `match_players` (`match_id`, `team_id`, `player_slot`, `account_id`, `steam_id`,
                                    `user_id`, `kills`, `assists`, `deaths`, `claimed_denies`, `claimed_farm_gold`, `denies`,
                                    `gold`, `gold_per_min`, `hero_damage`, `hero_healing`, `hero_id`, `last_hits`,
                                    `leaver_status`, `level`, `tower_damage`, `xp_per_minute`, `item1`, `item2`, `item3`,
                                    `item4`, `item5`, `item6`) VALUES " . $sql_values .
                                    ' ON DUPLICATE KEY UPDATE
                                        `team_id` = VALUES(`team_id`),
                                        `account_id` = VALUES(`account_id`),
                                        `steam_id` = VALUES(`steam_id`),
                                        `user_id` = VALUES(`user_id`),
                                        `kills` = VALUES(`kills`),
                                        `assists` = VALUES(`assists`),
                                        `deaths` = VALUES(`deaths`),
                                        `claimed_denies` = VALUES(`claimed_denies`),
                                        `claimed_farm_gold` = VALUES(`claimed_farm_gold`),
                                        `denies` = VALUES(`denies`),
                                        `gold` = VALUES(`gold`),
                                        `gold_per_min` = VALUES(`gold_per_min`),
                                        `hero_damage` = VALUES(`hero_damage`),
                                        `hero_healing` = VALUES(`hero_healing`),
                                        `hero_id` = VALUES(`hero_id`),
                                        `last_hits` = VALUES(`last_hits`),
                                        `leaver_status` = VALUES(`leaver_status`),
                                        `level` = VALUES(`level`),
                                        `tower_damage` = VALUES(`tower_damage`),
                                        `xp_per_minute` = VALUES(`xp_per_minute`),
                                        `item1` = VALUES(`item1`),
                                        `item2` = VALUES(`item2`),
                                        `item3` = VALUES(`item3`),
                                        `item4` = VALUES(`item4`),
                                        `item5` = VALUES(`item5`),
                                        `item6` = VALUES(`item6`);';

                                if (!empty($sql_values)) {
                                    $db->q($sql);

                                    if (!empty($db->handle()->error)) {
                                        //echo '<hr />' . $db->handle()->error . '<hr />';
                                        echo '<br /><br />' . $sql . '<hr />';
                                    }
                                } else {
                                    echo 'No values on line: ' . $line . '<br />';
                                }
                            } else {
                                echo 'One of the teams was empty on line: ' . $line . '<br />';
                            }
                        }
                    } else {
                        echo 'No match_id or mod<hr />';
                    }


                    /*echo '<pre>';
                    print_r($match);
                    echo '</pre>';*/

                }
            }
            if (!feof($handle)) {
                echo "Error: unexpected stream_get_line() fail\n";
            }
            fclose($handle);

            ob_flush();
            flush();
        }

    } else {
        echo 'No DB';
    }
} catch (Exception $e) {
    echo $e->getMessage();
}