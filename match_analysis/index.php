<?php
require_once('./functions.php');
require_once('./connections/parameters.php');

try {
    $db = new dbWrapper($hostname, $username, $password, $database, false);
    if ($db) {
        $memcache = new Memcache;
        $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

        $match_db_details = get_match_db_details($db);

        $header = '<div id="header">';
        if (!empty($match_db_details)) {
            $header .= '<div id="player_db_summary">
                <h1>Player DB Details</h1>';
            $header .= '<table class="table table-bordered table-hover table-condensed">';
            $header .= '<tr>
						<th>Players</th>
						<td>' . number_format($match_db_details['player_count_total']) . '</td>
					</tr>';
            $header .= '<tr>
						<th>Anon</th>
						<td>' . number_format($match_db_details['player_count_total'] - $match_db_details['player_count_registered']) . '</td>
					</tr>';
            $header .= '<tr>
						<th>Registered</th>
						<td>' . number_format($match_db_details['player_count_registered']) . '</td>
					</tr>';
            $header .= '<tr>
						<th>Distinct</th>
						<td>' . number_format($match_db_details['player_count_distinct']) . '</td>
					</tr>';
            $header .= '<tr>
						<th>Theoretical</th>
						<td><em>' . number_format(floor(($match_db_details['player_count_distinct'] / $match_db_details['player_count_registered']) * ($match_db_details['player_count_total'] - $match_db_details['player_count_registered']))) . '</em></td>
					</tr>';
            $header .= '</table>';
            $header .= '</div>';

            ////////////////////////////////////////////////////

            $header .= '<div id="match_db_summary">
                <h1>Match DB Details</h1>';
            $header .= '<table class="table table-bordered table-hover table-condensed">';
            $header .= '<tr>
						<th>Matches</th>
						<td colspan="3">' . number_format($match_db_details['match_count']) . '</td>
					</tr>';
            $header .= '<tr>
						<th>Heroes</th>
						<td colspan="3">' . number_format($match_db_details['heroes_played']) . '</td>
					</tr>';
            $header .= '<tr>
						<th colspan="3">Date</th>
						<th>Match ID</th>
					</tr>';
            $header .= '<tr>
						<th>Oldest</th>
						<td>' . relative_time($match_db_details['date_oldest']) . '</td>
						<td>' . gmdate("d/m/Y H:i:s", $match_db_details['date_oldest']) . '</td>
						<td>' . $match_db_details['match_oldest'] . '</td>
					</tr>';
            $header .= '<tr>
						<th>Newest</th>
						<td>' . relative_time($match_db_details['date_recent']) . '</td>
						<td>' . gmdate("d/m/Y H:i:s", $match_db_details['date_recent']) . '</td>
						<td>' . $match_db_details['match_recent'] . '</td>
					</tr>';
            $header .= '</table>';
            $header .= '</div>';
        } else {
            $header .= '<div id="player_db_summary">
                <h1>Player DB Details</h1>';
            $header .= 'No data!';
            $header .= '</div>';
            $header .= '<div id="match_db_summary">
                <h1>Match DB Details</h1>';
            $header .= 'No data!';
            $header .= '</div>';
        }

        //////////
        $header .= '</div>';

        echo $header;
    } else {
        echo 'No DB';
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
?>
