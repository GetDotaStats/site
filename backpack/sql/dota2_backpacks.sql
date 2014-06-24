SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

CREATE TABLE IF NOT EXISTS `economy_attributes` (
  `attribute_id` int(255) NOT NULL,
  `attribute_name` varchar(255) NOT NULL,
  `attribute_class` varchar(255) DEFAULT NULL,
  `attribute_description_format` varchar(255) DEFAULT NULL,
  `attribute_effect_type` varchar(255) DEFAULT NULL,
  `attribute_hidden` tinyint(1) DEFAULT NULL,
  `attribute_stored_as_integer` tinyint(1) DEFAULT NULL,
  `attribute_description_string` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`attribute_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `economy_items` (
  `item_id` int(255) NOT NULL,
  `item_nice_name` varchar(255) NOT NULL,
  `item_class` varchar(255) DEFAULT NULL,
  `item_type_name` varchar(255) DEFAULT NULL,
  `item_set` varchar(255) DEFAULT NULL,
  `item_description` text,
  `item_quality` int(255) DEFAULT NULL,
  `item_image_inventory` text,
  `item_min_ilevel` tinyint(2) unsigned DEFAULT NULL,
  `item_max_ilevel` tinyint(2) unsigned DEFAULT NULL,
  `item_image_url` text,
  `item_image_url_large` text,
  `tool_type` varchar(255) DEFAULT NULL,
  `tool_use_string` varchar(255) DEFAULT NULL,
  `tool_restriction` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `economy_items_attributes` (
  `item_id` int(255) NOT NULL,
  `attribute_name` varchar(255) NOT NULL,
  `attribute_class` varchar(255) NOT NULL,
  `attribute_value` int(255) NOT NULL,
  PRIMARY KEY (`item_id`,`attribute_name`),
  KEY `attribute_name` (`attribute_name`),
  KEY `attribute_class` (`attribute_class`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `economy_items_capabilities` (
  `item_id` int(255) NOT NULL,
  `can_craft_mark` tinyint(1) NOT NULL,
  `can_be_restored` tinyint(1) NOT NULL,
  `strange_parts` tinyint(1) NOT NULL,
  `paintable_unusual` tinyint(1) NOT NULL,
  `autograph` tinyint(1) NOT NULL,
  `can_consume` tinyint(1) NOT NULL,
  `nameable` tinyint(1) NOT NULL,
  `can_have_sockets` tinyint(1) NOT NULL,
  `usable` tinyint(1) NOT NULL,
  `usable_gc` tinyint(1) NOT NULL,
  `usable_out_of_game` tinyint(1) NOT NULL,
  `decodable` tinyint(1) NOT NULL,
  `can_increment` tinyint(1) NOT NULL,
  `uses_essence` tinyint(1) NOT NULL,
  `no_key_required` tinyint(1) NOT NULL,
  PRIMARY KEY (`item_id`),
  KEY `can_craft_mark` (`can_craft_mark`,`can_be_restored`,`strange_parts`,`paintable_unusual`,`autograph`,`can_consume`,`nameable`,`can_have_sockets`,`usable`,`usable_gc`,`usable_out_of_game`,`decodable`,`can_increment`,`uses_essence`,`no_key_required`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `economy_items_styles` (
  `item_id` int(255) NOT NULL,
  `style_id` int(255) NOT NULL,
  `style_name` varchar(255) NOT NULL,
  PRIMARY KEY (`item_id`,`style_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `economy_items_tools_usage` (
  `item_id` int(255) NOT NULL,
  `usage_type` varchar(255) NOT NULL,
  `usage_value` tinyint(1) NOT NULL,
  PRIMARY KEY (`item_id`,`usage_type`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `economy_item_sets` (
  `item_set_identifier` varchar(255) NOT NULL,
  `item_set_name` varchar(255) NOT NULL,
  `item_set_store_bundle` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`item_set_identifier`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `economy_item_sets_attributes` (
  `item_set_identifier` varchar(255) NOT NULL,
  `item_set_attribute_name` varchar(255) NOT NULL,
  `item_set_attribute_class` varchar(255) NOT NULL,
  `item_set_attribute_value` int(255) NOT NULL,
  UNIQUE KEY `item_set_identifier` (`item_set_identifier`,`item_set_attribute_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `economy_item_sets_items` (
  `item_set_identifier` varchar(255) NOT NULL,
  `item_set_item_id` int(255) NOT NULL,
  `item_set_item_name` varchar(255) NOT NULL,
  PRIMARY KEY (`item_set_identifier`,`item_set_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `economy_origins` (
  `origin_id` int(255) NOT NULL,
  `origin_nice_name` varchar(255) NOT NULL,
  PRIMARY KEY (`origin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `economy_qualities` (
  `quality_id` bigint(255) NOT NULL,
  `quality_identifier` varchar(255) NOT NULL,
  `quality_nice_name` varchar(255) NOT NULL,
  PRIMARY KEY (`quality_id`),
  UNIQUE KEY `identifier` (`quality_identifier`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
