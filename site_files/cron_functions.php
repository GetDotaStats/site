<?php
require_once(dirname(__FILE__) . '/connections/parameters.php');
require_once(dirname(__FILE__) . '/global_functions.php');

if (!class_exists('cron_task')) {
    class cron_task
    {
        protected $db = null;
        protected $memcached = null;
        protected $isLocal = null;
        protected $allowWebhooks = null;
        protected $runningWindows = null;
        protected $behindProxy = null;
        protected $webHook = null;
        protected $webAPIkey = null;
        protected $taskID = null;
        protected $taskName = null;
        protected $timeStart = null;
        protected $timeEnd = null;
        protected $logFileTimeStart = null;

        public function __construct($db, $memcached, bool $isLocal, bool $allowWebhooks, bool $runningWindows, bool $behindProxy, string $webHook, string $webAPIkey, $actualStartUnixTimestamp = NULL)
        {
            $this->logFileTimeStart = isset($actualStartUnixTimestamp) && is_numeric($actualStartUnixTimestamp)
                ? date('Y-m-d_H-i-s', $actualStartUnixTimestamp)
                : date('Y-m-d_H-i-s');

            if (empty($db)) throw new Exception('No DB connection provided!');
            if (empty($memcached)) throw new Exception('No memcached connection provided!');
            if (!is_bool($isLocal)) throw new Exception('Variable `isLocal` invalid!');
            if (!is_bool($allowWebhooks)) throw new Exception('Variable `allowWebhooks` invalid!');
            if (!is_bool($runningWindows)) throw new Exception('Variable `runningWindows` invalid!');
            if (!is_bool($behindProxy)) throw new Exception('Variable `behindProxy` invalid!');
            if (filter_var($webHook, FILTER_VALIDATE_URL) === FALSE) throw new Exception('Invalid URL provided!');
            if (empty($webAPIkey)) throw new Exception('No webAPI key provided!');

            $this->db = $db;
            $this->memcached = $memcached;
            $this->isLocal = $isLocal;
            $this->allowWebhooks = $allowWebhooks;
            $this->runningWindows = $runningWindows;
            $this->behindProxy = $behindProxy;
            $this->webHook = $webHook;
            $this->webAPIkey = $webAPIkey;
        }

        protected function task_queue(
            string $taskName,
            string $taskGroup = NULL,
            array $taskParameters = NULL,
            int $taskPriority = 1,
            int $taskBlocking = 1,
            int $taskUser = NULL
        )
        {
            if (empty($taskName)) throw new Exception("Task name missing in queue request!");
            if (empty($taskGroup)) $taskGroup = $taskName;

            if (!empty($taskParameters)) {
                if (!is_array($taskParameters)) throw new Exception("Task parameters not given as an array!");
                $taskParameters = json_encode($taskParameters);
            }

            $this->db->q("INSERT INTO `cron_tasks`
                  (
                      `cron_task`,
                      `cron_task_group`,
                      `cron_parameters`,
                      `cron_priority`,
                      `cron_blocking`,
                      `cron_user`
                  )
                VALUES (?, ?, ?, ?, ?, ?);",
                'sssiii',
                array(
                    $taskName,
                    $taskGroup,
                    $taskParameters,
                    $taskPriority,
                    $taskBlocking,
                    $taskUser
                )
            );
        }

        protected function task_validate(int $taskID, string $taskName): bool
        {
            if (empty($taskID) || !is_numeric($taskID)) throw new Exception("Invalid TaskID!");
            if (empty($taskName)) throw new Exception("Variable `taskName` missing!");

            $taskSQL = $this->db->q("SELECT `cron_id` FROM `cron_tasks` WHERE `cron_id` = ? AND `cron_task` = ? LIMIT 0,1;",
                'is',
                array($taskID, $taskName)
            );

            if (!empty($taskSQL)) {
                return true;
            } else {
                return false;
            }
        }

        protected function task_update_status(int $taskID, int $taskStatus, int $taskDuration = NULL, $taskNotes = array())
        {
            if (empty($taskID) || !is_numeric($taskID)) throw new Exception("Invalid TaskID!");
            if (!isset($taskStatus) || !is_numeric($taskStatus) || $taskStatus < 0 || $taskStatus > 3) throw new Exception("Invalid Task Status!");
            if (isset($taskDuration) && !is_numeric($taskDuration)) throw new Exception("Invalid Task Duration!");
            if (!empty($taskNotes) && is_array($taskNotes)) {
                $taskNotes = json_encode($taskNotes);
            } else {
                $taskNotes = null;
            }

            $this->db->q("UPDATE `cron_tasks` SET `cron_status` = ?, `cron_duration` = ?, `cron_notes` = ? WHERE `cron_id` = ?;",
                'iisi',
                array(
                    $taskStatus,
                    $taskDuration,
                    $taskNotes,
                    $taskID,
                )
            );
        }

        protected function report_execution_stats(
            string $taskShortGroupName,
            string $taskShortID = NULL,
            string $taskName,

            int $durationValue,
            int $durationMin,
            float $durationMaxGrowth,

            int $report1Value,
            int $report1Min,
            float $report1MaxGrowth,
            string $report1Units,

            int $report2Value = NULL,
            int $report2Min = NULL,
            float $report2MaxGrowth = NULL,
            string $report2Units = NULL,

            int $report3Value = NULL,
            int $report3Min = NULL,
            float $report3MaxGrowth = NULL,
            string $report3Units = NULL
        )
        {
            if (!isset($durationValue) || !is_numeric($durationValue)) throw new Exception('Invalid duration given!');

            echo "<br />Task ran for {$durationValue} seconds<br />";

            $serviceReport = new serviceReporting($this->db);

            try {
                {
                    $durationArray = array();
                    isset($durationValue)
                        ? $durationArray['value'] = $durationValue
                        : NULL;
                    isset($durationMin)
                        ? $durationArray['min'] = $durationMin
                        : NULL;
                    !empty($durationMaxGrowth)
                        ? $durationArray['growth'] = $durationMaxGrowth
                        : NULL;
                    if (
                        empty($durationArray) ||
                        !isset($durationArray['value']) ||
                        !isset($durationArray['min']) ||
                        !isset($durationArray['growth'])
                    ) throw new Exception('Invalid duration parameters given!');
                }

                {
                    $report1Array = array();
                    isset($report1Value)
                        ? $report1Array['value'] = $report1Value
                        : NULL;
                    isset($report1Min)
                        ? $report1Array['min'] = $report1Min
                        : NULL;
                    !empty($report1MaxGrowth)
                        ? $report1Array['growth'] = $report1MaxGrowth
                        : NULL;
                    !empty($report1Units)
                        ? $report1Array['unit'] = $report1Units
                        : NULL;
                    if (
                        empty($report1Array) ||
                        !isset($report1Array['value']) ||
                        !isset($report1Array['min']) ||
                        !isset($report1Array['growth'])
                    ) throw new Exception('Invalid Report Array #1 parameters given!');
                }

                {
                    $report2Array = array();
                    isset($report2Value)
                        ? $report2Array['value'] = $report2Value
                        : NULL;
                    isset($report2Min)
                        ? $report2Array['min'] = $report2Min
                        : NULL;
                    !empty($report2MaxGrowth)
                        ? $report2Array['growth'] = $report2MaxGrowth
                        : NULL;
                    !empty($report2Units)
                        ? $report2Array['unit'] = $report2Units
                        : NULL;
                    if (
                        empty($report2Array) ||
                        !isset($report2Array['value']) ||
                        !isset($report2Array['min']) ||
                        !isset($report2Array['growth'])
                    ) $report2Array = NULL;
                }

                {
                    $report3Array = array();
                    isset($report3Value)
                        ? $report3Array['value'] = $report3Value
                        : NULL;
                    isset($report3Min)
                        ? $report3Array['min'] = $report3Min
                        : NULL;
                    !empty($report3MaxGrowth)
                        ? $report3Array['growth'] = $report3MaxGrowth
                        : NULL;
                    !empty($report3Units)
                        ? $report3Array['unit'] = $report3Units
                        : NULL;
                    if (
                        empty($report3Array) ||
                        !isset($report3Array['value']) ||
                        !isset($report3Array['min']) ||
                        !isset($report3Array['growth'])
                    ) $report3Array = NULL;
                }

                $serviceReport->logAndCompareOld(
                    $taskName,
                    $durationArray,
                    $report1Array,
                    $report2Array,
                    $report3Array,
                    FALSE
                );
            } catch (Exception $e) {
                echo '<br />Caught Exception (MAIN) -- ' . $e->getFile() . ':' . $e->getLine() . '<br />' . $e->getMessage() . '<br /><br />';

                //WEBHOOK
                {
                    if ($this->allowWebhooks) {
                        $irc_message = new irc_message($this->webHook);

                        $message = array();

                        $message[] = array(
                            $irc_message->colour_generator('red'),
                            '[CRON]',
                            $irc_message->colour_generator(NULL),
                        );

                        $message[] = array(
                            $irc_message->colour_generator('green'),
                            '[' . $taskShortGroupName . ']',
                            $irc_message->colour_generator(NULL),
                        );

                        if (!empty($taskShortID)) {
                            $message[] = array(
                                $irc_message->colour_generator('orange'),
                                '[' . $taskShortID . ']',
                                $irc_message->colour_generator(NULL),
                            );
                        }

                        $message[] = array(
                            $irc_message->colour_generator('bold'),
                            $irc_message->colour_generator('blue'),
                            'Warning:',
                            $irc_message->colour_generator(NULL),
                            $irc_message->colour_generator('bold'),
                        );

                        $message[] = array($e->getMessage() . ' ||');

                        $message[] = array('http://getdotastats.com/s2/routine/logs/log_cron_' . $this->logFileTimeStart . '.html');


                        $message = $irc_message->combine_message($message);
                        $irc_message->post_message($message, array('localDev' => false));
                    }
                }
            }
        }

    }
}

