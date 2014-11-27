<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<?php
require_once('./functions.php');
require_once('../connections/parameters.php');
try {
    if (!isset($_SESSION)) {
        session_start();
    }

    $db = new dbWrapper($hostname_steamtracks, $username_steamtracks, $password_steamtracks, $database_steamtracks, false);
    if ($db) {

        switch ($_GET['orderby']) {
            case '0-0':
                $orderby = '`steam_id` ASC';
                break;
            case '0-1':
                $orderby = '`steam_id` DESC';
                break;
            case '1-0':
                $orderby = '`steam_name` ASC';
                break;
            case '1-1':
                $orderby = '`steam_name` DESC';
                break;
            case '2-0':
                $orderby = '`dota_level` ASC';
                break;
            case '2-1':
                $orderby = '`dota_level` DESC';
                break;
            case '3-0':
                $orderby = '`dota_wins` ASC';
                break;
            case '3-1':
                $orderby = '`dota_wins` DESC';
                break;
            case '4-0':
                $orderby = '`rank_solo` ASC';
                break;
            case '4-1':
                $orderby = '`rank_solo` DESC';
                break;
            case '5-0':
                $orderby = '`rank_team` ASC';
                break;
            case '5-1':
                $orderby = '`rank_team` DESC';
                break;
            case '6-0':
                $orderby = '`date_added` ASC';
                break;
            case '6-1':
                $orderby = '`date_added` DESC';
                break;
            case '7-0':
                $orderby = '`last_updated` ASC';
                break;
            case '7-1':
                $orderby = '`last_updated` DESC';
                break;
            case '8-0':
                $orderby = '`private_profile` ASC';
                break;
            case '8-1':
                $orderby = '`private_profile` DESC';
                break;
            default:
                $orderby = '`rank_solo` DESC, `rank_team` DESC';
                break;
        }

        $people = $db->q(
            "SELECT
			`steam_id`, 
			`steam_name`,
			`private_profile`, 
			`dota_level`, 
			`dota_wins`, 
			`rank_solo`,
			`rank_solo_calib`, 
			`rank_team`, 
			`rank_team_calib`, 
			`commends_forgiving`, 
			`commends_friendly`, 
			`commends_leadership`, 
			`commends_teaching`,
			`date_added`,
			`last_updated`
		FROM `mmr`
		ORDER BY $orderby;"
        );

        $people_count = $db->q(
            "SELECT COUNT(*) as count FROM `mmr` LIMIT 0,1;"
        );

        echo 'Total People: ' . $people_count[0]['count'] . '<hr /><br />';

        if (isset($_GET['sids'])) {
            $sid1 = '
		<th>steam_id<br /><a href="./stats.php?orderby=0-0">Asc</a> <a href="./stats.php?orderby=0-1">Desc</a></th>';
        }

        echo '<table cellspacing=1 cellpadding=1 border=1>';
        echo '<tr>
		<th>Rank</th>' . $sid1 . '
		<th>steam_name<br /><a href="./stats.php?orderby=1-0">Asc</a> <a href="./stats.php?orderby=1-1">Desc</a></th>
		<th>private_profile<br /><a href="./stats.php?orderby=8-0">Asc</a> <a href="./stats.php?orderby=8-1">Desc</a></th>
		<th>dota_level<br /><a href="./stats.php?orderby=2-0">Asc</a> <a href="./stats.php?orderby=2-1">Desc</a></th>
		<th>dota_wins<br /><a href="./stats.php?orderby=3-0">Asc</a> <a href="./stats.php?orderby=3-1">Desc</a></th>
		<th>rank_solo<br /><a href="./stats.php?orderby=4-0">Asc</a> <a href="./stats.php?orderby=4-1">Desc</a></th>
		<th>rank_team<br /><a href="./stats.php?orderby=5-0">Asc</a> <a href="./stats.php?orderby=5-1">Desc</a></th>
		<th>commends (forgiving, friendly, leadership, teaching)</th>
		<th>date_added<br /><a href="./stats.php?orderby=6-0">Asc</a> <a href="./stats.php?orderby=6-1">Desc</a></th>
		<th>last_updated<br /><a href="./stats.php?orderby=7-0">Asc</a> <a href="./stats.php?orderby=7-1">Desc</a></th>
		</tr>';
        foreach ($people as $key => $value) {
            if (isset($_GET['sids'])) {
                $sid2 = '
				<th align="left">' . $value['steam_id'] . '</th>';
            }

            $solo_calib = '';
            if (!empty($value['rank_solo_calib'])) {
                $solo_calib = ' (' . $value['rank_solo_calib'] . ')';
            }
            $team_calib = '';
            if (!empty($value['rank_team_calib'])) {
                $team_calib = ' (' . $value['rank_team_calib'] . ')';
            }

            echo '<tr>
			<th align="left">' . ($key + 1) . '</th>' . $sid2 . '
			<th align="left"><a href="http://dotabuff.com/players/' . $value['steam_id'] . '">' . $value['steam_name'] . '</a></th>
			<th>' . $value['private_profile'] . '</th>
			<th>' . $value['dota_level'] . '</th>
			<th>' . $value['dota_wins'] . '</th>
			<th>' . $value['rank_solo'] . $solo_calib . '</th>
			<th>' . $value['rank_team'] . $team_calib . '</th>
			<th>' . $value['commends_forgiving'] . ' | ' . $value['commends_friendly'] . ' | ' . $value['commends_leadership'] . ' | ' . $value['commends_teaching'] . '</th>
			<th>' . relative_time($value['date_added']) . '</th>
			<th>' . relative_time($value['last_updated']) . '</th>
			</tr>';
        }
        echo '</table>';
    } else {
        echo bootstrapMessage('Oh Snap', 'No DB!');
    }
} catch
(Exception $e) {
    echo $e->getMessage();
}
?>
