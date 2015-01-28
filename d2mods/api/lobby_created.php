<?php
require_once('../../global_functions.php');
require_once('../functions.php');
require_once('./functions.php');
require_once('../../connections/parameters.php');

//Allow client plugin to communicate new lobbies to site

try {
    $userID = !empty($_GET['uid']) && is_numeric($_GET['uid'])
        ? $_GET['uid']
        : NULL;

    $username = !empty($_GET['un'])
        ? urlencode(unicodeToUTF_8($_GET['un']))
        : NULL;

    $modID = !empty($_GET['mid']) && is_numeric($_GET['mid'])
        ? $_GET['mid']
        : NULL;

    $workshopID = !empty($_GET['wid']) && is_numeric($_GET['wid'])
        ? $_GET['wid']
        : NULL;

    $map = !empty($_GET['map'])
        ? urlencode(unicodeToUTF_8($_GET['map']))
        : NULL;

    $pass = !empty($_GET['p'])
        ? urlencode(unicodeToUTF_8($_GET['p']))
        : NULL;

    $maxPlayers = !empty($_GET['mp']) && is_numeric($_GET['mp']) && $_GET['mp'] > 1 && $_GET['mp'] <= 20
        ? $_GET['mp']
        : NULL;

    $region = !empty($_GET['r']) && is_numeric($_GET['r']) && $_GET['r'] <= 100
        ? $_GET['r']
        : NULL;

    $lobbyName = !empty($_GET['ln'])
        ? urlencode(unicodeToUTF_8($_GET['ln']))
        : NULL;

    $lobbyOptions = !empty($_GET['lo'])
        ? urlencode(unicodeToUTF_8($_GET['lo']))
        : NULL;

    $lobbyVersion = !empty($_GET['lv'])
        ? urlencode(unicodeToUTF_8($_GET['lv']))
        : NULL;

    //$lobbyOptions
    ////[{type:"textbox",label:"Text Label",name:"text_name",width:"30",default="default stuff"},{type:"dropdown",label:"Dropdown Label",name:"dropdown1",default="Number 1",options:[{label:"Number 1",data:"1"},{label:"Second",data:"Second"}]},{type:"checkbox",label:"Checkbox Label",name:"checkbox_name",default:true}]

    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $lobbySecureToken = '';
    for ($i = 0; $i < 10; $i++)
        $lobbySecureToken .= $characters[rand(0, 35)];

    $steamID = new SteamID($userID);
    $userID = $steamID->getSteamID64();

    if (!empty($userID) && !empty($modID) && !empty($workshopID) && !empty($map) && !empty($pass) && !empty($maxPlayers) && !empty($username)) {
        $memcache = new Memcache;
        $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

        $lobbyStatus = array();

        $db = new dbWrapper_v2($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, false);
        $db->q('SET NAMES utf8;');

        if ($db) {
            $modDetails = getModDetails($memcache, $db, $modID);

            $modGUID = !empty($modDetails['mod_guid'])
                ? $modDetails['mod_guid']
                : 1;

            $sqlResult = $db->q(
                'INSERT INTO `lobby_list`(`mod_id`, `mod_guid`, `workshop_id`, `lobby_name`, `lobby_region`, `lobby_max_players`, `lobby_leader`, `lobby_leader_name`, `lobby_active`, `lobby_hosted`, `lobby_pass`, `lobby_map`, `lobby_secure_token`, `date_keep_alive`, `date_recorded`, `lobby_options`, `lobby_version`)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 0, ?, ?, ?, NULL, NULL, ?, ?);',
                'isssiisssssss',
                $modID, $modGUID, $workshopID, $lobbyName, $region, $maxPlayers, $userID, $username, $pass, $map, $lobbySecureToken, $lobbyOptions, $lobbyVersion
            );

            if (!empty($sqlResult)) {
                //RETURN LOBBY ID
                $lobbyStatus['result'] = 'Lobby ' . $db->last_index() . ' created!';
                $lobbyStatus['lobby_id'] = $db->last_index();
                $lobbyStatus['token'] = $lobbySecureToken;
            } else {
                //SOMETHING FUNKY HAPPENED
                $lobbyStatus['error'] = 'Unknown error!';
            }
        } else {
            $lobbyStatus['error'] = 'No DB connection!';
        }

        $memcache->close();
    } else {
        $lobbyStatus['error'] = 'Missing field!';
    }

} catch (Exception $e) {
    unset($lobbyStatus);
    $lobbyStatus['error'] = 'Contact getdotastats.com - Caught Exception: ' . $e->getMessage();
}

try {
    echo utf8_encode(json_encode($lobbyStatus));
} catch (Exception $e) {
    unset($lobbyStatus);
    $lobbyStatus['error'] = 'Contact getdotastats.com - Caught Exception: ' . $e->getMessage();
    echo utf8_encode(json_encode($lobbyStatus));
}