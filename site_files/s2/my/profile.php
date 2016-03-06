<?php
require_once('../../global_functions.php');
require_once('../../connections/parameters.php');

try {
    if (!isset($_SESSION)) {
        session_start();
    }

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcached = new Cache(NULL, NULL, $localDev);

    checkLogin_v2();
    if (empty($_SESSION['user_id64'])) throw new Exception('Not logged in!');

    $userID64 = $_SESSION['user_id64'];

    $userOptions = cached_query(
        's2_my_options_' . $userID64,
        'SELECT
                guo.`user_id32`,
                guo.`user_id64`,
                guo.`user_email`,
                guo.`sub_dev_news`,
                guo.`mmr_public`,
                guo.`date_updated`,
                guo.`date_recorded`
            FROM `gds_users_options` guo
            LEFT JOIN `gds_users` gu ON guo.`user_id64` = gu.`user_id64`
            WHERE guo.`user_id64` = ?
            LIMIT 0,1;',
        's',
        $userID64,
        5
    );

    echo '<div class="page-header"><h2>My Profile</h2></div>';

    echo '<span class="h4">&nbsp;</span>';

    try {
        if (empty($userOptions)) {
            throw new Exception('User has not stored any options!');
        }
    } catch (Exception $e) {
        echo formatExceptionHandling($e);
    }

    echo '<form id="userProfileOptions">';
    echo '<div class="container">';
    {
        $emailTooltip = 'Giving us your email address allows us to occasionally get in contact with you. We will not share this address with anyone, and promise not to spam it.';
        $subModDev = 'We may need to get in contact with you regarding your mod or the stat-collection library. We promise not to spam you!';
        $MMRPublic = 'We allowed users to opt-in to tracking their MMR via the Lobby Explorer. Do you want to allow this data to be displayed publicly?';

        $emailAddress = !empty($userOptions[0]['user_email'])
            ? 'value=\'' . $userOptions[0]['user_email'] . '\''
            : '';

        echo "<div class='row'>
                    <div class='col-md-2'><span class='glyphicon glyphicon-question-sign' title='{$emailTooltip}'></span> Email</div>
                    <div class='col-md-4'>
                        <input name='user_email' class='formTextArea boxsizingBorder' type='text' maxlength='100' size='45' placeholder='example@mail.com' {$emailAddress}>
                    </div>
                </div>";

        echo '<span class="h5">&nbsp;</span>';

        echo "<div class='row'>
                    <div class='col-md-2'><span class='glyphicon glyphicon-question-sign' title='{$subModDev}'></span> Dev News</div>
                    <div class='col-md-4'>
                        <input name='sub_dev_news' type='radio' value='1'" . (!empty($userOptions[0]['sub_dev_news']) ? ' checked' : '') . ">Yes<br />
                        <input name='sub_dev_news' type='radio' value='0'" . (!empty($userOptions[0]['sub_dev_news']) ? '' : ' checked') . ">No
                    </div>
                </div>";

        echo '<span class="h5">&nbsp;</span>';

        echo "<div class='row'>
                    <div class='col-md-2'><span class='glyphicon glyphicon-question-sign' title='{$MMRPublic}'></span> MMR Public</div>
                    <div class='col-md-4'>
                        <input name='mmr_public' type='radio' value='1'" . (!empty($userOptions[0]['mmr_public']) ? ' checked' : '') . ">Yes<br />
                        <input name='mmr_public' type='radio' value='0'" . (!empty($userOptions[0]['mmr_public']) ? '' : ' checked') . ">No
                    </div>
                </div>";

        echo '<span class="h5">&nbsp;</span>';

        echo '<div class="row">
                    <div class="col-md-6 text-center">
                        <button>Submit</button>
                    </div>
                </div>';

        echo '<span class="h5">&nbsp;</span>';
    }
    echo '</div>';
    echo '</form>';

    echo '<div id="AJAXresult_container" class="row">
                <div class="col-md-6">
                    <span id="AJAXresult" class="labelWarnings label label-danger"></span>
                </div>
            </div>';

    echo '<script type="application/javascript">
                $("#userProfileOptions").submit(function (event) {
                    event.preventDefault();
                    $.post("./s2/my/profile_ajax.php", $("#userProfileOptions").serialize(), function (data) {
                        try {
                            if(data){
                                var response = JSON.parse(data);
                                if(response && response.error){
                                    $("#AJAXresult").html(response.error);
                                }
                                else if(response && response.result){
                                    loadPage("#s2__my__profile",0);
                                }
                                else{
                                    $("#AJAXresult").html(data);
                                }
                            }
                        } catch(err) {
                            $("#AJAXresult").html("Parsing Error: " + err.message + "<br />" + data);
                        }
                    }, "text");
                });
            </script>';


    echo '<hr />';

    echo '<span class="h4">&nbsp;</span>';

    echo '<div class="text-center">
            <a class="nav-clickable btn btn-default btn-lg" href="#s2__my__mods">My Mods</a>
        </div>';

    echo '<span class="h4">&nbsp;</span>';
} catch (Exception $e) {
    echo formatExceptionHandling($e);
} finally {
    if (isset($memcached)) $memcached->close();
}