<?php
require_once("./functions.php");
require_once("../connections/parameters.php");

?>
    <p>You can lookup your 64bit steam ID <a href="http://steamidfinder.ru/" target="_blank">here</a> OR <a
            href="http://steamidconverter.com/" target="_blank">here</a></p>

    <form action="./backpack/dummy.php" method="POST">
        <table cellspacing="1" cellpadding="5" border="1">
            <tr>
                <th align="left">Steam ID</th>
                <td colspan="2"><input name="uid" type="number" min="0"
                                       value="<?= !empty($_GET["uid"]) && is_numeric($_GET["uid"]) ? $_GET["uid"] : 0 ?>"
                                       required></td>
            </tr>
            <tr>
                <td colspan="4" align="center"><input type="submit" value="Lookup"></td>
            </tr>
        </table>
    </form>
    <br/>
<?php
if (!empty($_GET["uid"]) && is_numeric($_GET["uid"])) {
    $user_id = $_GET["uid"];

    if (!empty($_GET["flush"]) && $_GET["flush"] == 1) {
        $flush = 1;
    } else {
        $flush = 0;
    }

    $db = new dbWrapper($hostname_backpacks, $username_backpacks, $password_backpacks, $database_backpacks, true);

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    $player_items = $memcache->get("d2_player_items" . $user_id);
    if (empty($player_items) || $flush) {
        $player_items = get_d2_player_backpack($user_id, 1, $api_key_dbe);
        $memcache->set("d2_player_items" . $user_id, $player_items, 0, 15 * 60);
    }

    $economy_cards = $memcache->get("d2_economy_cards");
    if (empty($economy_cards) || $flush) {
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

    $player_items_filtered = $memcache->get("d2_player_items_formatted" . $user_id);
    if (empty($player_items_filtered) || $flush) {
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

    $player_items_filtered = json_decode($player_items_filtered, true);
    $errors = !empty($array['errors'])
        ? $array['errors']
        : NULL;
    unset($player_items_filtered['errors']);

    if (!empty($player_items_filtered)) {
        echo '<h2>TI4 card summary for your inventory</h2>';
        echo '<p>Backpack and item schema cached for 15mins. This will not show which cards you have stamped, as there is no public API for that.</p>';

        if (empty($errors)) {
            foreach ($card_schema_arranged as $key => $value) {
                $row1 = $row2 = $row3 = '';
                $min_card = NULL;
                $colspan = 0;
                foreach ($value as $key2 => $value2) {
                    $card_count = !empty($player_items_filtered[$value2['item_id']]['count'])
                        ? $player_items_filtered[$value2['item_id']]['count']
                        : 0;
                    //$card_count = $player_items_filtered['item_id']['count'];

                    if ($min_card === NULL) {
                        $min_card = $card_count;
                    } else if ($card_count < $min_card) {
                        $min_card = $card_count;
                    }

                    $card_class = '';
                    if ($card_count > 0) {
                        $card_colour = 'label-success';
                    } else {
                        $card_colour = 'label-danger';
                        $card_class = ' class="item-not-owned"';
                    }

                    $card_name = cut_str($value2['name'] . '||', 'Card: ', '||');

                    $row1 .= '<td align="center"><a href="http://steamcommunity.com/market/search?category_570_Hero%5B%5D=any&category_570_Slot%5B%5D=any&category_570_Type%5B%5D=any&appid=570&q=player+card+' . $card_name . '" target="_blank">' . $card_name . '</a></td>';
                    $row2 .= '<td><img' . $card_class . ' width="100px" src="' . $value2['image_url'] . '" /></td>';
                    $row3 .= '<td align="center"><span class="label ' . $card_colour . '">' . $card_count . '</span></td>';

                    $colspan++;
                }
                echo '<h2>' . $key . '</h2>';
                echo '<table border="1" cellspacing="1">';
                echo '<tr>' . $row1 . '</tr>';
                echo '<tr>' . $row2 . '</tr>';
                echo '<tr>' . $row3 . '</tr>';
                echo '</table><br />';

                if ($min_card <= 0) {
                    echo '<div><h4>Enough cards for <span class="label label-danger">' . $min_card . '</span> levels</h4></div>';
                } else if ($min_card > 0) {
                    echo '<div><h4>Enough cards for <span class="label label-success">' . $min_card . '</span> levels</h4></div>';
                }
                echo '<hr />';

            }
        } else {
            print_r($errors);
        }
    } else {
        echo '<div><span class="label label-danger">No cards in selected inventory!</span></div>';
        //print_r($player_items_filtered);
    }

    //print_r($player_items_filtered);
    //print_r($card_schema_arranged);
    /*echo '<hr /><h1>Errors:</h1>';
    print_r($errors);*/
} else {
    echo '<div class="alert alert-danger">No user_id provided <b>OR</b> user_id provided is invalid.</div>';
}
