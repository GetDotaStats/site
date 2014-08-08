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
        echo '<pre>';

        echo '<h1>Servers/Regions:</h1>';
        $stats = json_decode(curl('http://net1.d2modd.in/stats/general', NULL, NULL, NULL, NULL, 20), 1);

        $stats['lobby_total'] = !isset($stats['lobby_total']) || empty($stats['lobby_total'])
            ? 0
            : $stats['lobby_total'];

        $stats['lobby_wait'] = !isset($stats['lobby_wait']) || empty($stats['lobby_wait'])
            ? 0
            : $stats['lobby_wait'];

        $stats['lobby_play'] =  !isset($stats['lobby_play']) || empty($stats['lobby_play'])
            ? 0
            : $stats['lobby_play'];

        $stats['lobby_queue'] =  !isset($stats['lobby_queue']) || empty($stats['lobby_queue'])
            ? 0
            : $stats['lobby_queue'];

        $db->q("INSERT INTO `stats_production` (`lobby_total`, `lobby_wait`, `lobby_play`, `lobby_queue`) VALUES (?, ?, ?, ?)",
            "iiii",
            $stats['lobby_total'], $stats['lobby_wait'], $stats['lobby_play'], $stats['lobby_queue']);

        ////////////////////////
        //REGIONS
        ////////////////////////
        if (!empty($stats['regions'])) {
            $sql = array();
            foreach ($stats['regions'] as $key => $value) {
                if (isset($value['name']) && isset($value['id']) && isset($value['servercount']) && isset($value['playing'])) {
                    if ($value['name'] == 'UNKNOWN') {
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
        } else {
            echo '<br />No regions!<br />';
        }

        ////////////////////////
        //SERVERS
        ////////////////////////
        if (!empty($stats['servers'])) {
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
        } else {
            echo 'No servers!<br />';
        }

        if (!empty($stats)) {
            print_r($stats);
        }
        echo '<hr />';
    }

    ////////////////////////
    //MODS
    ////////////////////////
    {
        echo '<h1>Mods:</h1>';
        $stats = json_decode(curl('http://net1.d2modd.in/stats/mods', NULL, NULL, NULL, NULL, 20), 1);

        if (!empty($stats['mods'])) {
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

            print_r($stats);
        }
        else{
            echo 'No mods!<br />';
        }
        echo '<hr />';
    }


    ////////////////////////
    //PLAYERS
    ////////////////////////
    {
        echo '<h1>Players:</h1>';
        $stats = json_decode(curl('http://net1.d2modd.in/stats/players', NULL, NULL, NULL, NULL, 20), 1);

        if(empty($stats)){
            echo 'No players!<br />';
        }

        $stats['online'] = !isset($stats['online']) || empty($stats['online'])
            ? 0
            : $db->escape($stats['online']);

        $stats['lastmonth'] = !isset($stats['lastmonth']) || empty($stats['lastmonth'])
            ? 0
            : $db->escape($stats['lastmonth']);

        $stats['playing'] = !isset($stats['playing']) || empty($stats['playing'])
            ? 0
            : $db->escape($stats['playing']);

        $db->q("INSERT INTO `stats_production_players` (`players_online`, `players_lastmonth`, `players_playing`) VALUES (?, ?, ?)",
            "iii",
            $stats['online'], $stats['lastmonth'], $stats['playing']);

        print_r($stats);
        echo '</pre>';
        echo '<hr />';
    }
} catch (Exception $e) {
    echo $e->getMessage();
}