#!/usr/bin/php -q
<?php
try {
    require_once('../../../global_functions.php');
    require_once('../../../connections/parameters.php');

    $db = new dbWrapper_v3($hostname_gds_cron, $username_gds_cron, $password_gds_cron, $database_gds_cron, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    $serviceReport = new serviceReporting($db);

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
                WHERE `mod_rejected` = 0
                ORDER BY `date_recorded`;'
        );

        $workshopCronCounts = array(
            'success' => 0,
            'failure' => 0,
            'unknown' => 0,
        );

        $time_start1 = time();
        echo '<h2>Workshop Scraping</h2>';

        foreach ($modList as $key => $value) {
            try {
                if (!empty($value['mod_workshop_link']) && is_numeric($value['mod_workshop_link'])) {
                    $workshopID = $value['mod_workshop_link'];

                    $page = 'http://api.steampowered.com/ISteamRemoteStorage/GetPublishedFileDetails/v1/';

                    $fields = array(
                        'itemcount' => '1',
                        'publishedfileids[0]' => $workshopID,
                        'key' => $api_key6,
                        'format' => 'json',
                    );

                    $fields_string = '';
                    foreach ($fields as $key2 => $value2) {
                        $fields_string .= $key2 . '=' . $value2 . '&';
                    }
                    rtrim($fields_string, '&');

                    $modWorkshopDetails = curl($page, $fields_string, NULL, NULL, NULL, 10, 10);
                    $modWorkshopDetails = json_decode($modWorkshopDetails, true);

                    $tempArray = array();

                    if ($modWorkshopDetails['response']['result'] == 1 && ($modWorkshopDetails['response']['resultcount'] >= 1 || $modWorkshopDetails['response']['publishedfiledetails'][0]['result'] == 1)) {
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


                        if ($sqlResult) {
                            $db->q(
                                'UPDATE `mod_list` SET `workshop_updated` = FROM_UNIXTIME(?), `mod_size` = ? WHERE `mod_id` = ?;',
                                'ssi',
                                array(
                                    $tempArray['date_last_updated'],
                                    $tempArray['mod_size'],
                                    $value['mod_id']
                                )
                            );

                            $workshopCronCounts['success'] += 1;
                            echo "[SUCCESS] Added workshop details for: $workshopID!<br />";
                        } else {
                            $workshopCronCounts['unknown'] += 1;
                            echo "[FAILURE] Adding workshop details for: $workshopID!<br />";
                        }
                    } else {
                        $workshopCronCounts['failure'] += 1;
                        echo "<strong>[FAILURE] NO DATA for:</strong> $workshopID!<br />";
                        echo $modWorkshopDetails;
                        echo '<hr />';
                    }
                }
            } catch (Exception $e) {
                echo '<br />';
                echo "<strong>[FAILURE]</strong> Adding workshop details for: $workshopID!<br />";
                echo $e->getMessage() . '<br /><br />';
                $workshopCronCounts['failure'] += 1;
            }
        }

        $time_end1 = time();
        $totalRunTime = $time_end1 - $time_start1;
        echo 'Total Running: ' . ($time_end1 - $time_start1) . " seconds<br /><br />";
        echo '<hr />';

        try {
            $serviceReport->logAndCompareOld(
                's2_cron_workshop_scrape',
                array(
                    'value' => $totalRunTime,
                    'min' => 30,
                    'growth' => 0.5,
                ),
                array(
                    'value' => $workshopCronCounts['success'],
                    'min' => 1,
                    'growth' => 0.01,
                    'unit' => 'successful scrapes',
                ),
                array(
                    'value' => $workshopCronCounts['failure'],
                    'min' => 1,
                    'growth' => 0.01,
                    'unit' => 'failed scrapes',
                ),
                array(
                    'value' => $workshopCronCounts['unknown'],
                    'min' => 1,
                    'growth' => 0.01,
                    'unit' => 'unknown scrapes',
                ),
                FALSE
            );
        } catch (Exception $e) {
            echo 'Caught Exception (MAIN) -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage() . '<br /><br />';

            //WEBHOOK
            {
                $irc_message = new irc_message($webhook_gds_site_admin);

                $message = array(
                    array(
                        $irc_message->colour_generator('red'),
                        '[CRON]',
                        $irc_message->colour_generator(NULL),
                    ),
                    array(
                        $irc_message->colour_generator('green'),
                        '[WORKSHOP]',
                        $irc_message->colour_generator(NULL),
                    ),
                    array(
                        $irc_message->colour_generator('bold'),
                        $irc_message->colour_generator('blue'),
                        'Warning:',
                        $irc_message->colour_generator(NULL),
                        $irc_message->colour_generator('bold'),
                    ),
                    array($e->getMessage() . ' ||'),
                    array('http://getdotastats.com/s2/routine/log_hourly.html?' . time())
                );

                $message = $irc_message->combine_message($message);
                $irc_message->post_message($message, array('localDev' => $localDev));
            }
        }
    }

} catch (Exception $e) {
    echo 'Caught Exception (MAIN) -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage() . '<br /><br />';
} finally {
    if (isset($memcache)) $memcache->close();
}