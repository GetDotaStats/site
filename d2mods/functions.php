<?php
if (!class_exists('chart')) {
    class chart
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
         * load jsapi
         */
        private function initChart()
        {
            self::$_first = false;

            $output = '';
            // start a code block
            $output .= '<script type="text/javascript" src="https://www.google.com/jsapi"></script>' . "\n";
            $output .= '<script type="text/javascript">google.load(\'visualization\', \'1.0\', {\'packages\':[\'corechart\', \'table\']});</script>' . "\n";

            return $output;
        }

        /**
         * draws the chart
         */

        public function draw($div, Array $options = array(), $dataTable = false, Array $options_dataTable = array())
        {
            $output = '';

            if (self::$_first) $output .= $this->initChart();

            // start a code block
            $output .= '<script type="text/javascript">';

            // set callback function
            $output .= 'google.setOnLoadCallback(drawChart' . self::$_count . ');';

            // create callback function
            $output .= 'function drawChart' . self::$_count . '(){';

            $output .= 'var data = new google.visualization.DataTable(' . $this->_data . ');';

            // set the options
            $output .= 'var options = ' . json_encode($options) . ';';

            // create and draw the chart
            $output .= 'new google.visualization.' . $this->_chartType . '(document.getElementById(\'' . $div . '\')).draw(data, options);';

            if ($dataTable) {
                $output .= 'var optionsDataTable = ' . json_encode($options_dataTable) . ';';
                $output .= 'new google.visualization.Table(document.getElementById(\'' . $div . '_dataTable\')).draw(data, optionsDataTable);';
            }

            $output .= '} </script>' . "\n";
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

if (!function_exists("convert_id")) {
    function convert_steamid($id, $required_output = '32')
    {
        if (empty($id)) return false;

        if (strlen($id) === 17 && $required_output == '32') {
            $converted = substr($id, 3) - 61197960265728;
        } else if (strlen($id) != 17 && $required_output == '64') {
            $converted = '765' . ($id + 61197960265728);
        } else {
            $converted = '';
        }

        return (string)$converted;
    }
}

if (!class_exists('SteamID')) {
    class SteamID
    {
        private $steamID = '';
        private $steamID32 = '';
        private $steamID64 = '';

        public function __construct($steam_id)
        {
            if (empty($steam_id)) {
                //$this->steamID = $this->steamID64 = '';
            } elseif (ctype_digit($steam_id) && strlen($steam_id) === 17) {
                $this->steamID64 = $steam_id;
                $this->steamID32 = $this->convert64to32($steam_id);
                $this->steamID = $this->convert64toID($steam_id);
            } elseif (ctype_digit($steam_id) && strlen($steam_id) != 17) {
                $this->steamID64 = $this->convert32to64($steam_id);
                $this->steamID32 = $steam_id;
                $this->steamID = $this->convert32toID($steam_id);
            } elseif (preg_match('/^STEAM_0:[01]:[0-9]+/', $steam_id)) {
                $this->steamID64 = $this->convertIDto64($steam_id);
                $this->steamID32 = $this->convertIDto32($steam_id);
                $this->steamID = $steam_id;
            } else {
                throw new RuntimeException('Invalid data provided; data is not a valid steamID or steamID32 or steamID64');
            }
        }

        private function convert64to32($steam_id)
        {
            $steam_cid = substr($steam_id, 3) - 61197960265728;
            return $steam_cid;
        }

        private function convert32to64($steam_id)
        {
            $steam_cid = '765' . ($steam_id + 61197960265728);
            return $steam_cid;
        }

        private function convert32toID($steam_id)
        {
            $steam_cid = '765' . ($steam_id + 61197960265728);
            $steam_cid = $this->convert64toID($steam_cid);
            return $steam_cid;
        }

        private function convert64toID($steam_cid)
        {
            $id = array('STEAM_0');
            $id[1] = substr($steam_cid, -1, 1) % 2 == 0 ? 0 : 1;
            $id[2] = bcsub($steam_cid, '76561197960265728');
            if (bccomp($id[2], '0') != 1) {
                return false;
            }
            $id[2] = bcsub($id[2], $id[1]);
            list($id[2],) = explode('.', bcdiv($id[2], 2), 2);
            return implode(':', $id);
        }

        private function convertIDto64($steam_id)
        {
            list(, $m1, $m2) = explode(':', $steam_id, 3);
            list($steam_cid,) = explode('.', bcadd((((int)$m2 * 2) + $m1), '76561197960265728'), 2);
            return $steam_cid;
        }

        private function convertIDto32($steam_id)
        {
            list(, $m1, $m2) = explode(':', $steam_id, 3);
            list($steam_cid,) = explode('.', bcadd((((int)$m2 * 2) + $m1), '76561197960265728'), 2);
            $steam_cid = $this->convert64to32($steam_cid);
            return $steam_cid;
        }

        public function getSteamID()
        {
            return $this->steamID;
        }

        public function getsteamID32()
        {
            return $this->steamID32;
        }

        public function getSteamID64()
        {
            return $this->steamID64;
        }
    }
}