if (!class_exists('cron_highscores')) {
    class cron_highscores extends cron_task
    {
        private $numPlayersPerLeaderboard = 51;
        private $modID = null;
        private $modName = null;
        private $highscoreID = null;
        private $highscoreName = null;
        private $highscoreObjective = 'max';
        private $numDeletes = 0;

        public function execute($taskID, $taskName, $taskParameters)
        {
            try {
                echo '<h2>Highscores</h2>';

                $this->timeStart = time();
                $this->taskID = $taskID;
                $this->taskName = $taskName;

                if (!$this->task_validate($this->taskID, $this->taskName)) throw new Exception("Invalid task specified!");

                $this->parse_parameters($taskParameters);

                $this->task_update_status($this->taskID, 1);

                echo "<h4>{$this->modName} <small>{$this->highscoreName}</small></h4>";

                echo "Getting leaderboard cut-off!<br />";
                $lowestLeaderboardValue = $this->get_minimum_leaderboard_value();
                echo "Clearing leaderboard of values below cut-off!<br />";
                $this->clear_low_leaderboard_values($lowestLeaderboardValue);
            } catch (Exception $e) {
                echo '<br />Caught Exception (CRON) -- ' . basename($e->getFile()) . ':' . $e->getLine() . '<br />' . $e->getMessage() . '<br /><br />';
            } finally {
                $this->timeEnd = time();
                $totalRunTime = $this->timeEnd - $this->timeStart;
                $this->task_update_status($this->taskID, 2, $totalRunTime);

                $this->report_execution_stats(
                    'HIGHSCORES',
                    $this->modName . ' -- ' . $this->highscoreName,
                    'cron_highscores__' . $this->modID . '_' . $this->highscoreID,
                    $totalRunTime, 10, 0.5,
                    $this->numDeletes, 100, 0.1, 'highscores dropped'
                );
            }
        }

        public function queue(int $taskPriority = 2, int $numPlayersPerLeaderboard = 51, int $modID = null, string $modName = null, int $highscoreID = null, string $highscoreName = null, string $highscoreObjective = null, $userID = NULL)
        {
            //If we called this function with a specific modID we can send it straight into the queue
            //otherwise we will call this function for every non-rejected modID
            $numPlayersPerLeaderboard = !empty($numPlayersPerLeaderboard) && is_numeric($numPlayersPerLeaderboard)
                ? $numPlayersPerLeaderboard
                : 51;

            if (!empty($modID)) {
                if (!is_numeric($modID)) throw new Exception("Invalid modID!");
                if (!isset($modName)) throw new Exception("Invalid modName!");
                if (!isset($highscoreID) || !is_numeric($highscoreID)) throw new Exception("Invalid highscoreID provided!");
                if (!isset($highscoreName)) throw new Exception("Invalid highscoreName!");
                if (!isset($highscoreObjective) || ($highscoreObjective != 'max' && $highscoreObjective != 'min')) throw new Exception("Invalid highscoreObjective!");
                if (isset($userID) && !is_numeric($userID)) throw new Exception("Invalid userID!");

                $this->task_queue(
                    'cron_highscores__' . $modID . '_' . $highscoreID,
                    'cron_highscores',
                    array(
                        'numPlayersPerLeaderboard' => $numPlayersPerLeaderboard,
                        'modID' => $modID,
                        'modName' => $modName,
                        'highscoreID' => $highscoreID,
                        'highscoreName' => $highscoreName,
                        'highscoreObjective' => $highscoreObjective
                    ),
                    $taskPriority,
                    1,
                    $userID
                );
            } else {
                $schemaList = cached_query(
                    'cron_highscore_schema_list',
                    'SELECT
                            shms.`highscoreID`,
                            shms.`highscoreIdentifier`,
                            shms.`modID`,
                            shms.`modIdentifier`,
                            shms.`secureWithAuth`,
                            shms.`highscoreName`,
                            shms.`highscoreDescription`,
                            shms.`highscoreActive`,
                            shms.`highscoreObjective`,
                            shms.`highscoreOperator`,
                            shms.`highscoreFactor`,
                            shms.`highscoreDecimals`,
                            shms.`date_recorded`,

                            ml.`mod_name`
                        FROM `stat_highscore_mods_schema` shms
                        JOIN `mod_list` ml ON shms.`modID` = ml.`mod_id`;'
                );
                if (empty($schemaList)) throw new Exception("No highscore schemas to clean highscores of!");

                foreach ($schemaList as $key => $value) {
                    $this->queue(
                        $taskPriority,
                        $numPlayersPerLeaderboard,
                        $value['modID'],
                        $value['mod_name'],
                        $value['highscoreID'],
                        $value['highscoreName'],
                        $value['highscoreObjective']
                    );
                }
            }
        }

        private function parse_parameters(string $taskParameters)
        {
            $taskParameters = json_decode($taskParameters, true);

            if (!empty($taskParameters['numPlayersPerLeaderboard']) && is_numeric($taskParameters['numPlayersPerLeaderboard'])) {
                $this->numPlayersPerLeaderboard = $taskParameters['numPlayersPerLeaderboard'];
            } else {
                throw new Exception('Invalid numPlayersPerLeaderboard parsed!');
            }

            if (!empty($taskParameters['modID']) && is_numeric($taskParameters['modID'])) {
                $this->modID = $taskParameters['modID'];
            } else {
                throw new Exception('Invalid modID parsed!');
            }

            if (!empty($taskParameters['highscoreID']) && is_numeric($taskParameters['highscoreID'])) {
                $this->highscoreID = $taskParameters['highscoreID'];
            } else {
                throw new Exception('Invalid highscoreID parsed!');
            }

            if (!empty($taskParameters['modName'])) {
                $this->modName = $taskParameters['modName'];
            } else {
                throw new Exception('Invalid modName parsed!');
            }

            if (!empty($taskParameters['highscoreName'])) {
                $this->highscoreName = $taskParameters['highscoreName'];
            } else {
                throw new Exception('Invalid highscoreName parsed!');
            }

            if (!empty($taskParameters['highscoreObjective'])) {
                $this->highscoreObjective = $taskParameters['highscoreObjective'];
            } else {
                throw new Exception('Invalid highscoreObjective parsed!');
            }
        }

        private function get_minimum_leaderboard_value(): int
        {
            if ($this->highscoreObjective == 'min') {
                $findPositionOfLast = $this->db->q(
                    "SELECT
                                `modID`,
                                `highscoreID`,
                                `steamID32`,
                                `steamID64`,
                                `highscoreAuthKey`,
                                `userName`,
                                `highscoreValue`,
                                `date_recorded`
                            FROM `stat_highscore_mods`
                            WHERE `modID` = ? AND `highscoreID` = ?
                            ORDER BY `highscoreValue` ASC
                            LIMIT {$this->numPlayersPerLeaderboard},1;",
                    'ii',
                    array($this->modID, $this->highscoreID)
                );
            } else {
                $findPositionOfLast = $this->db->q(
                    "SELECT
                                `modID`,
                                `highscoreID`,
                                `steamID32`,
                                `steamID64`,
                                `highscoreAuthKey`,
                                `userName`,
                                `highscoreValue`,
                                `date_recorded`
                            FROM `stat_highscore_mods`
                            WHERE `modID` = ? AND `highscoreID` = ?
                            ORDER BY `highscoreValue` DESC
                            LIMIT {$this->numPlayersPerLeaderboard},1;",
                    'ii',
                    array($this->modID, $this->highscoreID)
                );
            }

            if (empty($findPositionOfLast)) throw new Exception('Not enough entries in leaderboard to cull!');

            if (!empty($findPositionOfLast[0]['highscoreValue']) && is_numeric($findPositionOfLast[0]['highscoreValue'])) {
                return $findPositionOfLast[0]['highscoreValue'];
            } else {
                throw new Exception("Un-able to cull leaderboard. Leaderboard either too empty or value at bottom is not numeric!");
            }
        }

        private function clear_low_leaderboard_values(int $lowestLeaderboardValue)
        {
            if ($this->highscoreObjective == 'min') {
                $SQLdelete = $this->db->q(
                    'DELETE FROM `stat_highscore_mods_top` WHERE `highscoreValue` >= ?;',
                    'i',
                    array($lowestLeaderboardValue)
                );
            } else if ($this->highscoreObjective == 'max') {
                $SQLdelete = $this->db->q(
                    'DELETE FROM `stat_highscore_mods_top` WHERE `highscoreValue` <= ?;',
                    'i',
                    array($lowestLeaderboardValue)
                );
            } else {
                throw new Exception("Invalid highscoreObjective! Aborted deletion!");
            }

            $SQLdelete = is_numeric($SQLdelete)
                ? $SQLdelete
                : 0;

            $this->numDeletes += $SQLdelete;
        }
    }
}

