#!/usr/bin/php -q
<?php
require_once('../../../connections/parameters.php');
require_once('../../../global_functions.php');
require_once('../../../cron_functions.php');

//RUN AS CRON EVERY 30SECONDS
//WRITE TO A LOG FILE WITH DATE IN FILENAME
//HAVE A CRON TO DELETE LOGS OLDER THAN A DAY

try {
    /////////////////////////////
    // Parameters
    /////////////////////////////

    $db = new dbWrapper_v3($hostname_gds_cron, $username_gds_cron, $password_gds_cron, $database_gds_cron, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcached = new Cache(NULL, NULL, $localDev);

    set_time_limit(0);

    $timeStarted = time();

    //Grab active tasks
    $taskListActive = $db->q("SELECT
            `cron_id`,
            `cron_task_group`,
            `cron_task`,
            `cron_user`,
            `cron_blocking`,
            `cron_date`
        FROM `cron_tasks`
        WHERE `cron_status` = 1
        ORDER BY `cron_date` ASC;"
    );

    //Parse active tasks, abort if any of the active tasks are blocking
    if (!empty($taskListActive)) {
        echo "<h2>Active Tasks</h2>";
        echo "<table border='1' cellspacing='1' cellpadding='5'>";
        echo "<tr>
                    <td><strong>ID</strong></td>
                    <td><strong>Task</strong></td>
                    <td><strong>User</strong></td>
                    <td><strong>Blocking</strong></td>
                    <td><strong>Date</strong></td>
                </tr>
                ";

        $blockingTasks = false;
        foreach ($taskListActive as $key => $value) {
            $task = empty($value['cron_task_group'])
                ? $value['cron_task']
                : $value['cron_task_group'] . ' -- ' . $value['cron_task'];

            $user = !empty($value['cron_user'])
                ? $value['cron_user']
                : 'cron_job';

            echo "<tr>
                    <td>{$value['cron_id']}</td>
                    <td>{$task}</td>
                    <td>{$user}</td>
                    <td>{$value['cron_blocking']}</td>
                    <td>{$value['cron_date']}</td>
                </tr>
                ";

            if ($value['cron_blocking'] == 1) $blockingTasks = true;
        }
        echo "</table>";

        if ($blockingTasks) throw new Exception("Blocking tasks are currently running!");
    }

    //Grab the non-active task list
    $taskList = $db->q("SELECT
          `cron_id`,
          `cron_task_group`,
          `cron_task`,
          `cron_parameters`,
          `cron_priority`,
          `cron_blocking`,
          `cron_user`,
          `cron_date`
        FROM `cron_tasks`
        WHERE `cron_status` = 0
        ORDER BY `cron_priority` DESC, `cron_blocking` ASC, `cron_date` ASC
        LIMIT 0, 100;"
    );

    //Execute scheduled tasks
    {
        if (empty($taskList)) throw new Exception("No tasks to process!");

        //List the scheduled tasks
        echo "<h2>Scheduled Tasks</h2>";
        echo "<table border='1' cellspacing='1' cellpadding='5'>";
        echo "<tr>
                    <td><strong>ID</strong></td>
                    <td><strong>Task</strong></td>
                    <td><strong>User</strong></td>
                    <td><strong>Priority</strong></td>
                    <td><strong>Blocking</strong></td>
                    <td><strong>Parameters</strong></td>
                    <td><strong>Date</strong></td>
                </tr>
                ";

        foreach ($taskList as $key => $value) {
            $task = empty($value['cron_task_group'])
                ? $value['cron_task']
                : $value['cron_task_group'] . ' -- ' . $value['cron_task'];

            $user = !empty($value['cron_user'])
                ? $value['cron_user']
                : 'cron_job';

            echo "<tr>
                    <td>{$value['cron_id']}</td>
                    <td>{$task}</td>
                    <td>{$user}</td>
                    <td>{$value['cron_priority']}</td>
                    <td>{$value['cron_blocking']}</td>
                    <td>{$value['cron_parameters']}</td>
                    <td>{$value['cron_date']}</td>
                </tr>
                ";
        }
        echo "</table>";

        //Actually process tasks
        $startTime = microtime(true);
        foreach ($taskList as $key => $value) {
            ///////////////////////////////////////////////////
            // DO STUFF
            ///////////////////////////////////////////////////

            try {
                $evaluateFunction = !empty($value['cron_task_group'])
                    ? $value['cron_task_group']
                    : $value['cron_task'];

                switch ($evaluateFunction) {
                    case 'cron_matches':
                        $cron_mod_matches = new cron_mod_matches($db, $memcached, $localDev, $allowWebhooks, $runningWindows, $behindProxy, $webhook_gds_site_admin, $api_key1, $timeStarted);
                        $cron_mod_matches->execute($value['cron_id'], $value['cron_task']);
                        break;
                    case 'cron_workshop':
                        //we use API key 6 here, to capture the mods that are friends only to jimmydorry
                        $cron_workshop = new cron_workshop($db, $memcached, $localDev, $allowWebhooks, $runningWindows, $behindProxy, $webhook_gds_site_admin, $api_key6, $timeStarted);
                        $cron_workshop->execute($value['cron_id'], $value['cron_task'], $value['cron_parameters']);
                        break;
                    case 'cron_highscores':
                        $cron_highscores = new cron_highscores($db, $memcached, $localDev, $allowWebhooks, $runningWindows, $behindProxy, $webhook_gds_site_admin, $api_key1, $timeStarted);
                        $cron_highscores->execute($value['cron_id'], $value['cron_task'], $value['cron_parameters']);
                        break;
                    case 'cron_match_flags':
                        $cron_cron_match_flags = new cron_match_flags($db, $memcached, $localDev, $allowWebhooks, $runningWindows, $behindProxy, $webhook_gds_site_admin, $api_key1, $timeStarted);
                        $cron_cron_match_flags->execute($value['cron_id'], $value['cron_task'], $value['cron_parameters']);
                        break;
                    default:
                        echo '<h2>Unknown Cron Task</h2>';
                        break;
                }
            } catch (Exception $e) {
                echo '<br />Caught Exception (EXECUTION LOOP) -- ' . basename($e->getFile()) . ':' . $e->getLine() . '<br />' . $e->getMessage() . '<br /><br />';
            } finally {
                $currentTime = microtime(true);
                $tasksDuration = number_format(($currentTime - $startTime), 4);

                echo "<br /><strong>We have run for {$tasksDuration} seconds</strong>";
            }

            if (($currentTime - $startTime) >= 55) {
                throw new Exception("Tasks have run for more than 55 seconds!");
            }

            echo '<hr />';
        }
    }


} catch (Exception $e) {
    echo '<br />Caught Exception (MAIN) -- ' . basename($e->getFile()) . ':' . $e->getLine() . '<br />' . $e->getMessage() . '<br /><br />';
} finally {
    if (isset($memcached)) $memcached->close();
}

