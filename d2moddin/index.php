<?php
require_once('../connections/parameters.php');
require_once('./functions.php');

$db = new dbWrapper($hostname_d2moddin, $username_d2moddin, $password_d2moddin, $database_d2moddin, true);

$stats = json_decode(curl('http://ddp2.d2modd.in/stats/general'), 1);

//ONLY RECORD STATS IF GIVEN PARAMETER
if (isset($_GET["record"]) && $_GET["record"] = 1) {
    ////////////////////////
    //GENERAL STATS
    ////////////////////////
    $db->q("INSERT INTO `stats_production` (`lobby_total`, `lobby_wait`, `lobby_play`, `lobby_queue`) VALUES (?, ?, ?, ?)",
        "iiii",
        $stats['lobby_total'], $stats['lobby_wait'], $stats['lobby_play'], $stats['lobby_queue']);

    ////////////////////////
    //REGIONS
    ////////////////////////
    $sql = array();
    foreach ($stats['regions'] as $key => $value) {
        if (isset($value['name']) && $value['id'] && $value['servercount'] && $value['playing']) {
            $sql[] = '(\'' . $db->escape($value['name']) . '\', ' .
                $db->escape($value['id']) . ', ' .
                $db->escape($value['servercount']) . ', ' .
                $db->escape($value['playing']) . ')';
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
            $sql[] = '(' .
                $db->escape($value['region']) . ', \'' .
                $db->escape($value['name']) . '\', \'' .
                $db->escape($value['ip']) . '\', ' .
                $db->escape($value['activeinstances']) . ', ' .
                $db->escape($value['maxinstances']) . ')';
        }
    }
    $sql_values = implode(', ', $sql);
    unset($sql);

    $db->q("INSERT INTO `stats_production_servers` (`region_id`, `server_name`, `server_ip`, `server_activeinstances`, `server_maxinstances`) VALUES " . $sql_values);
}

echo '<pre>';
print_r($stats);
echo '</pre>';