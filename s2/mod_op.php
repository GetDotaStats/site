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
    //OP Combos
    //////////////////
    {
        try {
            echo '<h3>OP Custom Player Values</h3>';

            echo '<p>Breakdown of top 30 custom player value combos, per player value, sorted by winrate for all games played in the last week. Calculated twice a day.</p>';

            $schemaIDtoUse = $db->q(
                'SELECT
                        MAX(`schemaID`) as schemaID
                    FROM `s2_mod_custom_schema`
                    WHERE `modID` = ? AND `schemaApproved` = 1;',
                'i',
                $modID
            );

            if (empty($schemaIDtoUse) || empty($schemaIDtoUse[0]['schemaID'])) {
                throw new Exception('No approved schema to use!');
            } else {
                $schemaIDtoUse = $schemaIDtoUse[0]['schemaID'];
            }

            $customFields = cached_query(
                's2_mod_page_op_combos_schema_fields' . $modID,
                'SELECT
                        `fieldOrder`,
                        `customValueDisplay`
                    FROM `s2_mod_custom_schema_fields`
                    WHERE `schemaID` = ? AND `fieldType` = 2;',
                'i',
                $schemaIDtoUse
            );

            if (empty($schemaIDtoUse)) throw new Exception('No schema fields to use!');

            foreach ($customFields as $key => $value) {
                $fieldID = $value['fieldOrder'];
                $fieldName = $value['customValueDisplay'];

                $customPlayerValues = cached_query(
                    's2_mod_page_op_combos_values' . $modID . '_' . $fieldID,
                    'SELECT
                          ccpv.`modID`,
                          ccpv.`fieldOrder`,
                          ccpv.`fieldValue`,
                          ccpv.`numGames`,
                          ccpv.`numWins`,
                          (ccpv.`numWins` / ccpv.`numGames`) AS winrate
                        FROM `cache_custom_player_values` ccpv
                        WHERE ccpv.`modID` = ? AND ccpv.`fieldOrder` = ?
                        ORDER BY winrate DESC, ccpv.`numGames` DESC
                        LIMIT 0,30;',
                    'ii',
                    array($modID, $fieldID),
                    1
                );

                echo "<h3>$fieldName</h3>";

                if (empty($customPlayerValues)) throw new Exception('No custom player values recorded for this mod!');

                echo '<div class="row">
                            <div class="col-md-9"><strong>Value</strong></div>
                            <div class="col-md-1"><strong>Winrate</strong></div>
                            <div class="col-md-1"><strong>Wins</strong></div>
                            <div class="col-md-1"><strong>Players</strong></div>
                        </div>';

                foreach ($customPlayerValues as $key2 => $value2) {
                    if ($value2['fieldValue'] == '-1') continue;

                    $fieldValue = $value2['fieldValue'];
                    $winrate = number_format($value2['winrate'] * 100, 1);
                    $numWins = number_format($value2['numWins']);
                    $numGames = number_format($value2['numGames']);

                    echo "<div class='row'>
                            <div class='col-md-9'><div>{$fieldValue}</div></div>
                            <div class='col-md-1 text-right'>{$winrate}%</div>
                            <div class='col-md-1 text-right'>{$numWins}</div>
                            <div class='col-md-1 text-right'>{$numGames}</div>
                        </div>";
                }
            }
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