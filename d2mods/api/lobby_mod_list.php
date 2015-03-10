<?php
require_once('../../global_functions.php');
require_once('../functions.php');
require_once('../../connections/parameters.php');

//THIS LISTS ALL THE ACTIVE MODS ON THE SITE

try {
    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    $popularMods = $memcache->get('api_lobby_d2mods_list');
    if (!$popularMods) {
        $popularMods = array();

        $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
        if (empty($db)) throw new Exception('No DB!');

        $modListActive = simple_cached_query('api_lobby_d2mods_list_active1',
            'SELECT
                    ml.*,
                    (SELECT COUNT(*) FROM `mod_match_overview` mmo WHERE mmo.`mod_id` = ml.`mod_identifier` AND mmo.`match_recorded` >= now() - INTERVAL 7 DAY AND mmo.`match_duration` > 130 GROUP BY `mod_id`) AS games_last_week,
                    (SELECT COUNT(*) FROM `mod_match_overview` mmo WHERE mmo.`mod_id` = ml.`mod_identifier` AND mmo.`match_duration` > 130 GROUP BY `mod_id`) AS games_all_time
                FROM `mod_list` ml
                WHERE ml.`mod_active` = 1
                HAVING games_all_time > 0
                ORDER BY games_last_week DESC, games_all_time DESC;'
            , 30
        );

        if (!empty($modListActive)) {
            foreach ($modListActive as $key => $value) {
                $temp = array();

                $temp['modName'] = !empty($value['mod_name'])
                    ? $value['mod_name']
                    : 'Unknown Mod';

                $temp['workshopID'] = !empty($value['mod_workshop_link'])
                    ? $value['mod_workshop_link']
                    : 0;

                $temp['modID'] = !empty($value['mod_id'])
                    ? $value['mod_id']
                    : 0;

                $temp['mod_max_players'] = isset($value['mod_max_players']) && is_numeric($value['mod_max_players'])
                    ? $value['mod_max_players']
                    : 10;

                isset($key)
                    ? $temp['popularityRank'] = $key + 1
                    : NULL;

                !empty($value['games_last_week'])
                    ? $temp['gamesLastWeek'] = $value['games_last_week']
                    : $temp['gamesLastWeek'] = 0;

                !empty($value['games_all_time'])
                    ? $temp['gamesAllTime'] = $value['games_all_time']
                    : $temp['gamesAllTime'] = 0;

                !empty($value['mod_maps'])
                    ? $temp['mod_maps'] = $value['mod_maps']
                    : NULL;

                $temp['mod_options_enabled'] = !empty($value['mod_options_enabled']) && $value['mod_options_enabled'] == 1
                    ? $value['mod_options_enabled']
                    : 0;

                !empty($value['mod_options']) && $temp['mod_options_enabled'] == 1
                    ? $temp['mod_options'] = $value['mod_options']
                    : NULL;

                $popularMods[] = $temp;
            }
        } else {
            $popularMods['error'] = 'No active mods!';
        }

        $memcache->set('api_lobby_d2mods_list', $popularMods, 0, 1 * 60);
    }

    $memcache->close();
} catch (Exception $e) {
    unset($popularMods);
    $popularMods['error'] = 'Caught Exception: ' . $e->getMessage() . '<br /> Contact getdotastats.com';
}

try {
    echo utf8_encode(json_encode($popularMods));
} catch (Exception $e) {
    unset($popularMods);
    $popularMods['error'] = 'Contact getdotastats.com - Caught Exception: ' . $e->getMessage();
    echo utf8_encode(json_encode($popularMods));
}