if (!class_exists('cron_workshop')) {
    class cron_workshop extends cron_task
    {
        private $modID = null;
        private $modIdentifier = null;
        private $modName = null;
        private $workshopID = null;
        private $numWorkshopSuccess = 0;
        private $numWorkshopFailure = 0;
        private $numWorkshopUnknown = 0;

        public function execute($taskID, $taskName, $taskParameters)
        {
            try {
                echo '<h2>Workshop Scraping</h2>';

                $this->timeStart = time();
                $this->taskID = $taskID;
                $this->taskName = $taskName;

                if (!$this->task_validate($this->taskID, $this->taskName)) throw new Exception("Invalid task specified!");

                $this->parse_parameters($taskParameters);

                echo "Mod: {$this->modName}<br />";
                echo "Mod ID: <a target='_blank' href='//getdotastats.com/#s2__mod?id={$this->modID}'>{$this->modID}</a><br />";
                echo "Workshop ID: <a target='_blank' href='//steamcommunity.com/sharedfiles/filedetails/?id={$this->workshopID}'>{$this->workshopID}</a><br />";

                $this->task_update_status($this->taskID, 1);

                echo "Grabbing mod data from workshop!<br />";
                $modDetails = $this->get_mod_info_from_api($this->workshopID, $this->webAPIkey);
                if ($modDetails) {
                    //download that mod picture
                    try {
                        echo "Grabbing mod display pictures!<br />";
                        $this->get_mod_display_picture($modDetails['response']['publishedfiledetails'][0]['preview_url'], dirname(__FILE__) . '/images/mods/thumbs/', $this->modID . '.png', $this->behindProxy);
                    } catch (Exception $e) {
                        echo '<br />' . $e->getMessage() . '<br /><br />';
                    }

                    echo "Updating cache of mod details!<br />";
                    $this->set_workshop_details($modDetails);
                }
            } catch (Exception $e) {
                echo '<br />Caught Exception (CRON) -- ' . basename($e->getFile()) . ':' . $e->getLine() . '<br />' . $e->getMessage() . '<br /><br />';
            } finally {
                $this->timeEnd = time();
                $totalRunTime = $this->timeEnd - $this->timeStart;
                $this->task_update_status($this->taskID, 2, $totalRunTime);

                $this->report_execution_stats(
                    'WORKSHOP',
                    $this->modName,
                    's2_cron_workshop_scrape_' . $this->modID,
                    $totalRunTime, 30, 0.5,
                    $this->numWorkshopSuccess, 1, 0.01, 'successful scrapes',
                    $this->numWorkshopFailure, 1, 0.01, 'failed scrapes',
                    $this->numWorkshopUnknown, 1, 0.01, 'unknown scrapes'
                );
            }
        }

        public function queue(int $taskPriority = 2, int $modID = NULL, string $modIdentifier = NULL, int $workshopID = NULL, string $modName = null, $userID = NULL)
        {
            //If we called this function with a specific modID we can send it straight into the queue
            //otherwise we will call this function for every non-rejected modID
            if (!empty($modID)) {
                if (!is_numeric($modID)) throw new Exception("Invalid modID!");
                if (!isset($modIdentifier)) throw new Exception("Invalid modIdentifier!");
                if (!isset($workshopID) || !is_numeric($workshopID)) throw new Exception("Invalid workshop ID provided!");
                if (isset($userID) && !is_numeric($userID)) throw new Exception("Invalid userID!");
                if (empty($modName)) throw new Exception("Invalid `modName`!");

                $this->task_queue(
                    'cron_workshop__' . $modID,
                    'cron_workshop',
                    array(
                        'modID' => $modID,
                        'modIdentifier' => $modIdentifier,
                        'workshopID' => $workshopID,
                        'modName' => $modName
                    ),
                    $taskPriority,
                    1,
                    $userID
                );
            } else {
                $modList = $this->db->q(
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
                if (empty($modList)) throw new Exception("No non-rejected mods to scrape workshop for!");

                foreach ($modList as $key => $value) {
                    $this->queue(
                        $taskPriority,
                        $value['mod_id'],
                        $value['mod_identifier'],
                        $value['mod_workshop_link'],
                        $value['mod_name']
                    );
                }
            }
        }

        private function parse_parameters(string $taskParameters)
        {
            $taskParameters = json_decode($taskParameters, true);

            if (!empty($taskParameters['modID']) && is_numeric($taskParameters['modID'])) {
                $this->modID = $taskParameters['modID'];
            } else {
                throw new Exception('Invalid modID parsed!');
            }

            if (!empty($taskParameters['modIdentifier'])) {
                $this->modIdentifier = $taskParameters['modIdentifier'];
            } else {
                throw new Exception('Invalid modIdentifier parsed!');
            }

            if (!empty($taskParameters['workshopID']) && is_numeric($taskParameters['workshopID'])) {
                $this->workshopID = $taskParameters['workshopID'];
            } else {
                throw new Exception('Invalid workshopID parsed!');
            }

            if (!empty($taskParameters['modName'])) {
                $this->modName = $taskParameters['modName'];
            } else {
                throw new Exception('Invalid `modName` parsed!');
            }
        }

        private function get_mod_info_from_api(int $workshopID, string $webAPIkey)
        {
            //start defining API request
            $page = 'http://api.steampowered.com/ISteamRemoteStorage/GetPublishedFileDetails/v1/';

            $fields = array(
                'itemcount' => '1',
                'publishedfileids[0]' => $workshopID,
                'key' => $webAPIkey,
                'format' => 'json',
            );

            $fields_string = '';
            foreach ($fields as $key2 => $value2) {
                $fields_string .= $key2 . '=' . $value2 . '&';
            }
            rtrim($fields_string, '&');

            //scrape API
            $modWorkshopDetails = curl($page, $fields_string, NULL, NULL, NULL, 10, 10);
            $modWorkshopDetails = json_decode($modWorkshopDetails, true);

            //try API scrape again if we got a bad response
            if (
                empty($modWorkshopDetails['response']['result']) ||
                $modWorkshopDetails['response']['result'] != 1 ||
                (
                    (
                        !empty($modWorkshopDetails['response']['resultcount']) ||
                        $modWorkshopDetails['response']['resultcount'] >= 1
                    ) &&
                    (
                        !empty($modWorkshopDetails['response']['publishedfiledetails'][0]['result']) ||
                        $modWorkshopDetails['response']['publishedfiledetails'][0]['result'] == 1
                    )
                )
            ) {
                $modWorkshopDetails = curl($page, $fields_string, NULL, NULL, NULL, 10, 10);
                $modWorkshopDetails = json_decode($modWorkshopDetails, true);
            }

            //check if we finally have a good response
            if (
                !empty($modWorkshopDetails['response']['result']) &&
                $modWorkshopDetails['response']['result'] == 1 &&
                (
                    (
                        !empty($modWorkshopDetails['response']['resultcount']) &&
                        $modWorkshopDetails['response']['resultcount'] >= 1
                    ) ||
                    (
                        !empty($modWorkshopDetails['response']['publishedfiledetails'][0]['result']) &&
                        $modWorkshopDetails['response']['publishedfiledetails'][0]['result'] == 1
                    )
                )
            ) {
                return $modWorkshopDetails;
            } else {
                $this->numWorkshopFailure += 1;
                echo "<strong>[FAILURE] NO DATA for:</strong> {$this->modID}!<br />";
                print_r($modWorkshopDetails);
                echo '<hr />';

                return false;
            }
        }

        private function get_mod_display_picture(string $download_url, string $save_location, string $file_name, $behindProxy = false)
        {
            if ($behindProxy) {
                throw new Exception("Skipping download of mod display picture as we are behind a proxy!");
            } else {
                if (empty($download_url)) throw new Exception("Empty download URL!");
                if (filter_var($download_url, FILTER_VALIDATE_URL) === FALSE) throw new Exception('Invalid download URL provided!');
                if (empty($save_location)) throw new Exception("Empty save location!");
                if (!is_dir($save_location)) throw new Exception("Invalid save location provided!");
                if (empty($file_name)) throw new Exception("Empty file name!");

                curl_download($download_url, $save_location, $file_name);
            }
        }

        private function set_workshop_details(array $modDetails)
        {
            try {
                $tempArray = array();

                $tempArray['mod_identifier'] = isset($this->modIdentifier)
                    ? $this->modIdentifier
                    : NULL;

                $tempArray['mod_workshop_id'] = isset($modDetails['response']['publishedfiledetails'][0]['publishedfileid'])
                    ? $modDetails['response']['publishedfiledetails'][0]['publishedfileid']
                    : NULL;

                $tempArray['mod_size'] = isset($modDetails['response']['publishedfiledetails'][0]['file_size'])
                    ? $modDetails['response']['publishedfiledetails'][0]['file_size']
                    : 0;

                $tempArray['mod_hcontent_file'] = isset($modDetails['response']['publishedfiledetails'][0]['hcontent_file'])
                    ? $modDetails['response']['publishedfiledetails'][0]['hcontent_file']
                    : NULL;

                $tempArray['mod_hcontent_preview'] = isset($modDetails['response']['publishedfiledetails'][0]['hcontent_preview'])
                    ? $modDetails['response']['publishedfiledetails'][0]['hcontent_preview']
                    : NULL;

                $tempArray['mod_thumbnail'] = isset($modDetails['response']['publishedfiledetails'][0]['preview_url'])
                    ? $modDetails['response']['publishedfiledetails'][0]['preview_url']
                    : NULL;

                $tempArray['mod_views'] = isset($modDetails['response']['publishedfiledetails'][0]['views'])
                    ? $modDetails['response']['publishedfiledetails'][0]['views']
                    : 0;

                $tempArray['mod_subs'] = isset($modDetails['response']['publishedfiledetails'][0]['subscriptions'])
                    ? $modDetails['response']['publishedfiledetails'][0]['subscriptions']
                    : 0;

                $tempArray['mod_favs'] = isset($modDetails['response']['publishedfiledetails'][0]['favorited'])
                    ? $modDetails['response']['publishedfiledetails'][0]['favorited']
                    : 0;

                $tempArray['mod_subs_life'] = isset($modDetails['response']['publishedfiledetails'][0]['lifetime_subscriptions'])
                    ? $modDetails['response']['publishedfiledetails'][0]['lifetime_subscriptions']
                    : 0;

                $tempArray['mod_favs_life'] = isset($modDetails['response']['publishedfiledetails'][0]['lifetime_favorited'])
                    ? $modDetails['response']['publishedfiledetails'][0]['lifetime_favorited']
                    : 0;

                $tempArray['date_last_updated'] = isset($modDetails['response']['publishedfiledetails'][0]['time_updated'])
                    ? $modDetails['response']['publishedfiledetails'][0]['time_updated']
                    : NULL;

                $sqlResult = $this->db->q(
                    'INSERT INTO `mod_workshop`
                        (
                          `mod_identifier`,
                          `mod_workshop_id`,
                          `mod_size`,
                          `mod_hcontent_file`,
                          `mod_hcontent_preview`,
                          `mod_thumbnail`,
                          `mod_views`,
                          `mod_subs`,
                          `mod_favs`,
                          `mod_subs_life`,
                          `mod_favs_life`,
                          `date_last_updated`
                        )
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, FROM_UNIXTIME(?));',
                    'siisssiiiiis',
                    $tempArray
                );

                if ($sqlResult) {
                    $this->db->q(
                        'UPDATE `mod_list` SET `workshop_updated` = FROM_UNIXTIME(?), `mod_size` = ? WHERE `mod_id` = ?;',
                        'ssi',
                        array(
                            $tempArray['date_last_updated'],
                            $tempArray['mod_size'],
                            $this->modID
                        )
                    );

                    $this->numWorkshopSuccess += 1;
                    echo "[SUCCESS] Added workshop details for: {$this->modID}!<br />";
                } else {
                    $this->numWorkshopUnknown += 1;
                    echo "[UNKNOWN] Adding workshop details for: {$this->modID}!<br />";
                }
            } catch (Exception $e) {
                echo '<br />';
                echo "<strong>[UNKNOWN]</strong> Adding workshop details for: {$this->modID}!<br />";
                echo $e->getMessage() . '<br /><br />';
                $this->numWorkshopUnknown += 1;
            }
        }
    }
}

