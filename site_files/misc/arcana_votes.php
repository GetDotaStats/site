<?php
try {
    require_once('../connections/parameters.php');
    require_once('../global_functions.php');
    require_once('../global_variables.php');

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcached = new Cache(NULL, NULL, $localDev);

    echo '<h2>Arcana Votes</h2>';

    $apiEndpoint = 'http://www.dota2.com/webapi/IDOTA2Events/GetArcanaVotes/v0001?event_id=14';

    $arcanaVotes = $memcached->get('d2_arcana_votes');
    if (!$arcanaVotes) {
        $curlObject = new curl_improved($behindProxy, $apiEndpoint);
        $curlObject->setProxyDetails($proxyDetails['address'], $proxyDetails['port'], $proxyDetails['type'], $proxyDetails['user'], $proxyDetails['pass'], false);
        $arcanaVotes = $curlObject->getPage();

        $arcanaVotes = json_decode($arcanaVotes, true);
        //$arcanaVotes = change_booleans_to_numbers($page);

        if (empty($arcanaVotes)) throw new Exception("Couldn't get arcana votes!");

        $memcached->set('d2_arcana_votes', $arcanaVotes, 1 * 60);
    }

    $orderedRounds = array();
    $orderedRounds['meta']['round_time_remaining'] = $arcanaVotes['round_time_remaining'];
    $orderedRounds['meta']['round_number'] = $arcanaVotes['round_number'];
    $orderedRounds['meta']['voting_state'] = $arcanaVotes['voting_state'];
    foreach ($arcanaVotes['matches'] as $key => $value) {
        $orderedRounds[$value['round_number']]['voting_state'] = $value['voting_state'];
        $orderedRounds[$value['round_number']]['is_votes_hidden'] = !empty($value['is_votes_hidden']) ? 1 : 0;
        $orderedRounds[$value['round_number']]['calibration_time_remaining'] = $value['calibration_time_remaining'];

        if (!empty($value['hero_id_1'])) {
            $orderedRounds[$value['round_number']]['matches'][$value['match_id']]['match_id'] = $value['match_id'];
            $orderedRounds[$value['round_number']]['matches'][$value['match_id']]['hero_id_0'] = $value['hero_id_0'];
            $orderedRounds[$value['round_number']]['matches'][$value['match_id']]['hero_id_1'] = $value['hero_id_1'];
            $orderedRounds[$value['round_number']]['matches'][$value['match_id']]['hero_seeding_0'] = $value['hero_seeding_0'];
            $orderedRounds[$value['round_number']]['matches'][$value['match_id']]['hero_seeding_1'] = $value['hero_seeding_1'];
            $orderedRounds[$value['round_number']]['matches'][$value['match_id']]['vote_count_0'] = $value['vote_count_0'];
            $orderedRounds[$value['round_number']]['matches'][$value['match_id']]['vote_count_1'] = $value['vote_count_1'];
        } else {
            $orderedRounds[$value['round_number']]['byes'][$value['hero_seeding_0']] = array(
                'match_id' => $value['match_id'],
                'hero_id_0' => $value['hero_id_0'],
                'hero_seeding_0' => $value['hero_seeding_0'],
            );

            ksort($orderedRounds[$value['round_number']]['byes']);
        }
    }

    $selectedRound = !empty($_GET['r']) && is_numeric($_GET['r'])
        ? $_GET['r']
        : null;

    {//SKIP MENU
        if (!empty($orderedRounds)) {
            $numRounds = count($orderedRounds) - 1;
            echo "<table style='border: 1px; padding: 5px;'>";
            echo "<tr><th>Jump Menu</th></tr>";
            echo "<tr>";
            echo empty($selectedRound)
                ? "<td width='90px'><a class='nav-clickable boldGreenText' href='#misc__arcana_votes'>All</a></td>"
                : "<td width='90px'><a class='nav-clickable' href='#misc__arcana_votes'>All</a></td>";
            for ($i = 0; $i < $numRounds;) {
                $i++;
                if ($i == $selectedRound) {
                    echo "<td width='90px'><a class='nav-clickable boldGreenText' href='#misc__arcana_votes?r={$i}'>Round #{$i}</a></td>";
                } else if ($i == $orderedRounds['meta']['round_number']) {
                    echo "<td width='90px'><a class='nav-clickable boldRedText' href='#misc__arcana_votes?r={$i}'>Round #{$i}</a></td>";
                } else {
                    echo "<td width='90px'><a class='nav-clickable' href='#misc__arcana_votes?r={$i}'>Round #{$i}</a></td>";
                }

                if (($i + 1) % 7 == 0 && $i != $numRounds) {
                    echo "</tr><tr>";
                }
            }
            echo "</tr></table>";
            echo "<br />";
        }
    }

    {//META TABLE
        $roundTimeRemaining = secs_to_h($orderedRounds['meta']['round_time_remaining']);
        echo "<table style='border: 1px; padding: 5px;'>";
        echo "<tr>
                        <th width='120px'>Time Left</th>
                        <td>{$roundTimeRemaining}</td>
                    </tr>";

        echo "<tr>
                        <th>Current Round</th>
                        <td>{$orderedRounds['meta']['round_number']}</td>
                    </tr>";

        echo "<tr>
                        <th>Voting Enabled</th>
                        <td>{$orderedRounds['meta']['voting_state']}</td>
                    </tr>";
        echo "</table>";
    }

    foreach ($orderedRounds as $key => $roundData) {
        if (!empty($selectedRound) && $key != $selectedRound) {
            continue;
        }

        $skipKeys = array('meta');
        if (in_array($key, $skipKeys)) {
            continue;
        }

        if ($key == $orderedRounds['meta']['round_number']) {
            echo "<h2 class='boldRedText'>Round #{$key}</h2>";
        } else {
            echo "<h2>Round #{$key}</h2>";
        }

        if (empty($roundData['matches'])) {
            echo "<p>No pairing data. The previous probably has not finished.</p>";
        } else {
            $roundMatchesTables = array();

            $numTotalVotes = 0;
            foreach ($roundData['matches'] as $key2 => $matchData) {
                $numTotalVotes += $matchData['vote_count_0'];
                $numTotalVotes += $matchData['vote_count_1'];
            }

            foreach ($roundData['matches'] as $key2 => $matchData) {
                $roundMatchesTables[$key2] = '';

                $roundMatchesTables[$key2] .= "<table style='border: 1px; padding: 5px; border-spacing: 2px;'>";
                if (!empty($matchData['hero_id_0']) && is_numeric($matchData['hero_id_0'])) {
                    if (!empty($heroes[$matchData['hero_id_0']])) {
                        $hero0_name = $heroes[$matchData['hero_id_0']]['name_formatted'];
                        $hero0_img = $heroes[$matchData['hero_id_0']]['pic'] . '.png';
                    } else {
                        $hero0_name = 'Unknown';
                        $hero0_img = $heroes[0]['pic'] . '.png';
                    }
                } else {
                    $hero0_name = 'Bye';
                    $hero0_img = $heroes[0]['pic'] . '.png';
                }
                $hero0_img = $imageCDN . '/images/heroes/' . $hero0_img;
                $hero0_img = "<img width='54' height='30' title='{$hero0_name}' alt='Image for hero #{$matchData['hero_id_0']}' src='{$hero0_img}' />";
                $hero0_votes = number_format($matchData['vote_count_0']);
                $hero0_seed = $matchData['hero_seeding_0'];

                if (!empty($matchData['hero_id_1']) && is_numeric($matchData['hero_id_1'])) {
                    if (!empty($heroes[$matchData['hero_id_1']])) {
                        $hero1_name = $heroes[$matchData['hero_id_1']]['name_formatted'];
                        $hero1_img = $heroes[$matchData['hero_id_1']]['pic'] . '.png';
                    } else {
                        $hero1_name = 'Unknown';
                        $hero1_img = $heroes[0]['pic'] . '.png';
                    }
                } else {
                    $hero1_name = 'Bye';
                    $hero1_img = $heroes[0]['pic'] . '.png';
                }
                $hero1_img = $imageCDN . '/images/heroes/' . $hero1_img;
                $hero1_img = "<img width='54' height='30' title='{$hero1_name}' alt='Image for hero #{$matchData['hero_id_1']}' src='{$hero1_img}' />";
                $hero1_votes = number_format($matchData['vote_count_1']);
                $hero1_seed = $matchData['hero_seeding_1'];

                $matchData['vote_count_0'] > $matchData['vote_count_1']
                    ? $hero0_name_formatted = "<span class='boldGreenText'><em>{$hero0_name}</em></span>"
                    : $hero0_name_formatted = $hero0_name;

                $matchData['vote_count_1'] > $matchData['vote_count_0']
                    ? $hero1_name_formatted = "<span class='boldGreenText'><em>{$hero1_name}</em></span>"
                    : $hero1_name_formatted = $hero1_name;

                //$roundMatchesTables[$key2] .= "<tr><th colspan='3' align='center'>Match #{$key2}</th></tr>";

                $roundMatchesTables[$key2] .= "<tr>
                        <td align='left' width='160px'>{$hero0_name_formatted}</td>
                        <td align='center'><strong>vs.</strong></td>
                        <td align='right' width='160px'>{$hero1_name_formatted}</td>
                    </tr>";

                $roundMatchesTables[$key2] .= "<tr>
                        <td align='center'>{$hero0_img}</td>
                        <td>&nbsp;</td>
                        <td align='center'>{$hero1_img}</td>
                    </tr>";

                $roundMatchesTables[$key2] .= "<tr>
                        <td align='left'>{$hero0_votes}</td>
                        <td align='center'><strong>votes</strong></td>
                        <td align='right'>{$hero1_votes}</td>
                    </tr>";


                if (($matchData['vote_count_0'] + $matchData['vote_count_1']) > 0) {
                    $votesMax = $matchData['vote_count_0'] + $matchData['vote_count_1'];
                    $votePercentageHero0 = number_format($matchData['vote_count_0'] / ($matchData['vote_count_0'] + $matchData['vote_count_1']) * 100, 0);
                    $votePercentageHero1 = number_format($matchData['vote_count_1'] / ($matchData['vote_count_0'] + $matchData['vote_count_1']) * 100, 0);

                    $hero0classMeet = $matchData['vote_count_0'] > $matchData['vote_count_1']
                        ? ($matchData['vote_count_1'] == 0
                            ? 'green arcana-progress-bar-meet-right-special'
                            : 'green arcana-progress-bar-meet-right')
                        : ($matchData['vote_count_1'] == 0
                            ? 'red arcana-progress-bar-meet-right-special'
                            : 'red arcana-progress-bar-meet-right');

                    $hero1classMeet = $matchData['vote_count_1'] > $matchData['vote_count_0']
                        ? ($matchData['vote_count_0'] == 0
                            ? 'green arcana-progress-bar-meet-left-special'
                            : 'green arcana-progress-bar-meet-left')
                        : ($matchData['vote_count_0'] == 0
                            ? 'red arcana-progress-bar-meet-left-special'
                            : 'red arcana-progress-bar-meet-left');

                    if ($matchData['vote_count_0'] == 0) $hero0classMeet = 'arcana-progress-bar-hide';
                    if ($matchData['vote_count_1'] == 0) $hero1classMeet = 'arcana-progress-bar-hide';

                    $roundMatchesTables[$key2] .= "<tr>
                            <td colspan='3'>
                                <div class='arcana-progress-bar'>
                                    <span class='{$hero0classMeet}' title='{$hero0_name}' style='width: {$votePercentageHero0}%;'>{$votePercentageHero0}%</span>
                                    <span class='{$hero1classMeet}' title='{$hero1_name}' style='width: {$votePercentageHero1}%;'>{$votePercentageHero1}%</span>
                                </div>
                            </td>
                        </tr>";

                    $thisMatchVotes = $matchData['vote_count_0'] + $matchData['vote_count_1'];
                    $thisMatchVotes_percent = number_format($thisMatchVotes / $numTotalVotes * 100, 0);
                    $thisMatchVotes_percent_accurate = number_format($thisMatchVotes / $numTotalVotes * 100, 1);
                    $thisMatchVotes_formatted = number_format($thisMatchVotes) . ' total votes';
                    $roundMatchesTables[$key2] .= "<tr><td>&nbsp;</td></tr>";
                    $roundMatchesTables[$key2] .= "<tr><td colspan='3'>{$thisMatchVotes_percent_accurate}% of total round votes</td></tr>";
                    $roundMatchesTables[$key2] .= "<tr>
                            <td colspan='3'>
                                <div class='arcana-progress-bar' style='height: 15px'>
                                    <span class='blue arcana-progress-bar-meet-right-special' title='{$thisMatchVotes_formatted}' style='width: {$thisMatchVotes_percent}%;'></span>
                                </div>
                            </td>
                        </tr>";
                }

                /*$roundMatchesTables[$key2] .= "<tr>
                        <td align='left'>{$hero0_seed}</td>
                        <td align='center'><strong>seed</strong></td>
                        <td align='right'>{$hero1_seed}</td>
                    </tr>";*/

                $roundMatchesTables[$key2] .= "<tr>
                        <td colspan='3'><hr /></td>
                    </tr>";
                $roundMatchesTables[$key2] .= "</table>";
            }

            if (!empty($roundMatchesTables)) {
                echo "<table style='width: 100%'><tr>";
                $i = 0;
                $countTableCells = count($roundMatchesTables);
                foreach ($roundMatchesTables as $key2 => $roundMatchesTablesData) {
                    $i++;
                    echo '<td>' . $roundMatchesTablesData . '</td>';

                    if ($i % 2 == 0 && $i != $countTableCells) {
                        echo "</tr></table><table style='width: 100%'><tr>";
                    }
                }
                echo "</tr></table>";
            }

            if (!empty($roundData['byes'])) {
                $numByes = count($roundData['byes']);
                //$rowNames = "<tr><th>&nbsp;</th>";
                $rowImages = "<tr><th>&nbsp;</th>";
                //$rowSeed = "<tr><th align='left'>Seed</th>";

                echo "<h3>Byes <small>({$numByes})</small></h3>";

                echo "<table style='border: 1px; padding: 5px; border-spacing: 2px;'>";

                foreach ($roundData['byes'] as $key2 => $byeData) {
                    if (!empty($byeData['hero_id_0']) && is_numeric($byeData['hero_id_0'])) {
                        if (!empty($heroes[$byeData['hero_id_0']])) {
                            $hero0_name = $heroes[$byeData['hero_id_0']]['name_formatted'];
                            $hero0_img = $heroes[$byeData['hero_id_0']]['pic'] . '.png';
                        } else {
                            $hero0_name = 'Unknown';
                            $hero0_img = $heroes[0]['pic'] . '.png';
                        }
                    } else {
                        $hero0_name = 'Bye (#' . $byeData['match_id'] . ')';
                        $hero0_img = $heroes[0]['pic'] . '.png';
                    }
                    $hero0_img = $imageCDN . '/images/heroes/' . $hero0_img;
                    $hero0_img = "<img class='arcana-hero-image' width='54' height='30' title='{$hero0_name}' alt='Image for hero #{$byeData['hero_id_0']}' src='{$hero0_img}' />";
                    $hero0_seed = $byeData['hero_seeding_0'];

                    //$rowNames .= "<td align='center' width='100px'>{$hero0_name}</td>";
                    $rowImages .= "<td align='center'>{$hero0_img}</td>";
                    //$rowSeed .= "<td align='center'>{$hero0_seed}</td>";
                }

                //echo $rowNames . '</tr>' . $rowImages . '</tr>' . $rowSeed . '</tr></table>';
                echo $rowImages . '</tr></table>';
                echo '<hr />';
            }
        }
    }


    /*echo '<pre>';
    print_r($orderedRounds);
    echo '</pre>';*/


} catch (Exception $e) {
    echo formatExceptionHandling($e);
} finally {
    if (isset($memcached)) $memcached->close();
}