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

    checkLogin_v2();
    if (empty($_SESSION['user_id64'])) throw new Exception('Not logged in!');

    $adminCheck = adminCheck($_SESSION['user_id64'], 'admin');
    if (empty($adminCheck)) throw new Exception('Not an admin!');

    echo '<h2>List of Tasks Queued for Execution</h2>';
    echo '<p>This is the admin section dedicated to the management of cron tasks.</p>';

    echo '<hr />';

    ///////////////////////
    // Active Tasks
    ///////////////////////

    $cronTasksActive = cached_query(
        'admin_cron_tasks_active',
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
                <div class="col-md-3"><span class="h4">Task</span></div>
                <div class="col-md-2"><span class="h4">Group</span></div>
                <div class="col-md-1 text-center"><span class="h4">Priority</span></div>
                <div class="col-md-1 text-center"><span class="h4">Blocking</span></div>
                <div class="col-md-2"><span class="h4">User</span></div>
                <div class="col-md-2 text-right"><span class="h4">Queued</span></div>
                <div class="col-md-1 text-center"><span class="h4">Kill</span></div>
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
                <div class="col-md-3">' . $value['cron_id'] . ' -- ' . $value['cron_task'] . '</div>
                <div class="col-md-2">' . $group . '</div>
                <div class="col-md-1 text-center">' . $value['cron_priority'] . '</div>
                <div class="col-md-1 text-center">' . $value['cron_blocking'] . '</div>
                <div class="col-md-2">' . $user . '</div>
                <div class="col-md-2 text-right">' . relative_time_v3($value['cron_date']) . '</div>
                <div class="col-md-1 text-center">X</div>
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
                <div class="col-md-3"><span class="h4">Task</span></div>
                <div class="col-md-2"><span class="h4">Group</span></div>
                <div class="col-md-1 text-center"><span class="h4">Priority</span></div>
                <div class="col-md-1 text-center"><span class="h4">Blocking</span></div>
                <div class="col-md-2"><span class="h4">User</span></div>
                <div class="col-md-2 text-right"><span class="h4">Queued</span></div>
                <div class="col-md-1 text-center"><span class="h4">Kill</span></div>
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
                <div class="col-md-3">' . $value['cron_id'] . ' -- ' . $value['cron_task'] . '</div>
                <div class="col-md-2">' . $group . '</div>
                <div class="col-md-1 text-center">' . $value['cron_priority'] . '</div>
                <div class="col-md-1 text-center">' . $value['cron_blocking'] . '</div>
                <div class="col-md-2">' . $user . '</div>
                <div class="col-md-2 text-right">' . relative_time_v3($value['cron_date']) . '</div>
                <div class="col-md-1 text-center">X</div>
            </div>';

                echo '<span class="h5">&nbsp;</span>';
            }
        } else {
            echo 'No tasks have been queued!';
        }
    }

    echo '<hr />';

    ///////////////////////
    // Completed Tasks
    ///////////////////////

    $cronTasksCompleted = cached_query(
        'admin_cron_tasks_completed',
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
            WHERE `cron_status` = 2
            ORDER BY `cron_date` DESC
            LIMIT 0, 20;',
        NULL,
        NULL,
        1
    );

    echo '<h3>Completed Tasks <small>Last 20</small></h3>';
    {
        if (!empty($cronTasksCompleted)) {
            echo '<div class="row">
                <div class="col-md-3"><span class="h4">Task</span></div>
                <div class="col-md-2"><span class="h4">Group</span></div>
                <div class="col-md-1 text-center"><span class="h4">Priority</span></div>
                <div class="col-md-1 text-center"><span class="h4">Blocking</span></div>
                <div class="col-md-2"><span class="h4">User</span></div>
                <div class="col-md-1 text-right"><span class="h4">Duration</span></div>
                <div class="col-md-2 text-right"><span class="h4">Queued</span></div>
            </div>';

            echo '<span class="h5">&nbsp;</span>';

            foreach ($cronTasksCompleted as $key => $value) {
                $user = !empty($value['cron_user'])
                    ? $value['cron_user']
                    : 'cron_job';

                $group = !empty($value['cron_task_group'])
                    ? $value['cron_task_group']
                    : '-';

                echo '<div class="row searchRow">
                <div class="col-md-3">' . $value['cron_id'] . ' -- ' . $value['cron_task'] . '</div>
                <div class="col-md-2">' . $group . '</div>
                <div class="col-md-1 text-center">' . $value['cron_priority'] . '</div>
                <div class="col-md-1 text-center">' . $value['cron_blocking'] . '</div>
                <div class="col-md-2">' . $user . '</div>
                <div class="col-md-1 text-right">' . $value['cron_duration'] . ' sec</div>
                <div class="col-md-2 text-right">' . relative_time_v3($value['cron_date']) . '</div>
            </div>';

                echo '<span class="h5">&nbsp;</span>';
            }
        } else {
            echo 'No tasks have been completed!';
        }
    }

    echo '<hr />';

    echo '<span class="h4">&nbsp;</span>';

    echo '<div class="text-center">
                <a class="nav-clickable btn btn-default btn-lg" href="#admin__mod_schema_create">Create Schema</a>
                <a class="nav-clickable btn btn-default btn-lg" href="#admin__mod_schema_edit">Edit Schema</a>
           </div>';

    echo '<span class="h4">&nbsp;</span>';

} catch (Exception $e) {
    echo formatExceptionHandling($e);
} finally {
    if (isset($memcached)) $memcached->close();
}