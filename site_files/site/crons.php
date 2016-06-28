<?php
try {
    require_once('../global_functions.php');
    require_once('../connections/parameters.php');

    if (!isset($_SESSION)) {
        session_start();
    }

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcached = new Cache(NULL, NULL, $localDev);

    echo '<h2>List of Tasks Queued for Execution</h2>';
    echo '<p>This section lists the running and queued cron tasks. We execute one large query at a time, to reduce site load and better manage resources.</p>';

    echo '<hr />';

    ///////////////////////
    // Active Tasks
    ///////////////////////

    $cronTasksActive = cached_query(
        's2_cron_tasks_active',
        'SELECT
                `cron_id`,
                `cron_task`,
                `cron_task_group`,
                `cron_parameters`,
                `cron_priority`,
                `cron_blocking`,
                `cron_user`,
                `cron_status`,
                `cron_duration`,
                `cron_date`
            FROM `cron_tasks`
            WHERE `cron_status` = 1
            ORDER BY `cron_date` ASC;',
        NULL,
        NULL,
        1
    );

    echo '<h3>Active Tasks</h3>';
    {
        if (!empty($cronTasksActive)) {
            echo '<div class="row">
                <div class="col-md-1"><span class="h4">ID</span></div>
                <div class="col-md-4"><span class="h4">Task</span></div>
                <div class="col-md-2"><span class="h4">Group</span></div>
                <div class="col-md-1 text-center"><span class="h4">Priority</span></div>
                <div class="col-md-1 text-center"><span class="h4">Blocking</span></div>
                <div class="col-md-1"><span class="h4">User</span></div>
                <div class="col-md-2 text-right"><span class="h4">Queued</span></div>
            </div>';

            echo '<span class="h5">&nbsp;</span>';

            foreach ($cronTasksActive as $key => $value) {
                $user = !empty($value['cron_user'])
                    ? $value['cron_user']
                    : 'cron_job';

                $group = !empty($value['cron_task_group'])
                    ? $value['cron_task_group']
                    : '-';

                echo '<div class="row searchRow">
                <div class="col-md-1">' . $value['cron_id'] . '</div>
                <div class="col-md-4">' . $value['cron_task'] . '</div>
                <div class="col-md-2">' . $group . '</div>
                <div class="col-md-1 text-center">' . $value['cron_priority'] . '</div>
                <div class="col-md-1 text-center">' . $value['cron_blocking'] . '</div>
                <div class="col-md-1">' . $user . '</div>
                <div class="col-md-2 text-right">' . relative_time_v3($value['cron_date']) . '</div>
            </div>';

                echo '<span class="h5">&nbsp;</span>';
            }
        } else {
            echo 'No tasks are active!';
        }
    }

    echo '<hr />';

    ///////////////////////
    // Queued Tasks
    ///////////////////////

    $cronTasksQueued = cached_query(
        'admin_cron_tasks_queued',
        'SELECT
                `cron_id`,
                `cron_task`,
                `cron_task_group`,
                `cron_parameters`,
                `cron_priority`,
                `cron_blocking`,
                `cron_user`,
                `cron_status`,
                `cron_duration`,
                `cron_date`
            FROM `cron_tasks`
            WHERE `cron_status` = 0
            ORDER BY `cron_date` ASC;',
        NULL,
        NULL,
        1
    );

    echo '<h3>Queued Tasks</h3>';
    {
        if (!empty($cronTasksQueued)) {
            echo '<div class="row">
                <div class="col-md-1"><span class="h4">ID</span></div>
                <div class="col-md-4"><span class="h4">Task</span></div>
                <div class="col-md-2"><span class="h4">Group</span></div>
                <div class="col-md-1 text-center"><span class="h4">Priority</span></div>
                <div class="col-md-1 text-center"><span class="h4">Blocking</span></div>
                <div class="col-md-1"><span class="h4">User</span></div>
                <div class="col-md-2 text-right"><span class="h4">Queued</span></div>
            </div>';

            echo '<span class="h5">&nbsp;</span>';

            foreach ($cronTasksQueued as $key => $value) {
                $user = !empty($value['cron_user'])
                    ? $value['cron_user']
                    : 'cron_job';

                $group = !empty($value['cron_task_group'])
                    ? $value['cron_task_group']
                    : '-';

                echo '<div class="row searchRow">
                <div class="col-md-1">' . $value['cron_id'] . '</div>
                <div class="col-md-4">' . $value['cron_task'] . '</div>
                <div class="col-md-2">' . $group . '</div>
                <div class="col-md-1 text-center">' . $value['cron_priority'] . '</div>
                <div class="col-md-1 text-center">' . $value['cron_blocking'] . '</div>
                <div class="col-md-1">' . $user . '</div>
                <div class="col-md-2 text-right">' . relative_time_v3($value['cron_date']) . '</div>
            </div>';

                echo '<span class="h5">&nbsp;</span>';
            }
        } else {
            echo 'No tasks have been queued!';
        }
    }

    echo '<hr />';

    echo '<span class="h4">&nbsp;</span>';

} catch (Exception $e) {
    echo formatExceptionHandling($e);
} finally {
    if (isset($memcached)) $memcached->close();
}