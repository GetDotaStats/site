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
        's2_cron_flags_active_mods',
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
    echo '<h2>Mod Flags</h2>';

    $db->q('DROP TABLE IF EXISTS `cache_custom_flags_temp0`;');

    $totalMatchesUsed = $totalFlagsUsed = $totalFlagCombos = 0;

    foreach ($activeMods as $key => $value) {
        $modID = $value['mod_id'];
        $modName = $value['mod_name'];

        $time_start1 = time();
        echo "<h3>$modName</h3>";

        $db->q('DROP TABLE IF EXISTS `cache_custom_flags_temp1`;');
        $db->q('DROP TABLE IF EXISTS `cache_custom_flags_temp2`;');

        $db->q("CREATE TABLE IF NOT EXISTS `cache_custom_flags` (
            `modID` bigint(255) NOT NULL,
            `flagName` varchar(100) NOT NULL,
            `flagValue` varchar(100) NOT NULL,
            `numGames` bigint(255) NOT NULL,
            PRIMARY KEY (`modID`, `flagName`, `flagValue`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

        $db->q("CREATE TABLE IF NOT EXISTS `cache_custom_flags_temp0` (
            `modID` bigint(255) NOT NULL,
            `flagName` varchar(100) NOT NULL,
            `flagValue` varchar(100) NOT NULL,
            `numGames` bigint(255) NOT NULL,
            PRIMARY KEY (`modID`, `flagName`, `flagValue`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

        $db->q("CREATE TABLE IF NOT EXISTS `cache_custom_flags_temp1` (
            `modID` bigint(255) NOT NULL,
            `matchID` bigint(255) NOT NULL,
            PRIMARY KEY (`modID`, `matchID`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

        $db->q("CREATE TABLE IF NOT EXISTS `cache_custom_flags_temp2` (
            `modID` int(255) NOT NULL,
            `flagName` varchar(100) NOT NULL,
            `flagValue` varchar(100) NOT NULL,
            KEY (`modID`, `flagName`, `flagValue`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

        $matchesUsed = $db->q(
            'INSERT INTO `cache_custom_flags_temp1`
                SELECT
                  `modID`,
                  `matchID`
                FROM `s2_match`
                WHERE `modID` = ? AND `dateRecorded` >= NOW() - INTERVAL 7 DAY;',
            's',
            $modID
        );

        $flagsUsed = $db->q(
            'INSERT INTO `cache_custom_flags_temp2`
                SELECT
                        s2mf.`modID`,
                        s2mf.`flagName`,
                        s2mf.`flagValue`
                    FROM `s2_match_flags` s2mf
                    WHERE s2mf.`matchID` IN (
                      SELECT
                        `matchID`
                      FROM `cache_custom_flags_temp1`
                      WHERE `modID` = ?
                    );',
            's',
            $modID
        );

        $flagValueCombinations = $db->q(
            'INSERT INTO `cache_custom_flags_temp0`
                SELECT
                        s2mf.`modID`,
                        s2mf.`flagName`,
                        s2mf.`flagValue`,
                        COUNT(*) AS numGames
                    FROM `cache_custom_flags_temp2` s2mf
                    GROUP BY s2mf.`modID`, s2mf.`flagName`, s2mf.`flagValue`;'
        );

        $time_end1 = time();

        $totalMatchesUsed += $matchesUsed = is_numeric($matchesUsed)
            ? $matchesUsed
            : 0;

        $totalFlagsUsed += $flagsUsed = is_numeric($flagsUsed)
            ? $flagsUsed
            : 0;

        $totalFlagCombos += $flagValueCombinations = is_numeric($flagValueCombinations)
            ? $flagValueCombinations
            : 0;

        echo "<strong>Results:</strong> Flags: $flagsUsed || Flag Combos: $flagValueCombinations || Matches: $matchesUsed<br />";

        echo '<strong>Run Time:</strong> ' . ($time_end1 - $time_start1) . " seconds";

        echo '<hr />';
    }

    $time_start1 = time();
    echo "<h3>Final Insert</h3>";

    $db->q('DROP TABLE IF EXISTS `cache_custom_flags_temp1`;');
    $db->q('DROP TABLE IF EXISTS `cache_custom_flags_temp2`;');
    $db->q('TRUNCATE `cache_custom_flags`;');

    $flagCombinations = $db->q(
        'INSERT INTO `cache_custom_flags`
            SELECT
              `modID`,
              `flagName`,
              `flagValue`,
              `numGames`
            FROM `cache_custom_flags_temp0`;'
    );

    $db->q('DROP TABLE IF EXISTS `cache_custom_flags_temp0`;');

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
                '[FLAGS]',
                $irc_message->colour_generator(NULL),
            ),
            array(
                $irc_message->colour_generator('bold'),
                $irc_message->colour_generator('blue'),
                'Processed:',
                $irc_message->colour_generator(NULL),
                $irc_message->colour_generator('bold'),
            ),
            array($totalFlagsUsed . ' flags ||'),
            array($totalFlagCombos . ' combos ||'),
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