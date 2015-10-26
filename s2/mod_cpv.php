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
    //CustomGameValues
    //////////////////
    {
        try {
            echo '<h3>Custom Player Values</h3>';

            echo '<p>Breakdown of custom player values for all games played in the last week. Calculated twice a day. Player values are arbitrary values that the mod assigns per user at the end of the game or round.</p>';

            $schemaIDtoUse = $db->q(
                'SELECT
                        MAX(`schemaID`) as schemaID
                    FROM `s2_mod_custom_schema`
                    WHERE `modID` = ? AND `schemaApproved` = 1;',
                'i',
                $modID
            );

            if (empty($schemaIDtoUse)) {
                throw new Exception('No approved schema to use!');
            } else {
                $schemaIDtoUse = $schemaIDtoUse[0]['schemaID'];
            }

            $customPlayerValues = cached_query(
                's2_mod_page_custom_player_values' . $modID,
                'SELECT
                      ccpv.`modID`,
                      ccpv.`fieldOrder`,
                      ccpv.`fieldValue`,
                      ccpv.`numGames`,
                      s2mcsf.`customValueDisplay`
                    FROM `cache_custom_player_values` ccpv
                    JOIN (
                      SELECT
                          `fieldOrder`,
                          `customValueDisplay`
                        FROM `s2_mod_custom_schema_fields`
                        WHERE `fieldType` = 2 AND `schemaID` = ? AND `noGraph` = 0
                    ) s2mcsf ON s2mcsf.`fieldOrder` = ccpv.`fieldOrder`
                    WHERE ccpv.`modID` = ?
                    ORDER BY ccpv.`modID`, ccpv.`fieldOrder`, ccpv.`fieldValue`;',
                'is',
                array($schemaIDtoUse, $modID),
                1
            );

            if (empty($customPlayerValues)) throw new Exception('No custom player values recorded for this mod!');

            $bigArray = array();
            $lastModID = -1;
            foreach ($customPlayerValues as $key => $value) {
                if ($value['fieldValue'] == '-1') continue;

                $numGames = !empty($value['numGames']) && is_numeric($value['numGames'])
                    ? intval($value['numGames'])
                    : 0;

                $bigArray[$value['customValueDisplay']][] = array(
                    $value['fieldValue'],
                    $numGames,
                );
            }

            $customGameValueChartDivs = '';
            $numCustomGameValues = count($bigArray);
            $columnWidth = $numCustomGameValues > 1
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
                    'container_custom_player_value_' . $key,
                    "{$key}",
                    "{$numGames} players had this value"
                );

                if ($i == 1) {
                    $customGameValueChartDivs = '<div class="row">';
                } else if ($i % 2 != 0) {
                    $customGameValueChartDivs .= '</div><div class="row">';
                }
                $i++;

                $customGameValueChartDivs .= "<div class='col-md-{$columnWidth}'><div id='container_custom_player_value_{$key}'></div>$pieChart</div>";
            }

            if ($numCustomGameValues % 2 != 0) {
                $customGameValueChartDivs .= '</div>';
            }

            echo $customGameValueChartDivs;

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