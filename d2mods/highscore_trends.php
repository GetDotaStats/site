<?php
require_once('./functions.php');
require_once('../global_functions.php');
require_once('../connections/parameters.php');

try {
    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    if ($db) {
        echo '<div class="page-header"><h2>10 Invokes <small>Trends</small></h2></div>';

        echo '<p>This graph illustrates trends in high score values. It is read right to left, with the data on the farthest left being the most recent.</p>';

        $minigameTrendSQL = cached_query(
            'hs_trends',
            'SELECT
                `minigameID`,
                `leaderboard`,
                (1000 * floor(`highscore_value` / 1000)) AS `range_start`,
                (1000 * floor(`highscore_value` / 1000) + 1000) AS `range_end`,
                COUNT(*) AS `num_scores`
            FROM `stat_highscore`
            WHERE `minigameID` = "112871c41019e5cbb2fc0e0d08e7d518" AND `leaderboard` = "Ten Invokes"
            GROUP BY 1,2,4
            HAVING `range_end` < 61000
            ORDER BY 1,2,4;',
            30
        );

        //minigame trend
        {
            if (!empty($minigameTrendSQL) || !empty($gamesPlayedSQL)) {
                $testArray = array();

                if (!empty($minigameTrendSQL)) {
                    foreach ($minigameTrendSQL as $key => $value) {
                        $rangeStart = number_format($value['range_start'] / 1000, 0);
                        $rangeEnd = number_format($value['range_end'] / 1000, 0);

                        $testArray[$rangeStart . ' - ' . $rangeEnd] = $value['num_scores'];
                    }
                }

                $options = array(
                    'bar' => array(
                        'groupWidth' => 6,
                    ),
                    'height' => 300,
                    'chartArea' => array(
                        'width' => '87%',
                        'height' => '85%',
                        'left' => 80,
                        'top' => 10,
                    ),
                    'hAxis' => array(
                        'textPosition' => 'none',
                    ),
                    'vAxes' => array(
                        0 => array(
                            'title' => 'Num. of Scores',
                            //'textPosition' => 'in',
                            //'logScale' => 1,
                        ),
                    ),
                    'legend' => array(
                        'position' => 'none',
                        //'alignment' => 'center',
                    ),
                    'seriesType' => 'bars',
                    'series' => array(
                        0 => array(
                            'type' => 'bar',
                            'targetAxisIndex' => 0,
                        ),
                    ),
                    'tooltip' => array( //'isHtml' => 1,
                    ),
                    'isStacked' => 1,
                    'focusTarget' => 'category',
                );

                $chart = new chart2('ComboChart');

                $totalScores = array_sum($testArray);

                $super_array = array();
                foreach ($testArray as $key2 => $value2) {
                    $numScores = !empty($value2)
                        ? $value2
                        : 0;

                    $percentageScores = $totalScores > 0
                        ? number_format(($value2 / $totalScores * 100), 1) . '%'
                        : '0%';

                    $super_array[] = array('c' => array(
                        array('v' => $key2),
                        array('v' => $numScores),
                        array('v' => number_format($numScores) . ' [' . $percentageScores . ']'),
                    ));
                }

                $data = array(
                    'cols' => array(
                        array('id' => '', 'label' => 'Date', 'type' => 'string'),
                        array('id' => '', 'label' => 'Scores', 'type' => 'number'),
                        array('id' => '', 'label' => 'Tooltip', 'type' => 'string', 'role' => 'tooltip', 'p' => array('html' => 1)),
                    ),
                    'rows' => $super_array
                );

                $chart_width = max(count($super_array) * 8, 700);
                $options['width'] = $chart_width;
                $options['hAxis']['gridlines']['count'] = count($super_array);

                echo '<div id="breakdown_num_scores" class="d2mods-graph d2mods-graph-overflow"></div>';

                $chart->load(json_encode($data));
                echo $chart->draw('breakdown_num_scores', $options);
            } else {
                echo bootstrapMessage('Oh Snap', 'No lobby data!', 'danger');
            }
        }
    } else {
        echo bootstrapMessage('Oh Snap', 'No db!', 'danger');
    }

    echo '<p>
            <div class="text-center">
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__lobby_list">Lobby List</a>
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__recent_games">Recent Games</a>
           </div>
        </p>';
} catch (Exception $e) {
    $message = 'Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage();
    echo bootstrapMessage('Oh Snap', $message, 'danger');
}