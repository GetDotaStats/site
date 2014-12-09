<?php
require_once('../global_functions.php');
require_once('../connections/parameters.php');

try {
    if (!isset($_SESSION)) {
        session_start();
    }

    checkLogin_v2();

    echo '
        <head>
            <link href="//getdotastats.com/bootstrap/css/bootstrap.min.css" rel="stylesheet">
            <link href="//getdotastats.com/getdotastats.css?10" rel="stylesheet">
        </head>
    ';

    if (!function_exists('json_error_inspector')) {
        function json_error_inspector()
        {
            switch (json_last_error()) {
                case JSON_ERROR_NONE:
                    $json_error = 'No errors';
                    break;
                case JSON_ERROR_DEPTH:
                    $json_error = 'Maximum stack depth exceeded';
                    break;
                case JSON_ERROR_STATE_MISMATCH:
                    $json_error = 'Underflow or the modes mismatch';
                    break;
                case JSON_ERROR_CTRL_CHAR:
                    $json_error = 'Unexpected control character found';
                    break;
                case JSON_ERROR_SYNTAX:
                    $json_error = 'Syntax error, malformed JSON';
                    break;
                case JSON_ERROR_UTF8:
                    $json_error = 'Malformed UTF-8 characters, possibly incorrectly encoded';
                    break;
                default:
                    $json_error = 'Unknown error';
                    break;
            }

            return $json_error;
        }
    }

    if (!empty($_SESSION['user_id64'])) {
        $db = new dbWrapper_v2($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site);
        if ($db) {
            $messages = $db->q('SELECT * FROM `node_listener` ORDER BY test_id DESC LIMIT 0,10;');

            foreach ($messages as $key => $value) {

                ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                //echo '<hr />';

                echo $value['test_id'];

                $parsed = json_decode(utf8_encode($value['message']), 1);

                /////////////////////////////////////////////////////////////////////////////////////////

                //echo $parsed['matchID'] . ' || ' . $parsed['modID'] . ' || ' . $parsed['duration'] . '||' . $value['date_recorded'] . '<br />';
                $db->q(
                    'INSERT INTO `node_listener`
                        (
                            `test_id`,
                            `mod_id`,
                            `match_id`
                        )
                        VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE
                            `match_id` = VALUES(`match_id`);',
                    'iss',
                    $value['test_id'], $parsed['modID'], $parsed['matchID']
                );


                /////////////////////////////////////////////////////////////////////////////////////////

                if (!empty($parsed['matchID'])) {
                    if (isset($parsed['rounds']['players'])) {
                        $numPlayers = !empty($parsed['rounds']['players'])
                            ? count($parsed['rounds']['players'])
                            : 0;
                    } else if (isset($parsed['rounds'][0]['players'])) {
                        $numPlayers = !empty($parsed['rounds'][0]['players'])
                            ? count($parsed['rounds'][0]['players'])
                            : 0;
                    } else {
                        $numPlayers = !empty($parsed['rounds'][0]['players'])
                            ? count($parsed['rounds'][0]['players'])
                            : NULL;
                    }

                    $winningTeam = !empty($parsed['winner'])
                        ? $parsed['winner']
                        : 0;

                    $matchDuration = !empty($parsed['duration'])
                        ? round($parsed['duration'], 2)
                        : 0;

                    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                    //echo '[match] ' . $parsed['matchID'] . ' || ' . number_format($matchDuration / 60, 2) . ' mins || ' . $numPlayers . '<br />';

                    $db->q(
                        'INSERT INTO `mod_match_overview` (`match_id`, `mod_id`, `message_id`, `match_duration`, `match_num_players`, `match_winning_team`, `match_recorded`)
                            VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE
                                `mod_id` = VALUES(`mod_id`),
                                `message_id` = VALUES(`message_id`),
                                `match_duration` = VALUES(`match_duration`),
                                `match_winning_team` = VALUES(`match_winning_team`),
                                `match_num_players` = VALUES(`match_num_players`),
                                `match_recorded` = VALUES(`match_recorded`);',
                        'ssisiis',
                        $parsed['matchID'],
                        $parsed['modID'],
                        $value['test_id'],
                        $matchDuration,
                        $numPlayers,
                        $winningTeam,
                        $value['date_recorded']
                    );

                    /////////////////////////////////////////////////////////////////////////////////////////

                    $matchID = $parsed['matchID'];
                    $modID = $parsed['modID'];

                    if (!empty($parsed['rounds'])) {
                        foreach ($parsed['rounds'] as $key2 => $value2) {

                            /*echo '<pre>';
                            print_r($value2);
                            echo '</pre>';
                            exit();*/

                            if ($key2 === 'players') {
                                //do legacy parsing

                                foreach ($value2 AS $key3 => $value3) {
                                    $player_sid32 = !empty($value3['steamID32'])
                                        ? $value3['steamID32']
                                        : 0;

                                    $isBot = $value3['steamID32'] == 0 && empty($value3['playerName'])
                                        ? 1
                                        : 0;

                                    $player_connection_status = 0;
                                    if (!empty($value3['connectionStatus'])) {
                                        $player_connection_status = $value3['connectionStatus'];
                                    } else if (!empty($value3['leaverStatus'])) {
                                        $player_connection_status = $value3['leaverStatus'];
                                    }

                                    $player_name = !empty($value3['playerName'])
                                        ? $value3['playerName']
                                        : 'N/A';

                                    $player_teamID = !empty($value3['teamID'])
                                        ? $value3['teamID']
                                        : 0;

                                    $player_roundID = 0;

                                    $player_slotID = !empty($value3['slotID'])
                                        ? $value3['slotID']
                                        : 0;

                                    $player_leaver_status = !empty($value3['leaverStatus'])
                                        ? $value3['leaverStatus']
                                        : 0;

                                    $player_won = $player_teamID == $winningTeam
                                        ? 1
                                        : 0;

                                    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                                    //echo '[LEGACY](player) ' . $matchID . ' || ' . $player_sid32 . '<br />';

                                    $db->q(
                                        'INSERT INTO `mod_match_players`
                                              (
                                                  `match_id`,
                                                  `mod_id`,
                                                  `player_sid32`,
                                                  `isBot`,
                                                  `connection_status`,
                                                  `player_won`,
                                                  `player_name`,
                                                  `player_round_id`,
                                                  `player_team_id`,
                                                  `player_slot_id`,
                                                  `date_recorded`
                                              )
                                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE
                                                `isBot` = VALUES(`isBot`),
                                                `connection_status` = VALUES(`connection_status`),
                                                `player_won` = VALUES(`player_won`),
                                                `player_name` = VALUES(`player_name`),
                                                `player_team_id` = VALUES(`player_team_id`),
                                                `player_slot_id` = VALUES(`player_slot_id`),
                                                `date_recorded` = VALUES(`date_recorded`);',
                                        'ssiiiisiiis',
                                        $matchID,
                                        $modID,
                                        $player_sid32,
                                        $isBot,
                                        $player_connection_status,
                                        $player_won,
                                        $player_name,
                                        $player_roundID,
                                        $player_teamID,
                                        $player_slotID,
                                        $value['date_recorded']
                                    );

                                    $db->q(
                                        'INSERT INTO `mod_match_players_names`
                                              (`player_sid32`,
                                              `player_name`,
                                              `date_recorded`)
                                            VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE
                                                `player_name` = VALUES(`player_name`),
                                                `date_recorded` = VALUES(`date_recorded`);',
                                        'iss',
                                        $player_sid32,
                                        $player_name,
                                        $value['date_recorded']
                                    );

                                    if (!empty($value3['hero'])) {
                                        ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                                        //echo ' <strong>HERO</strong>';

                                        $hero_heroID = !empty($value3['hero']['heroID'])
                                            ? $value3['hero']['heroID']
                                            : 0;

                                        $hero_won = $player_teamID == $winningTeam
                                            ? 1
                                            : 0;

                                        $hero_level = !empty($value3['hero']['level'])
                                            ? $value3['hero']['level']
                                            : 0;

                                        $hero_kills = !empty($value3['hero']['kills'])
                                            ? $value3['hero']['kills']
                                            : 0;

                                        $hero_assists = !empty($value3['hero']['assists'])
                                            ? $value3['hero']['assists']
                                            : 0;

                                        $hero_deaths = !empty($value3['hero']['deaths'])
                                            ? $value3['hero']['deaths']
                                            : 0;

                                        $hero_gold = !empty($value3['hero']['gold'])
                                            ? $value3['hero']['gold']
                                            : 0;

                                        $hero_lastHits = !empty($value3['hero']['lastHits'])
                                            ? $value3['hero']['lastHits']
                                            : 0;

                                        $hero_denies = !empty($value3['hero']['denies'])
                                            ? $value3['hero']['denies']
                                            : 0;

                                        $goldSpentBuyBack = !empty($value3['hero']['goldSpentBuyBack'])
                                            ? $value3['hero']['goldSpentBuyBack']
                                            : 0;

                                        $goldSpentConsumables = !empty($value3['hero']['goldSpentConsumables'])
                                            ? $value3['hero']['goldSpentConsumables']
                                            : 0;

                                        $goldSpentItems = !empty($value3['hero']['goldSpentItems'])
                                            ? $value3['hero']['goldSpentItems']
                                            : 0;

                                        $goldSpentSupport = !empty($value3['hero']['goldSpentSupport'])
                                            ? $value3['hero']['goldSpentSupport']
                                            : 0;

                                        $numPurchasedConsumables = !empty($value3['hero']['numPurchasedConsumables'])
                                            ? $value3['hero']['numPurchasedConsumables']
                                            : 0;

                                        $numPurchasedItems = !empty($value3['hero']['numPurchasedItems'])
                                            ? $value3['hero']['numPurchasedItems']
                                            : 0;

                                        $stunAmount = !empty($value3['hero']['stunAmount'])
                                            ? $value3['hero']['stunAmount']
                                            : 0;

                                        $totalEarnedGold = !empty($value3['hero']['totalEarnedGold'])
                                            ? $value3['hero']['totalEarnedGold']
                                            : 0;

                                        $totalEarnedXP = !empty($value3['hero']['totalEarnedXP'])
                                            ? $value3['hero']['totalEarnedXP']
                                            : 0;

                                        $db->q(
                                            'INSERT INTO `mod_match_heroes`
                                                  (
                                                      `match_id`,
                                                      `mod_id`,
                                                      `player_round_id`,
                                                      `player_team_id`,
                                                      `player_slot_id`,
                                                      `player_sid32`,
                                                      `hero_id`,
                                                      `hero_won`,
                                                      `hero_level`,
                                                      `hero_kills`,
                                                      `hero_deaths`,
                                                      `hero_assists`,
                                                      `hero_gold`,
                                                      `hero_lasthits`,
                                                      `hero_denies`,
                                                      `hero_gold_spent_buyback`,
                                                      `hero_gold_spent_consumables`,
                                                      `hero_gold_spent_items`,
                                                      `hero_gold_spent_support`,
                                                      `hero_num_purchased_consumables`,
                                                      `hero_num_purchased_items`,
                                                      `hero_stun_amount`,
                                                      `hero_total_earned_gold`,
                                                      `hero_total_earned_xp`,
                                                      `date_recorded`
                                                  )
                                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE
                                                    `player_sid32` = VALUES(`player_sid32`),
                                                    `player_round_id` = VALUES(`player_round_id`),
                                                    `player_team_id` = VALUES(`player_team_id`),
                                                    `player_slot_id` = VALUES(`player_slot_id`),
                                                    `hero_id` = VALUES(`hero_id`),
                                                    `hero_won` = VALUES(`hero_won`),
                                                    `hero_level` = VALUES(`hero_level`),
                                                    `hero_kills` = VALUES(`hero_kills`),
                                                    `hero_deaths` = VALUES(`hero_deaths`),
                                                    `hero_assists` = VALUES(`hero_assists`),
                                                    `hero_gold` = VALUES(`hero_gold`),
                                                    `hero_lasthits` = VALUES(`hero_lasthits`),
                                                    `hero_denies` = VALUES(`hero_denies`),
                                                    `hero_gold_spent_buyback` = VALUES(`hero_gold_spent_buyback`),
                                                    `hero_gold_spent_consumables` = VALUES(`hero_gold_spent_consumables`),
                                                    `hero_gold_spent_items` = VALUES(`hero_gold_spent_items`),
                                                    `hero_gold_spent_support` = VALUES(`hero_gold_spent_support`),
                                                    `hero_num_purchased_consumables` = VALUES(`hero_num_purchased_consumables`),
                                                    `hero_num_purchased_items` = VALUES(`hero_num_purchased_items`),
                                                    `hero_stun_amount` = VALUES(`hero_stun_amount`),
                                                    `hero_total_earned_gold` = VALUES(`hero_total_earned_gold`),
                                                    `hero_total_earned_xp` = VALUES(`hero_total_earned_xp`),
                                                    `date_recorded` = VALUES(`date_recorded`);',
                                            'ssiiiiiiiiiiiiiiiiiiisiis',
                                            $matchID,
                                            $modID,
                                            $player_roundID,
                                            $player_teamID,
                                            $player_slotID,
                                            $player_sid32,
                                            $hero_heroID,
                                            $hero_won,
                                            $hero_level,
                                            $hero_kills,
                                            $hero_deaths,
                                            $hero_assists,
                                            $hero_gold,
                                            $hero_lastHits,
                                            $hero_denies,
                                            $goldSpentBuyBack,
                                            $goldSpentConsumables,
                                            $goldSpentItems,
                                            $goldSpentSupport,
                                            $numPurchasedConsumables,
                                            $numPurchasedItems,
                                            $stunAmount,
                                            $totalEarnedGold,
                                            $totalEarnedXP,
                                            $value['date_recorded']
                                        );

                                        ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                                        //echo '<br />';
                                    } else {
                                        //echo '<strong>NO HERO DATA!!</strong> player: ' . $player_sid32 . '<br />';
                                    }

                                    ///////////////////////////////////
                                    //ITEM DATA
                                    ///////////////////////////////////
                                    if (!empty($value3['items'])) {
                                        foreach ($value3['items'] as $key_items => $value_items) {
                                            $item_index = !empty($value_items['index'])
                                                ? $value_items['index']
                                                : 0;

                                            $item_name = !empty($value_items['itemName'])
                                                ? $value_items['itemName']
                                                : NULL;

                                            $item_start_time = !empty($value_items['itemStartTime'])
                                                ? $value_items['itemStartTime']
                                                : NULL;

                                            $db->q(
                                                'INSERT INTO `mod_match_items`
                                                      (
                                                          `match_id`,
                                                          `mod_id`,
                                                          `player_round_id`,
                                                          `player_team_id`,
                                                          `player_slot_id`,
                                                          `player_sid32`,
                                                          `item_index`,
                                                          `item_name`,
                                                          `item_start_time`,
                                                          `date_recorded`
                                                      )
                                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE
                                                        `player_sid32` = VALUES(`player_sid32`),
                                                        `item_index` = VALUES(`item_index`),
                                                        `item_name` = VALUES(`item_name`),
                                                        `item_start_time` = VALUES(`item_start_time`),
                                                        `date_recorded` = VALUES(`date_recorded`);',
                                                'ssiiisisss',
                                                $matchID,
                                                $modID,
                                                $player_roundID,
                                                $player_teamID,
                                                $player_slotID,
                                                $player_sid32,
                                                $item_index,
                                                $item_name,
                                                $item_start_time,
                                                $value['date_recorded']
                                            );
                                        }
                                    } else {
                                        //echo '<strong>NO ITEM DATA!!</strong> player: ' . $player_sid32 . '<br />';
                                    }

                                    ///////////////////////////////////
                                    //ABILITY DATA
                                    ///////////////////////////////////
                                    if (!empty($value3['abilities'])) {
                                        foreach ($value3['abilities'] as $key_abilities => $value_abilities) {
                                            $ability_index = !empty($value_abilities['index'])
                                                ? $value_abilities['index']
                                                : 0;

                                            $ability_name = !empty($value_abilities['abilityName'])
                                                ? $value_abilities['abilityName']
                                                : NULL;

                                            $ability_level = !empty($value_abilities['level'])
                                                ? $value_abilities['level']
                                                : 0;

                                            $db->q(
                                                'INSERT INTO `mod_match_abilities`
                                                      (
                                                          `match_id`,
                                                          `mod_id`,
                                                          `player_round_id`,
                                                          `player_team_id`,
                                                          `player_slot_id`,
                                                          `player_sid32`,
                                                          `ability_index`,
                                                          `ability_name`,
                                                          `ability_level`,
                                                          `date_recorded`
                                                      )
                                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE
                                                        `player_sid32` = VALUES(`player_sid32`),
                                                        `ability_index` = VALUES(`ability_index`),
                                                        `ability_name` = VALUES(`ability_name`),
                                                        `ability_level` = VALUES(`ability_level`),
                                                        `date_recorded` = VALUES(`date_recorded`);',
                                                'ssiiisisis',
                                                $matchID,
                                                $modID,
                                                $player_roundID,
                                                $player_teamID,
                                                $player_slotID,
                                                $player_sid32,
                                                $ability_index,
                                                $ability_name,
                                                $ability_level,
                                                $value['date_recorded']
                                            );
                                        }
                                    } else {
                                        //echo '<strong>NO ITEM DATA!!</strong> player: ' . $player_sid32 . '<br />';
                                    }
                                }
                            } else if (is_numeric($key2)) {
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                                //do round parsing style

                                //echo '<br />';

                                echo "<strong>*</strong>";

                                foreach ($value2['players'] AS $key3 => $value3) {
                                    $player_sid32 = !empty($value3['steamID32'])
                                        ? $value3['steamID32']
                                        : 0;

                                    $isBot = $value3['steamID32'] == 0 && empty($value3['playerName'])
                                        ? 1
                                        : 0;

                                    $player_name = !empty($value3['playerName'])
                                        ? $value3['playerName']
                                        : 'N/A';

                                    $player_teamID = !empty($value3['teamID'])
                                        ? $value3['teamID']
                                        : 0;

                                    $player_roundID = $key2;

                                    $player_slotID = !empty($value3['slotID'])
                                        ? $value3['slotID']
                                        : 0;

                                    $player_connection_status = 0;
                                    if (!empty($value3['connectionStatus'])) {
                                        $player_connection_status = $value3['connectionStatus'];
                                    } else if (!empty($value3['leaverStatus'])) {
                                        $player_connection_status = $value3['leaverStatus'];
                                    }

                                    $player_won = $player_teamID == $winningTeam
                                        ? 1
                                        : 0;

                                    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                                    //echo '[NEW](player) ' . $matchID . ' || ' . $player_sid32 . '<br />';

                                    $db->q(
                                        'INSERT INTO `mod_match_players`
                                              (
                                                  `match_id`,
                                                  `mod_id`,
                                                  `player_sid32`,
                                                  `isBot`,
                                                  `connection_status`,
                                                  `player_won`,
                                                  `player_name`,
                                                  `player_round_id`,
                                                  `player_team_id`,
                                                  `player_slot_id`,
                                                  `date_recorded`
                                              )
                                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE
                                                `isBot` = VALUES(`isBot`),
                                                `connection_status` = VALUES(`connection_status`),
                                                `player_won` = VALUES(`player_won`),
                                                `player_name` = VALUES(`player_name`),
                                                `player_team_id` = VALUES(`player_team_id`),
                                                `player_slot_id` = VALUES(`player_slot_id`),
                                                `date_recorded` = VALUES(`date_recorded`);',
                                        'ssiiiisiiis',
                                        $matchID,
                                        $modID,
                                        $player_sid32,
                                        $isBot,
                                        $player_connection_status,
                                        $player_won,
                                        $player_name,
                                        $player_roundID,
                                        $player_teamID,
                                        $player_slotID,
                                        $value['date_recorded']
                                    );

                                    $db->q(
                                        'INSERT INTO `mod_match_players_names`
                                              (`player_sid32`,
                                              `player_name`,
                                              `date_recorded`)
                                            VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE
                                                `player_name` = VALUES(`player_name`),
                                                `date_recorded` = VALUES(`date_recorded`);',
                                        'iss',
                                        $player_sid32,
                                        $player_name,
                                        $value['date_recorded']
                                    );

                                    if (!empty($value3['hero'])) {
                                        ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                                        //echo ' <strong>HERO</strong>';

                                        $hero_heroID = !empty($value3['hero']['heroID'])
                                            ? $value3['hero']['heroID']
                                            : 0;

                                        $hero_won = $player_teamID == $winningTeam
                                            ? 1
                                            : 0;

                                        $hero_level = !empty($value3['hero']['level'])
                                            ? $value3['hero']['level']
                                            : 0;

                                        $hero_kills = !empty($value3['hero']['kills'])
                                            ? $value3['hero']['kills']
                                            : 0;

                                        $hero_assists = !empty($value3['hero']['assists'])
                                            ? $value3['hero']['assists']
                                            : 0;

                                        $hero_deaths = !empty($value3['hero']['deaths'])
                                            ? $value3['hero']['deaths']
                                            : 0;

                                        $hero_gold = !empty($value3['hero']['gold'])
                                            ? $value3['hero']['gold']
                                            : 0;

                                        $hero_lastHits = !empty($value3['hero']['lastHits'])
                                            ? $value3['hero']['lastHits']
                                            : 0;

                                        $hero_denies = !empty($value3['hero']['denies'])
                                            ? $value3['hero']['denies']
                                            : 0;

                                        $goldSpentBuyBack = !empty($value3['hero']['goldSpentBuyBack'])
                                            ? $value3['hero']['goldSpentBuyBack']
                                            : 0;

                                        $goldSpentConsumables = !empty($value3['hero']['goldSpentConsumables'])
                                            ? $value3['hero']['goldSpentConsumables']
                                            : 0;

                                        $goldSpentItems = !empty($value3['hero']['goldSpentItems'])
                                            ? $value3['hero']['goldSpentItems']
                                            : 0;

                                        $goldSpentSupport = !empty($value3['hero']['goldSpentSupport'])
                                            ? $value3['hero']['goldSpentSupport']
                                            : 0;

                                        $numPurchasedConsumables = !empty($value3['hero']['numPurchasedConsumables'])
                                            ? $value3['hero']['numPurchasedConsumables']
                                            : 0;

                                        $numPurchasedItems = !empty($value3['hero']['numPurchasedItems'])
                                            ? $value3['hero']['numPurchasedItems']
                                            : 0;

                                        $stunAmount = !empty($value3['hero']['stunAmount'])
                                            ? $value3['hero']['stunAmount']
                                            : 0;

                                        $totalEarnedGold = !empty($value3['hero']['totalEarnedGold'])
                                            ? $value3['hero']['totalEarnedGold']
                                            : 0;

                                        $totalEarnedXP = !empty($value3['hero']['totalEarnedXP'])
                                            ? $value3['hero']['totalEarnedXP']
                                            : 0;

                                        $db->q(
                                            'INSERT INTO `mod_match_heroes`
                                                  (
                                                      `match_id`,
                                                      `mod_id`,
                                                      `player_round_id`,
                                                      `player_sid32`,
                                                      `hero_id`,
                                                      `hero_won`,
                                                      `hero_level`,
                                                      `hero_kills`,
                                                      `hero_deaths`,
                                                      `hero_assists`,
                                                      `hero_gold`,
                                                      `hero_lasthits`,
                                                      `hero_denies`,
                                                      `hero_gold_spent_buyback`,
                                                      `hero_gold_spent_consumables`,
                                                      `hero_gold_spent_items`,
                                                      `hero_gold_spent_support`,
                                                      `hero_num_purchased_consumables`,
                                                      `hero_num_purchased_items`,
                                                      `hero_stun_amount`,
                                                      `hero_total_earned_gold`,
                                                      `hero_total_earned_xp`,
                                                      `date_recorded`
                                                  )
                                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE
                                                    `player_sid32` = VALUES(`player_sid32`),
                                                    `player_round_id` = VALUES(`player_round_id`),
                                                    `hero_id` = VALUES(`hero_id`),
                                                    `hero_won` = VALUES(`hero_won`),
                                                    `hero_level` = VALUES(`hero_level`),
                                                    `hero_kills` = VALUES(`hero_kills`),
                                                    `hero_deaths` = VALUES(`hero_deaths`),
                                                    `hero_assists` = VALUES(`hero_assists`),
                                                    `hero_gold` = VALUES(`hero_gold`),
                                                    `hero_lasthits` = VALUES(`hero_lasthits`),
                                                    `hero_denies` = VALUES(`hero_denies`),
                                                    `hero_gold_spent_buyback` = VALUES(`hero_gold_spent_buyback`),
                                                    `hero_gold_spent_consumables` = VALUES(`hero_gold_spent_consumables`),
                                                    `hero_gold_spent_items` = VALUES(`hero_gold_spent_items`),
                                                    `hero_gold_spent_support` = VALUES(`hero_gold_spent_support`),
                                                    `hero_num_purchased_consumables` = VALUES(`hero_num_purchased_consumables`),
                                                    `hero_num_purchased_items` = VALUES(`hero_num_purchased_items`),
                                                    `hero_stun_amount` = VALUES(`hero_stun_amount`),
                                                    `hero_total_earned_gold` = VALUES(`hero_total_earned_gold`),
                                                    `hero_total_earned_xp` = VALUES(`hero_total_earned_xp`),
                                                    `date_recorded` = VALUES(`date_recorded`);',
                                            'ssiiiiiiiiiiiiiiiiisiis',
                                            $matchID,
                                            $modID,
                                            $player_roundID,
                                            $player_sid32,
                                            $hero_heroID,
                                            $hero_won,
                                            $hero_level,
                                            $hero_kills,
                                            $hero_deaths,
                                            $hero_assists,
                                            $hero_gold,
                                            $hero_lastHits,
                                            $hero_denies,
                                            $goldSpentBuyBack,
                                            $goldSpentConsumables,
                                            $goldSpentItems,
                                            $goldSpentSupport,
                                            $numPurchasedConsumables,
                                            $numPurchasedItems,
                                            $stunAmount,
                                            $totalEarnedGold,
                                            $totalEarnedXP,
                                            $value['date_recorded']
                                        );

                                        ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                                        //echo '<br />';
                                    } else {
                                        //echo '<strong>NO HERO DATA!!</strong> player: ' . $player_sid32 . '<br />';
                                    }
                                }
                            } else {
                                echo '<strong>NO PLAYER DATA!!</strong> match: ' . $matchID . ' || player: ' . $player_sid32 . '<br />';

                                echo '<pre>';
                                print_r($parsed);
                                echo '</pre>';
                                exit();
                            }
                        }
                    } else {
                        echo '<strong>NO ROUND DATA!!</strong> ' . $matchID . ' || ' . $modID . '<br />';

                        print_r($parsed);
                        exit();
                    }
                } else {
                    echo '<strong>NO MATCH ID!!</strong> Message ID: ' . $value['test_id'] . '<br />';

                    print_r($parsed);
                    exit();
                }

                echo ', ';

                flush();
            }
        } else {
            echo bootstrapMessage('Oh Snap', 'No DB!');
        }
    } else {
        echo bootstrapMessage('Oh Snap', 'Not logged in!');
        echo '<a href="../">Go back to main site</a>';
    }
} catch (Exception $e) {
    echo '<div class="page-header"><div class="alert alert-danger" role="alert"><strong>Oh Snap:</strong> Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage() . '</div></div>';
}