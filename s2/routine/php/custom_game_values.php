#!/usr/bin/php -q
<?php
require_once('../../../global_functions.php');
require_once('../../../connections/parameters.php');

try {
    $db = new dbWrapper_v3($hostname_gds_cron, $username_gds_cron, $password_gds_cron, $database_gds_cron, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    $activeMods = cached_query(
        's2_cron_custom_game_values_active_mods',
        'SELECT
              ml.`mod_id`,
              ml.`mod_identifier`,
              ml.`mod_name`,
              ml.`mod_steam_group`,
              ml.`mod_workshop_link`,
              ml.`mod_size`,
              ml.`workshop_updated`,
              ml.`date_recorded`
            FROM `mod_list` ml
            WHERE ml.`mod_active` = 1;',
        NULL,
        NULL,
        5
    );

    if (empty($activeMods)) throw new Exception('No active mods!');

    $time_start1 = $time_start2 = time();
    echo '<h2>Custom Game Values</h2>';

    $db->q('DROP TABLE IF EXISTS `cache_custom_game_values_temp2`;');

    $totalMatchesUsed = $totalCustomGameValuesUsed = $totalCustomGameValueCombos = 0;

    foreach ($activeMods as $key => $value) {
        $modID = $value['mod_id'];
        $modName = $value['mod_name'];

        $time_start1 = time();
        echo "<h3>$modName</h3>";

        $db->q('DROP TABLE IF EXISTS `cache_custom_game_values_temp0`;');
        $db->q('DROP TABLE IF EXISTS `cache_custom_game_values_temp1`;');

        $db->q("CREATE TABLE IF NOT EXISTS `cache_custom_game_values` (
            `modID` bigint(255) NOT NULL,
            `fieldOrder` tinyint(1) NOT NULL,
            `fieldValue` varchar(100) NOT NULL,
            `numGames` bigint(255) NOT NULL,
            PRIMARY KEY (`modID`, `fieldOrder`, `fieldValue`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

        $db->q("CREATE TABLE IF NOT EXISTS `cache_custom_game_values_temp0` (
            `modID` bigint(255) NOT NULL,
            `matchID` bigint(255) NOT NULL,
            PRIMARY KEY (`modID`, `matchID`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

        $db->q("CREATE TABLE IF NOT EXISTS `cache_custom_game_values_temp1` (
            `modID` int(255) NOT NULL,
            `fieldOrder` varchar(100) NOT NULL,
            `fieldValue` varchar(100) NOT NULL,
            KEY (`modID`, `fieldOrder`, `fieldValue`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

        $db->q("CREATE TABLE IF NOT EXISTS `cache_custom_game_values_temp2` (
            `modID` bigint(255) NOT NULL,
            `fieldOrder` varchar(100) NOT NULL,
            `fieldValue` varchar(100) NOT NULL,
            `numGames` bigint(255) NOT NULL,
            PRIMARY KEY (`modID`, `fieldOrder`, `fieldValue`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

        $matchesUsed = $db->q(
            'INSERT INTO `cache_custom_game_values_temp0`
                SELECT
                  DISTINCT `modID`,
                  `matchID`
                FROM `s2_match_custom`
                WHERE
                  `matchID` IN (
                    SELECT
                        `matchID`
                      FROM `s2_match`
                      WHERE
                        `modID` = ? AND
                        `dateRecorded` >= NOW() - INTERVAL 7 DAY
                  ) AND
                  `schemaID` IN (
                    SELECT
                        `schemaID`
                      FROM `s2_mod_custom_schema`
                      WHERE
                        `modID` = ? AND
                        `schemaApproved` = 1
                  );',
            'ss',
            array($modID, $modID)
        );

        $customGameValuesUsed = $db->q(
            'INSERT INTO `cache_custom_game_values_temp1`
                SELECT
                        s2mc.`modID`,
                        s2mc.`fieldOrder`,
                        s2mc.`fieldValue`
                    FROM `s2_match_custom` s2mc
                    WHERE s2mc.`matchID` IN (
                      SELECT
                        `matchID`
                      FROM `cache_custom_game_values_temp0`
                      WHERE `modID` = ?
                    );',
            's',
            $modID
        );

        $customGameValueCombos = $db->q(
            'INSERT INTO `cache_custom_game_values_temp2`
                SELECT
                        s2mc.`modID`,
                        s2mc.`fieldOrder`,
                        s2mc.`fieldValue`,
                        COUNT(*) AS numGames
                    FROM `cache_custom_game_values_temp1` s2mc
                    GROUP BY s2mc.`modID`, s2mc.`fieldOrder`, s2mc.`fieldValue`;'
        );

        $time_end1 = time();

        $totalMatchesUsed += $matchesUsed = is_numeric($matchesUsed)
            ? $matchesUsed
            : 0;

        $totalCustomGameValuesUsed += $customGameValuesUsed = is_numeric($customGameValuesUsed)
            ? $customGameValuesUsed
            : 0;

        $totalCustomGameValueCombos += $customGameValueCombos = is_numeric($customGameValueCombos)
            ? $customGameValueCombos
            : 0;

        echo "<strong>Results:</strong> Game Values: $customGameValuesUsed || Game Value Combos: $customGameValueCombos || Matches: $matchesUsed<br />";

        echo '<strong>Run Time:</strong> ' . ($time_end1 - $time_start1) . " seconds";

        echo '<hr />';
    }

    $time_start1 = time();
    echo "<h3>Final Insert</h3>";

    $db->q('DROP TABLE IF EXISTS `cache_custom_game_values_temp0`;');
    $db->q('DROP TABLE IF EXISTS `cache_custom_game_values_temp1`;');
    $db->q('TRUNCATE `cache_custom_game_values`;');

    $flagCombinations = $db->q(
        'INSERT INTO `cache_custom_game_values`
            SELECT
              `modID`,
              `fieldOrder`,
              `fieldValue`,
              `numGames`
            FROM `cache_custom_game_values_temp2`;'
    );

    $db->q('DROP TABLE IF EXISTS `cache_custom_game_values_temp2`;');

    $time_end1 = time();
    echo '<strong>Run Time:</strong> ' . ($time_end1 - $time_start1) . " seconds";

    echo '<hr />';

    $time_end2 = time();
    echo '<strong>Total Run Time:</strong> ' . ($time_end2 - $time_start2) . " seconds<br /><br />";

    //WEBHOOK
    {
        $irc_message = new irc_message($webhook_gds_site_admin);

        $message = array(
            array(
                $irc_message->colour_generator('red'),
                '[CRON]',
                $irc_message->colour_generator(NULL),
            ),
            array(
                $irc_message->colour_generator('green'),
                '[CUSTOM GAME VALUES]',
                $irc_message->colour_generator(NULL),
            ),
            array(
                $irc_message->colour_generator('bold'),
                $irc_message->colour_generator('blue'),
                'Processed:',
                $irc_message->colour_generator(NULL),
                $irc_message->colour_generator('bold'),
            ),
            array($totalCustomGameValuesUsed . ' game values ||'),
            array($totalCustomGameValueCombos . ' game value combos ||'),
            array($totalMatchesUsed . ' matches ||'),
            array('http://getdotastats.com/s2/routine/log_hourly.html?' . time())
        );

        $message = $irc_message->combine_message($message);
        $irc_message->post_message($message, array('localDev' => $localDev));
    }

} catch (Exception $e) {
    echo 'Caught Exception (MAIN) -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage() . '<br /><br />';
} finally {
    if (isset($memcache)) $memcache->close();
}