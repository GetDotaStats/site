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

    $memcached = new Cache(NULL, NULL, $localDev);

    checkLogin_v2();
    if (empty($_SESSION['user_id64'])) throw new Exception('Not logged in!');

    $adminCheck = adminCheck($_SESSION['user_id64'], 'admin');
    if (empty($adminCheck)) throw new Exception('Not an admin!');

    $userSoloMMRs = cached_query(
        'admin_user_solo_mmrs',
        'SELECT
                100 * floor(`user_mmr_solo` / 100) as `range_start`,
                100 * floor(`user_mmr_solo` / 100) + 100 as `range_end`,
                COUNT(*) as num_mmrs
            FROM `gds_users_mmr` gum
            JOIN (
                  SELECT
                      `user_id32`,
                      MAX(`date_recorded`) as most_recent_mmr
                  FROM `gds_users_mmr`
                  GROUP BY `user_id32`
                  ORDER BY `user_mmr_solo`
            ) gum2 ON gum.`user_id32` = gum2.`user_id32` AND gum.`date_recorded` = gum2.`most_recent_mmr`
            GROUP BY 2
            ORDER BY 2;',
        null,
        null,
        10
    );

    $userPartyMMRs = cached_query(
        'admin_user_party_mmrs',
        'SELECT
                100 * floor(`user_mmr_party` / 100) as `range_start`,
                100 * floor(`user_mmr_party` / 100) + 100 as `range_end`,
                COUNT(*) as num_mmrs
            FROM `gds_users_mmr` gum
            JOIN (
                  SELECT
                      `user_id32`,
                      MAX(`date_recorded`) as most_recent_mmr
                  FROM `gds_users_mmr`
                  GROUP BY `user_id32`
                  ORDER BY `user_mmr_party`
            ) gum2 ON gum.`user_id32` = gum2.`user_id32` AND gum.`date_recorded` = gum2.`most_recent_mmr`
            GROUP BY 2
            ORDER BY 2;',
        null,
        null,
        10
    );

    $userMMRsCount = cached_query(
        'admin_user_mmrs_count',
        'SELECT
              COUNT(DISTINCT `user_id64`) as total_users
            FROM `gds_users_mmr`;',
        null,
        null,
        10
    );

    if (empty($userSoloMMRs) || empty($userMMRsCount)) throw new Exception('No MMRs recorded!');

    echo '<h3>Total Users: <small>' . $userMMRsCount[0]['total_users'] . '</small></h3>';

    echo '<p>This section likely won\'t be permament. It will serve as a temporary page to diagnose issues with MMR reporting.</p>';

    echo '<hr />';

    //SOLO MMR
    {
        $testArray = array();
        $lastNum = 0; //NEED TO BE NEGATIVE TO GRAPH 0 TOO

        $periodGrouping = 100; //CHANGE SQL TOO IF YOU MODIFY THIS
        $periodCutoff = 8000;

        foreach ($userSoloMMRs as $key => $value) {
            if ($value['range_end'] > $periodCutoff) {
                if (empty($testArray[$periodCutoff . '+'])) {
                    $testArray[$periodCutoff . '+'] = $value['num_mmrs'];
                } else {
                    $testArray[$periodCutoff . '+'] += $value['num_mmrs'];
                }
            } else {
                if ($value['range_end'] > ($lastNum + $periodGrouping)) {
                    while ($value['range_end'] > ($lastNum + $periodGrouping)) {
                        //$testArray[$lastNum . ' - ' . ($lastNum + $periodGrouping)] = 0;
                        $testArray[$lastNum . '+'] = 0;
                        $lastNum += $periodGrouping;
                    }
                }

                //$testArray[$lastNum . ' - ' . $value['range_end']] = $value['num_mmrs'];
                $testArray[$lastNum . '+'] = $value['num_mmrs'];

                $lastNum = $value['range_end'];
            }
        }

        $options = array(
            'bar' => array(
                'groupWidth' => 7,
            ),
            'height' => 300,
            'chartArea' => array(
                'width' => '100%',
                'height' => '70%',
                'left' => 80,
                'bottom' => 40,
                'top' => 10,
            ),
            'hAxis' => array(
                'title' => 'MMR',
                'slantedText' => 1,
                'slantedTextAngle' => 60,
            ),
            'vAxis' => array(
                'title' => 'Users',
                //'scaleType' => 'mirrorLog',
            ),
            'legend' => array(
                'position' => 'none',
            ),
            'seriesType' => "bars",
            'tooltip' => array(
                'isHtml' => 1,
            ),
        );

        $optionsDataTable = array(
            'sortColumn' => 0,
            'sortAscending' => true,
            'alternatingRowStyle' => true,
            'page' => 'enable',
            'pageSize' => 5);


        $chart = new chart2('ComboChart');

        $super_array = array();
        foreach ($testArray as $key2 => $value2) {
            $super_array[] = array('c' => array(array('v' => $key2), array('v' => $value2), array('v' => '<div class="d2mods-graph-tooltips"><strong>' . $key2 . '</strong><br />Users: <strong>' . number_format($value2) . '</strong><br />(' . number_format(100 * $value2 / array_sum($testArray), 2) . '%)</div>')));
        }

        $data = array(
            'cols' => array(
                array('id' => '', 'label' => 'MMR', 'type' => 'string'),
                array('id' => '', 'label' => 'Users', 'type' => 'number'),
                array('id' => '', 'label' => 'Tooltip', 'type' => 'string', 'role' => 'tooltip', 'p' => array('html' => 1)),
            ),
            'rows' => $super_array
        );

        end($value);
        $maxKey = key($value);


        $chart_width = max(count($super_array) * 9, 700);
        $options['width'] = $chart_width;
        $options['hAxis']['maxValue'] = $maxKey + 2;
        $options['hAxis']['gridlines']['count'] = ($maxKey + 2) / 2;

        echo '<h3>Distribution of Solo MMRs</h3>';
        echo '<div id="solo_mmr_breakdown" class="d2mods-graph"></div>';

        $chart->load(json_encode($data));
        echo $chart->draw('solo_mmr_breakdown', $options);
    }

    echo '<hr />';

    //TEAM MMR
    {
        $testArray = array();
        $lastNum = 0; //NEED TO BE NEGATIVE TO GRAPH 0 TOO

        $periodGrouping = 100; //CHANGE SQL TOO IF YOU MODIFY THIS
        $periodCutoff = 8000;

        foreach ($userPartyMMRs as $key => $value) {
            if ($value['range_end'] > $periodCutoff) {
                if (empty($testArray[$periodCutoff . '+'])) {
                    $testArray[$periodCutoff . '+'] = $value['num_mmrs'];
                } else {
                    $testArray[$periodCutoff . '+'] += $value['num_mmrs'];
                }
            } else {
                if ($value['range_end'] > ($lastNum + $periodGrouping)) {
                    while ($value['range_end'] > ($lastNum + $periodGrouping)) {
                        //$testArray[$lastNum . ' - ' . ($lastNum + $periodGrouping)] = 0;
                        $testArray[$lastNum . '+'] = 0;
                        $lastNum += $periodGrouping;
                    }
                }

                //$testArray[$lastNum . ' - ' . $value['range_end']] = $value['num_mmrs'];
                $testArray[$lastNum . '+'] = $value['num_mmrs'];

                $lastNum = $value['range_end'];
            }
        }

        $options = array(
            'bar' => array(
                'groupWidth' => 7,
            ),
            'height' => 300,
            'chartArea' => array(
                'width' => '100%',
                'height' => '70%',
                'left' => 80,
                'bottom' => 40,
                'top' => 10,
            ),
            'hAxis' => array(
                'title' => 'MMR',
                'slantedText' => 1,
                'slantedTextAngle' => 60,
            ),
            'vAxis' => array(
                'title' => 'Users',
                //'scaleType' => 'mirrorLog',
            ),
            'legend' => array(
                'position' => 'none',
            ),
            'seriesType' => "bars",
            'tooltip' => array(
                'isHtml' => 1,
            ),
        );

        $optionsDataTable = array(
            'sortColumn' => 0,
            'sortAscending' => true,
            'alternatingRowStyle' => true,
            'page' => 'enable',
            'pageSize' => 5);


        $chart = new chart2('ComboChart');

        $super_array = array();
        foreach ($testArray as $key2 => $value2) {
            $super_array[] = array('c' => array(array('v' => $key2), array('v' => $value2), array('v' => '<div class="d2mods-graph-tooltips"><strong>' . $key2 . '</strong><br />Users: <strong>' . number_format($value2) . '</strong><br />(' . number_format(100 * $value2 / array_sum($testArray), 2) . '%)</div>')));
        }

        $data = array(
            'cols' => array(
                array('id' => '', 'label' => 'MMR', 'type' => 'string'),
                array('id' => '', 'label' => 'Users', 'type' => 'number'),
                array('id' => '', 'label' => 'Tooltip', 'type' => 'string', 'role' => 'tooltip', 'p' => array('html' => 1)),
            ),
            'rows' => $super_array
        );

        end($value);
        $maxKey = key($value);


        $chart_width = max(count($super_array) * 9, 700);
        $options['width'] = $chart_width;
        $options['hAxis']['maxValue'] = $maxKey + 2;
        $options['hAxis']['gridlines']['count'] = ($maxKey + 2) / 2;

        echo '<h3>Distribution of Party MMRs</h3>';
        echo '<div id="party_mmr_breakdown" class="d2mods-graph"></div>';

        $chart->load(json_encode($data));
        echo $chart->draw('party_mmr_breakdown', $options);
    }

} catch (Exception $e) {
    echo formatExceptionHandling($e);
} finally {
    if (isset($memcached)) $memcached->close();
}