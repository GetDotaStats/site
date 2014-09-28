<pre>
<?php
require_once("./functions.php");
require_once("../../connections/parameters.php");

$db = new dbWrapper($hostname_dbe, $username_dbe, $password_dbe, $database_dbe, true);

$memcache = new Memcache;
$memcache->connect("localhost",11211); # You might need to set "localhost" to "127.0.0.1"

if(!empty($_GET["uid"]) && is_numeric($_GET["uid"])){
	$user_id = $_GET["uid"];
}
else{
	header("Location: ./?uid=76561197989020883");
}

$economy_itemlist = $memcache->get("d2_economy_items");
if(!$economy_itemlist){
	$economy_sql = $db->q("SELECT * FROM economy_items ORDER BY item_id ASC");
	//`item_id`, `name`, `item_class`, `item_type_name`, `item_name`, `item_description`, `proper_name`, `item_quality`, `image_inventory`, `min_ilevel`, `max_ilevel`, `image_url`, `image_url_large`, `item_set`, `capabilities_nameable`, `capabilities_can_craft_mark`, `capabilities_can_be_restored`, `capabilities_strange_parts`, `capabilities_paintable_unusual`
	
	$economy_itemlist = array();
	
	foreach($economy_sql as $key => $value){
		$item_id = $value['item_id'];
		
		$economy_itemlist[$item_id]['name'] = $value['item_name'];
		$economy_itemlist[$item_id]['item_class'] = $value['item_class'];
		$economy_itemlist[$item_id]['item_type_name'] = $value['item_type_name'];
		$economy_itemlist[$item_id]['item_description'] = $value['item_description'];
		$economy_itemlist[$item_id]['item_quality'] = $value['item_quality'];
		$economy_itemlist[$item_id]['image_url'] = $value['image_url'];
	}
	
	unset($economy_sql);

	$memcache->set("d2_economy_items", $economy_itemlist, 0, 1*60*60);
}

$economy_attributes = $memcache->get("d2_economy_attributes");
if(!$economy_attributes){
	$economy_attributes_sql = $db->q("SELECT * FROM economy_attributes ORDER BY attribute_id ASC");
	
	$economy_attributes = array();
	
	foreach($economy_attributes_sql as $key => $value){
		$attribute_id = $value['attribute_id'];
		
		$economy_attributes[$attribute_id]['name'] = $value['name'];
		$economy_attributes[$attribute_id]['attribute_class'] = $value['attribute_class'];
		$economy_attributes[$attribute_id]['description_string'] = $value['description_string'];
		$economy_attributes[$attribute_id]['description_format'] = $value['description_format'];
		$economy_attributes[$attribute_id]['effect_type'] = $value['effect_type'];
		$economy_attributes[$attribute_id]['hidden'] = $value['hidden'];
		$economy_attributes[$attribute_id]['stored_as_integer'] = $value['stored_as_integer'];
	}
	
	unset($economy_attributes_sql);

	$memcache->set("d2_economy_attributes", $economy_attributes, 0, 1*60*60);
}

$economy_attribute_particles = $memcache->get("d2_economy_attribute_particles");
if(!$economy_attribute_particles){
	$economy_attribute_particles_sql = $db->q("SELECT * FROM economy_attribute_particle_effects ORDER BY attribute_id ASC");
	
	$economy_attribute_particles = array();
	
	foreach($economy_attribute_particles_sql as $key => $value){
		$particle_id = $value['attribute_id'];
		
		$economy_attribute_particles[$particle_id]['name'] = $value['name'];
	}
	
	unset($economy_attribute_particles_sql);

	$memcache->set("d2_economy_attribute_particles", $economy_attribute_particles, 0, 1*60*60);
}

$player_items = $memcache->get("d2_player_items".$user_id);
if(!$player_items){
	$player_items = get_d2_player_backpack($user_id, 1, $api_key_dbe);
	$memcache->set("d2_player_items".$user_id, $player_items, 0, 10*60);
}


