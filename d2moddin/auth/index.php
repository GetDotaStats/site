<?php
require_once("../functions.php");
require_once("../connections/parameters.php");

try {
    if (!isset($_SESSION)) {
        session_start();
    }

    $user = new user;
    $user->apikey = $steam_api_key; // put your API key here
    $user->domain = $steam_api_domain; // put your domain

    //echo $steam_api_key . '<br />' . $steam_api_domain . '<br />';

    if (isset($_GET['login'])) {
        $user->signIn('../../#d2moddin/');
    }

    if (isset($_GET['logout'])) {
        $user->signOut('../../#d2moddin/');
    }


    /*if(empty($_SESSION['user_id']))
    {
        print ('<form action="?login" method="post">
            <input type="image" src="http://cdn.steamcommunity.com/public/images/signinthroughsteam/sits_large_border.png"/>
            </form>');
    }
    else
    {
        echo '<pre>';
        echo $_SESSION['user_name'].'<br />';
        print('<form method="post"><button title="Logout" name="logout">Logout</button></form>');
        print_r( $user->GetPlayerSummaries($_SESSION['user_id']) );
        echo '</pre>';
    }
    */
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
?>
