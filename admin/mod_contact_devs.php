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

    echo '<h2>Contact Mod Devs</h2>';
    echo '<p>This is the admin section dedicated to contacting active mod developers.</p>';

    echo '<hr />';

    /////////////////////////////
    // ACTIVE MODS
    /////////////////////////////
    try {
        echo '<h3>Active Mods</h3>';

        $contactList = cached_query(
            'admin_contact_devs_present',
            'SELECT
                    ml.`mod_id`,
                    ml.`steam_id64`,
                    ml.`mod_identifier`,
                    ml.`mod_name`,
                    ml.`mod_description`,
                    ml.`mod_workshop_link`,
                    ml.`mod_steam_group`,

                    guo.`user_email`,
                    guo.`sub_dev_news`,
                    guo.`date_updated` AS date_email_updated,

                    gu.`user_name`,
                    gu.`user_avatar`
                FROM `mod_list` ml
                JOIN `gds_users_options` guo ON ml.`steam_id64` = guo.`user_id64`
                LEFT JOIN `gds_users` gu ON ml.`steam_id64` = gu.`user_id64`
                WHERE ml.`mod_active` = 1
                ORDER BY ml.`mod_id` DESC;'
        );

        if (empty($contactList)) throw new Exception('No contact details available for active mods!');

        echo '<div class="row">
                <div class="col-md-3"><strong>Mod</strong></div>
                <div class="col-md-4"><strong>Developer</strong></div>
                <div class="col-md-3"><strong>Email</strong></div>
                <div class="col-md-2"><strong>Updated</strong></div>
            </div>';

        echo '<span class="h4">&nbsp;</span>';

        $combinedEmails = array();
        foreach ($contactList as $key => $value) {
            $combinedEmails[] = $value['user_email'];

            $user_name_raw = !empty($value['user_name'])
                ? $value['user_name']
                : '???';
            $user_id_raw = $value['steam_id64'];
            $user_name = "<a class='nav-clickable' href='#s2__user?id={$user_id_raw}'>{$user_name_raw}</a>";

            $user_avatar_raw = !empty($value['user_avatar'])
                ? $value['user_avatar']
                : $CDN_image . '/images/misc/steam/blank_avatar.jpg';
            $user_avatar = "<img src='{$user_avatar_raw}' alt='user steamcommunity avatar' width='16' height='16' />";
            $user_avatar = "<a target='_blank' href='https://steamcommunity.com/profiles/{$user_id_raw}'>{$user_avatar}</a>";

            $developer = $user_avatar . ' ' . $user_name;

            $modName = $value['mod_name'];
            $userEmail = $value['user_email'];
            $dateEmailUpdated = relative_time_v3($value['date_email_updated']);

            echo "<div class='row'>
                <div class='col-md-3'>{$modName}</div>
                <div class='col-md-4'>{$developer}</div>
                <div class='col-md-3'>{$userEmail}</div>
                <div class='col-md-2 text-right'>{$dateEmailUpdated}</div>
            </div>";

            echo '<span class="h5">&nbsp;</span>';
        }

        $combinedEmails = implode('; ', $combinedEmails);

        echo "<div class='row'>
                <div class='col-md-12'><textarea class='formTextArea boxsizingBorder' rows='3'>$combinedEmails</textarea></div>
            </div>";

        echo '<span class="h4">&nbsp;</span>';
    } catch (Exception $e) {
        echo formatExceptionHandling($e);
    }

    echo '<hr />';

    /////////////////////////////
    // MISSING CONTACT DETAILS
    /////////////////////////////
    try {
        echo '<h3>Mods Missing Contact Details</h3>';

        $contactList = cached_query(
            'admin_contact_devs_missing',
            'SELECT
                    ml.`mod_id`,
                    ml.`steam_id64`,
                    ml.`mod_identifier`,
                    ml.`mod_name`,
                    ml.`mod_description`,
                    ml.`mod_workshop_link`,
                    ml.`mod_steam_group`,
                    ml.`date_recorded`,

                    gu.`user_name`,
                    gu.`user_avatar`
                FROM `mod_list` ml
                LEFT JOIN `gds_users` gu ON ml.`steam_id64` = gu.`user_id64`
                WHERE ml.`mod_active` = 1 AND ml.`mod_id` NOT IN (
                  SELECT
                        ml2.`mod_id`
                    FROM `mod_list` ml2
                    JOIN `gds_users_options` guo2 ON ml2.`steam_id64` = guo2.`user_id64`
                    WHERE ml2.`mod_active` = 1
                )
                ORDER BY ml.`mod_id` ASC;'
        );

        if (empty($contactList)) throw new Exception('No active mods missing contact details!');

        echo '<div class="row">
                <div class="col-md-3"><strong>Mod</strong></div>
                <div class="col-md-7"><strong>Developer</strong></div>
                <div class="col-md-2"><strong>Updated</strong></div>
            </div>';

        echo '<span class="h4">&nbsp;</span>';

        foreach ($contactList as $key => $value) {
            $user_name_raw = !empty($value['user_name'])
                ? $value['user_name']
                : '???';
            $user_id_raw = $value['steam_id64'];
            $user_name = "<a class='nav-clickable' href='#s2__user?id={$user_id_raw}'>{$user_name_raw}</a>";

            $user_avatar_raw = !empty($value['user_avatar'])
                ? $value['user_avatar']
                : $CDN_image . '/images/misc/steam/blank_avatar.jpg';
            $user_avatar = "<img src='{$user_avatar_raw}' alt='user steamcommunity avatar' width='16' height='16' />";
            $user_avatar = "<a target='_blank' href='https://steamcommunity.com/profiles/{$user_id_raw}'>{$user_avatar}</a>";

            $developer = $user_avatar . ' ' . $user_name;

            $modName = $value['mod_name'];
            $dateEmailUpdated = relative_time_v3($value['date_recorded']);

            echo "<div class='row'>
                <div class='col-md-3'>{$modName}</div>
                <div class='col-md-7'>{$developer}</div>
                <div class='col-md-2 text-right'>{$dateEmailUpdated}</div>
            </div>";

            echo '<span class="h5">&nbsp;</span>';
        }

        echo '<span class="h4">&nbsp;</span>';
    } catch (Exception $e) {
        echo formatExceptionHandling($e);
    }

    echo '<hr />';

    echo '<span class="h4">&nbsp;</span>';

} catch (Exception $e) {
    echo formatExceptionHandling($e);
} finally {
    if (isset($memcache)) $memcache->close();
}