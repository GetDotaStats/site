<?php
if (!function_exists('modPageHeader')) {
    function modPageHeader($modID, $imageCDN)
    {
        global $db, $memcache;

        $result = '';

        $modDetails = cached_query(
            's2_mod_page_details' . $modID,
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
                  ml.`date_recorded`,

                  gu.`user_name`,

                  guo.`user_email`,

                  (SELECT
                        SUM(`gamesPlayed`)
                      FROM `cache_mod_matches` cmm
                      WHERE cmm.`modID` = ml.`mod_id` AND cmm.`gamePhase` = 3 AND cmm.`dateRecorded` >= now() - INTERVAL 7 DAY
                  ) AS games_last_week,
                  (SELECT
                        SUM(`gamesPlayed`)
                      FROM `cache_mod_matches` cmm
                      WHERE cmm.`modID` = ml.`mod_id` AND cmm.`gamePhase` = 3
                  ) AS games_all_time

                FROM `mod_list` ml
                JOIN `gds_users` gu ON ml.`steam_id64` = gu.`user_id64`
                LEFT JOIN `gds_users_options` guo ON ml.`steam_id64` = guo.`user_id64`
                WHERE ml.`mod_id` = ?
                LIMIT 0,1;',
            'i',
            $modID,
            15
        );

        if (empty($modDetails)) {
            throw new Exception('Invalid modID! Not recorded in database.');
        }

        //Tidy variables
        {
            //Mod name and thumb
            {
                $modThumb = is_file('../images/mods/thumbs/' . $modDetails[0]['mod_id'] . '.png')
                    ? $imageCDN . '/images/mods/thumbs/' . $modDetails[0]['mod_id'] . '.png'
                    : $imageCDN . '/images/misc/steam/blank_avatar.jpg';
                $modThumb = '<img width="24" height="24" src="' . $modThumb . '" alt="Mod thumbnail" />';
                $modThumb = '<a target="_blank" href="http://steamcommunity.com/sharedfiles/filedetails/?id=' . $modDetails[0]['mod_workshop_link'] . '">' . $modThumb . '</a>';

                $modNameLink = $modIDname = '';
                if (!empty($_SESSION['user_id64'])) {
                    //if admin, show modIdentifier too
                    $adminCheck = adminCheck($_SESSION['user_id64'], 'admin');
                    if (!empty($adminCheck)) {
                        $modIDname = '<small>' . $modDetails[0]['mod_identifier'] . '</small>';
                    }
                }
                $modNameLink = $modThumb . ' <a class="nav-clickable" href="#s2__mod?id=' . $modDetails[0]['mod_id'] . '">' . $modDetails[0]['mod_name'] . $modNameLink . '</a> ' . $modIDname;
            }

            //Mod external links
            {
                !empty($modDetails[0]['mod_workshop_link'])
                    ? $links['steam_workshop'] = '<a href="http://steamcommunity.com/sharedfiles/filedetails/?id=' . $modDetails[0]['mod_workshop_link'] . '" target="_new"><span class="glyphicon glyphicon-new-window"></span> Workshop</a>'
                    : NULL;
                !empty($modDetails[0]['mod_steam_group'])
                    ? $links['steam_group'] = '<a href="http://steamcommunity.com/groups/' . $modDetails[0]['mod_steam_group'] . '" target="_new"><span class="glyphicon glyphicon-new-window"></span> Steam Group</a>'
                    : NULL;
                $links = !empty($links)
                    ? implode(' || ', $links)
                    : 'None';
            }

            //Developer name and avatar
            {
                $developerAvatar = !empty($value['user_avatar'])
                    ? $value['user_avatar']
                    : $imageCDN . '/images/misc/steam/blank_avatar.jpg';
                $developerAvatar = '<img width="20" height="20" src="' . $developerAvatar . '" alt="Developer avatar" />';
                $developerLink = '<a target="_blank" href="http://steamcommunity.com/profiles/' . $modDetails[0]['steam_id64'] . '">' . $developerAvatar . '</a> <a class="nav-clickable" href="#s2__user?id=' . $modDetails[0]['steam_id64'] . '">' . $modDetails[0]['user_name'] . '</a>';
            }

            //Developer email
            {
                $developerEmail = '';
                if (!empty($_SESSION['user_id64'])) {
                    //if admin, show developer email too
                    $adminCheck = adminCheck($_SESSION['user_id64'], 'admin');
                    if (!empty($adminCheck)) {
                        $developerEmail = '<div class="row mod_info_panel">
                                <div class="col-sm-3"><strong>Developer Email</strong></div>
                                <div class="col-sm-9">';

                        if (!empty($modDetails[0]['user_email'])) {
                            $developerEmail .= $modDetails[0]['user_email'];
                        } else {
                            $developerEmail .= 'Developer has not given us it!';
                        }

                        $developerEmail .= '</div>
                            </div>';
                    }
                }
            }

            //Mod maps
            $modMaps = !empty($modDetails[0]['mod_maps'])
                ? implode(", ", json_decode($modDetails[0]['mod_maps'], 1))
                : 'unknown';

            //Status
            if (!empty($modDetails[0]['mod_rejected']) && !empty($modDetails[0]['mod_rejected_reason'])) {
                $modStatus = '<span class="boldRedText">Rejected:</span> ' . $modDetails[0]['mod_rejected_reason'];
            } else if ($modDetails[0]['mod_active'] == 1) {
                $modStatus = '<span class="boldGreenText">Accepted</span>';
            } else {
                $modStatus = '<span class="boldOrangeText">Pending Approval</span>';
            }

            //Mod Size
            {
                $modSize = !empty($modDetails[0]['mod_size'])
                    ? filesize_human_readable($modDetails[0]['mod_size'], 0, 'MB', true)
                    : NULL;

                $modSize = !empty($modSize)
                    ? $modSize['number'] . '<span class="db_link"> ' . $modSize['string'] . '</span>'
                    : '??<span class="db_link"> MB</span>';
            }
        }

        $result .= '<h2>' . $modNameLink . '</h2>';

        //FEATURE REQUEST
        $result .= '<div class="alert alert-danger"><strong>Help Wanted!</strong> We are re-designing every page. If there are features you would like to
                see on this page, please let us know by making a post per feature on this page\'s
                <a target="_blank" href="https://github.com/GetDotaStats/site/issues/162">issue</a>.</div>';

        //MOD INFO
        $result .= '<div class="container">';
        $result .= '<div class="col-sm-7">
                        <div class="row mod_info_panel">
                            <div class="col-sm-12 text-center">
                                <button class="btn btn-sm" data-toggle="collapse" data-target="#mod_info">Mod Info</button>
                            </div>
                        </div>
                    </div>';

        $result .= '<div id="mod_info" class="collapse col-sm-7">
                        <div class="row mod_info_panel">
                            <div class="col-sm-3"><strong>Status</strong></div>
                            <div class="col-sm-9">' . $modStatus . '</div>
                        </div>
                        <div class="row mod_info_panel">
                            <div class="col-sm-3"><strong>Links</strong></div>
                            <div class="col-sm-9">' . $links . '</div>
                        </div>
                        <div class="row mod_info_panel">
                            <div class="col-sm-3"><strong>Description</strong></div>
                            <div class="col-sm-9">' . $modDetails[0]['mod_description'] . '</div>
                        </div>
                        <div class="row mod_info_panel">
                            <div class="col-sm-3"><strong>Developer</strong></div>
                            <div class="col-sm-9">' . $developerLink . '</div>
                        </div>' . $developerEmail . '
                        <div class="row mod_info_panel">
                            <div class="col-sm-3"><strong>Maps</strong></div>
                            <div class="col-sm-9">' . $modMaps . '</div>
                        </div>
                        <div class="row mod_info_panel">
                            <div class="col-sm-3"><strong>Size</strong></div>
                            <div class="col-sm-9">' . $modSize . '</div>
                        </div>
                        <div class="row mod_info_panel">
                            <div class="col-sm-3"><strong>Total Games</strong></div>
                            <div class="col-sm-9">' . number_format($modDetails[0]['games_all_time']) . '</div>
                        </div>
                        <div class="row mod_info_panel">
                            <div class="col-sm-3"><strong>Games (Last Week)</strong></div>
                            <div class="col-sm-9">' . number_format($modDetails[0]['games_last_week']) . '</div>
                        </div>
                        <div class="row mod_info_panel">
                            <div class="col-sm-3"><strong>Updated</strong></div>
                            <div class="col-sm-9">' . relative_time_v3($modDetails[0]['workshop_updated']) . '</div>
                        </div>
                        <div class="row mod_info_panel">
                            <div class="col-sm-3"><strong>Added</strong></div>
                            <div class="col-sm-9">' . relative_time_v3($modDetails[0]['date_recorded']) . '</div>
                        </div>
                   </div>';
        $result .= '</div>';

        $result .= '<span class="h4">&nbsp;</span>';

        $result .= '<hr />';

        $result .= "<div class='row'>
                        <div class='col-sm-12 text-right'>
                            <a class='nav-clickable btn btn-info' href='#s2__mod?id={$modID}'>Num Games</a>
                            <a class='nav-clickable btn btn-info' href='#s2__mod_ws?id={$modID}'>Workshop</a>
                            <a class='nav-clickable btn btn-info' href='#s2__mod_f?id={$modID}'>Flags</a>
                            <a class='nav-clickable btn btn-info' href='#s2__mod_cgv?id={$modID}'>Game Values</a>
                            <a class='nav-clickable btn btn-info' href='#s2__mod_cpv?id={$modID}'>Player Values</a>
                            <a class='nav-clickable btn btn-info' href='#s2__mod_op?id={$modID}'>OP Combos</a>
                            <a class='nav-clickable btn btn-info' href='#s2__mod_rg?id={$modID}'>Recent Games</a>
                        </div>
                    </div>";

        $result .= '<hr />';

        return $result;
    }
}