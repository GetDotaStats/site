<?php
require_once('../../global_functions.php');
require_once('../functions.php');
require_once('../../connections/parameters.php');

//THIS LISTS ALL THE ACTIVE MODS ON THE SITE

try {
    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    $popularMods = $memcache->get('api_d2mods_get_popular1');
    if (!$popularMods) {
        $popularMods = array();

        $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
        if (empty($db)) throw new Exception('No DB!');

        $modListActive = simple_cached_query('api_d2mods_directory_active',
            'SELECT
                    ml.*,
                    gu.`user_name`,
                    gu.`user_avatar`,
                    (SELECT COUNT(*) FROM `mod_match_overview` mmo WHERE mmo.`mod_id` = ml.`mod_identifier` AND mmo.`match_recorded` >= now() - INTERVAL 7 DAY AND mmo.`match_duration` > 130 GROUP BY `mod_id`) AS games_last_week,
                    (SELECT COUNT(*) FROM `mod_match_overview` mmo WHERE mmo.`mod_id` = ml.`mod_identifier` AND mmo.`match_duration` > 130 GROUP BY `mod_id`) AS games_all_time
                FROM `mod_list` ml
                LEFT JOIN `gds_users` gu ON ml.`steam_id64` = gu.`user_id64`
                WHERE ml.`mod_active` = 1
                HAVING games_all_time > 0
                ORDER BY games_last_week DESC, games_all_time DESC;'
            , 5 * 60
        );

        if (!empty($modListActive)) {
            foreach ($modListActive as $key => $value) {
                $temp = array();

                $temp['modID'] = !empty($value['mod_id'])
                    ? $value['mod_id']
                    : 0;

                $temp['modName'] = !empty($value['mod_name'])
                    ? $value['mod_name']
                    : 'Unknown Mod';

                $temp['mod_max_players'] = isset($value['mod_max_players']) && is_numeric($value['mod_max_players'])
                    ? $value['mod_max_players']
                    : 10;

                isset($key)
                    ? $temp['popularityRank'] = $key + 1
                    : NULL;

                !empty($value['games_last_week'])
                    ? $temp['gamesLastWeek'] = number_format($value['games_last_week'])
                    : $temp['gamesLastWeek'] = 0;

                !empty($value['games_all_time'])
                    ? $temp['gamesAllTime'] = number_format($value['games_all_time'])
                    : $temp['gamesAllTime'] = 0;

                !empty($value['mod_workshop_link'])
                    ? $temp['workshopLink'] = 'http://steamcommunity.com/sharedfiles/filedetails/?id=' . $value['mod_workshop_link']
                    : NULL;

                !empty($value['mod_steam_group'])
                    ? $temp['steamGroup'] = 'http://steamcommunity.com/groups/' . $value['mod_steam_group']
                    : NULL;

                !empty($value['mod_id'])
                    ? $temp['modInfo'] = 'http://getdotastats.com/#s2__mod?id=' . $value['mod_id']
                    : NULL;

                !empty($value['user_name'])
                    ? $temp['modDeveloperName'] = htmlentitiesdecode_custom($value['user_name'])
                    : $temp['modDeveloperName'] = 'Unknown';

                !empty($value['user_avatar'])
                    ? $temp['modDeveloperAvatar'] = $value['user_avatar']
                    : NULL;

                !empty($value['date_recorded'])
                    ? $temp['modDateAdded'] = relative_time_v3($value['date_recorded'])
                    : NULL;

                !empty($value['mod_description'])
                    ? $temp['modDescription'] = htmlentitiesdecode_custom($value['mod_description'])
                    : NULL;

                !empty($value['mod_maps'])
                    ? $temp['mod_maps'] = $value['mod_maps']
                    : NULL;

                $popularMods[] = $temp;
            }
        } else {
            $popularMods['error'] = 'No active mods!';
        }

        $memcache->set('api_d2mods_get_popular1', $popularMods, 0, 10 * 60);
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