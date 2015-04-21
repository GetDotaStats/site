<?php
try {
    require_once('../global_functions.php');
    require_once('./functions.php');
    require_once('../connections/parameters.php');

    if (!isset($_SESSION)) {
        session_start();
    }

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    checkLogin_v2();
    if (empty($_SESSION['user_id64'])) throw new Exception('Not logged in!');

    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    $userID64 = $_SESSION['user_id64'];

    $myMMR = cached_query(
        'user_my_mmr' . $userID64,
        'SELECT
                HOUR(`date_recorded`) AS date_hour,
                DAY(`date_recorded`) AS date_day,
                MONTH(`date_recorded`) AS date_month,
                YEAR(`date_recorded`) AS date_year,
                `user_id32`,
                `user_id64`,
                `user_name`,
                MAX(`user_games`) AS user_games,
                AVG(`user_mmr_solo`) AS user_mmr_solo,
                AVG(`user_mmr_party`) AS user_mmr_party,
                `date_recorded`
            FROM `gds_users_mmr`
            WHERE `user_id64` = ?
            GROUP BY 4,3,2,1
            ORDER BY 4 DESC, 3 DESC, 2 DESC, 1 DESC;',
        's',
        $userID64,
        10
    );

    if (empty($myMMR)) throw new Exception('No MMRs recorded!');

    echo '<h2>User MMR History</h2>';

    echo '<p>Below is a graph that shows the history of your MMR (averaged for each day), if you opted into sharing
    your MMR with us. The most recent data is on the left, with the oldest on the right. If you have not yet opted
    into sharing your MMR, ensure your Lobby Explorer is up to date, and then tick the "Share your MMR with
    GetDotaStats" check-box in the main menu of the client.</p>';

    //MMR History
    {
        if (!empty($myMMR)) {
            $testArray = array();

            foreach ($myMMR as $key => $value) {
                $modDate = $value['date_year'] . '-' . $value['date_month'] . '-' . $value['date_day'];
                $testArray[$modDate]['solo_mmr'] = $value['user_mmr_solo'];
            }

            foreach ($myMMR as $key => $value) {
                $modDate = $value['date_year'] . '-' . $value['date_month'] . '-' . $value['date_day'];
                $testArray[$modDate]['party_mmr'] = $value['user_mmr_party'];
            }

            foreach ($myMMR as $key => $value) {
                $modDate = $value['date_year'] . '-' . $value['date_month'] . '-' . $value['date_day'];
                $testArray[$modDate]['num_games'] = $value['user_games'];
            }

            $options = array(
                'height' => 400,
                'chartArea' => array(
                    'width' => '80%',
                    'height' => '85%',
                    'left' => 80,
                    'top' => 10,
                ),
                'hAxis' => array(
                    'textPosition' => 'none',
                ),
                'vAxes' => array(
                    0 => array(
                        'title' => 'MMR',
                        //'textPosition' => 'in',
                        //'logScale' => 1,
                    ),
                    1 => array(
                        'title' => 'Games',
                        'textPosition' => 'out',
                        //'logScale' => 1,
                    ),
                ),
                'legend' => array(
                    'position' => 'bottom',
                    'alignment' => 'start',
                ),
                'seriesType' => 'line',
                'series' => array(
                    0 => array(
                        'type' => 'line',
                        'targetAxisIndex' => 0,
                    ),
                    1 => array(
                        'type' => 'line',
                        'targetAxisIndex' => 0,
                    ),
                    2 => array(
                        'type' => 'line',
                        'targetAxisIndex' => 1,
                    ),
                ),
                'tooltip' => array( //'isHtml' => 1,
                ),
                'isStacked' => 1,
                'focusTarget' => 'category',
            );

            $chart = new chart2('ComboChart');

            $super_array = array();
            foreach ($testArray as $key2 => $value2) {
                $soloMMR = !empty($value2['solo_mmr'])
                    ? $value2['solo_mmr']
                    : 0;

                $partyMMR = !empty($value2['party_mmr'])
                    ? $value2['party_mmr']
                    : 0;

                $numGames = !empty($value2['num_games'])
                    ? $value2['num_games']
                    : 0;

                $super_array[] = array('c' => array(
                    array('v' => $key2),
                    array('v' => $soloMMR),
                    array('v' => number_format($soloMMR)),
                    array('v' => $partyMMR),
                    array('v' => number_format($partyMMR)),
                    array('v' => $numGames),
                    array('v' => number_format($numGames)),
                ));
            }

            $data = array(
                'cols' => array(
                    array('id' => '', 'label' => 'Date', 'type' => 'string'),
                    array('id' => '', 'label' => 'Solo', 'type' => 'number'),
                    array('id' => '', 'label' => 'Tooltip', 'type' => 'string', 'role' => 'tooltip', 'p' => array('html' => 1)),
                    array('id' => '', 'label' => 'Party', 'type' => 'number'),
                    array('id' => '', 'label' => 'Tooltip', 'type' => 'string', 'role' => 'tooltip', 'p' => array('html' => 1)),
                    array('id' => '', 'label' => 'Games', 'type' => 'number'),
                    array('id' => '', 'label' => 'Tooltip', 'type' => 'string', 'role' => 'tooltip', 'p' => array('html' => 1)),
                ),
                'rows' => $super_array
            );

            $chart_width = max(count($super_array) * 8, 800);
            $options['width'] = $chart_width;

            echo '<div id="breakdown_mmr_history" class="d2mods-graph-wide-tall d2mods-graph-overflow"></div>';

            $chart->load(json_encode($data));
            echo $chart->draw('breakdown_mmr_history', $options);
        } else {
            echo bootstrapMessage('Oh Snap', 'No MMR data!', 'danger');
        }
    }

    echo '<span class="h3">&nbsp;</span>';

    echo '<p>This is an opt-in service. We do not collect this data until you opt-in, and we will
    not share your personal information with third parties. We may how ever use this data when creating aggregate
    statistics, but you will not be made identifiable by them.</p>';

    $memcache->close();
} catch (Exception $e) {
    //$message = 'Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage();
    $message = $e->getMessage();
    echo bootstrapMessage('Oh Snap', $message, 'danger');
}