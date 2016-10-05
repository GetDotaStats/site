<?php
require_once('./functions.php');
require_once('../../global_functions.php');
require_once('../../connections/parameters.php');

try {
    $s2_response = array();

    $memcached = new Cache(NULL, NULL, $localDev);

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    //$todayDay = date('j', time());
    //$todayMonth = date('n', time());
    //$todayYear = date('Y', time());

    if (empty($_GET['dr']) || !is_numeric($_GET['dr']) || ($_GET['dr'] != 7 && $_GET['dr'] != 14)) {
        throw new Exception('Invalid `date range`! Choose either 7 or 14.');
    }

    $dateRange = $_GET['dr'];

    $s2_response = $memcached->get('api_mod_list_' . $dateRange);
    if (empty($s2_response)) {
        $modList = $db->q(
            'SELECT
              ml.`mod_name`,
              cmm.`day`,
              cmm.`month`,
              cmm.`year`,
              cmm.`modID`,
              cmm.`gamePhase`,
              cmm.`gamesPlayed`,
              cmm.`dateRecorded`
          FROM `cache_mod_matches` cmm
          JOIN `mod_list` ml ON cmm.`modID` = ml.`mod_id`
          WHERE
            cmm.`dateRecorded` >= now() - INTERVAL ? DAY AND
            ml.`mod_active` = 1
          ORDER BY cmm.`dateRecorded`, cmm.`modID`, cmm.`gamePhase`;',
            's',
            array($dateRange)
        );
        if (empty($modList)) throw new Exception('No mod data to report on!');

        foreach ($modList as $key => $value) {
            if (empty($s2_response['data'][urlencode($value['mod_name'])][$value['dateRecorded']])) {
                $s2_response['data'][urlencode($value['mod_name'])][$value['dateRecorded']] = 0;
            }
            $s2_response['data'][urlencode($value['mod_name'])][$value['dateRecorded']] += $value['gamesPlayed'];
        }

        $s2_response['result'] = 1;
        $s2_response['version'] = $responseVersionModList;

        $memcached->set('api_mod_list_' . $dateRange, $s2_response, 300);
    }
} catch (Exception $e) {
    unset($s2_response);
    $s2_response['result'] = 0;
    $s2_response['error'] = 'Caught Exception: ' . $e->getMessage();
    $s2_response['version'] = $responseVersionModList;
} finally {
    if (isset($memcached)) $memcached->close();
    if (!isset($s2_response)) $s2_response = array('error' => 'Unknown exception');
}

try {
    header('Content-Type: application/json');
    echo utf8_encode(json_encode($s2_response));
} catch (Exception $e) {
    unset($s2_response);
    $s2_response['result'] = 0;
    $s2_response['error'] = 'Caught Exception: ' . $e->getMessage();
    echo utf8_encode(json_encode($s2_response));
}