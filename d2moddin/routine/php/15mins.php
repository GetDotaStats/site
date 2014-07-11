#!/usr/bin/php -q
<?php
require_once('../../../connections/parameters.php');
require_once('../../../global_functions.php');

try {
    $db = new dbWrapper($hostname_d2moddin, $username_d2moddin, $password_d2moddin, $database_d2moddin, true);

    ////////////////////////
    //GENERAL STATS
    ////////////////////////
    {
        $stats = json_decode(curl('http://net1.d2modd.in/stats/general', NULL, NULL, NULL, NULL, 20), 1);

        $db->q("INSERT INTO `stats_production` (`lobby_total`, `lobby_wait`, `lobby_play`, `lobby_queue`) VALUES (?, ?, ?, ?)",
            "iiii",
            $stats['lobby_total'], $stats['lobby_wait'], $stats['lobby_play'], $stats['lobby_queue']);

        ////////////////////////
        //REGIONS
        ////////////////////////
        $sql = array();
        foreach ($stats['regions'] as $key => $value) {
            if (isset($value['name']) && isset($value['id']) && isset($value['servercount']) && isset($value['playing'])) {
                if($value['name'] == 'UNKNOWN'){
                    $value['name'] = 'All Regions';
                }
                $sql[] = '(\'' . $db->escape($value['name']) . '\', ' .
                    $db->escape($value['id']) . ', ' .
                    $db->escape($value['servercount']) . ', ' .
                    $db->escape($value['playing']) . ')';
            } else {
                echo 'Failed: ' . $value['name'] . '<br />';
            }
        }
        $sql_values = implode(', ', $sql);
        unset($sql);

        $db->q("INSERT INTO `stats_production_regions` (`region_name`, `region_id`, `region_servercount`, `region_playing`) VALUES " . $sql_values);

        ////////////////////////
        //SERVERS
        ////////////////////////
        $sql = array();
        foreach ($stats['servers'] as $key => $value) {
            if (isset($value['region']) && isset($value['name']) && isset($value['ip']) && isset($value['activeinstances']) && isset($value['maxinstances'])) {
                foreach ($value['region'] as $key2 => $value2) {
                    if ($key2 == 0 || ($key2 > 0 && $value2 > 0)) {
                        $sql[] = '(' .
                            $db->escape($value2) . ', \'' .
                            $db->escape($value['name']) . '\', \'' .
                            $db->escape($value['ip']) . '\', ' .
                            $db->escape($value['activeinstances']) . ', ' .
                            $db->escape($value['maxinstances']) . ')';
                    }
                }
            } else {
                echo 'Failed: ' . $value['name'] . ' | ' . $value['region'] . '<br />';
            }
        }
        $sql_values = implode(', ', $sql);
        unset($sql);

        $db->q("INSERT INTO `stats_production_servers` (`region_id`, `server_name`, `server_ip`, `server_activeinstances`, `server_maxinstances`) VALUES " . $sql_values);

        echo '<pre>';
        print_r($stats);
        echo '</pre>';
    }

    echo '<hr />';

    ////////////////////////
    //MODS
    ////////////////////////
    {
        $stats = json_decode(curl('http://net1.d2modd.in/stats/mods', NULL, NULL, NULL, NULL, 20), 1);

        $sql = array();
        foreach ($stats['mods'] as $key => $value) {
            if (isset($value['name']) && isset($value['version']) && isset($value['lobbies'])) {
                $sql[] = '(\'' . $db->escape($value['name']) . '\', \'' .
                    $db->escape($value['version']) . '\', ' .
                    $db->escape($value['lobbies']) . ')';
            }
        }
        $sql_values = implode(', ', $sql);
        unset($sql);

        $db->q("INSERT INTO `stats_production_mods` (`mod_name`, `mod_version`, `mod_lobbies`) VALUES " . $sql_values);

        echo '<pre>';
        print_r($stats);
        echo '</pre>';
    }
} catch (Exception $e) {
    echo $e->getMessage();
}