<?php
require_once('../../../connections/parameters.php');
require_once('../../../global_functions.php');

try {
    //find the most recent match
    function mostRecentMatch($db)
    {
        $mostRecentMatch = $db->q('SELECT MAX(`match_date`) as match_date FROM match_stats;');
        $mostRecentMatch = !empty($mostRecentMatch[0]['match_date'])
            ? $mostRecentMatch[0]['match_date']
            : 0;

        return $mostRecentMatch;
    }

    //run a query or check the number of results
    function searchMongoD2moddin($tableRef, $mostRecentMatch, $check = 0, $limit = 10)
    {
        if ($check) {
            //'mod' => "lod",
            $cursor = $tableRef->find(array('date' => array('$gt' => $mostRecentMatch)))->limit(1)->sort(array("date" => 1))->count();
        } else {
            $cursor = $tableRef->find(array('date' => array('$gt' => $mostRecentMatch)))->limit($limit)->sort(array("date" => 1));
        }
        return $cursor;
    }

    //CONTROL VARS
    $loopReps = 10; //the number of times to loop
    $documentsPerQuery = 200;


    //MYSQL
    {
        $db = new dbWrapper($hostname_d2moddin, $username_d2moddin, $password_d2moddin, $database_d2moddin, true);
    }

    //MONGOFB
    {
        // connect to mongo
        $mongoDB = new MongoClient("mongodb://$d2moddin_mongo_username:$d2moddin_mongo_password@$d2moddin_mongo_host/$d2moddin_mongo_database");
        // select a database
        $d2moddin_database = $mongoDB->d2moddin;
        // select a collection (analogous to a relational database's table)
        $d2moddin_table = $d2moddin_database->matchResults;
    }

    $mostRecentMatch = mostRecentMatch($db);
    $queryCount = searchMongoD2moddin($d2moddin_table, $mostRecentMatch, 1); //find number of documents in the query

    $i = 0;

    if ($queryCount > 0) {
        while ($queryCount > 0 && $i < $loopReps) {
            if ($queryCount <= 0) {
                break;
            }

            $i++;
            echo '<h1>' . $i . '</h1>';

            //CHECK IF MONGO RETURNED ANY RESULTS
            if ($queryCount > 0) {
                $mostRecentMatch = mostRecentMatch($db);
                $cursor = searchMongoD2moddin($d2moddin_table, $mostRecentMatch, 0, $documentsPerQuery);

                // iterate through the results
                echo '<pre>';
                while ($cursor->hasNext()) {
                    $match = $cursor->getNext();
                    echo date('d/m/Y', $match['date']) . ' - ' . $match['match_id'] . ' - ' . $match['mod'] . '<br />';
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
                            $db->q("INSERT INTO `match_stats` (`_id`, `match_id`, `mod`, `automatic_surrender`, `match_date`, `duration`, `first_blood_time`, `good_guys_win`, `mass_disconnect`, `num_teams`, `num_players`, `server_addr`, `server_version`, `match_ended`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, FROM_UNIXTIME(`match_date`))
                        ON DUPLICATE KEY UPDATE `match_id` = VALUES(`match_id`), `mod` = VALUES(`mod`), `automatic_surrender` = VALUES(`automatic_surrender`), `match_date` = VALUES(`match_date`), `duration` = VALUES(`duration`), `first_blood_time` = VALUES(`first_blood_time`), `good_guys_win` = VALUES(`good_guys_win`), `mass_disconnect` = VALUES(`mass_disconnect`), `num_teams` = VALUES(`num_teams`), `num_players` = VALUES(`num_players`), `server_addr` = VALUES(`server_addr`), `server_version` = VALUES(`server_version`), `match_ended` = FROM_UNIXTIME(VALUES(`match_date`));",
                                "sssiiiiiiiisi",
                                $match['_id']->{'$id'}, $match['match_id'], $match['mod'], $match['automatic_surrender'], $match['date'], $match['duration'], $match['first_blood_time'], $match['good_guys_win'], $match['mass_disconnect'], count($match['teams']), array_sum($match['num_players']), $match['server_addr'], $match['server_version']);


                            ////////////////////////
                            // add `MATCH PLAYERS`
                            ////////////////////////
                            foreach ($match['teams'] as $key => $value) {
                                if (!empty($value['players'])) {
                                    $match_id = $match['match_id'];
                                    $team_id = $key;

                                    $sql = array();
                                    foreach ($value['players'] as $key2 => $value2) {
                                        $player_slot = $key2;
                                        if (isset($value2['account_id']) && isset($value2['steam_id']) && isset($value2['user_id'])) {
                                            $item1 = empty($value2['items'][0])
                                                ? 0
                                                : $value2['items'][0];
                                            $item2 = empty($value2['items'][1])
                                                ? 0
                                                : $value2['items'][1];
                                            $item3 = empty($value2['items'][2])
                                                ? 0
                                                : $value2['items'][2];
                                            $item4 = empty($value2['items'][3])
                                                ? 0
                                                : $value2['items'][3];
                                            $item5 = empty($value2['items'][4])
                                                ? 0
                                                : $value2['items'][4];
                                            $item6 = empty($value2['items'][5])
                                                ? 0
                                                : $value2['items'][5];

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
                                        echo 'No values on of the lines for : ' . $match['match_id'] . '<br />';
                                    }
                                } else {
                                    echo 'One of the teams was empty for match: '.$match['match_id'].'<br />';
                                }
                            }
                        } else {
                            echo 'No match_id or mod<hr />';
                        }
                    }
                }
                echo '</pre>';
            } else {
                echo 'No results!<br />';
            }

            $queryCount = searchMongoD2moddin($d2moddin_table, $mostRecentMatch, 1); //find number of documents in the query

            echo '<hr />';
        }
    } else {
        echo 'No results!<br />';
    }
} catch (Exception $e) {
    echo $e->getMessage();
}