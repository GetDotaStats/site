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
            $item_key_values = array();
            //$capabilities = array();
            //$attributes = array();

            foreach ($items as $key => $value) {
                //$value['defindex'];
                //$value['name'];
                //$value['item_quality'];
                $item_id = $value['defindex'];

                isset($value['item_class']) && !empty($value['item_class'])
                    ? NULL
                    : $value['item_class'] = NULL;
                isset($value['item_type_name']) && !empty($value['item_type_name'])
                    ? NULL
                    : $value['item_type_name'] = NULL;
                isset($value['item_set']) && !empty($value['item_set'])
                    ? NULL
                    : $value['item_set'] = NULL;
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

                if (isset($value['defindex']) && isset($value['name']) && isset($value['item_quality'])) {
                    $sql = $db->q('INSERT INTO economy_items (`item_id`, `item_nice_name`, `item_class`, `item_type_name`, `item_set`, `item_description`, `item_quality`, `item_image_inventory`, `min_ilevel`, `max_ilevel`, `item_image_url`, `item_image_url_large`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                                   ON DUPLICATE KEY UPDATE `item_nice_name` = VALUES(`item_nice_name`), `item_class` = VALUES(`item_class`), `item_type_name` = VALUES(`item_type_name`), `item_set` = VALUES(`item_set`), `item_description` = VALUES(`item_description`), `item_quality` = VALUES(`item_quality`), `item_image_inventory` = VALUES(`item_image_inventory`), `min_ilevel` = VALUES(`min_ilevel`), `max_ilevel` = VALUES(`max_ilevel`), `item_image_url` = VALUES(`item_image_url`), `item_image_url_large` = VALUES(`item_image_url_large`)',
                        'isssssisiiss',
                        $item_id, $value['name'], $value['item_class'], $value['item_type_name'], $value['item_set'], $value['item_description'], $value['item_quality'], $value['image_inventory'], $value['min_ilevel'], $value['max_ilevel'], $value['image_url'], $value['image_url_large']);
                } else {
                    echo '[items] Failed to update `' . $item_id . ' | ' . $value['name'] . ' | ' . $value['item_quality'] . '<br />';
                }

                if (isset($value['capabilities']) && !empty($value['capabilities'])) {

                    isset($value['capabilities']['can_craft_mark']) && !empty($value['capabilities']['can_craft_mark'])
                        ? NULL
                        : $value['capabilities']['can_craft_mark'] = 0;
                    isset($value['capabilities']['can_be_restored']) && !empty($value['capabilities']['can_be_restored'])
                        ? NULL
                        : $value['capabilities']['can_be_restored'] = 0;
                    isset($value['capabilities']['strange_parts']) && !empty($value['capabilities']['strange_parts'])
                        ? NULL
                        : $value['capabilities']['strange_parts'] = 0;
                    isset($value['capabilities']['paintable_unusual']) && !empty($value['capabilities']['paintable_unusual'])
                        ? NULL
                        : $value['capabilities']['paintable_unusual'] = 0;
                    isset($value['capabilities']['autograph']) && !empty($value['capabilities']['autograph'])
                        ? NULL
                        : $value['capabilities']['autograph'] = 0;
                    isset($value['capabilities']['can_consume']) && !empty($value['capabilities']['can_consume'])
                        ? NULL
                        : $value['capabilities']['can_consume'] = 0;
                    isset($value['capabilities']['nameable']) && !empty($value['capabilities']['nameable'])
                        ? NULL
                        : $value['capabilities']['nameable'] = 0;
                    isset($value['capabilities']['can_have_sockets']) && !empty($value['capabilities']['can_have_sockets'])
                        ? NULL
                        : $value['capabilities']['can_have_sockets'] = 0;
                    isset($value['capabilities']['usable']) && !empty($value['capabilities']['usable'])
                        ? NULL
                        : $value['capabilities']['usable'] = 0;
                    isset($value['capabilities']['usable_gc']) && !empty($value['capabilities']['usable_gc'])
                        ? NULL
                        : $value['capabilities']['usable_gc'] = 0;
                    isset($value['capabilities']['usable_out_of_game']) && !empty($value['capabilities']['usable_out_of_game'])
                        ? NULL
                        : $value['capabilities']['usable_out_of_game'] = 0;
                    isset($value['capabilities']['decodable']) && !empty($value['capabilities']['decodable'])
                        ? NULL
                        : $value['capabilities']['decodable'] = 0;
                    isset($value['capabilities']['can_increment']) && !empty($value['capabilities']['can_increment'])
                        ? NULL
                        : $value['capabilities']['can_increment'] = 0;
                    isset($value['capabilities']['uses_essence']) && !empty($value['capabilities']['uses_essence'])
                        ? NULL
                        : $value['capabilities']['uses_essence'] = 0;
                    isset($value['capabilities']['no_key_required']) && !empty($value['capabilities']['no_key_required'])
                        ? NULL
                        : $value['capabilities']['no_key_required'] = 0;

                    $sql = $db->q('INSERT INTO economy_items_capabilities (`item_id`, `can_craft_mark`, `can_be_restored`, `strange_parts`, `paintable_unusual`, `autograph`, `can_consume`, `nameable`, `can_have_sockets`, `usable`, `usable_gc`, `usable_out_of_game`, `decodable`, `can_increment`, `uses_essence`, `no_key_required`)
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                                      ON DUPLICATE KEY UPDATE `can_craft_mark` = VALUES(`can_craft_mark`), `can_be_restored` = VALUES(`can_be_restored`), `strange_parts` = VALUES(`strange_parts`), `paintable_unusual` = VALUES(`paintable_unusual`), `autograph` = VALUES(`autograph`), `can_consume` = VALUES(`can_consume`), `nameable` = VALUES(`nameable`), `can_have_sockets` = VALUES(`can_have_sockets`), `usable` = VALUES(`usable`), `usable_gc` = VALUES(`usable_gc`), `usable_out_of_game` = VALUES(`usable_out_of_game`), `decodable` = VALUES(`decodable`), `can_increment` = VALUES(`can_increment`), `uses_essence` = VALUES(`uses_essence`), `no_key_required` = VALUES(`no_key_required`)',
                        'iiiiiiiiiiiiiiii',
                        $item_id, $value['capabilities']['can_craft_mark'], $value['capabilities']['can_be_restored'], $value['capabilities']['strange_parts'], $value['capabilities']['paintable_unusual'], $value['capabilities']['autograph'], $value['capabilities']['can_consume'], $value['capabilities']['nameable'], $value['capabilities']['can_have_sockets'], $value['capabilities']['usable'], $value['capabilities']['usable_gc'], $value['capabilities']['usable_out_of_game'], $value['capabilities']['decodable'], $value['capabilities']['can_increment'], $value['capabilities']['uses_essence'], $value['capabilities']['no_key_required']);
                }

                if (isset($value['attributes']) && !empty($value['attributes'])) {
                    foreach ($value['attributes'] as $key2 => $value2) {
                        if (isset($value2['name']) && isset($value2['class']) && isset($value2['value'])) {
                            $sql = $db->q('INSERT INTO economy_items_attributes (`item_id`, `attribute_name`, `attribute_class`, `attribute_value`)
                                      VALUES (?, ?, ?, ?)
                                      ON DUPLICATE KEY UPDATE `attribute_name` = VALUES(`attribute_name`), `attribute_class` = VALUES(`attribute_class`), `attribute_value` = VALUES(`attribute_value`)',
                                'issi',
                                $item_id, $value2['name'], $value2['class'], $value2['value']);
                        } else {
                            echo '[itemsAttributes] Failed to update `' . $item_id . ' | ' . $value2['name'] . ' | ' . $value2['class'] . ' | ' . $value2['value'] . '<br />';
                        }
                    }
                }

                /////////////////////////////////////////////////////////////////////////////////////////////////////
                //TODO
                //tool
                //styles
                /////////////////////////////////////////////////////////////////////////////////////////////////////

                //COMPILE LIST OF ITEM KEYS
               foreach ($value as $key2 => $value2) {
                    isset($item_key_values[$key2])
                        ? $item_key_values[$key2] += 1
                        : $item_key_values[$key2] = 1;
                }

                //COMPILE LIST OF CAPABILITIES
                /*if (isset($value['capabilities']) && !empty($value['capabilities'])) {
                    foreach ($value['capabilities'] as $key2 => $value2) {
                        isset($capabilities[$key2])
                           ? $capabilities[$key2] += 1
                            : $capabilities[$key2] = 1;
                    }
                }*/

                //COMPILE LIST OF attributes
                /*if (isset($value['attributes']) && !empty($value['attributes'])) {
                    foreach ($value['attributes'] as $key2 => $value2) {
                        isset($attributes[$key2])
                            ? $attributes[$key2] += 1
                            : $attributes[$key2] = 1;
                    }
                }*/


                if (!$sql) {
                    //echo '[items] Failed to update `' . $value['name'] . '`!!<br />';
                }
            }
            print_r($item_key_values); //print out all the key values
            //print_r($capabilities); //print out array of all the capabilities
            //print_r($attributes); //print out array of all the attributes

            echo '[items] Completed<br />';
        } else {
            echo '[items] No items available!!<br />';
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