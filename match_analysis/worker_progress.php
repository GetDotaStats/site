<?php
require_once('./functions.php');
require_once('../connections/parameters.php');
?>

<?php
try {
    $db = new dbWrapper($hostname_match_analysis, $username_match_analysis, $password_match_analysis, $database_match_analysis, false);

    if ($db) {
        $memcache = new Memcache;
        $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

        $d2_worker_task_count = simple_cached_query('d2_worker_task_count', "SELECT COUNT(*) as total_tasks, MIN(`last_edit`) as earliest_date, MAX(`last_edit`) as latest_date FROM `parser_manager`", 60);

        $d2_worker_task_count_completed = simple_cached_query('d2_worker_task_count_completed', "SELECT COUNT(*) as total_tasks FROM `parser_manager` WHERE completed = '1'", 60);
        $d2_workers = simple_cached_query('d2_workers', "SELECT * FROM `parser_manager` WHERE started = '1' AND completed = '0' ORDER BY `parser`", 60);

        $d2_worker_task_queue = simple_cached_query('d2_worker_task_queue', "SELECT * FROM `parser_manager` WHERE started = '0' AND completed = '0' ORDER BY priority DESC, seq_start ASC LIMIT 0,10", 60);
        $d2_worker_task_completed = simple_cached_query('d2_worker_task_completed', "SELECT * FROM `parser_manager` WHERE started = '1' AND completed = '1' ORDER BY `last_edit` DESC, `job_id` DESC LIMIT 0,10", 60);

        $d2_worker_task_completed_last10 = simple_cached_query('d2_worker_task_completed_l10',
            "SELECT MAX(`last_edit`) as max_date, MIN(`last_edit`) as min_date
            FROM (
                SELECT *
                FROM `parser_manager`
                WHERE started = '1' AND completed = '1'
                ORDER BY `last_edit` DESC, `job_id` DESC
                LIMIT 0,21
            ) as temp;"
            , 60);

        $tasks_total_format = number_format($d2_worker_task_count[0]['total_tasks']);
        $tasks_completed_format = number_format($d2_worker_task_count_completed[0]['total_tasks']);
        $tasks_completed_per_format = number_format($d2_worker_task_count_completed[0]['total_tasks'] / $d2_worker_task_count[0]['total_tasks'] * 100, 2) . '%';
        echo '<span class="h4">Tasks:</span> ' . $tasks_completed_format . ' / ' . $tasks_total_format . ' (' . $tasks_completed_per_format . ')<br />';

        $time_taken = (strtotime($d2_worker_task_count[0]['latest_date']) - strtotime($d2_worker_task_count[0]['earliest_date'])) / 60 / 60 / 24;
        echo '<span class="h4">Total Uptime:</span> ' . number_format($time_taken, 2) . 'days<br />';

        if ($d2_worker_task_count_completed[0]['total_tasks'] <= 0) $d2_worker_task_count_completed[0]['total_tasks'] = 1;
        $estimated_time = ($d2_worker_task_count[0]['total_tasks'] / $d2_worker_task_count_completed[0]['total_tasks']) * $time_taken;
        echo '<span class="h4">Estimated Time Left:</span> ' . number_format($estimated_time - $time_taken, 2) . 'days (Total of ' . number_format($estimated_time, 2) . 'days.)<br />';

        echo '<br />';

        $time_taken_l10 = (strtotime($d2_worker_task_completed_last10[0]['max_date']) - strtotime($d2_worker_task_completed_last10[0]['min_date'])) / 60 / 60 / 24;
        $estimated_time_l10 = $time_taken_l10 / 20 * ($d2_worker_task_count[0]['total_tasks'] - $d2_worker_task_count_completed[0]['total_tasks']);
        echo '<span class="h4">Time Required for last 20tasks:</span> ' . number_format($time_taken_l10, 2) . 'days<br />';
        echo '<span class="h4">Estimated Time Left (last 20 tasks):</span> ' . number_format($estimated_time_l10 - $time_taken_l10, 2) . 'days<br />';

        //////////////////////////////////////////////

        echo '<h1>Thread Status</h1>';

        if (!empty($d2_workers)) {
            echo '<table class="table table-bordered table-condensed">';
            echo '
			<tr>
				<th>Worker</th>
				<th>Job ID</th>
				<th>Completion</th>
				<th>Match ID Range</th>
				<th>Last Update</th>
			</tr>';
            foreach ($d2_workers as $key => $value) {
                $completion = ($value['seq_current'] > $value['seq_start'])
                    ? number_format(($value['seq_current'] - $value['seq_start']) / ($value['seq_end'] - $value['seq_start']) * 100, 2)
                    : 0;
                echo '
				<tr>
					<td>' . $value['parser'] . '</td>
					<td>' . $value['job_id'] . '</td>
					<td>' . $completion . '%</td>
					<td>' . number_format($value['seq_start']) . ' - ' . number_format($value['seq_end']) . '</td>
					<td>' . relative_time(strtotime($value['last_edit'])) . '</td>
				</tr>';
            }
            echo '</table>';
        } else {
            echo 'No data on current worker status.';
        }

        //////////////////////////////

        echo '<h1>Next 10 Entries in Worklog</h1>';

        if (!empty($d2_worker_task_queue)) {
            echo '<table class="table table-bordered table-condensed">';
            echo '
			<tr>
				<th>Job ID</th>
				<th>Priority</th>
				<th>Match ID Range</th>
			</tr>';
            foreach ($d2_worker_task_queue as $key => $value) {
                echo '
				<tr>
					<td>' . $value['job_id'] . '</td>
					<td>' . $value['priority'] . '</td>
					<td>' . number_format($value['seq_start']) . ' - ' . number_format($value['seq_end']) . '</td>
			</tr>';
            }
            echo '</table>';
        } else {
            echo 'No jobs left in queue.';
        }

        ///////////////////////////

        echo '<h1>Last 20 Tasks Completed</h1>';

        if (!empty($d2_worker_task_completed)) {
            echo '<table class="table table-bordered table-condensed">';
            echo '
			<tr>
				<th>Job ID</th>
				<th>Worker</th>
				<th>Priority</th>
				<th>Match ID Range</th>
				<th>Completed</th>
			</tr>';
            foreach ($d2_worker_task_completed as $key => $value) {
                echo '
				<tr>
					<td>' . $value['job_id'] . '</td>
					<td>' . $value['parser'] . '</td>
					<td>' . $value['priority'] . '</td>
					<td>' . number_format($value['seq_start']) . ' - ' . number_format($value['seq_end']) . '</td>
					<td>' . relative_time(strtotime($value['last_edit'])) . '</td>
				</tr>';
            }
            echo '</table>';
        } else {
            echo 'No jobs completed yet.';
        }
    } else {
        echo 'No DB';
    }
} catch
(Exception $e) {
    echo $e->getMessage();
}
?>
