<?php
require_once('./functions.php');
require_once('../global_functions.php');
require_once('../connections/parameters.php');

$start = time();

if (!isset($_SESSION)) {
    session_start();
}

try {
    if (!function_exists('dota2TeamName')) {
        function dota2TeamName($teamID)
        {
            switch ($teamID) {
                case -1:
                    $teamName = 'No Winner';
                    break;
                case 2:
                    $teamName = 'Radiant';
                    break;
                case 3:
                    $teamName = 'Dire';
                    break;
                default:
                    $teamName = '#' . $teamID;
                    break;
            }
            return $teamName;
        }
    }

    $db = new dbWrapper($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    $db->q('SET NAMES utf8;');

    if ($db) {
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
                          mmp.`match_id`,
                          mmp.`mod_id`,
                          mmp.`player_sid32`,
                          mmp.`player_sid64`,
                          mmp.`isBot`,
                          mmp.`leaver_status`,
                          mmp.`player_won`,
                          mmp.`player_name`,
                          mmp.`player_round_id`,
                          mmp.`player_team_id`,
                          mmp.`player_slot_id`
                        FROM `mod_match_players` mmp
                        WHERE mmp.`match_id` = ?
                        ORDER BY mmp.`player_round_id`, mmp.`player_team_id`, mmp.`player_slot_id`;',
                    's',
                    $matchID
                );

                $matchHeroDetails = $db->q(
                    'SELECT
                          mmh.`match_id`,
                          mmh.`mod_id`,
                          mmh.`player_round_id`,
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
                        ORDER BY mmh.`player_round_id`, mmh.`player_sid32`;',
                    's',
                    $matchID
                );

                $matchDetailsSorted = array();

                if (!empty($matchPlayerDetails)) {
                    foreach ($matchPlayerDetails as $mh_key => $mh_value) {
                        foreach ($mh_value as $mh_key2 => $mh_value2) {
                            $matchDetailsSorted[$mh_value['player_round_id']][$mh_value['player_sid32']][$mh_key2] = $mh_value2;
                        }
                    }
                }

                if (!empty($matchHeroDetails)) {
                    foreach ($matchHeroDetails as $mh_key => $mh_value) {
                        foreach ($mh_value as $mh_key2 => $mh_value2) {
                            $matchDetailsSorted[$mh_value['player_round_id']][$mh_value['player_sid32']][$mh_key2] = $mh_value2;
                        }
                    }
                }

                echo '<h2><a class="nav-clickable" href="#d2mods__stats?id=' . $matchDetails[0]['mod_id'] . '">' . $matchDetails[0]['mod_name'] . '</a> <small>' . $matchID . '</small></h2>';

                $sg = !empty($matchDetails[0]['mod_steam_group'])
                    ? '<a href="http://steamcommunity.com/groups/' . $matchDetails[0]['mod_steam_group'] . '" target="_new">Steam Group</a>'
                    : 'Steam Group';

                $wg = !empty($matchDetails[0]['mod_workshop_link'])
                    ? '<a href="http://steamcommunity.com/sharedfiles/filedetails/?id=' . $matchDetails[0]['mod_workshop_link'] . '" target="_new">Workshop</a>'
                    : 'Workshop';

                $schemaLink = !empty($matchDetails[0]['message_id'])
                    ? '<a href=" ./d2mods/?custom_match=' . $matchDetails[0]['message_id'] . '" target="_new">' . $matchDetails[0]['message_id'] . '</a>'
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

                        $lastTeam = -1;
                        foreach ($round_value as $key => $value) {
                            if ($value['player_team_id'] > $lastTeam) {
                                if ($value['player_team_id'] > 0) {
                                    echo '</table></div>';
                                }

                                $teamName = dota2TeamName($value['player_team_id']);

                                echo '<h4><small>Team:</small> ' . $teamName . '</h4>';

                                echo '<div class="table-responsive">
		                         <table class="table table-striped table-hover">';
                                /*
                                 * Hero
                                 * Player name
                                 * Level
                                 * Kills
                                 * Deaths
                                 * Assists
                                 * Last Hits
                                 * Denies
                                 * Gold
                                 */
                                echo '<tr>
                                        <th class="col-sm-1">&nbsp;</th>
                                        <th>Player</th>
                                        <th class="col-sm-1 text-center">Bot?</th>
                                        <th class="col-sm-1 text-center">lvl</th>
                                        <th class="col-sm-1 text-center">Kills</th>
                                        <th class="col-sm-1 text-center">Deaths</th>
                                        <th class="col-sm-1 text-center">Assists</th>
                                        <th class="col-sm-1 text-center">LH</th>
                                        <th class="col-sm-1 text-center">Denies</th>
                                        <th class="col-sm-1 text-center">Gold</th>
                                </tr>';

                                $lastTeam = $value['player_team_id'];
                            }

                            $heroID = !empty($value['hero_id'])
                                ? $value['hero_id']
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

                            $value['player_name'] = $value['isBot'] != 1 && !empty($value['player_name'])
                                ? $value['player_name']
                                : '??';

                            $dbLink = !empty($value['player_sid32']) && is_numeric($value['player_sid32'])
                                ? '<a href="http://dotabuff.com/players/' . $value['player_sid32'] . '" target="_new">' . $value['player_name'] . '</a>'
                                : $value['player_name'];

                            $isBot = !empty($value['isBot']) && $value['isBot'] == 1
                                ? '<span class="glyphicon glyphicon-ok"></span>'
                                : '<span class="glyphicon glyphicon-remove"></span>';

                            ///////////////

                            $img_link = '//static.getdotastats.com/images/heroes/' . strtolower(str_replace('\'', '', str_replace(' ', '-', $heroData['localized_name']))) . '.png';

                            $heroLevel = !empty($value['hero_level'])
                                ? $value['hero_level']
                                : '-';

                            $heroKills = !empty($value['hero_kills'])
                                ? $value['hero_kills']
                                : '-';

                            $heroDeaths = !empty($value['hero_deaths'])
                                ? $value['hero_deaths']
                                : '-';

                            $heroAssists = !empty($value['hero_assists'])
                                ? $value['hero_assists']
                                : '-';

                            $heroLastHits = !empty($value['hero_lasthits'])
                                ? $value['hero_lasthits']
                                : '-';

                            $heroDenies = !empty($value['hero_denies'])
                                ? $value['hero_denies']
                                : '-';

                            $heroGold = !empty($value['hero_gold'])
                                ? $value['hero_gold']
                                : '-';

                            ///////////////

                            echo '<tr>
                                <td><img class="match_overview_hero_image" src="' . $img_link . '" alt="' . $heroData['localized_name'] . ' {ID: ' . $heroID . '}" /></td>
                                <td>' . $dbLink . '</td>
                                <td class="text-center">' . $isBot . '</td>
                                <td class="text-center">' . $heroLevel . '</td>
                                <td class="text-center">' . $heroKills . '</td>
                                <td class="text-center">' . $heroDeaths . '</td>
                                <td class="text-center">' . $heroAssists . '</td>
                                <td class="text-center">' . $heroLastHits . '</td>
                                <td class="text-center">' . $heroDenies . '</td>
                                <td class="text-center">' . $heroGold . '</td>
                            </tr>';
                        }
                        echo '</table></div>';

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
    } else {
        echo bootstrapMessage('Oh Snap', 'No DB!', 'danger');
    }

    echo '<p>
            <div class="text-center">
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__directory">Mod Directory</a>
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__recent_games">Recent Games</a>
            </div>
        </p>';


    echo '<div id="pagerendertime" class="pagerendertime">';
    echo '<hr />Page generated in ' . (time() - $start) . 'secs';
    echo '</div>';
} catch (Exception $e) {
    $message = 'Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage();
    echo bootstrapMessage('Oh Snap', $message, 'danger');
}