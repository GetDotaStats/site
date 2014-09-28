SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

CREATE DATABASE `dota2_backpacks` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `dota2_backpacks`;

CREATE TABLE IF NOT EXISTS `economy_attribute_cap` (
  `attribute_particles_id` int(255) NOT NULL,
  `system` varchar(255) NOT NULL,
  `attach_to_rootbone` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`attribute_particles_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='attribute_controlled_attached_particles';

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

CREATE TABLE IF NOT EXISTS `economy_item_levels` (
  `item_level_name` varchar(255) NOT NULL,
  `level` int(255) NOT NULL,
  `required_score` int(255) NOT NULL,
  `level_name` text NOT NULL,
  PRIMARY KEY (`item_level_name`,`level`)
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

CREATE TABLE IF NOT EXISTS `economy_kill_est` (
  `kest_type` int(255) NOT NULL,
  `kest_type_name` varchar(255) NOT NULL,
  PRIMARY KEY (`kest_type`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='kill_eater_score_types';

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

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
