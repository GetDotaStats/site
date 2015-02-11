<?php
require_once("./functions.php");
require_once("../global_functions.php");
require_once("../connections/parameters.php");

try {
    if (!isset($_SESSION)) {
        session_start();
    }

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);

    $steam_api_domain = 'getdotastats.com'; //////////////////////////////////////////////////

    $user = new user;
    $user->apikey = $api_key6; // put your API key here
    $user->domain = $steam_api_domain; // put your domain

    if (isset($_GET['login'])) {
        $user->signIn('../', $db);
    }
    if (isset($_GET['logout'])) {
        $user->signOut('../', $db);
    }

    /*
    stdClass Object
    (
        [steamid] => 76561198111755442
        [communityvisibilitystate] => 3
        [profilestate] => 1
        [personaname] => getdotabet
        [profileurl] => http://steamcommunity.com/id/getdotabet/
        [avatar] => http://media.steampowered.com/steamcommunity/public/images/avatars/63/6334ac1c60cbd025d4cc87071414e6569d2b64e8.jpg
        [avatarmedium] => http://media.steampowered.com/steamcommunity/public/images/avatars/63/6334ac1c60cbd025d4cc87071414e6569d2b64e8_medium.jpg
        [avatarfull] => http://media.steampowered.com/steamcommunity/public/images/avatars/63/6334ac1c60cbd025d4cc87071414e6569d2b64e8_full.jpg
        [personastate] => 0
        [primaryclanid] => 103582791429521408
        [timecreated] => 1382411448
        [loccountrycode] => RU
    )
    */
} catch (Exception $e) {
    echo $e->getMessage();
}
