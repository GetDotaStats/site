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

    $memcached = new Cache(NULL, NULL, $localDev);

    echo modPageHeader($modID, $CDN_image);

    //////////////////
    //FLAGS
    //////////////////
    {
        try {
            echo '<h3>Flags</h3>';

            echo '<p>Breakdown of flags for all games played in the last week. Calculated twice a day. Flags are arbitrary values that a mod assigns before the game starts.</p>';

            try {
                $serviceReporting = new serviceReporting($db);
                $lastCronUpdateDetails = $serviceReporting->getServiceLog('cron_match_flags__' . $modID);
                $lastCronUpdateRunTime = $serviceReporting->getServiceLogRunTime();
                $lastCronUpdateExecutionTime = $serviceReporting->getServiceLogExecutionTime();

                echo "<p>This data was last updated <strong>{$lastCronUpdateRunTime}</strong>, taking <strong>{$lastCronUpdateExecutionTime}</strong> to generate.</p>";
            } catch (Exception $e) {
                echo formatExceptionHandling($e);
            }

            $flags = cached_query(
                's2_mod_page_flags' . $modID,
                'SELECT
                      ccf.`modID`,
                      ccf.`flagName`,
                      ccf.`flagValue`,
                      ccf.`numGames`
                    FROM `cache_custom_flags` ccf
                    WHERE ccf.`modID` = ?
                    ORDER BY ccf.`modID`, ccf.`flagName`, ccf.`flagValue`;',
                's',
                $modID,
                1
            );

            if (empty($flags)) throw new Exception('No flags recorded for this mod!');

            $bigArray = array();
            $lastModID = -1;
            foreach ($flags as $key => $value) {
                $numGames = !empty($value['numGames']) && is_numeric($value['numGames'])
                    ? intval($value['numGames'])
                    : 0;

                $bigArray[$value['flagName']][] = array(
                    $value['flagValue'],
                    $numGames,
                );
            }

            $flagChartDivs = '';
            $numFlags = count($bigArray);
            $columnWidth = $numFlags > 1
                ? 6
                : 12;
            $i = 1;
            foreach ($bigArray as $key => $value) {
                $numGames = 0;
                $valueTest = array();
                foreach ($value as $key2 => $value2) {
                    $numGames += $value2[1];
                    $valueTest[$value2[0]] = $value2;
                }

                ksort($valueTest);
                $o = 0;
                $valueFinal = array();
                foreach ($valueTest as $key2 => $value2) {
                    $valueFinal[$o] = $value2;
                    $o++;
                }

                $pieChart = makePieChart(
                    $valueFinal,
                    'container_flag_' . $key,
                    "{$key}",
                    "{$numGames} matches had this flag"
                );

                if ($i == 1) {
                    $flagChartDivs = '<div class="row">';
                } else if ($i % 2 != 0) {
                    $flagChartDivs .= '</div><div class="row">';
                }
                $i++;

                $flagChartDivs .= "<div class='col-md-{$columnWidth}'><div id='container_flag_{$key}'></div>$pieChart</div>";
            }

            if ($numFlags % 2 != 0) {
                $flagChartDivs .= '</div>';
            }

            echo $flagChartDivs;

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
    if (isset($memcached)) $memcached->close();
}