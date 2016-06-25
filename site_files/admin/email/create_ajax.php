<?php
try {
    require_once('../../connections/parameters.php');
    require_once('../../global_functions.php');

    if (!isset($_SESSION)) {
        session_start();
    }

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB `gds_site`!');

    $memcached = new Cache(NULL, NULL, $localDev);

    {//do auth stuff
        checkLogin_v2();
        if (empty($_SESSION['user_id64'])) throw new Exception('Not logged in!');

        $adminCheck = adminCheck($_SESSION['user_id64'], 'admin');
        if (empty($adminCheck)) throw new Exception('Do not have `admin` privileges!');

        $adminCheck = adminCheck($_SESSION['user_id64'], 'email');
        if (empty($adminCheck)) throw new Exception('Do not have `email` privileges!');
    }
    unset($db);

    //switch to email DB
    $db = new dbWrapper_v3($hostname_jimmydorry_email, $username_jimmydorry_email, $password_jimmydorry_email, $database_jimmydorry_email, true);
    if (empty($db)) throw new Exception('No DB `jimmydorry_email`!');

    if (empty($_POST['email_id'])) {
        throw new Exception('Missing or invalid `email_id`!');
    }

    if (empty($_POST['service_name'])) {
        throw new Exception('Missing or invalid `service_name`!');
    }

    if (empty($_POST['service_main_url'])) {
        throw new Exception('Missing or invalid `service_main_url`!');
    }

    $_POST['service_login_url'] = !empty($_POST['service_login_url'])
        ? $_POST['service_login_url']
        : NULL;

    $_POST['user_name'] = !empty($_POST['user_name'])
        ? $_POST['user_name']
        : NULL;

    $insertSQL = $db->q(
        'INSERT INTO `email_lookup`
              (
                `email_id`,
                `service_name`,
                `user_name`,
                `service_main_url`,
                `service_login_url`
              )
            VALUES (?, ?, ?, ?, ?);',
        'sssss',
        array(
            $_POST['email_id'],
            $_POST['service_name'],
            $_POST['user_name'],
            $_POST['service_main_url'],
            $_POST['service_login_url']
        )
    );

    if ($insertSQL) {
        $json_response['result'] = "Success! Email alias added to DB.";
    } else {
        throw new Exception('Email alias not added to DB! Likely a duplicate emailID.');
    }
} catch (Exception $e) {
    $json_response['error'] = 'Caught Exception: ' . $e->getMessage();
} finally {
    if (isset($memcached)) $memcached->close();
    if (!isset($json_response)) $json_response = array('error' => 'Unknown exception');
}

try {
    echo utf8_encode(json_encode($json_response));
} catch (Exception $e) {
    unset($json_response);
    $json_response['error'] = 'Caught Exception: ' . $e->getMessage();
    echo utf8_encode(json_encode($json_response));
}