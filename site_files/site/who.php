<?php
try {
    require_once('../global_functions.php');
    require_once('../connections/parameters.php');

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcached = new Cache(NULL, NULL, $localDev);

    echo '<h2>Who are we?</h2>';

    echo '<p>We are a stand-alone complex of individuals that form part of the original core of community custom games developers
    and infrastructure providers. We have been around since before custom games were facilitated by Valve. We have
    striven to make good content for the community to enjoy and make lives easier for future developers.</p>';

    echo '<hr />';

    $who = array(
        array(
            'roles' => array(
                'Chief Econometrics Engineer',
                'Principal Systems Architect',
                'Co-Founder',
            ),
            'users' => array('76561197989020883'), //jimmydorry
        ),
        array(
            'roles' => array(
                'Chief Lua Engineer',
                'Principal Client Technologies Architect',
                'Co-Founder',
            ),
            'users' => array('76561198039302883'), //Sinz
        ),
        array(
            'roles' => array(
                'Chief User Experience Engineer',
                'Principal User Communications Architect',
                'Knowledge Management Custodian',
                'Resident Games Design Expert',
                'Co-Founder',
            ),
            'users' => array('76561198029169398'), //BMD
        ),
        array(
            'roles' => array(
                'Head of Sustainability and Innovation',
                'Principal Design Architect',
                'Resident Games Design Expert',
                'Resident Memester',
            ),
            'users' => array('76561198046984233'), //Noya
        ),
        array(
            'roles' => array(
                'Head of Research and Technology Development',
                'Resident Games Design Expert',
                'Resident Memester',
                'Co-Founder',
            ),
            'users' => array('76561197988355984'), //Ash47
        ),
        array(
            'roles' => array(
                'Quality Assurance',
            ),
            'users' => array(
                '76561198046984233', //Noya
                '76561198000729788', //Myll
                '76561197996571267', //Ractidous
                '76561198052613450', //Kobb
            ),
        ),
        array(
            'roles' => array(
                'Translations',
            ),
            'users' => array(
                '76561198009765825', //Toyoka
                '76561198137607735', //Apacherus
            ),
        ),
        array(
            'roles' => array(
                'Site Logo',
            ),
            'users' => array(
                '76561198014930359', //KaNNis
            ),
        ),
        array(
            'roles' => array(
                'Dotabuff Extended',
            ),
            'users' => array(
                '76561198072059242', //gmilanche
            ),
        ),
        array(
            'roles' => array(
                'Channel Spammers',
            ),
            'users' => array(
                '76561198031929129', //Davoness
                '76561197996991248', //Ricochet
                '76561198016811380', //Crash of Rhinos
                '76561198032577270', //Amuse
                '76561198040813698', //critwhale
                '76561198016791926', //Enjay
                '76561197965495526', //Corgan
                '76561198027264543', //A_Dizzle
                '76561197994175600', //penguinwizzard
                '76561197984677271', //Rook
                '76561197971980579', //royawesome
                '76561198039944489', //Aderum
                '76561197975484185', //DrTeaSpoon
                '76561197993928301', //func_door
                '76561198087803933', //Hewdraw
                '76561198010795590', //hex6
                '76561198005952231', //Jexah
                '76561198015886976', //pizzalol
                '76561197996256872', //Zed`
                '76561198026598161', //DarkMio
            ),
        ),
        array(
            'roles' => array(
                'Stronk Comrades',
            ),
            'users' => array(
                '76561197972494985', //XPaw
                '76561197989222171', //RJ
                '76561198048900680', //manveru
                '76561197996755305', //underyx
                '76561198018549692', //Tea
                '76561197966822642', //AC-Town
                '76561198050444480', //Chauffer
            ),
        ),
    );

    if (empty($who)) throw new Exception('No users credited!');

    echo '<div class="row">
            <div class="col-md-5">
                <strong>Role(s)</strong>
            </div>
            <div class="col-md-4">
                <strong>Username</strong>
            </div>
            <div class="col-md-3">
                <strong>Contact</strong>
            </div>
        </div>';

    echo '<span class="h3">&nbsp;</span>';

    foreach ($who as $key => $value) {
        //Construct roles div
        {
            if (empty($value['roles'])) throw new Exception("No roles assigned for key #$key!");
            $roles = '<strong><small>';
            $roles .= implode('</small></strong><br/><strong><small>', $value['roles']);
            $roles .= '</small></strong>';
        }

        //Construct user contact div
        {
            if (empty($value['users'])) throw new Exception("No users assigned for key #$key!");
            $user_contact = "<a target='_blank' href='https://steamcommunity.com/profiles/";
            $user_contact .= implode("'><span class='glyphicon glyphicon-new-window'></span> Steam</a><br /><a target='_blank' href='https://steamcommunity.com/profiles/", $value['users']);
            $user_contact .= "'><span class='glyphicon glyphicon-new-window'></span> Steam</a>";
        }

        //Construct user name(s) div
        {
            $usernames = array();
            foreach ($value['users'] as $key2 => $value2) {
                $username_lookup = cached_query(
                    'site_who_userlookup' . $value2,
                    'SELECT
                            gu.`user_id64`,
                            gu.`user_id32`,
                            gu.`user_name`,
                            gu.`user_avatar`,
                            gu.`user_avatar_medium`,
                            gu.`user_avatar_large`,
                            gu.`date_recorded`
                        FROM `gds_users` gu
                        WHERE gu.`user_id64` = ?
                        LIMIT 0,1;',
                    's',
                    $value2,
                    5
                );

                if (!empty($username_lookup)) {
                    $user_avatar = "<img src='{$username_lookup[0]['user_avatar']}' alt='user steamcommunity avatar' width='16' height='16' />";
                    $user_name = "<a class='nav-clickable' href='#s2__user?id={$username_lookup[0]['user_id32']}'>{$username_lookup[0]['user_name']}</a>";

                    $usernames[] = $user_avatar . ' ' . $user_name;
                } else {
                    $usernames[] = '????';
                }
            }
            $users = implode('<br/>', $usernames);
        }

        echo "<div class='row'>
                <div class='col-md-5'>
                    <div>$roles</div>
                </div>
                <div class='col-md-4'>
                    <div>$users</div>
                </div>
                <div class='col-md-3'>
                    <div>$user_contact</div>
                </div>
            </div>";

        echo '<span class="h4">&nbsp;</span>';
    }


} catch (Exception $e) {
    echo formatExceptionHandling($e);
} finally {
    if (isset($memcached)) $memcached->close();
}