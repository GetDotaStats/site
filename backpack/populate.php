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

        if ($schema['result']['status'] == 1) {
            unset($schema['result']['status']);
            unset($schema['result']['items_game_url']);
            unset($schema['result']['qualities']);
            unset($schema['result']['qualityNames']);
            unset($schema['result']['originNames']);
            unset($schema['result']['items']);
            unset($schema['result']['attributes']);
            unset($schema['result']['item_sets']);
            //unset($schema['result']['items']);


            ///////////////////////////////
            //ADD QUALITIES
            ///////////////////////////////
            if (isset($schema['result']['qualities']) && isset($schema['result']['qualityNames']) && !empty($schema['result']['qualities']) && !empty($schema['result']['qualityNames'])) {
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
                foreach ($schema['result']['items'] as $key => $value) {
                    if (isset($value['defindex']) && isset($value['name'])) {
                        //$value['defindex'];
                        //$value['name'];
                        //$value['item_quality'];
                        $item_id = $value['defindex'];

                        isset($value['item_quality']) && !empty($value['item_quality'])
                            ? NULL
                            : $value['item_quality'] = NULL;
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
                        isset($value['tool']['type']) && !empty($value['tool']['type'])
                            ? NULL
                            : $value['tool']['type'] = NULL;
                        isset($value['tool']['use_string']) && !empty($value['tool']['use_string'])
                            ? NULL
                            : $value['tool']['use_string'] = NULL;
                        isset($value['tool']['restriction']) && !empty($value['tool']['restriction'])
                            ? NULL
                            : $value['tool']['restriction'] = NULL;

                        //ITEM CAPABILITIES
                        //should come back and de-normalise this
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

                        //ITEM attributes
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

                        //ITEM tool usage capabilities
                        if (isset($value['tool']['usage_capabilities']) && !empty($value['tool']['usage_capabilities'])) {
                            foreach ($value['tool']['usage_capabilities'] as $key2 => $value2) {
                                $sql = $db->q('INSERT INTO economy_items_tools_usage (`item_id`, `usage_type`, `usage_value`)
                                      VALUES (?, ?, ?)
                                      ON DUPLICATE KEY UPDATE `usage_type` = VALUES(`usage_type`), `usage_value` = VALUES(`usage_value`)',
                                    'isi',
                                    $item_id, $key2, $value2);
                            }
                        }

                        //ITEM styles
                        if (isset($value['styles']) && !empty($value['styles'])) {
                            foreach ($value['styles'] as $key2 => $value2) {
                                $sql = $db->q('INSERT INTO economy_items_styles (`item_id`, `style_id`, `style_name`)
                                      VALUES (?, ?, ?)
                                      ON DUPLICATE KEY UPDATE `style_id` = VALUES(`style_id`), `style_name` = VALUES(`style_name`)',
                                    'iis',
                                    $item_id, $key2, $value2['name']);
                            }
                        }

                        $sql = $db->q('INSERT INTO economy_items (`item_id`, `item_nice_name`, `item_class`, `item_type_name`, `item_set`, `item_description`, `item_quality`, `item_image_inventory`, `item_min_ilevel`, `item_max_ilevel`, `item_image_url`, `item_image_url_large`, `tool_type`, `tool_use_string`, `tool_restriction`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                                   ON DUPLICATE KEY UPDATE `item_nice_name` = VALUES(`item_nice_name`), `item_class` = VALUES(`item_class`), `item_type_name` = VALUES(`item_type_name`), `item_set` = VALUES(`item_set`), `item_description` = VALUES(`item_description`), `item_quality` = VALUES(`item_quality`), `item_image_inventory` = VALUES(`item_image_inventory`), `item_min_ilevel` = VALUES(`item_min_ilevel`), `item_max_ilevel` = VALUES(`item_max_ilevel`), `item_image_url` = VALUES(`item_image_url`), `item_image_url_large` = VALUES(`item_image_url_large`), `tool_type` = VALUES(`tool_type`), `tool_use_string` = VALUES(`tool_use_string`), `tool_restriction` = VALUES(`tool_restriction`)',
                            'isssssisiisssss',
                            $item_id, $value['name'], $value['item_class'], $value['item_type_name'], $value['item_set'], $value['item_description'], $value['item_quality'], $value['image_inventory'], $value['min_ilevel'], $value['max_ilevel'], $value['image_url'], $value['image_url_large'], $value['tool']['type'], $value['tool']['use_string'], $value['tool']['restriction']);
                    } else {
                        echo '[items] Failed to update ' . $item_id . ' | ' . $value['name'] . ' | ' . $value['item_quality'] . '<br />';
                    }
                }
                echo '[items] Completed<br />';
            } else {
                echo '[items] No items available!!<br />';
            }


            ///////////////////////////////
            //ADD attributes
            ///////////////////////////////
            if (isset($schema['result']['attributes']) && !empty($schema['result']['attributes'])) {
                foreach ($schema['result']['attributes'] as $key => $value) {
                    if (isset($value['defindex']) && !empty($value['defindex']) && isset($value['name']) && !empty($value['name'])) {
                        isset($value['attribute_class']) && !empty($value['attribute_class'])
                            ? NULL
                            : $value['attribute_class'] = NULL;
                        isset($value['description_format']) && !empty($value['description_format'])
                            ? NULL
                            : $value['description_format'] = NULL;
                        isset($value['effect_type']) && !empty($value['effect_type'])
                            ? NULL
                            : $value['effect_type'] = NULL;
                        isset($value['hidden']) && !empty($value['hidden'])
                            ? $value['hidden'] = 1
                            : $value['hidden'] = 0;
                        isset($value['stored_as_integer']) && !empty($value['stored_as_integer'])
                            ? $value['stored_as_integer'] = 1
                            : $value['stored_as_integer'] = 0;
                        isset($value['description_string']) && !empty($value['description_string'])
                            ? NULL
                            : $value['description_string'] = NULL;

                        $sql = $db->q('INSERT INTO economy_attributes (`attribute_id`, `attribute_name`, `attribute_class`, `attribute_description_format`, `attribute_effect_type`, `attribute_hidden`, `attribute_stored_as_integer`, `attribute_description_string`)
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                                      ON DUPLICATE KEY UPDATE `attribute_name` = VALUES(`attribute_name`), `attribute_class` = VALUES(`attribute_class`), `attribute_description_format` = VALUES(`attribute_description_format`), `attribute_effect_type` = VALUES(`attribute_effect_type`), `attribute_hidden` = VALUES(`attribute_hidden`), `attribute_stored_as_integer` = VALUES(`attribute_stored_as_integer`), `attribute_description_string` = VALUES(`attribute_description_string`)',
                            'issssiis',
                            $value['defindex'], $value['name'], $value['attribute_class'], $value['description_format'], $value['effect_type'], $value['hidden'], $value['stored_as_integer'], $value['description_string']);
                    } else {
                        echo '[Attributes] Failed to update ' . $value['defindex'] . ' | ' . $value['name'] . '<br />';
                    }
                }
                echo '[attributes] Completed<br />';
            } else {
                echo '[attributes] No attributes available!!<br />';
            }

            ///////////////////////////////
            //ADD item_sets
            ///////////////////////////////
            //SHOULD CONVERT PRIMARY TO INT AND UPDATE THE ITEM FOREIGN KEY TO MATCH
            if (isset($schema['result']['item_sets']) && !empty($schema['result']['item_sets'])) {
                foreach ($schema['result']['item_sets'] as $key => $value) {
                    if (isset($value['item_set']) && !empty($value['item_set']) && isset($value['name']) && !empty($value['name'])) {
                        $item_set_identifier = $value['item_set'];

                        isset($value['store_bundle']) && !empty($value['store_bundle'])
                            ? NULL
                            : $value['store_bundle'] = NULL;

                        $sql = $db->q('INSERT INTO economy_item_sets (`item_set_identifier`, `item_set_name`, `item_set_store_bundle`)
                                      VALUES (?, ?, ?)
                                      ON DUPLICATE KEY UPDATE `item_set_name` = VALUES(`item_set_name`), `item_set_store_bundle` = VALUES(`item_set_store_bundle`)',
                            'sss',
                            $item_set_identifier, $value['name'], $value['store_bundle']);

                        //ITEMS in item_set
                        if (isset($value['items']) && !empty($value['items'])) {
                            foreach ($value['items'] as $key2 => $value2) {
                                $sql = $db->q('INSERT INTO economy_item_sets_items (`item_set_identifier`, `item_set_item_id`, `item_set_item_name`)
                                      VALUES (?, ?, ?)
                                      ON DUPLICATE KEY UPDATE `item_set_item_id` = VALUES(`item_set_item_id`), `item_set_item_name` = VALUES(`item_set_item_name`)',
                                    'sis',
                                    $item_set_identifier, $key2, $value2);
                            }
                        }

                        //ATTRIBUTES in item_set
                        if (isset($value['attributes']) && !empty($value['attributes'])) {
                            foreach ($value['attributes'] as $key2 => $value2) {
                                $sql = $db->q('INSERT INTO economy_item_sets_attributes (`item_set_identifier`, `item_set_attribute_name`, `item_set_attribute_class`, `item_set_attribute_value`)
                                      VALUES (?, ?, ?, ?)
                                      ON DUPLICATE KEY UPDATE `item_set_attribute_class` = VALUES(`item_set_attribute_class`), `item_set_attribute_value` = VALUES(`item_set_attribute_value`)',
                                    'sssi',
                                    $item_set_identifier, $value2['name'], $value2['class'], $value2['value']);
                            }
                        }
                    } else {
                        echo '[item_sets] Failed to update ' . $value['item_set'] . ' | ' . $value['name'] . '<br />';
                    }
                }
                echo '[itemSets] Completed<br />';
            } else {
                echo '[itemSets] No item_sets available!!<br />';
            }

            /*
             //DUMMY
             if (isset($schema['result']['attributes']) && !empty($schema['result']['attributes'])) {
                $attributes_values = array();

                foreach ($schema['result']['attributes'] as $key => $value) {


                    //COMPILE LIST OF ITEM KEYS
                    foreach ($value as $key2 => $value2) {
                        isset($attributes_values[$key2])
                            ? $attributes_values[$key2] += 1
                            : $attributes_values[$key2] = 1;
                    }
                }
                print_r($attributes_values); //print out all the key values
                echo '[attributes] Completed<br />';
            } else {
                echo '[attributes] No attributes available!!<br />';
            }
             */


        } else {
            echo 'Schema status is not 1';
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