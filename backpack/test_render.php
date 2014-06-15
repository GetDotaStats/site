<pre><?php

require_once("./functions.php");
require_once("../connections/parameters.php");

if(!empty($_GET["uid"]) && is_numeric($_GET["uid"])){
	$user_id = $_GET["uid"];
}
else{
	header("Location: ./test_render.php?uid=76561197989020883");
}

$page = json_decode(curl('http://getdotastats.com/dbe/backpack/?uid='.$user_id),1);

$backpack_just_slotted = array();
$backpack_not_slotted = array();

foreach($page as $key => $value){
	if($value['slot'] > 0){
		$backpack_just_slotted[$value['slot']] = $value;
	}
	else{
		$backpack_not_slotted[] = $value;
	}
}

unset($page);

echo '<h1>Inventory of '.$user_id.':</h1>';

/////////////////////////////
$middle = '';

if(!empty($backpack_not_slotted)){
	echo 'New items not placed in backpack yet:<br />';
	echo '<table border="1" cellpadding="5" cellspacing="1">';

	foreach($backpack_not_slotted as $key => $value){
		$middle .= '<td><a href="http://getdotastats.com/matches/items_details.php?id='.$value['item_id'].'"><img title="'.$value['item_description'].'" width="80" src="'. $value['image_url'] .'"></a></td>';
	
		if($key%6 == 0 && $key > 0){
			echo '<tr height="54">'.$middle.'</tr>';
			$middle = '';
		}
	}
	if($key%6 != 0 || count($backpack_not_slotted == 1)){
		echo '<tr height="54">'.$middle.'</tr>';
		$middle = '';
	}
	
	echo '</table>';
}

echo '<br /><br />';

//////////////////////////////////

if(!empty($backpack_just_slotted)){
	$highest_position = end($backpack_just_slotted);
	$highest_position = $highest_position['slot'];
	$highest_page = ceil($highest_position / 60);
	
	echo 'Number of Pages: '. $highest_page .'<br />';
	echo '<table border="1" cellpadding="5" cellspacing="1">';
	
	$middle = '';
	
	for($i=0; $i <= ($highest_page * 60); $i++){
		if(isset($backpack_just_slotted[$i])){
			$class = '';
			/*if(isset($top5pc_rare_fixed[$backpack_just_slotted[$i]['item_id']]) && $top5pc_rare_fixed[$backpack_just_slotted[$i]['item_id']]['quality'] == $backpack_just_slotted[$i]['quality']){
				$class = ' class="rare"'; 
			}*/
	
			$middle .= '<td'.$class.'><a href="http://getdotastats.com/matches/items_details.php?id='.$backpack_just_slotted[$i]['item_id'].'"><img title="'.$backpack_just_slotted[$i]['item_description'].'" width="80" src="'. $backpack_just_slotted[$i]['image_url'] .'"></a></td>';
		}
		else if($i > 0){
			$middle .= '<td>&nbsp;</td>';
		}
		
		if($i%6 == 0 && $i > 0){
			echo '<tr height="54">'.$middle.'</tr>';
			$middle = '';
		}
		if($i%60 == 0 && $i < ($highest_page * 60)){
			echo '<tr><td colspan="8">Page: '. (($i / 60) + 1) .'</td></tr>';
		}
	}
	echo '</table>';
}
else{
	echo 'Player is not sharing backpack, or does not have any items.';
}
?>
</pre>