<?php
require_once("./functions.php");
require_once("../connections/parameters.php");

$db = new dbWrapper($hostname_backpacks, $username_backpacks, $password_backpacks, $database_backpacks, true);

$memcache = new Memcache;
$memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

if (!empty($_GET["uid"]) && is_numeric($_GET["uid"])) {
    $user_id = $_GET["uid"];
} else {
    header("Location: ./?uid=76561197989020883");
}

$player_items = $memcache->get("d2_player_items" . $user_id);
if (!$player_items) {
    $player_items = get_d2_player_backpack($user_id, 1, $api_key_dbe);
    $memcache->set("d2_player_items" . $user_id, $player_items, 0, 15 * 60);
}

$economy_cards = $memcache->get("d2_economy_cards");
if (!$economy_cards) {
    $economy_cards_sql = $db->q("SELECT * FROM `economy_items` WHERE `item_id` IN (SELECT `item_id` FROM `economy_items_attributes` WHERE `attribute_name` = 'international tag' AND `attribute_value` = 2014) AND (`item_type_name` = 'Player Card' OR `item_type_name` = 'Tool') ORDER BY `economy_items`.`item_image_inventory` DESC");
    //`item_id`, `item_nice_name`, `item_class`, `item_type_name`, `item_set`, `item_description`, `item_quality`, `item_image_inventory`, `item_min_ilevel`, `item_max_ilevel`, `item_image_url`, `item_image_url_large`, `tool_type`, `tool_use_string`, `tool_restriction`

    $economy_cards = array();
    foreach ($economy_cards_sql as $key => $value) {
        $team_name = cut_str(basename($value['item_image_inventory']) . '||', '_', '||');

        $economy_cards[$value['item_id']]['name'] = $value['item_nice_name'];
        $economy_cards[$value['item_id']]['item_id'] = $value['item_id'];
        $economy_cards[$value['item_id']]['team'] = $team_name;
        $economy_cards[$value['item_id']]['image_url'] = $value['item_image_url'];
        $economy_cards[$value['item_id']]['image_url_large'] = $value['item_image_url_large'];
    }
    unset($economy_cards_sql);

    $memcache->set("d2_economy_cards", $economy_cards, 0, 15 * 60);
}

//$player_items_filtered = $memcache->get("d2_player_items_formatted" . $user_id);
if (empty($player_items_filtered)) {
    $player_items_filtered = sortCardsFromInventory($player_items, $economy_cards);
    $memcache->set("d2_player_items_formatted" . $user_id, $player_items_filtered, 0, 15 * 60);
}

$memcache->close();

$card_schema_arranged = array();
foreach ($economy_cards as $key => $value) {
    $team_name = $value['team'];
    $card_schema_arranged[$team_name][$value['item_id']]['name'] = $value['name'];
    $card_schema_arranged[$team_name][$value['item_id']]['item_id'] = $value['item_id'];
    $card_schema_arranged[$team_name][$value['item_id']]['image_url'] = $value['image_url'];
}

echo '<pre>';

$player_items_filtered = json_decode($player_items_filtered, true);
$errors = !empty($array['errors'])
    ? $array['errors']
    : NULL;
unset($player_items_filtered['errors']);

echo '<h2>TI4 card summary for your inventory</h2>';
echo '<p>Backpack and item schema cached for 15mins.</p>';

foreach($card_schema_arranged as $key => $value){
    $row1 = $row2 = $row3 = '';
    $min_card = NULL;
    $colspan = 0;
    foreach($value as $key2 => $value2){
        $card_count = !empty($player_items_filtered[$value2['item_id']]['count'])
            ? $player_items_filtered[$value2['item_id']]['count']
            : 0;
        //$card_count = $player_items_filtered['item_id']['count'];

        if(isset($min_card) && $card_count < $min_card){
            $min_card = $card_count;
        }
        else{
            $min_card = $card_count;
        }

        $row1 .= '<td>' . cut_str($value2['name'].'||', 'Card: ', '||') . '</td>';
        $row2 .= '<td><img width="100px" src="' . $value2['image_url'] . '" /></td>';
        $row3 .= '<td align="center"><span style="font-size:20px;font-weight:bold;">' . $card_count . '</span></td>';

        $colspan++;
    }
    echo '<h2>'.$key.'</h2>';
    echo '<table border="1" cellspacing="1">';
    echo '<tr>'.$row1.'</tr>';
    echo '<tr>'.$row2.'</tr>';
    echo '<tr>'.$row3.'</tr>';
    echo '<tr><td colspan="'.$colspan.'"><em>Enough cards for '.$min_card.' levels</em></td></tr>';
    echo '</table>';

}

//print_r($player_items_filtered);
//print_r($card_schema_arranged);
/*echo '<hr /><h1>Errors:</h1>';
print_r($errors);*/


echo '</pre>';
