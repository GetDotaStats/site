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

                echo " This data was last updated {$lastCronUpdateRunTime}, taking {$lastCronUpdateExecutionTime} to generate.</p>";
            } catch (Exception $e) {
                echo '</p>';
                echo formatExceptionHandling($e);
            }

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
                's2_mod_ws_' . $modID,
                'SELECT
                      DAY(`date_recorded`) as `day`,
                      MONTH(`date_recorded`) as `month`,
                      YEAR(`date_recorded`) as `year`,
                      `mod_check_id`,
                      `mod_identifier`,
                      `mod_workshop_id`,
                      `mod_size`,
                      `mod_hcontent_file`,
                      `mod_hcontent_preview`,
                      `mod_thumbnail`,
                      `mod_views`,
                      `mod_subs`,
                      `mod_favs`,
                      `mod_subs_life`,
                      `mod_favs_life`,
                      `date_last_updated`,
                      `date_recorded`
                    FROM `mod_workshop`
                    WHERE `mod_identifier` = ?
                    GROUP BY 3,2,1
                    ORDER BY 3,2,1;',
                's',
                array($modIdentifier),
                5
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