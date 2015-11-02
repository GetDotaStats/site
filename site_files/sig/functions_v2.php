<?php

if (!function_exists("get_account_details")) {
    function get_account_details($account_id = '28755155', $limit_result, $min_games, $flush, $cacheTimeHours)
    {
        global $db, $memcache;
        if (!isset($db)) throw new Exception('No DB defined!');
        if (!isset($memcache)) throw new Exception('No memcache defined!');

        $userDetails = cached_query(
            'sigs_db_user_details' . $account_id,
            'SELECT
                    `user_id32`,
                    `user_id64`,
                    `last_match`,
                    `account_win`,
                    `account_loss`,
                    `account_abandons`,
                    `account_percent`,
                    `winRateHeroes`,
                    `mostPlayedHeroes`,
                    `date_updated`,
                    `date_recorded`
                FROM `sigs_dotabuff_info`
                WHERE `user_id32` = ?
                LIMIT 0,1;',
            's',
            $account_id,
            1
        );

        if (!empty($userDetails)) {
            $hoursSinceUpdated = relative_time_v3($userDetails[0]['date_updated'], 1, 'hour', true);
            $hoursSinceUpdated = $hoursSinceUpdated['number'];

            if ($hoursSinceUpdated > $cacheTimeHours || $flush == 1) {
                $bigArray = getAndUpdateDBDetails($account_id, $limit_result, $min_games, $flush);
            } else {
                $bigArray = array(
                    'last_match' => $userDetails[0]['last_match'],
                    'account_win' => $userDetails[0]['account_win'],
                    'account_loss' => $userDetails[0]['account_loss'],
                    'account_abandons' => $userDetails[0]['account_abandons'],
                    'account_percent' => $userDetails[0]['account_percent'],
                    'winRateHeroes' => json_decode($userDetails[0]['winRateHeroes'], true),
                    'mostPlayedHeroes' => json_decode($userDetails[0]['mostPlayedHeroes'], true),
                );
            }
        } else {
            $bigArray = getAndUpdateDBDetails($account_id, $limit_result, $min_games, $flush);
        }

        return $bigArray;
    }
}

if (!function_exists("getAndUpdateDBDetails")) {
    function getAndUpdateDBDetails($account_id, $limit_result, $min_games, $flush)
    {
        global $db, $memcache;
        if (!isset($db)) throw new Exception('No DB defined!');
        if (!isset($memcache)) throw new Exception('No memcache defined!');

        $steamID = new SteamID($account_id);

        $sig_stats_winrate = get_account_char_winrate($account_id, $limit_result, $min_games, $flush);
        $sig_stats_most_played = get_account_char_mostplayed($account_id, $limit_result, $min_games, $flush);

        $bigArray = array_merge($sig_stats_winrate, $sig_stats_most_played);

        if (empty($bigArray)) throw new Exception('No data returned from Dotabuff');
        if (empty($bigArray['winRateHeroes']) || empty($bigArray['mostPlayedHeroes'])) throw new Exception('Not enough games played');


        $db->q(
            'INSERT INTO `sigs_dotabuff_info`
                (
                    `user_id32`,
                    `user_id64`,
                    `last_match`,
                    `account_win`,
                    `account_loss`,
                    `account_abandons`,
                    `account_percent`,
                    `winRateHeroes`,
                    `mostPlayedHeroes`,
                    `date_updated`
                )
                VALUES (?, ?, FROM_UNIXTIME(?), ?, ?, ?, ?, ?, ?, FROM_UNIXTIME(?))
                ON DUPLICATE KEY UPDATE
                    `last_match` = VALUES(last_match),
                    `account_win` = VALUES(account_win),
                    `account_loss` = VALUES(account_loss),
                    `account_abandons` = VALUES(account_abandons),
                    `account_percent` = VALUES(account_percent),
                    `winRateHeroes` = VALUES(winRateHeroes),
                    `mostPlayedHeroes` = VALUES(mostPlayedHeroes),
                    `date_updated` = VALUES(date_updated);',
            'sssiiissss',
            array(
                $steamID->getsteamID32(),
                $steamID->getSteamID64(),
                strtotime($bigArray['last_match']),
                $bigArray['account_win'],
                $bigArray['account_loss'],
                $bigArray['account_abandons'],
                $bigArray['account_percent'],
                json_encode($bigArray['winRateHeroes']),
                json_encode($bigArray['mostPlayedHeroes']),
                time()
            )
        );

        return $bigArray;
    }
}


