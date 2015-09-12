<?php
try {
    require_once('../global_functions.php');
    require_once('../connections/parameters.php');

    if (!isset($_SESSION)) {
        session_start();
    }

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    checkLogin_v2();
    if (empty($_SESSION['user_id64'])) throw new Exception('Not logged in!');

    $adminCheck = adminCheck($_SESSION['user_id64'], 'admin');
    if (empty($adminCheck)) throw new Exception('Not an admin!');

    if (
        empty($_POST['modID']) ||
        empty($_POST['modName']) ||
        empty($_POST['modMaps']) || $_POST['modMaps'] == 'One map per line' ||
        empty($_POST['modDescription']) ||
        empty($_POST['modWorkshop']) ||
        !isset($_POST['modActive']) || !is_numeric($_POST['modActive']) ||
        !isset($_POST['modMaxPlayers']) || !is_numeric($_POST['modMaxPlayers']) ||
        !isset($_POST['modOptionsActive']) || !is_numeric($_POST['modOptionsActive'])
    ) {
        throw new Exception('Missing or invalid required parameter(s)!');
    }

    $modID = htmlentities($_POST['modID']);
    $modName = htmlentities($_POST['modName']);
    $modDescription = htmlentities($_POST['modDescription']);
    $modGroup = !empty($_POST['modGroup'])
        ? htmlentities($_POST['modGroup'])
        : NULL;
    $modMaps = json_encode(array_map('trim', explode("\n", htmlentities($_POST['modMaps']))));
    $modWorkshop = htmlentities($_POST['modWorkshop']);
    $modMaxPlayers = htmlentities($_POST['modMaxPlayers']);
    $modOptions = !empty($_POST['modOptions'])
        ? $_POST['modOptions']
        : NULL;
    $modActive = $_POST['modActive'];
    $modOptionsActive = $_POST['modOptionsActive'];

    if(!empty($modOptions) && empty(json_decode($modOptions))){
        throw new Exception('Bad JSON given in `Options`!');
    }

    if($modOptionsActive == '1' && empty($modOptions)){
        throw new Exception('Can\'t activate options without `Options` field populated!');
    }

    $insertSQL = $db->q(
        'UPDATE `mod_list`
          SET
            `mod_active` = ?,
            `mod_name` = ?,
            `mod_description` = ?,
            `mod_steam_group` = ?,
            `mod_maps` = ?,
            `mod_max_players` = ?,
            `mod_workshop_link` = ?,
            `mod_options` = ?,
            `mod_options_enabled` = ?
          WHERE `mod_id` = ?;',
        'issssissis',
        $modActive, $modName, $modDescription, $modGroup, $modMaps, $modMaxPlayers, $modWorkshop, $modOptions, $modOptionsActive, $modID
    );

    if ($insertSQL) {
        $json_response['result'] = 'Custom Game updated!';

        $irc_message = new irc_message($webhook_gds_site_live);

        $message = array(
            array(
                $irc_message->colour_generator('red'),
                '[ADMIN]',
                $irc_message->colour_generator(NULL),
            ),
            array(
                $irc_message->colour_generator('green'),
                '[MOD]',
                $irc_message->colour_generator(NULL),
            ),
            array(
                $irc_message->colour_generator('bold'),
                $irc_message->colour_generator('blue'),
                'Edited:',
                $irc_message->colour_generator(NULL),
                $irc_message->colour_generator('bold'),
            ),
            array($modName),
            array(' || http://getdotastats.com/#d2mods__stats?id=' . $modID),
        );

        $message = $irc_message->combine_message($message);
        $irc_message->post_message($message);
    } else {
        throw new Exception('Custom Game not updated!');
    }
} catch (Exception $e) {
    $json_response['error'] = 'Caught Exception: ' . $e->getMessage();
} finally {
    if (isset($memcache)) $memcache->close();
}

try {
    echo utf8_encode(json_encode($json_response));
} catch (Exception $e) {
    unset($json_response);
    $json_response['error'] = 'Caught Exception: ' . $e->getMessage();
    echo utf8_encode(json_encode($json_response));
}