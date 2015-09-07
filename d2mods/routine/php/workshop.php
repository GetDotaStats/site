#!/usr/bin/php -q
<?php
try {
    echo '<br />';

    require_once('../../functions.php');
    require_once('../../../global_functions.php');
    require_once('../../../connections/parameters.php');

    $db = new dbWrapper_v3($hostname_gds_cron, $username_gds_cron, $password_gds_cron, $database_gds_cron, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    //UPDATE WORKSHOP DETAILS
    {
        set_time_limit(0);

        $modList = $db->q(
            'SELECT
                    `mod_id`,
                    `steam_id64`,
                    `mod_identifier`,
                    `mod_name`,
                    `mod_workshop_link`,
                    `mod_active`,
                    `date_recorded`
                FROM `mod_list`
                WHERE `mod_active` = 1
                ORDER BY `date_recorded`;'
        );

        foreach ($modList as $key => $value) {
            try {
                if (!empty($value['mod_workshop_link']) && is_numeric($value['mod_workshop_link'])) {
                    $workshopID = $value['mod_workshop_link'];

                    $page = 'http://api.steampowered.com/ISteamRemoteStorage/GetPublishedFileDetails/v1/';

                    $fields = array(
                        'itemcount' => '1',
                        'publishedfileids[0]' => $workshopID,
                        'key' => $api_key1,
                        'format' => 'json',
                    );

                    $fields_string = '';
                    foreach ($fields as $key2 => $value2) {
                        $fields_string .= $key2 . '=' . $value2 . '&';
                    }
                    rtrim($fields_string, '&');

                    $modWorkshopDetails = curl($page, $fields_string, NULL, NULL, NULL, 30);
                    $modWorkshopDetails = json_decode($modWorkshopDetails, true);

                    $tempArray = array();

                    if ($modWorkshopDetails['response']['result'] == 1 && $modWorkshopDetails['response']['publishedfiledetails'][0]['result'] == 1) {
                        try {
                            if (!empty($modWorkshopDetails['response']['publishedfiledetails'][0]['preview_url'])) {
                                curl_download($modWorkshopDetails['response']['publishedfiledetails'][0]['preview_url'], '../../../images/mods/thumbs/', $value['mod_id'] . '.png');
                            }
                        } catch (Exception $e) {
                            echo '<br />' . $e->getMessage() . '<br /><br />';
                        }

                        $tempArray['mod_identifier'] = isset($value['mod_identifier'])
                            ? $value['mod_identifier']
                            : NULL;

                        $tempArray['mod_workshop_id'] = isset($modWorkshopDetails['response']['publishedfiledetails'][0]['publishedfileid'])
                            ? $modWorkshopDetails['response']['publishedfiledetails'][0]['publishedfileid']
                            : NULL;

                        $tempArray['mod_size'] = isset($modWorkshopDetails['response']['publishedfiledetails'][0]['file_size'])
                            ? $modWorkshopDetails['response']['publishedfiledetails'][0]['file_size']
                            : 0;

                        $tempArray['mod_hcontent_file'] = isset($modWorkshopDetails['response']['publishedfiledetails'][0]['hcontent_file'])
                            ? $modWorkshopDetails['response']['publishedfiledetails'][0]['hcontent_file']
                            : NULL;

                        $tempArray['mod_hcontent_preview'] = isset($modWorkshopDetails['response']['publishedfiledetails'][0]['hcontent_preview'])
                            ? $modWorkshopDetails['response']['publishedfiledetails'][0]['hcontent_preview']
                            : NULL;

                        $tempArray['mod_thumbnail'] = isset($modWorkshopDetails['response']['publishedfiledetails'][0]['preview_url'])
                            ? $modWorkshopDetails['response']['publishedfiledetails'][0]['preview_url']
                            : NULL;

                        $tempArray['mod_views'] = isset($modWorkshopDetails['response']['publishedfiledetails'][0]['views'])
                            ? $modWorkshopDetails['response']['publishedfiledetails'][0]['views']
                            : 0;

                        $tempArray['mod_subs'] = isset($modWorkshopDetails['response']['publishedfiledetails'][0]['subscriptions'])
                            ? $modWorkshopDetails['response']['publishedfiledetails'][0]['subscriptions']
                            : 0;

                        $tempArray['mod_favs'] = isset($modWorkshopDetails['response']['publishedfiledetails'][0]['favorited'])
                            ? $modWorkshopDetails['response']['publishedfiledetails'][0]['favorited']
                            : 0;

                        $tempArray['mod_subs_life'] = isset($modWorkshopDetails['response']['publishedfiledetails'][0]['lifetime_subscriptions'])
                            ? $modWorkshopDetails['response']['publishedfiledetails'][0]['lifetime_subscriptions']
                            : 0;

                        $tempArray['mod_favs_life'] = isset($modWorkshopDetails['response']['publishedfiledetails'][0]['lifetime_favorited'])
                            ? $modWorkshopDetails['response']['publishedfiledetails'][0]['lifetime_favorited']
                            : 0;

                        $tempArray['date_last_updated'] = isset($modWorkshopDetails['response']['publishedfiledetails'][0]['time_updated'])
                            ? $modWorkshopDetails['response']['publishedfiledetails'][0]['time_updated']
                            : NULL;


                        $sqlResult = $db->q(
                            'INSERT INTO `mod_workshop`
                                (`mod_identifier`, `mod_workshop_id`, `mod_size`, `mod_hcontent_file`, `mod_hcontent_preview`, `mod_thumbnail`, `mod_views`, `mod_subs`, `mod_favs`, `mod_subs_life`, `mod_favs_life`, `date_last_updated`)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, FROM_UNIXTIME(?))',
                            'siisssiiiiis',
                            $tempArray
                        );

                        echo $sqlResult
                            ? "[SUCCESS] Added workshop details for: $workshopID!<br />"
                            : "[FAILURE] Adding workshop details for: $workshopID!<br />";

                    } else {
                        echo "<strong>[FAILURE] NO DATA for:</strong> $workshopID!<br />";
                    }
                }
            } catch (Exception $e) {
                echo '<br />';
                echo "<strong>[FAILURE]</strong> Adding workshop details for: $workshopID!<br />";
                echo $e->getMessage() . '<br /><br />';
            }
        }
    }

    $memcache->close();
} catch (Exception $e) {
    echo 'Caught Exception (MAIN) -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage() . '<br /><br />';
}