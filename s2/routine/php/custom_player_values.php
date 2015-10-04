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
        's2_cron_active_mods',
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
    echo '<h2>Custom Player Values</h2>';

    $db->q('DROP TABLE IF EXISTS `cache_custom_player_values_temp2`;');

    $totalMatchesUsed = $totalCustomPlayerValuesUsed = $totalCustomPlayerValueCombos = 0;

    foreach ($activeMods as $key => $value) {
        $modID = $value['mod_id'];
        $modName = $value['mod_name'];

        $time_start1 = time();
        echo "<h3>$modName</h3>";

        $db->q('DROP TABLE IF EXISTS `cache_custom_player_values_temp0`;');
        $db->q('DROP TABLE IF EXISTS `cache_custom_player_values_temp1`;');

        $db->q("CREATE TABLE IF NOT EXISTS `cache_custom_player_values` (
            `modID` bigint(255) NOT NULL,
            `fieldOrder` tinyint(1) NOT NULL,
            `fieldValue` varchar(100) NOT NULL,
            `numGames` bigint(255) NOT NULL,
            `numWins` bigint(255) NOT NULL,
            PRIMARY KEY (`modID`, `fieldOrder`, `fieldValue`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

        $db->q("CREATE TABLE IF NOT EXISTS `cache_custom_player_values_temp0` (
            `modID` bigint(255) NOT NULL,
            `matchID` bigint(255) NOT NULL,
            PRIMARY KEY (`modID`, `matchID`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

        $db->q("CREATE TABLE IF NOT EXISTS `cache_custom_player_values_temp1` (
            `modID` int(255) NOT NULL,
            `fieldOrder` varchar(100) NOT NULL,
            `fieldValue` varchar(100) NOT NULL,
            `isWinner` tinyint(1) NOT NULL,
            KEY (`modID`, `fieldOrder`, `fieldValue`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

        $db->q("CREATE TABLE IF NOT EXISTS `cache_custom_player_values_temp2` (
            `modID` bigint(255) NOT NULL,
            `fieldOrder` varchar(100) NOT NULL,
            `fieldValue` varchar(100) NOT NULL,
            `numGames` bigint(255) NOT NULL,
            `numWins` bigint(255) NOT NULL,
            PRIMARY KEY (`modID`, `fieldOrder`, `fieldValue`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

        $matchesUsed = $db->q(
            'INSERT INTO `cache_custom_player_values_temp0`
                SELECT
                  DISTINCT `modID`,
                  `matchID`
                FROM `s2_match_players_custom`
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
                        MAX(`schemaID`) AS schemaID
                      FROM `s2_mod_custom_schema`
                      WHERE
                        `modID` = ? AND
                        `schemaApproved` = 1
                  );',
            'ss',
            array($modID, $modID)
        );

        $customPlayerValuesUsed = $db->q(
            'INSERT INTO `cache_custom_player_values_temp1`
                SELECT
                        s2mpc.`modID`,
                        s2mpc.`fieldOrder`,
                        s2mpc.`fieldValue`,
                        s2mp.`isWinner`
                    FROM `s2_match_players_custom` s2mpc
                    JOIN `s2_match_players` s2mp ON
                      s2mpc.`matchID` = s2mp.`matchID` AND
                      s2mpc.`round` = s2mp.`roundID` AND
                      s2mpc.`userID32` = s2mp.`steamID32`
                    WHERE s2mpc.`matchID` IN (
                      SELECT
                        `matchID`
                      FROM `cache_custom_player_values_temp0`
                      WHERE `modID` = ?
                    );',
            's',
            $modID
        );

        $customPlayerValueCombos = $db->q(
            'INSERT INTO `cache_custom_player_values_temp2`
                SELECT
                        s2mc.`modID`,
                        s2mc.`fieldOrder`,
                        s2mc.`fieldValue`,
                        COUNT(*) AS numGames,
                        SUM(`isWinner`) AS numWins
                    FROM `cache_custom_player_values_temp1` s2mc
                    GROUP BY s2mc.`modID`, s2mc.`fieldOrder`, s2mc.`fieldValue`;'
        );

        $time_end1 = time();

        $totalMatchesUsed += $matchesUsed = is_numeric($matchesUsed)
            ? $matchesUsed
            : 0;

        $totalCustomPlayerValuesUsed += $customPlayerValuesUsed = is_numeric($customPlayerValuesUsed)
            ? $customPlayerValuesUsed
            : 0;

        $totalCustomPlayerValueCombos += $customPlayerValueCombos = is_numeric($customPlayerValueCombos)
            ? $customPlayerValueCombos
            : 0;

        echo "<strong>Results:</strong> Player Values: $customPlayerValuesUsed || Player Value Combos: $customPlayerValueCombos || Matches: $matchesUsed<br />";

        echo '<strong>Run Time:</strong> ' . ($time_end1 - $time_start1) . " seconds";

        echo '<hr />';
    }

    $time_start1 = time();
    echo "<h3>Final Insert</h3>";

    $db->q('DROP TABLE IF EXISTS `cache_custom_player_values_temp0`;');
    $db->q('DROP TABLE IF EXISTS `cache_custom_player_values_temp1`;');
    $db->q('TRUNCATE `cache_custom_player_values`;');

    $flagCombinations = $db->q(
        'INSERT INTO `cache_custom_player_values`
            SELECT
              `modID`,
              `fieldOrder`,
              `fieldValue`,
              `numGames`,
              `numWins`
            FROM `cache_custom_player_values_temp2`;'
    );

    $db->q('DROP TABLE IF EXISTS `cache_custom_player_values_temp2`;');

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
                '[CUSTOM PLAYER VALUES]',
                $irc_message->colour_generator(NULL),
            ),
            array(
                $irc_message->colour_generator('bold'),
                $irc_message->colour_generator('blue'),
                'Processed:',
                $irc_message->colour_generator(NULL),
                $irc_message->colour_generator('bold'),
            ),
            array($totalCustomPlayerValuesUsed . ' Player values ||'),
            array($totalCustomPlayerValueCombos . ' Player value combos ||'),
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