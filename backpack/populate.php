<?php
require_once("./functions.php");
require_once("../connections/parameters.php");

$db = new dbWrapper($hostname_backpacks, $username_backpacks, $password_backpacks, $database_backpacks, true);

try {
    if ($db) {
        $memcache = new Memcache;
        $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

        set_time_limit(0);

        $schema = get_d2_item_schema(0, $api_key6);
        echo '<pre>';
        /*echo '<pre>';
        print_r($schema);
        echo '</pre>';*/

        unset($schema['result']['status']);
        unset($schema['result']['items_game_url']);
        unset($schema['result']['qualities']);
        unset($schema['result']['qualityNames']);
        unset($schema['result']['originNames']);
        //unset($schema['result']['items']);

        ///////////////////////////////
        //ADD QUALITIES
        ///////////////////////////////
        if (isset($schema['result']['qualities']) && isset($schema['result']['qualityNames']) && !empty($schema['result']['qualities'] && $schema['result']['qualityNames'])) {
            $qualities = array();
            foreach ($schema['result']['qualities'] as $key => $value) {
                $qualities[$key]['quality_id'] = $value;
                $qualities[$key]['identifier'] = $key;
            }

            foreach ($schema['result']['qualityNames'] as $key => $value) {
                $qualities[$key]['identifier'] = $key;
                $qualities[$key]['nice_name'] = $value;
            }

            foreach ($qualities as $key => $value) {
                $sql = $db->q('INSERT INTO economy_qualities (`quality_id`, `quality_identifier`, `quality_nice_name`) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE `quality_identifier` = VALUES(`quality_identifier`), `quality_nice_name` = VALUES(`quality_nice_name`)',
                    'iss',
                    $value['quality_id'], $value['identifier'], $value['nice_name']);

                if (!$sql) {
                    //echo '[qualities] Failed to update `' . $value['identifier'] . '`!!<br />';
                }
            }
            echo '[qualities] Completed<br />';
        } else {
            echo '[qualities] No qualities available!!<br />';
        }

        ///////////////////////////////
        //ADD ORIGIN NAMES
        ///////////////////////////////
        if (isset($schema['result']['originNames']) && !empty($schema['result']['originNames'])) {
            $origins = $schema['result']['originNames'];

            foreach ($origins as $key => $value) {
                $sql = $db->q('INSERT INTO economy_origins (`origin_id`, `origin_nice_name`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `origin_nice_name` = VALUES(`origin_nice_name`)',
                    'is',
                    $value['origin'], $value['name']);

                if (!$sql) {
                    //echo '[originNames] Failed to update `' . $value['name'] . '`!!<br />';
                }
            }
            echo '[originNames] Completed<br />';
        } else {
            echo '[originNames] No origins available!!<br />';
        }

        ///////////////////////////////
        //ADD ITEMS
        ///////////////////////////////
        if (isset($schema['result']['items']) && !empty($schema['result']['items'])) {
            $items = $schema['result']['items'];
            $capabilities = array();

            foreach ($items as $key => $value) {

                //$value['defindex'];
                //$value['name'];
                //$value['item_quality'];
                isset($value['item_class']) && !empty($value['item_class'])
                    ? NULL
                    : $value['item_class'] = NULL;
                isset($value['item_type_name']) && !empty($value['item_type_name'])
                    ? NULL
                    : $value['item_type_name'] = NULL;
                isset($value['item_description']) && !empty($value['item_description'])
                    ? NULL
                    : $value['item_description'] = NULL;
                isset($value['image_inventory']) && !empty($value['image_inventory'])
                    ? NULL
                    : $value['image_inventory'] = NULL;
                isset($value['min_ilevel']) && !empty($value['min_ilevel'])
                    ? NULL
                    : $value['min_ilevel'] = NULL;
                isset($value['max_ilevel']) && !empty($value['max_ilevel'])
                    ? NULL
                    : $value['max_ilevel'] = NULL;
                isset($value['image_url']) && !empty($value['image_url'])
                    ? NULL
                    : $value['image_url'] = NULL;
                isset($value['image_url_large']) && !empty($value['image_url_large'])
                    ? NULL
                    : $value['image_url_large'] = NULL;

                $sql = $db->q('INSERT INTO economy_items (`item_id`, `item_nice_name`, `item_class`, `item_type_name`, `item_description`, `item_quality`, `item_image_inventory`, `min_ilevel`, `max_ilevel`, `item_image_url`, `item_image_url_large`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                                   ON DUPLICATE KEY UPDATE `item_nice_name` = VALUES(`item_nice_name`), `item_class` = VALUES(`item_class`), `item_type_name` = VALUES(`item_type_name`), `item_description` = VALUES(`item_description`), `item_quality` = VALUES(`item_quality`), `item_image_inventory` = VALUES(`item_image_inventory`), `min_ilevel` = VALUES(`min_ilevel`), `max_ilevel` = VALUES(`max_ilevel`), `item_image_url` = VALUES(`item_image_url`), `item_image_url_large` = VALUES(`item_image_url_large`)',
                    'issssisiiss',
                    $value['defindex'], $value['name'], $value['item_class'], $value['item_type_name'], $value['item_description'], $value['item_quality'], $value['image_inventory'], $value['min_ilevel'], $value['max_ilevel'], $value['image_url'], $value['image_url_large']);

                if(isset($value['capabilities']) && !empty($value['capabilities'])){
                    $sql = $db->q('INSERT INTO economy_item_capabilities (`item_id`, `can_craft_mark`, `can_be_restored`, `strange_parts`, `paintable_unusual`, `autograph`, `can_consume`, `nameable`, `can_have_sockets`, `usable`, `usable_gc`, `usable_out_of_game`, `decodable`, `can_increment`, `uses_essence`, `no_key_required`)
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                                      ON DUPLICATE KEY UPDATE `item_id` = VALUES(`item_id`), `can_craft_mark`, `can_be_restored`, `strange_parts`, `paintable_unusual`, `autograph`, `can_consume`, `nameable`, `can_have_sockets`, `usable`, `usable_gc`, `usable_out_of_game`, `decodable`, `can_increment`, `uses_essence`, `no_key_required`',
                        'issssisiiss',
                        $value['defindex']);


                    foreach($value['capabilities'] as $key2 => $value2){
                        isset($capabilities[$key2])
                            ? $capabilities[$key2] += 1
                            : $capabilities[$key2] = 1;
                    }
                }

                if (!$sql) {
                    //echo '[items] Failed to update `' . $value['name'] . '`!!<br />';
                }
            }
            print_r($capabilities);

            echo '[items] Completed<br />';
        } else {
            echo '[items] No origins available!!<br />';
        }



        print_r($schema);
        echo '</pre>';


        $memcache->close();
    } else {
        echo 'No DB';
    }
} catch (Exception $e) {
    echo $e->getMessage();
}