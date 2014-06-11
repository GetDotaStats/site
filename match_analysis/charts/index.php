<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>GetDotaStats Charts</title>
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
</head>

<body>
<pre>
<?php
require_once('./functions.php');
require_once('../../connections/parameters.php');

$db = new dbWrapper($hostname_match_analysis, $username_match_analysis, $password_match_analysis, $database_match_analysis, true);

$memcache = new Memcache;
$memcache->connect("localhost",11211); # You might need to set "localhost" to "127.0.0.1"

/*CREATE TABLE q1_lobby_types1 SELECT DATE(FROM_UNIXTIME(start_time)) AS startTimeDate, lobby_type, COUNT(*) as count FROM `matches` WHERE `game_mode` NOT IN (0, 7, 9, 15) GROUP BY startTimeDate, `lobby_type`;*/

/*SELECT 
		DISTINCT q1_1.`startTimeDate`, 
		(SELECT `count` FROM q1_lobby_types1 WHERE startTimeDate = q1_1.`startTimeDate` AND `lobby_type` = 0 LIMIT 0,1) AS lobbytype0, 
		(SELECT `count` FROM q1_lobby_types1 WHERE startTimeDate = q1_1.`startTimeDate` AND `lobby_type` = 1 LIMIT 0,1) AS lobbytype1, 
		(SELECT `count` FROM q1_lobby_types1 WHERE startTimeDate = q1_1.`startTimeDate` AND `lobby_type` = 4 LIMIT 0,1) AS lobbytype4, 
		(SELECT `count` FROM q1_lobby_types1 WHERE startTimeDate = q1_1.`startTimeDate` AND `lobby_type` = 5 LIMIT 0,1) AS lobbytype5, 
		(SELECT `count` FROM q1_lobby_types1 WHERE startTimeDate = q1_1.`startTimeDate` AND `lobby_type` = 6 LIMIT 0,1) AS lobbytype6, 
		(SELECT `count` FROM q1_lobby_types1 WHERE startTimeDate = q1_1.`startTimeDate` AND `lobby_type` = 7 LIMIT 0,1) AS lobbytype7 
	FROM `q1_lobby_types1` q1_1;
*/
$lobbytypes_breakdown1 = $db -> q(
	"SELECT 
		DISTINCT q1_1.`startTimeDate`, 
		(SELECT `count` FROM q1_lobby_types1 WHERE startTimeDate = q1_1.`startTimeDate` AND `lobby_type` = 0 LIMIT 0,1) AS lobbytype0, 
		(SELECT `count` FROM q1_lobby_types1 WHERE startTimeDate = q1_1.`startTimeDate` AND `lobby_type` = 7 LIMIT 0,1) AS lobbytype7 
	FROM `q1_lobby_types1` q1_1;");

/*CREATE TABLE q1_lobby_types2 SELECT DATE(FROM_UNIXTIME(start_time)) AS startTimeDate, lobby_type, COUNT(*) as count FROM `matches` WHERE `game_mode` NOT IN (0, 7, 9, 15) GROUP BY startTimeDate, `lobby_type`;*/
/*SELECT 
		DISTINCT q1_2.`startTimeDate`, 
		(SELECT `count` FROM `q1_lobby_types2` WHERE startTimeDate = q1_2.`startTimeDate` AND `lobby_type` = 0 LIMIT 0,1) AS lobbytype0, 
		(SELECT `count` FROM `q1_lobby_types2` WHERE startTimeDate = q1_2.`startTimeDate` AND `lobby_type` = 1 LIMIT 0,1) AS lobbytype1, 
		(SELECT `count` FROM `q1_lobby_types2` WHERE startTimeDate = q1_2.`startTimeDate` AND `lobby_type` = 4 LIMIT 0,1) AS lobbytype4, 
		(SELECT `count` FROM `q1_lobby_types2` WHERE startTimeDate = q1_2.`startTimeDate` AND `lobby_type` = 5 LIMIT 0,1) AS lobbytype5, 
		(SELECT `count` FROM `q1_lobby_types2` WHERE startTimeDate = q1_2.`startTimeDate` AND `lobby_type` = 6 LIMIT 0,1) AS lobbytype6, 
		(SELECT `count` FROM `q1_lobby_types2` WHERE startTimeDate = q1_2.`startTimeDate` AND `lobby_type` = 7 LIMIT 0,1) AS lobbytype7 
	FROM `q1_lobby_types2` q1_2;
*/
$lobbytypes_breakdown2 = $db -> q(
	"SELECT 
		DISTINCT q1_2.`startTimeDate`, 
		(SELECT `count` FROM `q1_lobby_types2` WHERE startTimeDate = q1_2.`startTimeDate` AND `lobby_type` = 0 LIMIT 0,1) AS lobbytype0, 
		(SELECT `count` FROM `q1_lobby_types2` WHERE startTimeDate = q1_2.`startTimeDate` AND `lobby_type` = 7 LIMIT 0,1) AS lobbytype7 
	FROM `q1_lobby_types2` q1_2;");

?>
<?php
	//PARTICLES/COLOURS JSON
	$data = array();
	$data2 = array();
	if(!empty($lobbytypes_breakdown1)){
		$i = 0;
		
		foreach($lobbytypes_breakdown1 as $key => $value){ 
			if(!empty($value)){
				$startTimeDate = $value['startTimeDate'];
				$lobby_type0 = !empty($value['lobbytype0']) ? $value['lobbytype0'] : 0;;
				//$lobby_type1 = !empty($value['lobbytype1']) ? $value['lobbytype1'] : 0;;
				//$lobby_type4 = !empty($value['lobbytype4']) ? $value['lobbytype4'] : 0;;
				//$lobby_type5 = !empty($value['lobbytype5']) ? $value['lobbytype5'] : 0;;
				//$lobby_type6 = !empty($value['lobbytype6']) ? $value['lobbytype6'] : 0;;
				$lobby_type7 = !empty($value['lobbytype7']) ? $value['lobbytype7'] : 0;;

				$data[$i][] = $startTimeDate;
				$data[$i][] = (int) $lobby_type0;
				//$data[$i][] = (int) $lobby_type1;
				//$data[$i][] = (int) $lobby_type4;
				//$data[$i][] = (int) $lobby_type5;
				//$data[$i][] = (int) $lobby_type6;
				$data[$i][] = (int) $lobby_type7;
				
				$i++;
			}
		}

		$lobbytypes_breakdown1_data = json_encode($data);
		//$colours_colours = json_encode($data2, JSON_FORCE_OBJECT);
	}

	//PARTICLES/COLOURS JSON
	$data = array();
	$data2 = array();
	if(!empty($lobbytypes_breakdown2)){
		$i = 0;
		
		foreach($lobbytypes_breakdown2 as $key => $value){ 
			if(!empty($value)){
				$startTimeDate = $value['startTimeDate'];
				$lobby_type0 = !empty($value['lobbytype0']) ? $value['lobbytype0'] : 0;;
				//$lobby_type1 = !empty($value['lobbytype1']) ? $value['lobbytype1'] : 0;;
				//$lobby_type4 = !empty($value['lobbytype4']) ? $value['lobbytype4'] : 0;;
				//$lobby_type5 = !empty($value['lobbytype5']) ? $value['lobbytype5'] : 0;;
				//$lobby_type6 = !empty($value['lobbytype6']) ? $value['lobbytype6'] : 0;;
				$lobby_type7 = !empty($value['lobbytype7']) ? $value['lobbytype7'] : 0;;

				$data[$i][] = $startTimeDate;
				$data[$i][] = (int) $lobby_type0;
				//$data[$i][] = (int) $lobby_type1;
				//$data[$i][] = (int) $lobby_type4;
				//$data[$i][] = (int) $lobby_type5;
				//$data[$i][] = (int) $lobby_type6;
				$data[$i][] = (int) $lobby_type7;
				
				$i++;
			}
		}

		$lobbytypes_breakdown2_data = json_encode($data);
		//$colours_colours = json_encode($data2, JSON_FORCE_OBJECT);
	}

	?>

<script type="text/javascript">
google.load('visualization', '1', {packages:['corechart','table']});

	function toggleRaw(id)
	{
		$('#'+id).toggle();
		$('#'+id+'_raw').toggle();
	}
	
	function drawChart() 
	{
		var options_table = 
		{
			sortColumn: 0, 
			sortAscending: true, 
			alternatingRowStyle: true,
			page: 'enable',
			pageSize: 20,
			allowHtml: true
		};

		<?php if(!empty($lobbytypes_breakdown1)){ ?>
		var data = new google.visualization.DataTable();
		data.addColumn('string', 'Date');
		data.addColumn('number', 'CASUAL_MATCH');
		//data.addColumn('number', 'PRACTICE');
		//data.addColumn('number', 'COOP_BOT_MATCH');
		//data.addColumn('number', 'TEAM_MATCH');
		//data.addColumn('number', 'SOLO_QUEUE_MATCH');
		data.addColumn('number', 'COMPETITIVE_MATCH');

		data.addRows(<?php echo $lobbytypes_breakdown1_data; ?>);
	
		var options = 
		{
			title: 'Lobby Types Breakdown',
			chartArea: { top: 20, left: 100, width: "90%", height: "80%" },
			hAxis: {title: 'Date'},
			vAxis: {title: 'Count'},
			legend: {position: 'none'},
			backgroundColor: '#C0D9D9'
		};

		new google.visualization.ColumnChart(document.getElementById('lobbytypes_breakdown1')).draw(data, options);	
		new google.visualization.Table(document.getElementById('lobbytypes_breakdown1_raw')).draw(data, options_table);				
		<?php } ?>

		<?php if(!empty($lobbytypes_breakdown2)){ ?>
		var data = new google.visualization.DataTable();
		data.addColumn('string', 'Date');
		data.addColumn('number', 'CASUAL_MATCH');
		//data.addColumn('number', 'PRACTICE');
		//data.addColumn('number', 'COOP_BOT_MATCH');
		//data.addColumn('number', 'TEAM_MATCH');
		//data.addColumn('number', 'SOLO_QUEUE_MATCH');
		data.addColumn('number', 'COMPETITIVE_MATCH');

		data.addRows(<?php echo $lobbytypes_breakdown2_data; ?>);
	
		var options = 
		{
			title: 'Lobby Types Breakdown',
			chartArea: { top: 20, left: 100, width: "90%", height: "80%" },
			hAxis: {title: 'Date'},
			vAxis: {title: 'Count'},
			legend: {position: 'none'},
			backgroundColor: '#C0D9D9'
		};

		new google.visualization.ColumnChart(document.getElementById('lobbytypes_breakdown2')).draw(data, options);	
		new google.visualization.Table(document.getElementById('lobbytypes_breakdown2_raw')).draw(data, options_table);				
		<?php } ?>
		
	}
	google.setOnLoadCallback(drawChart);
</script>
<div id='lobbytypes_breakdown1' style="width: 100%; height: 400px;">No quality data available. :(</div>
<?php if(!empty($lobbytypes_breakdown1_data)){ ?>
	<div id='lobbytypes_breakdown1_raw' style="width: 100%; display:none;"></div><br /><a href='javascript:void(0);' onclick="toggleRaw('lobbytypes_breakdown1');">Show/Hide Raw data</a>
<?php } ?>

<div id='lobbytypes_breakdown2' style="width: 100%; height: 400px;">No quality data available. :(</div>
<?php if(!empty($lobbytypes_breakdown2_data)){ ?>
	<div id='lobbytypes_breakdown2_raw' style="width: 100%; display:none;"></div><br /><a href='javascript:void(0);' onclick="toggleRaw('lobbytypes_breakdown2');">Show/Hide Raw data</a>
<?php } ?>

<?php $memcache->close(); ?>
</pre>
</body>
</html>