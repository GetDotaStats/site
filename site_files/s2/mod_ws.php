<?php
require_once('../global_functions.php');
require_once('../connections/parameters.php');
require_once('./functions.php');

require_once('../bootstrap/highcharts/Highchart.php');
require_once('../bootstrap/highcharts/HighchartJsExpr.php');
require_once('../bootstrap/highcharts/HighchartOption.php');
require_once('../bootstrap/highcharts/HighchartOptionRenderer.php');

if (!isset($_SESSION)) {
    session_start();
}

try {
    if (empty($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception('Invalid modID! Bad type.');
    }

    $modID = $_GET['id'];

    $filterTimeSpan = !empty($_GET['t']) && is_numeric($_GET['t'])
        ? $_GET['t']
        : -1;

    switch ($filterTimeSpan) {
        case 1:
            $filterTimeSpanSQL = ' AND mw.`date_recorded` >= NOW() - INTERVAL 7 DAY ';
            $sqlFilter = 1;
            break;
        case 2:
            $filterTimeSpanSQL = ' AND mw.`date_recorded` >= NOW() - INTERVAL 30 DAY ';
            $sqlFilter = 2;
            break;
        case 3:
            $filterTimeSpanSQL = ' AND mw.`date_recorded` >= NOW() - INTERVAL 60 DAY ';
            $sqlFilter = 3;
            break;
        case 4:
            $filterTimeSpanSQL = ' AND mw.`date_recorded` >= NOW() - INTERVAL 180 DAY ';
            $sqlFilter = 4;
            break;
        case 5:
            $filterTimeSpanSQL = '';
            $sqlFilter = 5;
            break;
        default:
            $filterTimeSpanSQL = ' AND mw.`date_recorded` >= NOW() - INTERVAL 30 DAY ';
            $sqlFilter = 2;
            break;
    }

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    echo modPageHeader($modID, $CDN_image);

    //////////////////
    //Workshop stats
    //////////////////
    {
        try {
            echo '<h3>Workshop Stats</h3>';

            echo '<p>Breakdown of workshop stats per day. Scraped twice a day, depending on availability of Steam webAPI.';

            try {
                $serviceReporting = new serviceReporting($db);
                $lastCronUpdateDetails = $serviceReporting->getServiceLog('s2_cron_workshop_scrape');
                $lastCronUpdateRunTime = $serviceReporting->getServiceLogRunTime();
                $lastCronUpdateExecutionTime = $serviceReporting->getServiceLogExecutionTime();

                echo " This data was last updated <strong>{$lastCronUpdateRunTime}</strong>, taking <strong>{$lastCronUpdateExecutionTime}</strong> to generate.</p>";
            } catch (Exception $e) {
                echo '</p>';
                echo formatExceptionHandling($e);
            }

            echo '<div class="text-center">
                    <a class="nav-clickable btn btn-sm btn-success" href="#s2__mod_ws?id=' . $modID . '&t=1">Last Week</a>
                    <a class="nav-clickable btn btn-sm btn-success" href="#s2__mod_ws?id=' . $modID . '&t=2">Last Month</a>
                    <a class="nav-clickable btn btn-sm btn-success" href="#s2__mod_ws?id=' . $modID . '&t=3">Last 2 Months</a>
                    <a class="nav-clickable btn btn-sm btn-success" href="#s2__mod_ws?id=' . $modID . '&t=4">Last 6 Months</a>
                    <a class="nav-clickable btn btn-sm btn-success" href="#s2__mod_ws?id=' . $modID . '&t=5">All Time</a>
               </div>';

            $modDetails = cached_query(
                's2_mod_ws_mod_details' . $modID,
                'SELECT
                        `mod_id`,
                        `steam_id64`,
                        `mod_identifier`,
                        `mod_name`
                    FROM `mod_list`
                    WHERE `mod_id` = ?
                    LIMIT 0,1;',
                'i',
                $modID
            );

            if (empty($modDetails)) throw new Exception('Invalid modID!');

            $modIdentifier = $modDetails[0]['mod_identifier'];
            $modName = $modDetails[0]['mod_name'];

            $workshopStats = cached_query(
                's2_mod_ws_' . $modID . '_' . $sqlFilter,
                'SELECT
                      DAY(mw.`date_recorded`) as `day`,
                      MONTH(mw.`date_recorded`) as `month`,
                      YEAR(mw.`date_recorded`) as `year`,
                      mw.`mod_check_id`,
                      mw.`mod_identifier`,
                      mw.`mod_workshop_id`,
                      mw.`mod_size`,
                      mw.`mod_hcontent_file`,
                      mw.`mod_hcontent_preview`,
                      mw.`mod_thumbnail`,
                      mw.`mod_views`,
                      mw.`mod_subs`,
                      mw.`mod_favs`,
                      mw.`mod_subs_life`,
                      mw.`mod_favs_life`,
                      mw.`date_last_updated`,
                      mw.`date_recorded`
                    FROM `mod_workshop` mw
                    WHERE mw.`mod_identifier` = ? ' . $filterTimeSpanSQL . '
                    GROUP BY 3,2,1
                    ORDER BY 3,2,1;',
                's',
                array($modIdentifier),
                10
            );

            if (empty($workshopStats)) {
                throw new Exception('No games recorded!');
            }

            $bigArray = array();
            foreach ($workshopStats as $key => $value) {
                $year = $value['year'];
                $month = $value['month'] >= 1
                    ? $value['month'] - 1
                    : $value['month'];
                $day = $value['day'];

                $modViews = !empty($value['mod_views']) && is_numeric($value['mod_views'])
                    ? intval($value['mod_views'])
                    : 0;

                $modSubs = !empty($value['mod_subs']) && is_numeric($value['mod_subs'])
                    ? intval($value['mod_subs'])
                    : 0;

                $modFavs = !empty($value['mod_favs']) && is_numeric($value['mod_favs'])
                    ? intval($value['mod_favs'])
                    : 0;

                $bigArray['Subscriptions'][] = array(
                    new HighchartJsExpr("Date.UTC($year, $month, $day)"),
                    $modSubs,
                );

                $bigArray['Views'][] = array(
                    new HighchartJsExpr("Date.UTC($year, $month, $day)"),
                    $modViews,
                );

                $bigArray['Favourites'][] = array(
                    new HighchartJsExpr("Date.UTC($year, $month, $day)"),
                    $modFavs,
                );
            }

            $lineChart = makeLineChart(
                $bigArray,
                'workshop_stats_all',
                'Workshop Subs / Views / Favs',
                new HighchartJsExpr("document.ontouchstart === undefined ? 'Click and drag in the plot area to zoom in' : 'Pinch the chart to zoom in'"),
                array('title' => 'Subscriptions', 'min' => 0),
                array(
                    'Views' => array('title' => 'Views', 'yAxis' => 1, 'min' => 0, 'opposite' => true),
                    'Favourites' => array('title' => 'Favourites', 'yAxis' => 2, 'min' => 0, 'opposite' => true),
                )
            );

            echo '<div id="workshop_stats_all"></div>';
            echo $lineChart;

        } catch (Exception $e) {
            echo formatExceptionHandling($e);
        }
    }

    echo '<hr />';

    echo '<span class="h4">&nbsp;</span>';

    echo '<div class="text-center">
                <a class="nav-clickable btn btn-default btn-lg" href="#s2__directory">Mod Directory</a>
                <a class="nav-clickable btn btn-default btn-lg" href="#s2__recent_games">Recent Games</a>
           </div>';

    echo '<span class="h4">&nbsp;</span>';
} catch (Exception $e) {
    echo formatExceptionHandling($e);
} finally {
    if (isset($memcache)) $memcache->close();
}