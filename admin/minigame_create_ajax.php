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
    if (empty($adminCheck)) {
        throw new Exception('Not an admin!');
    }

    if (
        !empty($_POST['minigame_name']) &&
        !empty($_POST['minigame_developer']) &&
        !empty($_POST['minigame_description']) &&
        //AESTHETICS
        !empty($_POST['minigame_objective']) &&
        !empty($_POST['minigame_operator']) &&
        !empty($_POST['minigame_factor']) &&
        !empty($_POST['minigame_decimals'])
    ) {
        $steamAPI = new steam_webapi($api_key1);
        $steamIDconverter = new SteamID();

        if (is_numeric($_POST['minigame_developer'])) {
            $steamIDconverter->setSteamID($_POST['minigame_developer']);
            $developerSteamID = $steamIDconverter->getSteamID64();
        } else if (stristr($_POST['minigame_developer'], 'steamcommunity.com/id/')) {
            $customUrl = rtrim(cut_str($_POST['minigame_developer'], 'steamcommunity.com/id/'), '/');
            $vanityURLResult = $steamAPI->ResolveVanityURL($customUrl);

            if (!empty($vanityURLResult) && $vanityURLResult['response']['success'] == 1) {
                $steamIDconverter->setSteamID($vanityURLResult['response']['steamid']);
                $developerSteamID = $steamIDconverter->getSteamID64();
            } else {
                throw new Exception('Failed to resolve vanity URL!');
            }
        } else if (stristr($_POST['minigame_developer'], 'steamcommunity.com/profiles/')) {
            $customUrl = rtrim(cut_str($_POST['minigame_developer'], 'steamcommunity.com/profiles/'), '/');

            if (!empty($customUrl) && is_numeric($customUrl)) {
                $steamIDconverter->setSteamID($customUrl);
                $developerSteamID = $steamIDconverter->getSteamID64();
            } else {
                throw new Exception('Failed to resolve profile link!');
            }
        } else {
            throw new Exception('Bad steam ID!');
        }

        $developer_user_details = cached_query(
            'minigame_user_details' . $developerSteamID,
            'SELECT
                    `user_id64`,
                    `user_id32`,
                    `user_name`,
                    `user_avatar`,
                    `user_avatar_medium`,
                    `user_avatar_large`
            FROM `gds_users`
            WHERE `user_id64` = ?
            LIMIT 0,1;',
            's',
            $developerSteamID,
            5
        );

        if (empty($developer_user_details)) {
            $developer_user_details_temp = $steamAPI->GetPlayerSummariesV2($steamIDconverter->getSteamID64());

            if (!empty($developer_user_details_temp)) {
                $developer_user_details[0]['user_id64'] = $steamIDconverter->getSteamID64();
                $developer_user_details[0]['user_id32'] = $steamIDconverter->getSteamID32();
                $developer_user_details[0]['user_name'] = $developer_user_details_temp['response']['players'][0]['personaname'];
                $developer_user_details[0]['user_avatar'] = $developer_user_details_temp['response']['players'][0]['avatar'];
                $developer_user_details[0]['user_avatar_medium'] = $developer_user_details_temp['response']['players'][0]['avatarmedium'];
                $developer_user_details[0]['user_avatar_large'] = $developer_user_details_temp['response']['players'][0]['avatarfull'];


                $db->q(
                    'INSERT INTO `gds_users`
                        (`user_id64`, `user_id32`, `user_name`, `user_avatar`, `user_avatar_medium`, `user_avatar_large`)
                        VALUES (?, ?, ?, ?, ?, ?)',
                    'ssssss',
                    array(
                        $developer_user_details[0]['user_id64'],
                        $developer_user_details[0]['user_id32'],
                        $developer_user_details[0]['user_name'],
                        $developer_user_details[0]['user_avatar'],
                        $developer_user_details[0]['user_avatar_medium'],
                        $developer_user_details[0]['user_avatar_large']
                    )
                );

                $memcache->set('minigame_user_details' . $developerSteamID, $developer_user_details, 0, 15);
            }
        }


        if (!empty($_POST['minigame_steam_group']) && stristr($_POST['minigame_steam_group'], 'steamcommunity.com/groups/')) {
            $minigameGroup = htmlentities(rtrim(cut_str($_POST['minigame_steam_group'], 'groups/'), '/'));
        } else {
            $minigameGroup = NULL;
        }

        $minigameName = htmlentities($_POST['minigame_name']);

        $minigameDescription = htmlentities($_POST['minigame_description']);

        $minigameObjective = !empty($_POST['minigame_objective'])
            ? $_POST['minigame_objective']
            : 'min';

        $minigameOperator = !empty($_POST['minigame_operator'])
            ? $_POST['minigame_operator']
            : 'multiply';

        $minigameFactor = !empty($_POST['minigame_factor']) && is_numeric($_POST['minigame_factor'])
            ? $_POST['minigame_factor']
            : 1;

        $minigameDecimals = isset($_POST['minigame_decimals']) && is_numeric($_POST['minigame_decimals'])
            ? $_POST['minigame_decimals']
            : 2;

        $insertSQL = $db->q(
            'INSERT INTO `stat_highscore_minigames` (`minigameID`, `minigameName`, `minigameDeveloper`, `minigameSteamGroup`, `minigameDescription`,
                  `minigameObjective`, `minigameOperator`, `minigameFactor`, `minigameDecimals`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?);',
            'sssssssss', //STUPID x64 windows PHP is actually x86
            md5($minigameName . time()), $minigameName, $developerSteamID, $minigameGroup, $minigameDescription, $minigameObjective, $minigameOperator, $minigameFactor, $minigameDecimals
        );

        if ($insertSQL) {
            $json_response['result'] = 'Success! Mini Game added to DB and under the developer\'s account.';
        } else {
            throw new Exception('Mini Game not added to DB!');
        }
    } else {
        throw new Exception('Missing parameter (name, developer, description, etc.)!');
    }

    $memcache->close();
} catch (Exception $e) {
    $json_response['error'] = 'Caught Exception: ' . $e->getMessage() . ' || Contact getdotastats.com';
}

try {
    echo utf8_encode(json_encode($json_response));
} catch (Exception $e) {
    unset($json_response);
    $json_response['error'] = 'Caught Exception: ' . $e->getMessage() . ' || Contact getdotastats.com';
    echo utf8_encode(json_encode($json_response));
}