$final_array = $memcache->get("d2_player_items_formatted".$user_id);
if(!$final_array){
	$final_array = array();
	
	if($player_items['result']['status'] == '1'){
		foreach($player_items['result']['items'] as $key => $value){
			$item_id = $value['defindex'];
			
			$final_array[$key]['item_id'] = $value['id'];
			$final_array[$key]['original_id'] = $value['original_id'];
			$final_array[$key]['defindex'] = $value['defindex'];
			$final_array[$key]['quality'] = $value['quality'];
			$final_array[$key]['slot'] = $value['inventory'] & 65535;
	
			isset($value['quantity']) && $value['quantity'] > 1 

				? $final_array[$key]['quantity'] = $value['quantity'] 
					: $final_array[$key]['quantity'] = NULL;
			isset($value['level']) && $value['level'] > 1 
				? $final_array[$key]['level'] = $value['level'] 
					: $final_array[$key]['level'] = NULL;
			
			isset($value['custom_name']) 
				? $final_array[$key]['custom_name'] = $value['custom_name'] 
					: $final_array[$key]['custom_name'] = NULL;
			isset($value['custom_desc ']) 
				? $final_array[$key]['custom_desc'] = $value['custom_desc '] 
					: $final_array[$key]['custom_desc'] = NULL;
			isset($value['flag_cannot_craft']) 
				? $final_array[$key]['flag_cannot_craft'] = $value['flag_cannot_craft'] 
					: $final_array[$key]['flag_cannot_craft'] = NULL;
			isset($value['flag_cannot_trade']) 
				? $final_array[$key]['flag_cannot_trade'] = $value['flag_cannot_trade'] 
					: $final_array[$key]['flag_cannot_trade'] = NULL;
			isset($value['style']) 
				? $final_array[$key]['style'] = $value['style'] 
					: $final_array[$key]['style'] = NULL;
			isset($value['origin']) 
				? $final_array[$key]['origin'] = $value['origin'] 
					: $final_array[$key]['origin'] = NULL;
	
			if(!empty($final_array[$key]['item_id']) && !empty($value['attributes'])){
				$final_array[$key]['attributes'] = array();
				
				foreach($value['attributes'] as $key2 => $value2){
					$attribute_id = $value2['defindex'];
					
					$final_array[$key]['attributes'][$key2]['defindex'] = $attribute_id;
					
					isset($economy_attributes[$attribute_id]['name'])
						? $final_array[$key]['attributes'][$key2]['name'] = $economy_attributes[$attribute_id]['name'] 
							: $final_array[$key]['attributes'][$key2]['name'] = 'Unknown attribute';
					
					if(!empty($economy_attributes[$attribute_id])){
						isset($final_array[$key]['attributes'][$key2]['name'])
							? $final_array[$key]['attributes'][$key2]['name'] = $economy_attributes[$attribute_id]['name']
								: $final_array[$key]['attributes'][$key2]['name'] = 'Unknown attribute';
								
						$final_array[$key]['attributes'][$key2]['description_format'] = $economy_attributes[$attribute_id]['description_format'];
						
						if($economy_attributes[$attribute_id]['hidden'] == '1'){
							if(count($final_array[$key]['attributes']) > 1){
								unset($final_array[$key]['attributes'][$key2]);
							}
							else{
								unset($final_array[$key]['attributes']);
							}
						}
						else{
							if($economy_attributes[$attribute_id]['stored_as_integer'] == '0'){
							//choose whether to look at float or integer ... stupid API
								if(isset($value2['float_value'])){
									if($economy_attributes[$attribute_id]['description_format'] == 'value_is_color'){
									//generate colour
										$final_array[$key]['attributes'][$key2]['value'] = sprintf('%06X', $value2['float_value']);
										$final_array[$key]['attributes'][$key2]['value_nice'] = hex2rgb($final_array[$key]['attributes'][$key2]['value']);
									}
									else if($economy_attributes[$attribute_id]['description_format'] == 'value_is_particle_index'){
									//generate particle name
										if(isset($economy_attribute_particles[$value2['float_value']]['name'])){
											$final_array[$key]['attributes'][$key2]['value'] = $economy_attribute_particles[$value2['float_value']]['name'];
										}
										else{
											$final_array[$key]['attributes'][$key2]['value'] = 'Unknown Particle '.$value2['float_value'];
										}
									}
									else{
									//default_value
										$final_array[$key]['attributes'][$key2]['value'] = $value2['float_value'];
									}
								}
								else{
									$final_array[$key]['attributes'][$key2]['value'] = '??';
								}
							}
							else{
								$final_array[$key]['attributes'][$key2]['value'] = isset($value2['value']) 
									? $value2['value']
										: '??';
							}
						}
						
					}
					else{
						$final_array[$key]['attributes'][$key2]['name'] = 'Unknown attribute';
					}
	
				}
			}
			
			if(!empty($economy_itemlist[$item_id])){
				$final_array[$key]['item_name'] = $economy_itemlist[$item_id]['name'];
				$final_array[$key]['item_class'] = $economy_itemlist[$item_id]['item_class'];
				$final_array[$key]['item_type_name'] = $economy_itemlist[$item_id]['item_type_name'];
				$final_array[$key]['item_description'] = $economy_itemlist[$item_id]['item_description'];
				$final_array[$key]['item_quality'] = $economy_itemlist[$item_id]['item_quality'];
				$final_array[$key]['image_url'] = $economy_itemlist[$item_id]['image_url'];
			}
			else{
				$final_array[$key]['item_name'] = 'Unknown item';
				$final_array[$key]['item_class'] = 'Unknown class';
				$final_array[$key]['item_type_name'] = 'Unknown type';
				$final_array[$key]['item_description'] = 'Item is not in the DB yet, or not in the API. DB is updated once a day, so please try again tomorrow.';
				$final_array[$key]['item_quality'] = '-1';
				$final_array[$key]['image_url'] = NULL;
			}
	
		}
		
		$final_array = json_encode($final_array);
	}
	else{
		$final_array = 'False';
	}
	
	$memcache->set("d2_player_items_formatted".$user_id, $final_array, 0, 10*60);
}

$memcache->close();

print_r(json_decode($final_array, 1));

?>
</pre>