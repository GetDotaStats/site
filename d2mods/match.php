<?php
require_once('./functions.php');
require_once('../global_functions.php');
require_once('../connections/parameters.php');

$start = time();

if (!isset($_SESSION)) {
    session_start();
}

try {
    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"


    $matchID = empty($_GET['id']) || strlen($_GET['id']) != 32
        ? NULL
        : $_GET['id'];


    if (!empty($matchID)) {
        $matchDetails = $db->q(
            'SELECT
                  mmo.`match_id`,
                  mmo.`mod_id`,
                  mmo.`message_id`,
                  mmo.`match_duration`,
                  mmo.`match_num_players`,
                  mmo.`match_winning_team`,
                  mmo.`match_recorded`,

                  ml.`mod_id`,
                  ml.`mod_identifier`,
                  ml.`mod_name`,
                  ml.`mod_description`,
                  ml.`mod_workshop_link`,
                  ml.`mod_steam_group`,
                  ml.`mod_active`
                FROM `mod_match_overview` mmo
                LEFT JOIN `mod_list` ml ON mmo.`mod_id` = ml.`mod_identifier`
                WHERE mmo.`match_id` = ?
                LIMIT 0,1;',
            's',
            $matchID
        );

        if (!empty($matchDetails)) {
            $matchPlayerDetails = $db->q(
                'SELECT
                      mmp.`player_sid32`,
                      mmp.`player_sid64`,
                      mmp.`isBot`,
                      mmp.`connection_status`,
                      mmp.`player_won`,
                      mmp.`player_name`,
                      mmp.`player_round_id`,
                      mmp.`player_team_id`,
                      mmp.`player_slot_id`,

                      gcs.`cs_id`,
                      gcs.`cs_string`,
                      gcs.`cs_name`
                    FROM `mod_match_players` mmp
                    LEFT JOIN `game_connection_status` gcs
                      ON mmp.`connection_status` = gcs.`cs_id`
                    WHERE mmp.`match_id` = ?
                    ORDER BY mmp.`player_round_id`, mmp.`player_team_id`, mmp.`player_slot_id`;',
                's',
                $matchID
            );

            $matchHeroDetails = $db->q(
                'SELECT
                      mmh.`player_round_id`,
                      mmh.`player_team_id`,
                      mmh.`player_slot_id`,
                      mmh.`player_sid32`,
                      mmh.`hero_id`,
                      mmh.`hero_won`,
                      mmh.`hero_level`,
                      mmh.`hero_kills`,
                      mmh.`hero_deaths`,
                      mmh.`hero_assists`,
                      mmh.`hero_gold`,
                      mmh.`hero_lasthits`,
                      mmh.`hero_denies`,
                      mmh.`hero_gold_spent_buyback`,
                      mmh.`hero_gold_spent_consumables`,
                      mmh.`hero_gold_spent_items`,
                      mmh.`hero_gold_spent_support`,
                      mmh.`hero_num_purchased_consumables`,
                      mmh.`hero_num_purchased_items`,
                      mmh.`hero_stun_amount`,
                      mmh.`hero_total_earned_gold`,
                      mmh.`hero_total_earned_xp`
                    FROM `mod_match_heroes` mmh
                    WHERE mmh.`match_id` = ?
                    ORDER BY mmh.`player_round_id`, mmh.`player_team_id`, mmh.`player_slot_id`;',
                's',
                $matchID
            );

            $matchItemDetails = $db->q(
                'SELECT
                      mmi.`player_sid32`,
                      mmi.`player_round_id`,
                      mmi.`player_team_id`,
                      mmi.`player_slot_id`,
                      mmi.`item_index`,
                      mmi.`item_name`,
                      mmi.`item_start_time`
                    FROM `mod_match_items` mmi
                    WHERE mmi.`match_id` = ?
                    ORDER BY mmi.`player_round_id`, mmi.`player_team_id`, mmi.`player_slot_id`, mmi.`item_start_time`;',
                's',
                $matchID
            );

            $matchAbilityDetails = $db->q(
                'SELECT
                      mma.`player_sid32`,
                      mma.`player_round_id`,
                      mma.`player_team_id`,
                      mma.`player_slot_id`,
                      mma.`ability_index`,
                      mma.`ability_name`,
                      mma.`ability_level`
                    FROM `mod_match_abilities` mma
                    WHERE mma.`match_id` = ?
                    ORDER BY mma.`player_round_id`, mma.`player_team_id`, mma.`player_slot_id`, mma.`ability_index`;',
                's',
                $matchID
            );

            $regularItems = $memcache->get('dota2_regular_items');
            if (!$regularItems) {
                $regularItemsSQL = $db->q('SELECT `item_id`, `item_name`, `item_nice_name` FROM `game_regular_items`;');

                $regularItems = array();
                foreach ($regularItemsSQL as $value) {
                    $regularItems[$value['item_id']] = $value['item_name'];
                }

                $memcache->set('dota2_regular_items', $regularItems, 0, 10 * 60); //10minutes
            }

            $regularAbilities = $memcache->get('dota2_regular_abilities');
            if (!$regularAbilities) {
                $regularAbilitiesSQL = $db->q('SELECT `ability_id`, `ability_name` FROM `game_regular_abilities`;');

                $regularAbilities = array();
                foreach ($regularAbilitiesSQL as $value) {
                    $regularAbilities[$value['ability_id']] = $value['ability_name'];
                }

                $memcache->set('dota2_regular_abilities', $regularAbilities, 0, 10 * 60); //10minutes
            }

            $matchSchema = $memcache->get('dota2_match_schema' . $matchID);
            if (!$matchSchema) {
                $matchSchemaSQL = $db->q(
                    'SELECT `message` FROM `node_listener` WHERE `match_id` = ? LIMIT 0,1;',
                    's',
                    $matchID
                );

                if (!empty($matchSchemaSQL)) {
                    $matchSchema = json_decode(utf8_encode($matchSchemaSQL[0]['message']), 1);
                    $memcache->set('dota2_match_schema' . $matchID, $matchSchema, 0, 1 * 60); //1minutes
                }
            }

            $matchDetailsSorted = array();

            if (!empty($matchPlayerDetails)) {
                foreach ($matchPlayerDetails as $mh_key => $mh_value) {
                    foreach ($mh_value as $mh_key2 => $mh_value2) {
                        $matchDetailsSorted[$mh_value['player_round_id']][$mh_value['player_team_id']][$mh_value['player_slot_id']][$mh_key2] = $mh_value2;
                    }
                }
            }
            /*
            //NEED TO FIX CAMEL CASE TO EXPECTED SQL COL NAMES
            else if(!empty($matchSchema)){
                foreach($matchSchema['rounds']['players'] as $mh_key => $mh_value){
                    foreach ($mh_value as $mh_key2 => $mh_value2) {
                        $matchDetailsSorted[0][$mh_value['teamID']][$mh_value['slotID']][$mh_key2] = $mh_value2;
                    }
                }
            }*/

            if (!empty($matchHeroDetails)) {
                foreach ($matchHeroDetails as $mh_key => $mh_value) {
                    foreach ($mh_value as $mh_key2 => $mh_value2) {
                        $matchDetailsSorted[$mh_value['player_round_id']][$mh_value['player_team_id']][$mh_value['player_slot_id']][$mh_key2] = $mh_value2;
                    }
                }
            }

            if (!empty($matchItemDetails)) {
                foreach ($matchItemDetails as $mh_key => $mh_value) {
                    $matchDetailsSorted[$mh_value['player_round_id']][$mh_value['player_team_id']][$mh_value['player_slot_id']]['items'][$mh_value['item_index']]['item_name'] = $mh_value['item_name'];
                    $matchDetailsSorted[$mh_value['player_round_id']][$mh_value['player_team_id']][$mh_value['player_slot_id']]['items'][$mh_value['item_index']]['item_start_time'] = $mh_value['item_start_time'];
                }
            } else if (!empty($matchSchema['rounds']) && !empty($matchSchema['rounds']['players'])) {
                foreach ($matchSchema['rounds']['players'] as $mh_key => $mh_value) {
                    if (!empty($mh_value['items'])) {
                        foreach ($mh_value['items'] as $mh_key2 => $mh_value2) {
                            $matchDetailsSorted[0][$mh_value['teamID']][$mh_value['slotID']]['items'][$mh_value2['index']]['item_name'] = $mh_value2['itemName'];
                            $matchDetailsSorted[0][$mh_value['teamID']][$mh_value['slotID']]['items'][$mh_value2['index']]['item_start_time'] = $mh_value2['itemStartTime'];
                        }
                    }
                }
            }

            if (!empty($matchAbilityDetails)) {
                $i = 1;
                foreach ($matchAbilityDetails as $mh_key => $mh_value) {
                    $matchDetailsSorted[$mh_value['player_round_id']][$mh_value['player_team_id']][$mh_value['player_slot_id']]['abilities'][$i]['ability_name'] = $mh_value['ability_name'];
                    $matchDetailsSorted[$mh_value['player_round_id']][$mh_value['player_team_id']][$mh_value['player_slot_id']]['abilities'][$i]['ability_level'] = $mh_value['ability_level'];
                    $i++;
                }
            } else if (!empty($matchSchema['rounds']) && !empty($matchSchema['rounds']['players'])) {
                foreach ($matchSchema['rounds']['players'] as $mh_key => $mh_value) {
                    if (!empty($mh_value['items'])) {
                        $i = 1;
                        foreach ($mh_value['abilities'] as $mh_key2 => $mh_value2) {
                            $matchDetailsSorted[0][$mh_value['teamID']][$mh_value['slotID']]['abilities'][$i]['ability_name'] = $mh_value2['abilityName'];
                            $matchDetailsSorted[0][$mh_value['teamID']][$mh_value['slotID']]['abilities'][$i]['ability_level'] = $mh_value2['level'];
                            $i++;
                        }
                    }
                }
            }

            /*echo '<pre>';
            print_r($matchDetailsSorted);
            echo '</pre>';
            exit();*/

            echo '<h2><a class="nav-clickable" href="#d2mods__stats?id=' . $matchDetails[0]['mod_id'] . '">' . $matchDetails[0]['mod_name'] . '</a> <small>' . $matchID . '</small></h2>';

            $sg = !empty($matchDetails[0]['mod_steam_group'])
                ? '<a href="http://steamcommunity.com/groups/' . $matchDetails[0]['mod_steam_group'] . '" target="_new">Steam Group</a>'
                : 'Steam Group';

            $wg = !empty($matchDetails[0]['mod_workshop_link'])
                ? '<a href="http://steamcommunity.com/sharedfiles/filedetails/?id=' . $matchDetails[0]['mod_workshop_link'] . '" target="_new">Workshop</a>'
                : 'Workshop';

            $schemaLink = !empty($matchDetails[0]['message_id'])
                ? '<a href=" ./d2mods/schema.php?custom_match=' . $matchDetails[0]['message_id'] . '" target="_new">' . $matchDetails[0]['message_id'] . '</a>'
                : 'N/A';

            echo '<div class="container">
                        <div class="col-sm-7">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <tr>
                                        <th>Links</th>
                                        <td>' . $wg . ' || ' . $sg . '</td>
                                    </tr>
                                    <tr>
                                        <th>Description</th>
                                        <td>' . $matchDetails[0]['mod_description'] . '</td>
                                    </tr>
                                    <tr>
                                        <th>Schema</th>
                                        <td>' . $schemaLink . '</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                      </div>';

            echo '<div class="table-responsive">
		                    <table class="table table-condensed">
		                        <tr class="warning">
		                            <th class="col-sm-8 bashful">&nbsp;</th>
		                            <th class="col-sm-1 text-center">Players</th>
		                            <th class="col-sm-1 text-center">Duration</th>
		                            <th class="col-sm-2 text-center">Ended</th>
		                        </tr>
		                        <tr>
		                            <td class="bashful">&nbsp;</td>
		                            <td class="text-center">' . $matchDetails[0]['match_num_players'] . '</td>
		                            <td class="text-center">' . number_format($matchDetails[0]['match_duration'] / 60) . ' mins</td>
		                            <td class="text-right">' . relative_time($matchDetails[0]['match_recorded']) . '</td>
		                        </tr>
		                    </table>
		                </div>';

            echo '<div><h2><small>Winning Team:</small> ' . dota2TeamName($matchDetails[0]['match_winning_team']) . '</h2></div>';

            $roundCount = count($matchDetailsSorted);
            if (!empty($matchDetailsSorted)) {
                foreach ($matchDetailsSorted as $round_key => $round_value) {
                    if ($roundCount > 1) {
                        echo '<div><h3><small>Round:</small> ' . ($round_key + 1) . ' of ' . $roundCount . '</h3></div>';
                    }

                    foreach ($round_value as $team_key => $team_value) {

                        $firstTeamID = 0;
                        foreach ($team_value as $player_key => $player_value) {
                            if (isset($player_value['player_team_id'])) {
                                $firstTeamID = $player_value['player_team_id'];
                                break;
                            }
                        }

                        $teamName = dota2TeamName($firstTeamID);
                        echo '<h4><small>Team:</small> ' . $teamName . '</h4>';

                        echo '<div class="table-responsive">
		                            <table class="table table-striped table-hover">';

                        echo '<tr>
                                        <th class="col-sm-1">&nbsp;</th>
                                        <th>Player</th>
                                        <th class="col-sm-1 text-center">Con. <span class="glyphicon glyphicon-question-sign" title="Connection Status: Indicates the status of the player at the end of the game"></span></th>
                                        <th class="col-sm-1 text-center">Bot <span class="glyphicon glyphicon-question-sign" title="Indicates whether the player was a bot"></span></th>
                                        <th class="col-sm-1 text-center">LVL</th>
                                        <th class="col-sm-2 text-center">K / A / D <span class="glyphicon glyphicon-question-sign" title="Kills / Assists / Deaths"></span></th>
                                        <th class="col-sm-2 text-center">LH / D <span class="glyphicon glyphicon-question-sign" title="Last Hits / Denies"></span></th>
                                    </tr>';

                        foreach ($team_value as $player_key => $player_value) {


                            $heroID = !empty($player_value['hero_id'])
                                ? $player_value['hero_id']
                                : -1;

                            $heroData = $memcache->get('game_herodata' . $heroID);
                            if (!$heroData) {
                                $heroData = $db->q(
                                    'SELECT * FROM `game_heroes` WHERE `hero_id` = ? LIMIT 0,1;',
                                    'i',
                                    $heroID
                                );

                                if (empty($heroData)) {
                                    $heroData = array();
                                    $heroData['localized_name'] = 'aaa_blank';
                                } else {
                                    $heroData = $heroData[0];
                                }

                                $memcache->set('game_herodata' . $heroID, $heroData, 0, 1 * 60 * 60);
                            }

                            $player_value['player_name'] = $player_value['isBot'] != 1 && !empty($player_value['player_name'])
                                ? htmlentities($player_value['player_name'])
                                : '??';

                            $playerName = !empty($player_value['player_sid32']) && is_numeric($player_value['player_sid32'])
                                ? '<a class="nav-clickable" href="#d2mods__profile?id=' . $player_value['player_sid32'] . '">' . $player_value['player_name'] . '</a>'
                                : $player_value['player_name'];

                            $dbLink = !empty($player_value['player_sid32']) && is_numeric($player_value['player_sid32'])
                                ? ' <a class="db_link" href="http://dotabuff.com/players/' . $player_value['player_sid32'] . '" target="_new">[DB]</a>'
                                : '';

                            $isBot = !empty($player_value['isBot']) && $player_value['isBot'] == 1
                                ? '<span class="glyphicon glyphicon-ok"></span>'
                                : '<span class="glyphicon glyphicon-remove"></span>';

                            $arrayGoodConnectionStatus = array(2, 3, 5);
                            if (!empty($player_value['connection_status']) && in_array($player_value['connection_status'], $arrayGoodConnectionStatus)) {
                                $connectionStatus = '<span class="glyphicon glyphicon-ok-sign" title="' . $player_value['cs_string'] . '"></span>';
                            } else if (!empty($player_value['connection_status']) && $player_value['connection_status'] == 0) {
                                $connectionStatus = '<span class="glyphicon glyphicon-question-sign" title="' . $player_value['cs_string'] . '"></span>';
                            } else {
                                $connectionStatus = '<span class="glyphicon glyphicon-remove-sign" title="' . $player_value['cs_string'] . '"></span>';
                            }

                            ///////////////

                            $img_link = '//static.getdotastats.com/images/heroes/' . strtolower(str_replace('\'', '', str_replace(' ', '-', $heroData['localized_name']))) . '.png';

                            $heroLevel = !empty($player_value['hero_level'])
                                ? $player_value['hero_level']
                                : '-';

                            $heroKills = !empty($player_value['hero_kills'])
                                ? $player_value['hero_kills']
                                : '-';

                            $heroDeaths = !empty($player_value['hero_deaths'])
                                ? $player_value['hero_deaths']
                                : '-';

                            $heroAssists = !empty($player_value['hero_assists'])
                                ? $player_value['hero_assists']
                                : '-';

                            $heroLastHits = !empty($player_value['hero_lasthits'])
                                ? $player_value['hero_lasthits']
                                : '-';

                            $heroDenies = !empty($player_value['hero_denies'])
                                ? $player_value['hero_denies']
                                : '-';

                            $items = '';
                            if (!empty($player_value['items'])) {
                                $items = '<strong>Hero:</strong> ';
                                $break = false;
                                for ($i = 0; $i < 12; $i++) {
                                    if ($i >= 6 && !$break) {
                                        $items = rtrim($items, ' ');
                                        $items .= '<br /><strong>Stash:</strong> ';
                                        $break = true;
                                    }

                                    if (isset($player_value['items'][$i])) {
                                        $imgName = $player_value['items'][$i]['item_name'];

                                        if (in_array($player_value['items'][$i]['item_name'], $regularItems)) {
                                            if (stristr($imgName, 'recipe_')) {
                                                $imgName = 'recipe';
                                            } else {
                                                $imgName = str_replace('item_', '', $imgName);
                                            }

                                            if (file_exists('../images/items/default/' . $imgName . '.png')) {
                                                $img_url = '//dota2.photography/images/items/default/' . $imgName . '.png';
                                            } else {
                                                $img_url = '//dota2.photography/images/items/aaaa_unknown.png';
                                            }

                                            $items .= '<img class="match_item_placeholder" src="' . $img_url . '" title="' . $player_value['items'][$i]['item_name'] . ' OBTAINED AT: ' . secs_to_clock($player_value['items'][$i]['item_start_time']) . '" /> ';
                                        } else {
                                            if (stristr($imgName, 'recipe_')) {
                                                $imgName = 'recipe';
                                            } else {
                                                $imgName = str_replace('item_', '', $imgName);
                                            }

                                            if (file_exists('../images/items/' . $matchDetails[0]['mod_id'] . '/' . $imgName . '.png')) {
                                                $img_url = '//dota2.photography/images/items/' . $matchDetails[0]['mod_id'] . '/' . $imgName . '.png';
                                            } else {
                                                $img_url = '//dota2.photography/images/items/aaaa_unknown.png';
                                            }

                                            $items .= '<img class="match_item_placeholder" src="' . $img_url . '" title="' . $player_value['items'][$i]['item_name'] . ' OBTAINED AT: ' . secs_to_clock($player_value['items'][$i]['item_start_time']) . '" /> ';
                                        }
                                    } else {
                                        $items .= '<img class="match_item_placeholder" src="//dota2.photography/images/items/aaaa_empty.png" title="Empty slot" /> ';
                                    }
                                }
                            }

                            $abilities = '';
                            if (!empty($player_value['abilities'])) {
                                foreach ($player_value['abilities'] as $abilities_key => $abilities_value) {
                                    if (in_array($abilities_value['ability_name'], $regularAbilities)) {
                                        $imgName = $abilities_value['ability_name'];
                                        if (file_exists('../images/abilities/default/' . $imgName . '.png')) {
                                            $img_url = '//dota2.photography/images/abilities/default/' . $imgName . '.png';
                                        } else {
                                            $img_url = '//dota2.photography/images/abilities/aaaa_unknown.png';
                                        }
                                        $abilities .= '<img class="match_ability_placeholder" src="' . $img_url . '" title="' . $abilities_value['ability_name'] . ' LEVEL: ' . $abilities_value['ability_level'] . '" /> ';
                                    } else {
                                        $imgName = $abilities_value['ability_name'];
                                        if (file_exists('../images/abilities/' . $matchDetails[0]['mod_id'] . '/' . $imgName . '.png')) {
                                            $img_url = '//dota2.photography/images/abilities/' . $matchDetails[0]['mod_id'] . '/' . $imgName . '.png';
                                        } else {
                                            $img_url = '//dota2.photography/images/abilities/aaaa_unknown.png';
                                        }
                                        $abilities .= '<img class="match_ability_placeholder" src="' . $img_url . '" title="' . $abilities_value['ability_name'] . ' LEVEL: ' . $abilities_value['ability_level'] . '" /> ';
                                    }
                                }
                            }

                            if (!empty($items) || !empty($abilities)) {
                                $item_ability_row = '<tr>
                                        <td>&nbsp;</td>
                                        <td class="text-left" colspan="3"><div id="match_ability_container">' . $abilities . '</div></td>
                                        <td class="text-right" colspan="3"><div id="match_item_container">' . $items . '</div></td>
                                    </tr>';
                            } else {
                                $item_ability_row = '';
                            }

                            ///////////////

                            echo '<tr>
                                        <td><img class="match_overview_hero_image" src="' . $img_link . '" alt="' . $heroData['localized_name'] . ' {ID: ' . $heroID . '}" /></td>
                                        <td>' . $playerName . $dbLink . '</td>
                                        <td class="text-center">' . $connectionStatus . '</td>
                                        <td class="text-center">' . $isBot . '</td>
                                        <td class="text-center">' . $heroLevel . '</td>
                                        <td class="text-center">' . $heroKills . ' / ' . $heroAssists . ' / ' . $heroDeaths . '</td>
                                        <td class="text-center">' . $heroLastHits . ' / ' . $heroDenies . '</td>
                                    </tr>' . $item_ability_row;

                        }
                        echo '</table></div>';
                    }

                    echo '<hr />';
                }
            } else {
                echo bootstrapMessage('Oh Snap', 'Game ended without recording any player data!', 'danger');
            }
        } else {
            echo bootstrapMessage('Oh Snap', 'No match with that matchID!', 'danger');
        }
    } else {
        echo bootstrapMessage('Oh Snap', 'Invalid matchID!', 'danger');
    }

    $memcache->close();

    echo '<p>
            <div class="text-center">
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__directory">Mod Directory</a>
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__recent_games">Recent Games</a>
            </div>
        </p>';
} catch (Exception $e) {
    echo formatExceptionHandling($e);
}