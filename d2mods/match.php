<?php
require_once('./functions.php');
require_once('../global_functions.php');
require_once('../connections/parameters.php');

$start = time();

if (!isset($_SESSION)) {
    session_start();
}

try {
    $db = new dbWrapper($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
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
                      mmo.`match_duration`,
                      mmo.`match_num_players`,
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
                          mmp.`player_name`,
                          mmp.`player_round_id`,
                          mmp.`player_team_id`,
                          mmp.`player_slot_id`,
                          mmp.`player_hero_id`,
                          mmp.`player_hero_level`,
                          mmp.`player_hero_kills`,
                          mmp.`player_hero_deaths`,
                          mmp.`player_hero_assists`,
                          mmp.`player_hero_gold`,
                          mmp.`player_hero_lasthits`,
                          mmp.`player_hero_denies`
                        FROM `mod_match_players` mmp
                        WHERE mmp.`match_id` = ?
                        ORDER BY mmp.`player_round_id`, mmp.`player_team_id`, mmp.`player_slot_id`;',
                    's',
                    $matchID
                );

                echo '<h2><a class="nav-clickable" href="#d2mods__stats?id=' . $matchDetails[0]['mod_id'] . '">' . $matchDetails[0]['mod_name'] . '</a> <small>' . $matchID . '</small></h2>';

                $sg = !empty($matchDetails[0]['mod_steam_group'])
                    ? '<a href="http://steamcommunity.com/groups/' . $matchDetails[0]['mod_steam_group'] . '" target="_new">Steam Group</a>'
                    : 'Steam Group';

                $wg = !empty($matchDetails[0]['mod_workshop_link'])
                    ? '<a href="http://steamcommunity.com/sharedfiles/filedetails/?id=' . $matchDetails[0]['mod_workshop_link'] . '" target="_new">Workshop</a>'
                    : 'Workshop';

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

                $lastTeam = -1;
                foreach ($matchPlayerDetails as $key => $value) {
                    if ($value['player_team_id'] > $lastTeam) {
                        if ($value['player_team_id'] > 0) {
                            echo '</table></div>';
                        }

                        echo '<h3>Team: <small>' . $value['player_team_id'] . '</small></h3>';

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
                                <th class="col-sm-1 text-center">Level</th>
                                <th class="col-sm-1 text-center">Kills</th>
                                <th class="col-sm-1 text-center">Deaths</th>
                                <th class="col-sm-1 text-center">Assists</th>
                                <th class="col-sm-1 text-center">LH</th>
                                <th class="col-sm-1 text-center">Denies</th>
                                <th class="col-sm-1 text-center">Gold</th>
                        </tr>';

                        $lastTeam = $value['player_team_id'];
                    }

                    $heroID = $value['player_hero_id'];

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

                    echo '<tr>
                            <td><img class="match_overview_hero_image" src="//static.getdotastats.com/images/heroes/' . strtolower(str_replace(' ', '-', $heroData['localized_name'])) . '.png" alt="' . $heroData['localized_name'] . ' {ID: ' . $value['player_hero_id'] . '}" /></td>
                            <td><a href="http://dotabuff.com/players/' . $value['player_sid32'] . '" target="_new">' . $value['player_name'] . '</a></td>
                            <td class="text-center">' . $value['player_hero_level'] . '</td>
                            <td class="text-center">' . $value['player_hero_kills'] . '</td>
                            <td class="text-center">' . $value['player_hero_deaths'] . '</td>
                            <td class="text-center">' . $value['player_hero_assists'] . '</td>
                            <td class="text-center">' . $value['player_hero_lasthits'] . '</td>
                            <td class="text-center">' . $value['player_hero_denies'] . '</td>
                            <td class="text-center">' . $value['player_hero_gold'] . '</td>
                        </tr>';
                }

                echo '</table></div>';

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

    echo '<hr />';

    echo '<p><a class="nav-clickable" href="#d2mods__directory">Back to Mod Directory</a></p>';

    echo '<div id="pagerendertime" class="pagerendertime">';
    echo '<hr />Page generated in ' . (time() - $start) . 'secs';
    echo '</div>';
} catch (Exception $e) {
    $message = 'Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage();
    echo bootstrapMessage('Oh Snap', $message, 'danger');
}