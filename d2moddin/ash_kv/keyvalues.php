<?php
require_once('../../connections/parameters.php');
require_once('../../global_functions.php');

$db = new dbWrapper($hostname_d2moddin, $username_d2moddin, $password_d2moddin, $database_d2moddin, true);
$bansSQL = $db->q('SELECT * FROM `kv_lod_bans`');

if (!empty($bansSQL)) {
    $bans = array();

    /*echo '<pre>';
    print_r($bans);
    echo '</pre>';
    exit();*/

    $kv = '"Bans" { ';


    $kv .= ' "BannedCombinations" { ';

    $i = 1;
    foreach ($bansSQL as $key => $value) {

        $kv .= '"' . $i . '" { ';
        $kv .= '"1" "' . $value['ability1'] . '" ';
        $kv .= '"2" "' . $value['ability2'] . '" ';
        $kv .= '} ';

        $i++;
    }
    $kv .= '} ';


    $kv .= '}';

    echo $kv;
} else {
    echo 'No bans!';
}