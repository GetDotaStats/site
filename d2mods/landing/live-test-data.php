<?php
$json = array();

$json['matchID'] = '98426ea5f41590';
$json['modID'] = '4d710f4c81bf6402e5';
$json['modes'] = array('ctf', '1v1', 'best100', 'best20');
$json['version'] = '2.0.12';
$json['duration'] = '1234';
$json['winner'] = '2';
$json['numTeams'] = '2';
$json['numPlayers'] = '8';
$json['serverAddress'] = '192.168.0.1';
$json['dateEnded'] = '1409461194';

$json = json_encode($json);
echo $json .'<hr />';

print_r(json_decode($json,true));