if (!function_exists("get_account_char_winrate")) {
    function get_account_char_winrate($account_id = '28755155', $limit_result, $min_games, $flush)
    {
        global $db, $memcache;
        if (!isset($db)) throw new Exception('No DB defined!');
        if (!isset($memcache)) throw new Exception('No memcache defined!');

        if ($flush == 1) {
            $memcache->delete("d2_accountstats" . $account_id . '-' . $limit_result . '-' . $min_games . '-HighestWinRate');
        }

        $big_array = $memcache->get("d2_accountstats" . $account_id . '-' . $limit_result . '-' . $min_games . '-HighestWinRate');
        if (!$big_array) {
            $page = curl('http://dotabuff.com/players/' . $account_id . '/heroes?metric=winning&date=&game_mode=&match_type=real', NULL, NULL, NULL, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1', 10);

            if ($page == false) {
                throw new Exception('Could not reach Dotabuff');
            } else if (stristr($page, 'DOTABUFF - Not Found') || !$page) {
                throw new Exception('User does not exist or has not shared history');
            } else if (stristr($page, 'DOTABUFF - Too Many Requests')) {
                throw new Exception('GDS has been rate-limited');
            }

            $big_array = array();

            $big_array['user_pic'] = str_replace('full', 'medium', cut_str(cut_str($page, '<div class="image-container image-container-player image-container-bigavatar">', '</div>'), 'src="', '"'));

            //$big_array['username'] = cut_str($page, '<h1>', '<small>');
            $big_array['username'] = cut_str($page, '<img alt="', '"');

            $page_stats = cut_str($page, '<div class="header-content-secondary">', '<div class="header-content-interactive">');

            $page_stats = explode('<dl', $page_stats);

            $big_array['last_match'] = cut_str($page_stats[1], 'datetime="', '"');
            $big_array['account_win'] = str_replace(',', '', cut_str($page_stats[2], '<span class="wins">', '</span>'));
            $big_array['account_loss'] = str_replace(',', '', cut_str($page_stats[2], '<span class="losses">', '</span>'));
            $big_array['account_abandons'] = str_replace(',', '', cut_str($page_stats[2], '<span class="abandons">', '</span>'));
            $big_array['account_percent'] = cut_str($page_stats[3], '<dd>', '%');


            $page = cut_str($page, '<tbody>', '</tbody>');

            $page_array = explode('<tr', $page);
            empty($limit_result) ? $limit_result = count($page_array) : NULL;

            $i = 0;

            foreach ($page_array as $key => $value) {
                if ($key > 0 && $i < $limit_result) {
                    $page_array_test = explode('<td', $value);

                    $games_played = cut_str($page_array_test[4], 'data-value="', '"');

                    if ($games_played > $min_games) {
                        $big_array['winRateHeroes'][$i]['name'] = cut_str($page_array_test[1], 'data-value="', '"');
                        $big_array['winRateHeroes'][$i]['pic'] = cut_str($page_array_test[1], '<a href="/heroes/', '"');
                        $big_array['winRateHeroes'][$i]['winrate'] = cut_str($page_array_test[3], 'data-value="', '"');
                        $big_array['winRateHeroes'][$i]['gamesplayed'] = $games_played;

                        $i++;
                    }
                }
            }

            $memcache->set("d2_accountstats" . $account_id . '-' . $limit_result . '-' . $min_games . '-HighestWinRate', $big_array, 0, 60 * 60);
        }

        if (empty($big_array['username'])) {
            return false;
        } else {
            return $big_array;

        }
    }
}

if (!function_exists("get_account_char_mostplayed")) {
    function get_account_char_mostplayed($account_id = '28755155', $limit_result, $min_games, $flush)
    {
        global $db, $memcache;
        if (!isset($db)) throw new Exception('No DB defined!');
        if (!isset($memcache)) throw new Exception('No memcache defined!');

        if ($flush == 1) {
            $memcache->delete("d2_accountstats" . $account_id . '-' . $limit_result . '-MostPlayed');
        }

        $big_array = $memcache->get("d2_accountstats" . $account_id . '-' . $limit_result . '-MostPlayed');
        if (!$big_array) {
            $page = curl('http://dotabuff.com/players/' . $account_id . '/heroes?metric=played', NULL, NULL, NULL, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1', 10);

            if ($page == false) {
                throw new Exception('Could not reach Dotabuff');
            } else if (stristr($page, 'DOTABUFF - Not Found') || !$page) {
                throw new Exception('User does not exist or has not shared history');
            } else if (stristr($page, 'DOTABUFF - Too Many Requests')) {
                throw new Exception('GDS has been rate-limited');
            }

            $big_array = array();

            $big_array['user_pic'] = str_replace('full', 'medium', cut_str(cut_str($page, '<div class="image-container image-container-player image-container-bigavatar">', '</div>'), 'src="', '"'));

            //$big_array['username'] = cut_str($page, '<h1>', '<small>');
            $big_array['username'] = cut_str($page, '<img alt="', '"');

            $page_stats = cut_str($page, '<div class="header-content-secondary">', '<div class="header-content-interactive">');

            $page_stats = explode('<dl', $page_stats);

            $big_array['last_match'] = cut_str($page_stats[1], 'datetime="', '"');
            $big_array['account_win'] = str_replace(',', '', cut_str($page_stats[2], '<span class="wins">', '</span>'));
            $big_array['account_loss'] = str_replace(',', '', cut_str($page_stats[2], '<span class="losses">', '</span>'));
            $big_array['account_abandons'] = str_replace(',', '', cut_str($page_stats[2], '<span class="abandons">', '</span>'));
            $big_array['account_percent'] = cut_str($page_stats[3], '<dd>', '%');


            $page = cut_str($page, '<tbody>', '</tbody>');

            $page_array = explode('<tr', $page);
            empty($limit_result) ? $limit_result = count($page_array) : NULL;

            $i = 0;

            foreach ($page_array as $key => $value) {
                if ($key > 0 && $i < $limit_result) {
                    $page_array_test = explode('<td', $value);

                    $games_played = cut_str($page_array_test[3], 'data-value="', '"');

                    if ($games_played > $min_games) {
                        $big_array['mostPlayedHeroes'][$i]['name'] = cut_str($page_array_test[1], 'data-value="', '"');
                        $big_array['mostPlayedHeroes'][$i]['pic'] = cut_str($page_array_test[1], '<a href="/heroes/', '"');
                        $big_array['mostPlayedHeroes'][$i]['winrate'] = cut_str($page_array_test[4], 'data-value="', '"');
                        $big_array['mostPlayedHeroes'][$i]['gamesplayed'] = $games_played;

                        $i++;
                    }
                }
            }

            $memcache->set("d2_accountstats" . $account_id . '-' . $limit_result . '-MostPlayed', $big_array, 0, 60 * 60);
        }

        if (empty($big_array['username'])) {
            return false;
        } else {
            return $big_array;

        }
    }
}

if (!function_exists('LoadJPEG')) {
    function LoadJPEG($imgURL)
    {

        ##-- Get Image file from Port 80 --##
        $fp = fopen($imgURL, "r");
        $imageFile = fread($fp, 3000000);
        fclose($fp);

        ##-- Create a temporary file on disk --##
        $tmpfname = tempnam("/temp", "IMG");

        ##-- Put image data into the temp file --##
        $fp = fopen($tmpfname, "w");
        fwrite($fp, $imageFile);
        fclose($fp);

        ##-- Load Image from Disk with GD library --##
        $im = imagecreatefromjpeg($tmpfname);

        ##-- Delete Temporary File --##
        unlink($tmpfname);

        ##-- Check for errors --##
        if (!$im) {
            throw new Exception("Could not create JPEG image $imgURL");
        }

        return $im;
    }
}

if (!class_exists('chart2')) {
    class chart2
    {

        private static $_first = true;
        private static $_count = 0;

        private $_chartType;

        private $_data;
        private $_dataType;
        private $_skipFirstRow;

        /**
         * sets the chart type and updates the chart counter
         */
        public function __construct($chartType, $skipFirstRow = false)
        {
            $this->_chartType = $chartType;
            $this->_skipFirstRow = $skipFirstRow;
            self::$_count++;
        }

        /**
         * loads the dataset and converts it to the correct format
         */
        public function load($data, $dataType = 'json')
        {
            $this->_data = ($dataType != 'json') ? $this->dataToJson($data) : $data;
        }

        /**
         * draws the chart
         */

        public function draw($div, Array $options = array(), $dataTable = false, Array $options_dataTable = array(), $downloadCSV = false)
        {
            $output = '';

            // start a code block
            $output .= '<script type="text/javascript">';

            $output .= "var data = '';";

            // create callback function
            $output .= 'function drawChart' . self::$_count . '(){';

            $output .= 'data = new google.visualization.DataTable(' . $this->_data . ');';

            // set the options
            $output .= 'var options = ' . json_encode($options) . ';';

            // create and draw the chart
            $output .= 'new google.visualization.' . $this->_chartType . '(document.getElementById(\'' . $div . '\')).draw(data, options);';

            if ($dataTable) {
                $output .= 'var optionsDataTable = ' . json_encode($options_dataTable) . ';';
                $output .= 'new google.visualization.Table(document.getElementById(\'' . $div . '_dataTable\')).draw(data, optionsDataTable);';
            }

            $output .= '}';

            if ($downloadCSV) {
                $output .= "
                function downloadCSV(filename) {
                    jsonDataTable = data.toJSON();

                    var jsonObj = eval('(' + jsonDataTable + ')');
                    output = JSONObjtoCSV(jsonObj,filename);
                }

                function JSONObjtoCSV(jsonObj, filename){
                    filename = filename || 'download.csv';
                    var body = '';      var j = 0;
                    var columnObj = []; var columnLabel = []; var columnType = [];
                    var columnRole = [];    var outputLabel = []; var outputList = [];
                    for(var i=0; i<jsonObj.cols.length; i++){
                        columnObj = jsonObj.cols[i];
                        columnLabel[i] = columnObj.label;
                        columnType[i] = columnObj.type;
                        columnRole[i] = columnObj.role;

                        if (columnRole[i] == null) {
                            outputLabel[j] = '\"' + columnObj.label + '\"';
                            outputList[j] = i;
                            j++;
                        }
                    }

                    body += outputLabel.join(',') + String.fromCharCode(13);

                    for(var thisRow=0; thisRow<jsonObj.rows.length; thisRow++){
                        outputData = [];

                        for(var k=0; k<outputList.length; k++){
                            var thisColumn = outputList[k];
                            var thisType = columnType[thisColumn];
                            thisValue = jsonObj.rows[thisRow].c[thisColumn].v;

                            switch(thisType) {
                                case 'string':
                                    outputData[k] = '\"' + thisValue + '\"'; break;
                                case 'datetime':
                                    thisDateTime = eval('new ' + thisValue);
                                    outputData[k] = '\"' + thisDateTime.getDate() + '-' + (thisDateTime.getMonth()+1) + '-' + thisDateTime.getFullYear() + ' ' + thisDateTime.getHours() + ':' + thisDateTime.getMinutes() + ':' + thisDateTime.getSeconds() + '\"';
                                    delete window.thisDateTime;
                                    break;
                                default:
                                    outputData[k] = thisValue;
                            }
                        }

                        body += outputData.join(',');
                        body += String.fromCharCode(13);
                    }

                    uriContent = 'data:text/csv;filename='+filename+',' + encodeURIComponent(body);
                    newWindow=downloadWithName(uriContent, filename);
                    return(body);
                }

                function downloadWithName(uri, name) {
                    function eventFire(el, etype){
                        if (el.fireEvent) {
                            (el.fireEvent('on' + etype));
                        } else {
                            var evObj = document.createEvent('Events');
                            evObj.initEvent(etype, true, false);
                            el.dispatchEvent(evObj);
                        }
                    }

                    var link = document.createElement('a');
                    link.download = name;
                    link.href = uri;
                    eventFire(link, 'click');
                }";
            }

            $output .= '</script>' . "\n";

            $callbackoptions = urlencode('{"modules" : [ {"name" : "visualization", "version" : "1.0", "packages" : ["corechart", "table"], "callback" : "drawChart' . self::$_count . '"}]}');
            $output .= '<script type="text/javascript" src="//www.google.com/jsapi?autoload=' . $callbackoptions . '"></script>' . "\n";


            return $output;
        }

        /**
         * substracts the column names from the first and second row in the dataset
         */
        private function getColumns($data)
        {
            $cols = array();
            foreach ($data[0] as $key => $value) {
                if (is_numeric($key)) {
                    if (is_string($data[1][$key])) {
                        $cols[] = array('id' => '', 'label' => $value, 'type' => 'string');
                    } else {
                        $cols[] = array('id' => '', 'label' => $value, 'type' => 'number');
                    }
                    $this->_skipFirstRow = true;
                } else {
                    if (is_string($value)) {
                        $cols[] = array('id' => '', 'label' => $key, 'type' => 'string');
                    } else {
                        $cols[] = array('id' => '', 'label' => $key, 'type' => 'number');
                    }
                }
            }
            return $cols;
        }

        /**
         * convert array data to json
         * info: http://code.google.com/intl/nl-NL/apis/chart/interactive/docs/datatables_dataviews.html#javascriptliteral
         */
        private function dataToJson($data)
        {
            $cols = $this->getColumns($data);

            $rows = array();
            foreach ($data as $key => $row) {
                if ($key != 0 || !$this->_skipFirstRow) {
                    $c = array();
                    foreach ($row as $v) {
                        $c[] = array('v' => $v);
                    }
                    $rows[] = array('c' => $c);
                }
            }

            return json_encode(array('cols' => $cols, 'rows' => $rows));
        }

    }
}