if (!class_exists('cron_mod_matches')) {
    class cron_mod_matches extends cron_task
    {
        private $numMatchesProcessed = 0;

        public function execute($taskID, $taskName)
        {
            try {
                echo '<h2>Mod Matches</h2>';

                $this->timeStart = time();
                $this->taskID = $taskID;
                $this->taskName = $taskName;

                if (!$this->task_validate($this->taskID, $this->taskName)) throw new Exception("Invalid task specified!");

                $this->task_update_status($this->taskID, 1);

                echo "Creating tables!<br />";
                $this->create_tables();
                echo "Grabbing match data!<br />";
                $this->populate_match_processing_table();
                echo "Parsing match data!<br />";
                $this->process_matches();
                echo "Updating cache of match summary!<br />";
                $this->update_cache_table();
                echo "Displaying match periods updated!<br />";
                $this->display_match_periods_updated();
                echo "Cleaning up tables!<br />";
                $this->cleanup_temp_tables();
            } catch (Exception $e) {
                echo '<br />Caught Exception (CRON) -- ' . basename($e->getFile()) . ':' . $e->getLine() . '<br />' . $e->getMessage() . '<br /><br />';
            } finally {
                $this->timeEnd = time();
                $totalRunTime = $this->timeEnd - $this->timeStart;
                $this->task_update_status($this->taskID, 2, $totalRunTime);

                $this->report_execution_stats(
                    'MATCHES',
                    NULL,
                    's2_cron_matches',
                    $totalRunTime, 60, 1,
                    $this->numMatchesProcessed, 10, 0.1, 'matches'
                );
            }
        }

        public function queue($taskPriority = 2)
        {
            $this->task_queue('cron_matches', NULL, NULL, $taskPriority);
        }

        private function create_tables()
        {
            $this->db->q("CREATE TABLE IF NOT EXISTS `cache_mod_matches_temp0_fix1` (
                  `matchID` BIGINT(255) NOT NULL,
                  `modID` INT(255) NOT NULL,
                  `matchPhaseID` TINYINT(1) NOT NULL,
                  `dateRecorded` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00'
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1;"
            );

            $this->db->q("ALTER TABLE `cache_mod_matches_temp0_fix1`
                  ADD PRIMARY KEY (`matchID`),
                  ADD KEY `indx_mod_winner` (`modID`),
                  ADD KEY `indx_dateRecorded` (`dateRecorded`),
                  ADD KEY `indx_mod_phase` (`modID`,`matchPhaseID`);"
            );

            $this->db->q("CREATE TABLE IF NOT EXISTS `cache_mod_matches_temp1` (
                        `day` INT(2) NOT NULL DEFAULT '0',
                        `month` INT(2) NOT NULL DEFAULT '0',
                        `year` INT(4) NOT NULL DEFAULT '0',
                        `modID` INT(255) NOT NULL,
                        `gamePhase` TINYINT(1) NOT NULL,
                        `gamesPlayed` BIGINT(255) NOT NULL,
                        `dateRecorded` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (`modID`, `gamePhase`, `year`,`month`,`day`),
                        KEY (`dateRecorded`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;"
            );

            $this->db->q("CREATE TABLE IF NOT EXISTS `cache_mod_matches` (
                        `day` INT(2) NOT NULL DEFAULT '0',
                        `month` INT(2) NOT NULL DEFAULT '0',
                        `year` INT(4) NOT NULL DEFAULT '0',
                        `modID` INT(255) NOT NULL,
                        `gamePhase` TINYINT(1) NOT NULL,
                        `gamesPlayed` BIGINT(255) NOT NULL,
                        `dateRecorded` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (`modID`, `gamePhase`, `year`,`month`,`day`),
                        KEY (`dateRecorded`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;"
            );

            $this->db->q('TRUNCATE `cache_mod_matches_temp0_fix1`;');
            $this->db->q('TRUNCATE `cache_mod_matches_temp1`;');
        }

        private function populate_match_processing_table()
        {
            $numMatchesProcessed = $this->db->q('INSERT INTO `cache_mod_matches_temp0_fix1`
                    SELECT
                      `matchID`, `modID`, `matchPhaseID`, `dateRecorded`
                    FROM `s2_match`
                    WHERE `dateRecorded` >= (SELECT DATE_FORMAT( IF( MAX(`dateRecorded`) >0, MAX(`dateRecorded`), (SELECT MIN(`dateRecorded`) FROM `s2_match` ) ), "%Y-%m-%d 00:00:00") - INTERVAL 1 DAY FROM `cache_mod_matches`);'
            );

            if (!empty($numMatchesProcessed)) $this->numMatchesProcessed = $numMatchesProcessed;
        }

        private function process_matches()
        {
            $this->db->q('INSERT INTO `cache_mod_matches_temp1`
                                SELECT
                                    DAY(`dateRecorded`) AS `day`,
                                    MONTH(`dateRecorded`) AS `month`,
                                    YEAR(`dateRecorded`) AS `year`,
                                    `modID`,
                                    `matchPhaseID` AS gamePhase,
                                    COUNT(*) AS `gamesPlayed`,
                                    DATE_FORMAT(MAX(`dateRecorded`), "%Y-%m-%d 00:00:00") AS `dateRecorded`
                                FROM `cache_mod_matches_temp0_fix1`
                                GROUP BY 4,5,3,2,1
                                ORDER BY 4 DESC, 5 DESC, 3 DESC, 2 DESC, 1 DESC
                            ON DUPLICATE KEY UPDATE
                                `gamesPlayed` = VALUES(`gamesPlayed`);'
            );
        }

        private function update_cache_table()
        {
            $this->db->q(
                'INSERT INTO `cache_mod_matches`
                        SELECT
                            *
                        FROM `cache_mod_matches_temp1`
                        ON DUPLICATE KEY UPDATE
                          `gamesPlayed` = VALUES(`gamesPlayed`);'
            );
        }

        private function display_match_periods_updated()
        {
            $last_rows = $this->db->q('SELECT * FROM `cache_mod_matches_temp1` ORDER BY `dateRecorded` DESC, `modID`, `gamePhase`;');

            echo '<table border="1" cellspacing="1">';
            echo '<tr>
                        <th>modID</th>
                        <th>Phase</th>
                        <th>Games</th>
                        <th>Date</th>
                    </tr>';

            foreach ($last_rows as $key => $value) {
                echo '<tr>
                        <td>' . $value['modID'] . '</td>
                        <td>' . $value['gamePhase'] . '</td>
                        <td>' . $value['gamesPlayed'] . '</td>
                        <td>' . $value['dateRecorded'] . '</td>
                    </tr>';
            }

            echo '</table>';
        }

        private function cleanup_temp_tables()
        {
            $this->db->q('DROP TABLE `cache_mod_matches_temp0_fix1`;');
            $this->db->q('DROP TABLE `cache_mod_matches_temp1`;');
        }
    }
}

if (!class_exists('cron_match_flags')) {
    class cron_match_flags extends cron_task
    {
        private $numMatchesToUse = null;
        private $modID = null;
        private $modName = null;
        private $cronNotes = array();

        private $maxMatchID = null;
        private $minMatchID = null;
        private $totalFlagValues = 0;
        private $totalFlagValueCombos = 0;

        public function execute($taskID, $taskName, $taskParameters)
        {
            try {
                echo '<h2>Mod Flags</h2>';

                $this->timeStart = time();
                $this->taskID = $taskID;
                $this->taskName = $taskName;

                if (!$this->task_validate($this->taskID, $this->taskName)) throw new Exception("Invalid task specified!");

                $this->parse_parameters($taskParameters);

                $this->task_update_status($this->taskID, 1);

                echo "<h4>{$this->modName}</h4>";

                echo "Getting `matchID` ranges for parsing!<br />";
                $this->get_match_range($this->numMatchesToUse);
                echo "Setting up tables!<br />";
                $this->setup_tables();
                echo "Grabbing flags from matches!<br />";
                $this->grab_flags_from_matches($this->minMatchID, $this->maxMatchID);
                echo "Aggregating flags!<br />";
                $this->aggregate_flags();
                echo "Cleaning up tables!<br />";
                $this->clean_tables();
            } catch (Exception $e) {
                echo '<br />Caught Exception (CRON) -- ' . basename($e->getFile()) . ':' . $e->getLine() . '<br />' . $e->getMessage() . '<br /><br />';
                $this->cronNotes['Failure'] = basename($e->getFile()) . ':' . $e->getLine() . ' -- ' . $e->getMessage();
            } finally {
                $this->timeEnd = time();
                $totalRunTime = $this->timeEnd - $this->timeStart;
                $this->task_update_status($this->taskID, 2, $totalRunTime, $this->cronNotes);

                $this->report_execution_stats(
                    'CMF',
                    $this->modName,
                    'cron_match_flags__' . $this->modID,
                    $totalRunTime, 5, 1,
                    $this->totalFlagValues, 10, 0.5, 'flag values',
                    $this->totalFlagValueCombos, 10, 0.5, 'flag value combos'
                );
            }
        }

        public function queue(int $taskPriority = 0, int $numMatchesToUse = 10000, int $modID = null, string $modName = null, $userID = NULL)
        {
            //If we called this function with a specific modID we can send it straight into the queue
            //otherwise we will call this function for every non-rejected modID
            $numMatchesToUse = !empty($numMatchesToUse) && is_numeric($numMatchesToUse)
                ? $numMatchesToUse
                : 10000;

            if (!empty($modID)) {
                if (!is_numeric($modID)) throw new Exception("Invalid modID!");
                if (!isset($modName)) throw new Exception("Invalid modName!");
                if (isset($userID) && !is_numeric($userID)) throw new Exception("Invalid userID!");

                $this->task_queue(
                    'cron_match_flags__' . $modID,
                    'cron_match_flags',
                    array(
                        'numMatchesToUse' => $numMatchesToUse,
                        'modID' => $modID,
                        'modName' => $modName,
                    ),
                    $taskPriority,
                    1,
                    $userID
                );
            } else {
                $activeMods = $this->db->q(
                    'SELECT
                              ml.`mod_id`,
                              ml.`mod_identifier`,
                              ml.`mod_name`,
                              ml.`mod_steam_group`,
                              ml.`mod_workshop_link`,
                              ml.`mod_size`,
                              ml.`workshop_updated`,
                              ml.`date_recorded`
                            FROM `mod_list` ml
                            WHERE ml.`mod_active` = 1;'
                );
                if (empty($activeMods)) throw new Exception("No active mods!");

                foreach ($activeMods as $key => $value) {
                    $this->queue(
                        $taskPriority,
                        $numMatchesToUse,
                        $value['mod_id'],
                        $value['mod_name']
                    );
                }
            }
        }

        private function parse_parameters(string $taskParameters)
        {
            $taskParameters = json_decode($taskParameters, true);

            if (!empty($taskParameters['numMatchesToUse']) && is_numeric($taskParameters['numMatchesToUse'])) {
                $this->numMatchesToUse = $taskParameters['numMatchesToUse'];
            } else {
                throw new Exception('Invalid `numMatchesToUse` parsed!');
            }

            if (!empty($taskParameters['modID']) && is_numeric($taskParameters['modID'])) {
                $this->modID = $taskParameters['modID'];
            } else {
                throw new Exception('Invalid modID parsed!');
            }

            if (!empty($taskParameters['modName'])) {
                $this->modName = $taskParameters['modName'];
            } else {
                throw new Exception('Invalid modName parsed!');
            }
        }

        private function get_match_range($numMatchesToUse)
        {
            //MAX
            $maxSQL = $this->db->q(
                'SELECT `matchID`, `dateRecorded` FROM `s2_match` WHERE `modID` = ? ORDER BY `dateRecorded` DESC LIMIT 0,1;',
                'i',
                $this->modID
            );
            if (empty($maxSQL)) throw new Exception('No matches for this modID!');

            $this->maxMatchID = $maxSQL[0]['matchID'];
            $maxMatchDate = $maxSQL[0]['dateRecorded'];
            echo "<strong>Max:</strong> {$this->maxMatchID} [{$maxMatchDate}]<br />";

            $this->cronNotes['Max matchID'] = $this->maxMatchID;
            $this->cronNotes['Max Date'] = $maxMatchDate;

            //MIN
            $minSQL = $this->db->q(
                "SELECT `matchID`, `dateRecorded`
                      FROM
                        (
                            SELECT `matchID`, `dateRecorded`
                            FROM `s2_match`
                            WHERE
                              `modID` = ? AND
                              `dateRecorded` >= (? - INTERVAL 7 DAY)
                            ORDER BY `dateRecorded` DESC
                            LIMIT 0,{$numMatchesToUse}
                        ) t1
                      ORDER BY `dateRecorded` ASC
                      LIMIT 0,1;",
                'is',
                array($this->modID, $maxMatchDate)
            );
            if (empty($minSQL)) throw new Exception('No matches for this modID!');

            $this->minMatchID = $minSQL[0]['matchID'];
            $minMatchDate = $minSQL[0]['dateRecorded'];
            echo "<strong>Min:</strong> {$this->minMatchID} [{$minMatchDate}]<br />";

            $this->cronNotes['Min matchID'] = $this->minMatchID;
            $this->cronNotes['Min Date'] = $minMatchDate;
        }

        private function setup_tables()
        {
            $this->db->q('DROP TABLE IF EXISTS `cache_custom_flags_temp0_games`;');
            $this->db->q('DROP TABLE IF EXISTS `cache_custom_flags_temp1_sort`;');

            $this->db->q("CREATE TABLE IF NOT EXISTS `cache_custom_flags` (
                        `modID` BIGINT(255) NOT NULL,
                        `flagName` VARCHAR(100) NOT NULL,
                        `flagValue` VARCHAR(100) NOT NULL,
                        `numGames` BIGINT(255) NOT NULL,
                        PRIMARY KEY (`modID`, `flagName`, `flagValue`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;"
            );

            $this->db->q("CREATE TEMPORARY TABLE IF NOT EXISTS `cache_custom_flags_temp0_games` (
                        `flagName` VARCHAR(100) NOT NULL,
                        `flagValue` VARCHAR(100) NOT NULL,
                        KEY (`flagName`, `flagValue`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;"
            );

            $this->db->q("CREATE TABLE IF NOT EXISTS `cache_custom_flags_temp1_sort` (
                        `modID` BIGINT(255) NOT NULL,
                        `flagName` VARCHAR(100) NOT NULL,
                        `flagValue` VARCHAR(100) NOT NULL,
                        `numGames` BIGINT(255) NOT NULL,
                        PRIMARY KEY (`modID`, `flagName`, `flagValue`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;"
            );

            $this->db->q("INSERT INTO `cache_custom_flags_temp1_sort`(`modID`, `flagName`, `flagValue`, `numGames`)
                      SELECT `modID`, `flagName`, `flagValue`, `numGames`
                        FROM `cache_custom_flags`;"
            );

            $this->db->q("DELETE FROM `cache_custom_flags_temp1_sort` WHERE `modID` = ?;",
                'i',
                array($this->modID)
            );
        }

        private function grab_flags_from_matches(int $minMatchID, int $maxMatchID)
        {
            if (empty($minMatchID) || empty($maxMatchID) || $maxMatchID <= $minMatchID) throw new Exception('Invalid min or max matchID!');

            $totalFlagValues = $this->db->q("INSERT INTO `cache_custom_flags_temp0_games`(`flagName`, `flagValue`)
                      SELECT `flagName`, `flagValue`
                        FROM `s2_match_flags`
                        WHERE `matchID` BETWEEN ? AND ? AND `modID` = ?;",
                'iii',
                array($minMatchID, $maxMatchID, $this->modID)
            );
            if (empty($totalFlagValues)) throw new Exception('No flags found for given `modID`');

            $this->totalFlagValues = $totalFlagValues;
            echo "<strong>Flag Values:</strong> {$this->totalFlagValues}<br />";
        }

        private function aggregate_flags()
        {
            $flagValueCombinations = $this->db->q(
                'INSERT INTO `cache_custom_flags_temp1_sort` (`modID`, `flagName`, `flagValue`, `numGames`)
                        SELECT
                                ?,
                                ccft0.`flagName`,
                                ccft0.`flagValue`,
                                COUNT(*) AS numGames
                            FROM `cache_custom_flags_temp0_games` ccft0
                            GROUP BY ccft0.`flagName`, ccft0.`flagValue`;',
                's',
                array($this->modID)
            );
            if (empty($flagValueCombinations)) throw new Exception('No flag combos found for given `modID`');

            $this->totalFlagValueCombos = $flagValueCombinations;
            echo "<strong>Flag Combos:</strong> {$this->totalFlagValueCombos}<br />";
        }

        private function clean_tables()
        {
            $this->db->q('RENAME TABLE `cache_custom_flags` TO `cache_custom_flags_old`, `cache_custom_flags_temp1_sort` TO `cache_custom_flags`;');

            $this->db->q('DROP TABLE IF EXISTS `cache_custom_flags_old`;');
            $this->db->q('DROP TABLE IF EXISTS `cache_custom_flags_temp0_games`;');
            $this->db->q('DROP TABLE IF EXISTS `cache_custom_flags_temp1_sort`;');
        }
    }
}
