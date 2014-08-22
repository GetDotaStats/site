<?php
require_once('./functions.php');
require_once('../global_functions.php');
require_once('../connections/parameters.php');

$start = time();

if (!isset($_SESSION)) {
    session_start();
}

try {
    $db = new dbWrapper_v2($hostname_sig, $username_sig, $password_sig, $database_sig);
    if ($db) {
        $memcache = new Memcache;
        $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

        echo '<h2>Breakdown of Signature Popularity</h2>';

        echo '<p>Below are graphs that depict the usage of our Dota2 signature since its inception. All data is anonymised, and no personally identifying data will be shared above and beyond what using the signature already tells people.</p>';

        {
            ////////////////////////////////////////////////////////
            // Hourly (All Time)
            ////////////////////////////////////////////////////////

            $chart = new chart2('ComboChart');

            $production_stats = simple_cached_query('stats_signature_adoption_alltime',
                'SELECT `hour`, `day`, `month`, `year`, `sig_views` FROM `stats_1_count` WHERE `date_accessed` >= (SELECT MAX(`date_accessed`) FROM `stats_1_count`) - INTERVAL 7 DAY GROUP BY 4,3,2,1 ORDER BY 4 DESC,3 DESC,2 DESC,1 DESC;',
                10);
            $production_range = simple_cached_query('stats_signature_adoption_range',
                'SELECT MIN(`date_accessed`) as min_date, MAX(`date_accessed`) as max_date, SUM(`sig_views`) as total_views FROM `stats_1_count` WHERE `date_accessed` >= (SELECT MAX(`date_accessed`) FROM `stats_1_count`) - INTERVAL 7 DAY;',
                60);

            echo '<h3>Last Week of Data <small>' . number_format($production_range[0]['total_views']) . ' views</small></h3>';

            $super_array = array();
            foreach ($production_stats as $key => $value) {
                $date = $value['year'] . '-' . $value['month'] . '-' . $value['day'] . ' ' . str_pad($value['hour'], 2, '0', STR_PAD_LEFT) . ':00';
                $super_array[] = array('c' => array(array('v' => $date), array('v' => $value['sig_views'])));
            }

            $data = array(
                'cols' => array(
                    array('id' => '', 'label' => 'Date', 'type' => 'string'),
                    array('id' => '', 'label' => 'Views', 'type' => 'number'),
                ),
                'rows' => $super_array
            );

            $chart_width = max(count($production_stats) * 2, 800);
            $options = array(
                //'title' => 'Average spins in ' . $hits . ' attacks',
                //'theme' => 'maximized',
                'axisTitlesPosition' => 'in',
                'width' => $chart_width,
                'bar' => array(
                    'groupWidth' => 1,
                ),
                'height' => 300,
                'chartArea' => array(
                    'width' => '100%',
                    'height' => '95%',
                    'left' => 70,
                    'top' => 10,
                ),
                'hAxis' => array(
                    'title' => 'Date',
                    'maxAlternation' => 1,
                    'textPosition' => 'none',
                    //'textPosition' => 'in',
                    //'viewWindowMode' => 'maximized'
                ),
                'vAxis' => array(
                    'title' => 'Views',
                    //'textPosition' => 'in',
                ),
                'legend' => array(
                    'position' => 'none',
                ),
                'seriesType' => "bars",
                /*'series' => array(
                    3 => array(
                        'type' => "line"
                    ),
                ),*/
                'isStacked' => 'true',
            );

            $optionsDataTable = array(
                'width' => 800,
                'sortColumn' => 0,
                'sortAscending' => true,
                'alternatingRowStyle' => true,
                'page' => 'enable',
                'pageSize' => 6);

            echo '<div id="sig_views_lastweek" style="width: 800px;"></div>';
            echo '<div style="width: 800px;"><h4 class="text-center">' . relative_time($production_range[0]['max_date']) . ' --> ' . relative_time($production_range[0]['min_date']) . '</h4></div>';
            echo '<div class="panel panel-default" style="width: 800px;">
                <div class="panel-heading">
                    <h4 class="panel-title text-center">
                        <a data-toggle="collapse" data-target="#collapseTwo" class="btn btn btn-success collapsed" type="button">Data Table</a>
                    </h4>
                </div>
                <div id="collapseTwo" class="panel-collapse collapse">
                    <div class="panel-body">
                        <div id="sig_views_lastweek_dataTable" style="width: 100%;"></div>
                    </div>
                </div>
            </div>';

            $chart->load(json_encode($data));
            echo $chart->draw('sig_views_lastweek', $options, true, $optionsDataTable);
        }

        {
            ////////////////////////////////////////////////////////
            // Daily (All Time)
            ////////////////////////////////////////////////////////

            $chart = new chart2('ComboChart');

            $production_stats = simple_cached_query('stats_signature_adoption_alltime_daily',
                'SELECT `day`, `month`, `year`, SUM(`sig_views`) as sig_views FROM `stats_1_count` GROUP BY 3,2,1 ORDER BY 3 DESC,2 DESC,1 DESC;',
                10);
            $production_range = simple_cached_query('stats_signature_adoption_range_alltime',
                'SELECT MIN(`date_accessed`) as min_date, MAX(`date_accessed`) as max_date, SUM(`sig_views`) as total_views FROM `stats_1_count`;',
                60);

            echo '<h3>All Time Data <small>' . number_format($production_range[0]['total_views']) . ' views</small></h3>';

            $super_array = array();
            foreach ($production_stats as $key => $value) {
                $date = $value['year'] . '-' . $value['month'] . '-' . $value['day'];
                $super_array[] = array('c' => array(array('v' => $date), array('v' => $value['sig_views'])));
            }

            $data = array(
                'cols' => array(
                    array('id' => '', 'label' => 'Date', 'type' => 'string'),
                    array('id' => '', 'label' => 'Views', 'type' => 'number'),
                ),
                'rows' => $super_array
            );

            $chart_width = max(count($production_stats) * 2, 800);
            $options = array(
                //'title' => 'Average spins in ' . $hits . ' attacks',
                //'theme' => 'maximized',
                'axisTitlesPosition' => 'in',
                'width' => $chart_width,
                'bar' => array(
                    'groupWidth' => 1,
                ),
                'height' => 300,
                'chartArea' => array(
                    'width' => '100%',
                    'height' => '95%',
                    'left' => 70,
                    'top' => 10,
                ),
                'hAxis' => array(
                    'title' => 'Date',
                    'maxAlternation' => 1,
                    'textPosition' => 'none',
                    //'textPosition' => 'in',
                    //'viewWindowMode' => 'maximized'
                ),
                'vAxis' => array(
                    'title' => 'Views',
                    //'textPosition' => 'in',
                ),
                'legend' => array(
                    'position' => 'none',
                ),
                'seriesType' => "bars",
                /*'series' => array(
                    3 => array(
                        'type' => "line"
                    ),
                ),*/
                'isStacked' => 'true',
            );

            $optionsDataTable = array(
                'width' => 800,
                'sortColumn' => 0,
                'sortAscending' => true,
                'alternatingRowStyle' => true,
                'page' => 'enable',
                'pageSize' => 6);

            echo '<div id="sig_views_alltime_daily" style="overflow-x: scroll; width: 800px;"></div>';
            echo '<div style="width: 800px;"><h4 class="text-center">' . relative_time($production_range[0]['max_date']) . ' --> ' . relative_time($production_range[0]['min_date']) . '</h4></div>';
            echo '<div class="panel-heading" style="width: 800px;">
                    <h4 class="text-center">
                        <a class="btn btn-success collapsed" type="button" onclick="downloadCSV(\'sig_stats' . time() . '.csv\')">Download to CSV</a>
                    </h4>
                </div>';

            $chart->load(json_encode($data));
            echo $chart->draw('sig_views_alltime_daily', $options, false, array(), true);
        }

        echo '<div id="pagerendertime" style="font-size: 12px;">';
        echo '<hr />Page generated in ' . (time() - $start) . 'secs';
        echo '</div>';


        $memcache->close();
    } else {
        echo '<div class="page-header"><div class="alert alert-danger" role="alert"><strong>Oh Snap:</strong> No DB!</div></div>';
    }
} catch (Exception $e) {
    echo '<div class="page-header"><div class="alert alert-danger" role="alert"><strong>Oh Snap:</strong> Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage() . '</div></div>';
}