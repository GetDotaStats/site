<?php
try {
    require_once('../global_functions.php');
    require_once('../connections/parameters.php');

    if (!isset($_SESSION)) {
        session_start();
    }

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcached = new Cache(NULL, NULL, $localDev);

    checkLogin_v2();
    if (empty($_SESSION['user_id64'])) throw new Exception('Not logged in!');

    $adminCheck = adminCheck($_SESSION['user_id64'], 'admin');
    if (empty($adminCheck)) throw new Exception('Not an admin!');

    if (
        empty($_POST['highscore_name']) ||
        empty($_POST['highscore_modid']) || $_POST['highscore_modid'] == '--' ||
        empty($_POST['highscore_description']) ||
        //AESTHETICS
        !isset($_POST['highscore_objective']) ||
        !isset($_POST['highscore_operator']) ||
        !isset($_POST['highscore_factor']) || !is_numeric($_POST['highscore_factor']) ||
        !isset($_POST['highscore_decimals']) || !is_numeric($_POST['highscore_decimals'])
    ) {
        throw new Exception('Missing or invalid required parameter(s)!');
    }

    $highscoreName = htmlentities($_POST['highscore_name']);

    $highscoreModID = htmlentities($_POST['highscore_modid']);

    $highscoreDescription = htmlentities($_POST['highscore_description']);

    $highscoreObjective = !empty($_POST['highscore_objective'])
        ? $_POST['highscore_objective']
        : 'min';

    $highscoreOperator = !empty($_POST['highscore_operator'])
        ? $_POST['highscore_operator']
        : 'multiply';

    $highscoreFactor = !empty($_POST['highscore_factor']) && is_numeric($_POST['highscore_factor'])
        ? $_POST['highscore_factor']
        : 1;

    $highscoreDecimals = isset($_POST['highscore_decimals']) && is_numeric($_POST['highscore_decimals'])
        ? $_POST['highscore_decimals']
        : 2;

    $highscoreID = md5($highscoreModID . '_' . $highscoreName . '_' . time());

    $modDetails = cached_query(
        's2_mod_header_mod_details' . $highscoreModID,
        'SELECT
                  ml.`mod_id`,
                  ml.`steam_id64`,
                  ml.`mod_identifier`,
                  ml.`mod_name`,
                  ml.`mod_description`,
                  ml.`mod_workshop_link`,
                  ml.`mod_steam_group`,
                  ml.`mod_active`,
                  ml.`mod_rejected`,
                  ml.`mod_rejected_reason`,
                  ml.`mod_size`,
                  ml.`workshop_updated`,
                  ml.`mod_maps`,
                  ml.`date_recorded`

                FROM `mod_list` ml
                WHERE ml.`mod_identifier` = ?
                LIMIT 0,1;',
        's',
        $highscoreModID,
        15
    );
    if (empty($modDetails)) throw new Exception('Unknown modID!');


    $insertSQL = $db->q(
        'INSERT INTO `stat_highscore_mods_schema`
              (
                `highscoreID`,
                `modID`,
                `highscoreName`,
                `highscoreDescription`,
                `highscoreObjective`,
                `highscoreOperator`,
                `highscoreFactor`,
                `highscoreDecimals`
              )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?);',
        'ssssssss', //STUPID x64 windows PHP is actually x86
        array(
            $highscoreID,
            $highscoreModID,
            $highscoreName,
            $highscoreDescription,
            $highscoreObjective,
            $highscoreOperator,
            $highscoreFactor,
            $highscoreDecimals
        )
    );

    if ($insertSQL) {
        $json_response['result'] = "Success! Highscore type added to DB and under the account of the associated mod's developer.";

        $irc_message = new irc_message($webhook_gds_site_normal);

        $message = array(
            array(
                $irc_message->colour_generator('red'),
                '[ADMIN]',
                $irc_message->colour_generator(NULL),
            ),
            array(
                $irc_message->colour_generator('green'),
                '[HIGHSCORE-SCHEMA]',
                $irc_message->colour_generator(NULL),
            ),
            array(
                'Created highscore',
            ),
            array(
                $irc_message->colour_generator('bold'),
                $irc_message->colour_generator('blue'),
                $highscoreName,
                $irc_message->colour_generator(NULL),
                $irc_message->colour_generator('bold'),
            ),
            array(
                'for: ',
            ),
            array(
                $irc_message->colour_generator('bold'),
                $irc_message->colour_generator('purple'),
                $modDetails[0]['mod_name'],
                $irc_message->colour_generator(NULL),
                $irc_message->colour_generator('purple'),
            ),
            array(' || http://getdotastats.com/#admin__hs_mod'),
        );

        $message = $irc_message->combine_message($message);
        $irc_message->post_message($message, array('localDev' => $localDev));
    } else {
        throw new Exception('Highscore type not added to DB!');
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