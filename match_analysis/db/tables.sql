SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE IF NOT EXISTS `ability_upgrades` (
  `match_id` int(12) NOT NULL,
  `hero_id` int(12) NOT NULL,
  `level` tinyint(2) NOT NULL,
  `ability` int(12) NOT NULL,
  `time` int(12) NOT NULL,
  PRIMARY KEY (`match_id`,`hero_id`,`level`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `ability_upgrades3` (
  `match_id` int(12) NOT NULL,
  `hero_id` int(12) NOT NULL,
  `level` tinyint(2) NOT NULL,
  `ability` int(12) NOT NULL,
  `time` int(12) NOT NULL,
  PRIMARY KEY (`match_id`,`hero_id`,`level`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `additional_units` (
  `match_id` int(12) NOT NULL,
  `hero_id` int(10) NOT NULL,
  `unitname` varchar(50) NOT NULL,
  `item_0` int(5) NOT NULL DEFAULT '0',
  `item_1` int(5) NOT NULL DEFAULT '0',
  `item_2` int(5) NOT NULL DEFAULT '0',
  `item_3` int(5) NOT NULL DEFAULT '0',
  `item_4` int(5) NOT NULL DEFAULT '0',
  `item_5` int(5) NOT NULL DEFAULT '0',
  PRIMARY KEY (`match_id`,`hero_id`,`unitname`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `additional_units3` (
  `match_id` int(12) NOT NULL,
  `hero_id` int(10) NOT NULL,
  `unitname` varchar(50) NOT NULL,
  `item_0` int(5) NOT NULL DEFAULT '0',
  `item_1` int(5) NOT NULL DEFAULT '0',
  `item_2` int(5) NOT NULL DEFAULT '0',
  `item_3` int(5) NOT NULL DEFAULT '0',
  `item_4` int(5) NOT NULL DEFAULT '0',
  `item_5` int(5) NOT NULL DEFAULT '0',
  PRIMARY KEY (`match_id`,`hero_id`,`unitname`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `game_clusters` (
  `cluster` int(10) NOT NULL,
  `name` varchar(100) NOT NULL,
  `region` tinyint(2) NOT NULL,
  PRIMARY KEY (`cluster`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `game_heroes` (
  `hero_id` int(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `localized_name` varchar(255) NOT NULL,
  PRIMARY KEY (`hero_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `game_leaver_status` (
  `leaver_status` tinyint(2) NOT NULL,
  `nice_name` varchar(30) NOT NULL,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`leaver_status`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `game_lobby_types` (
  `lobby_type` tinyint(2) NOT NULL,
  `nice_name` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`lobby_type`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `game_modes` (
  `game_mode` tinyint(2) NOT NULL,
  `nice_name` varchar(30) NOT NULL,
  `name` varchar(30) NOT NULL,
  PRIMARY KEY (`game_mode`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `game_regions` (
  `region` tinyint(2) NOT NULL AUTO_INCREMENT,
  `region_name` varchar(255) NOT NULL,
  PRIMARY KEY (`region`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=13 ;

CREATE TABLE IF NOT EXISTS `matches` (
  `match_id` int(12) NOT NULL,
  `match_seq_num` int(12) NOT NULL,
  `lobby_type` tinyint(2) NOT NULL DEFAULT '0',
  `game_mode` tinyint(2) NOT NULL,
  `radiant_win` tinyint(1) NOT NULL DEFAULT '0',
  `duration` int(10) NOT NULL,
  `start_time` int(12) NOT NULL,
  `cluster` int(10) NOT NULL,
  `first_blood_time` int(10) NOT NULL,
  `league_id` int(10) DEFAULT NULL,
  `series_id` int(12) DEFAULT NULL,
  `series_type` tinyint(1) DEFAULT NULL,
  `tower_status_radiant` int(10) NOT NULL,
  `tower_status_dire` int(10) NOT NULL,
  `barracks_status_radiant` int(10) NOT NULL,
  `barracks_status_dire` int(10) NOT NULL,
  PRIMARY KEY (`match_id`),
  UNIQUE KEY `match_seq_num` (`match_seq_num`),
  KEY `index_game_mode` (`game_mode`),
  KEY `index_duration` (`duration`),
  KEY `index_cluster` (`cluster`),
  KEY `index_lobby_type` (`lobby_type`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `matches3` (
  `match_id` int(12) NOT NULL,
  `match_seq_num` int(12) NOT NULL,
  `lobby_type` tinyint(2) NOT NULL DEFAULT '0',
  `game_mode` tinyint(2) NOT NULL,
  `radiant_win` tinyint(1) NOT NULL DEFAULT '0',
  `duration` int(10) NOT NULL,
  `start_time` int(12) NOT NULL,
  `cluster` int(10) NOT NULL,
  `first_blood_time` int(10) NOT NULL,
  `league_id` int(10) DEFAULT NULL,
  `series_id` int(12) DEFAULT NULL,
  `series_type` tinyint(1) DEFAULT NULL,
  `tower_status_radiant` int(10) NOT NULL,
  `tower_status_dire` int(10) NOT NULL,
  `barracks_status_radiant` int(10) NOT NULL,
  `barracks_status_dire` int(10) NOT NULL,
  PRIMARY KEY (`match_id`),
  UNIQUE KEY `match_seq_num` (`match_seq_num`),
  KEY `index_game_mode` (`game_mode`),
  KEY `index_duration` (`duration`),
  KEY `index_cluster` (`cluster`),
  KEY `index_lobby_type` (`lobby_type`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `matches_temp` (
  `match_id` int(12) NOT NULL,
  `match_seq_num` int(12) NOT NULL,
  `lobby_type` tinyint(2) NOT NULL DEFAULT '0',
  `game_mode` tinyint(2) NOT NULL,
  `radiant_win` tinyint(1) NOT NULL DEFAULT '0',
  `duration` int(10) NOT NULL,
  `start_time` int(12) NOT NULL,
  `cluster` int(10) NOT NULL,
  `first_blood_time` int(10) NOT NULL,
  `league_id` int(10) DEFAULT NULL,
  `series_id` int(12) DEFAULT NULL,
  `series_type` tinyint(1) DEFAULT NULL,
  `tower_status_radiant` int(10) NOT NULL,
  `tower_status_dire` int(10) NOT NULL,
  `barracks_status_radiant` int(10) NOT NULL,
  `barracks_status_dire` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `matches_test2` (
  `match_id` int(12) NOT NULL,
  `match_seq_num` int(12) NOT NULL,
  `lobby_type` tinyint(2) NOT NULL DEFAULT '0',
  `game_mode` tinyint(2) NOT NULL,
  `radiant_win` tinyint(1) NOT NULL DEFAULT '0',
  `duration` int(10) NOT NULL,
  `start_time` int(12) NOT NULL,
  `cluster` int(10) NOT NULL,
  `first_blood_time` int(10) NOT NULL,
  `league_id` int(10) DEFAULT NULL,
  `series_id` int(12) DEFAULT NULL,
  `series_type` tinyint(1) DEFAULT NULL,
  `tower_status_radiant` int(10) NOT NULL,
  `tower_status_dire` int(10) NOT NULL,
  `barracks_status_radiant` int(10) NOT NULL,
  `barracks_status_dire` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `parser_manager` (
  `job_id` bigint(255) NOT NULL AUTO_INCREMENT,
  `seq_start` bigint(255) NOT NULL,
  `seq_end` bigint(255) NOT NULL,
  `priority` tinyint(1) NOT NULL DEFAULT '0',
  `started` tinyint(1) NOT NULL DEFAULT '0',
  `completed` tinyint(1) NOT NULL DEFAULT '0',
  `parser` varchar(20) DEFAULT NULL,
  `seq_current` bigint(255) DEFAULT NULL,
  `last_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`job_id`),
  UNIQUE KEY `seq_start` (`seq_start`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=10345 ;

CREATE TABLE IF NOT EXISTS `parser_manager2` (
  `seq_start` bigint(255) NOT NULL,
  `seq_end` bigint(255) NOT NULL,
  `priority` tinyint(1) NOT NULL DEFAULT '0',
  `started` tinyint(1) NOT NULL DEFAULT '0',
  `completed` tinyint(1) NOT NULL DEFAULT '0',
  `parser` varchar(20) DEFAULT NULL,
  `seq_current` bigint(255) DEFAULT NULL,
  `last_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `seq_start` (`seq_start`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `parser_manager3` (
  `job_id` bigint(255) NOT NULL AUTO_INCREMENT,
  `seq_start` bigint(255) NOT NULL,
  `seq_end` bigint(255) NOT NULL,
  `priority` tinyint(1) NOT NULL DEFAULT '0',
  `started` tinyint(1) NOT NULL DEFAULT '0',
  `completed` tinyint(1) NOT NULL DEFAULT '0',
  `parser` varchar(20) DEFAULT NULL,
  `seq_current` bigint(255) DEFAULT NULL,
  `last_edit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`job_id`),
  UNIQUE KEY `seq_start` (`seq_start`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1971 ;

CREATE TABLE IF NOT EXISTS `picks_bans` (
  `match_id` int(12) NOT NULL,
  `order` tinyint(2) NOT NULL,
  `team` tinyint(1) NOT NULL,
  `is_pick` tinyint(1) NOT NULL,
  `hero_id` int(5) NOT NULL,
  PRIMARY KEY (`match_id`,`order`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `picks_bans3` (
  `match_id` int(12) NOT NULL,
  `order` tinyint(2) NOT NULL,
  `team` tinyint(1) NOT NULL,
  `is_pick` tinyint(1) NOT NULL,
  `hero_id` int(5) NOT NULL,
  PRIMARY KEY (`match_id`,`order`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `players` (
  `match_id` int(20) NOT NULL,
  `account_id` bigint(255) DEFAULT NULL,
  `player_slot` smallint(3) NOT NULL,
  `hero_id` smallint(3) NOT NULL DEFAULT '0',
  `item_0` int(10) NOT NULL DEFAULT '0',
  `item_1` int(10) NOT NULL DEFAULT '0',
  `item_2` int(10) NOT NULL DEFAULT '0',
  `item_3` int(10) NOT NULL DEFAULT '0',
  `item_4` int(10) NOT NULL DEFAULT '0',
  `item_5` int(10) NOT NULL DEFAULT '0',
  `kills` int(10) NOT NULL,
  `deaths` int(10) NOT NULL,
  `assists` int(10) NOT NULL,
  `leaver_status` tinyint(2) NOT NULL,
  `gold` int(10) NOT NULL,
  `last_hits` int(10) NOT NULL,
  `denies` int(10) NOT NULL,
  `gold_per_min` int(10) NOT NULL,
  `xp_per_min` int(10) NOT NULL,
  `gold_spent` int(10) NOT NULL,
  `hero_damage` int(10) NOT NULL,
  `tower_damage` int(10) NOT NULL,
  `hero_healing` int(10) NOT NULL,
  `level` tinyint(2) NOT NULL,
  PRIMARY KEY (`match_id`,`player_slot`),
  KEY `index_account_id` (`account_id`),
  KEY `index_hero_id` (`hero_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `players3` (
  `match_id` int(20) NOT NULL,
  `account_id` bigint(255) DEFAULT NULL,
  `player_slot` smallint(3) NOT NULL,
  `hero_id` smallint(3) NOT NULL DEFAULT '0',
  `item_0` int(10) NOT NULL DEFAULT '0',
  `item_1` int(10) NOT NULL DEFAULT '0',
  `item_2` int(10) NOT NULL DEFAULT '0',
  `item_3` int(10) NOT NULL DEFAULT '0',
  `item_4` int(10) NOT NULL DEFAULT '0',
  `item_5` int(10) NOT NULL DEFAULT '0',
  `kills` int(10) NOT NULL,
  `deaths` int(10) NOT NULL,
  `assists` int(10) NOT NULL,
  `leaver_status` tinyint(2) NOT NULL,
  `gold` int(10) NOT NULL,
  `last_hits` int(10) NOT NULL,
  `denies` int(10) NOT NULL,
  `gold_per_min` int(10) NOT NULL,
  `xp_per_min` int(10) NOT NULL,
  `gold_spent` int(10) NOT NULL,
  `hero_damage` int(10) NOT NULL,
  `tower_damage` int(10) NOT NULL,
  `hero_healing` int(10) NOT NULL,
  `level` tinyint(2) NOT NULL,
  PRIMARY KEY (`match_id`,`player_slot`),
  KEY `index_account_id` (`account_id`),
  KEY `index_hero_id` (`hero_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `players_econ_4` (
  `match_id` int(12) NOT NULL,
  `lobby_type` tinyint(2) NOT NULL DEFAULT '0',
  `game_mode` tinyint(2) NOT NULL,
  `radiant_win` tinyint(1) NOT NULL DEFAULT '0',
  `duration` int(10) NOT NULL,
  `cluster` int(10) NOT NULL,
  `tower_status_radiant` int(10) NOT NULL,
  `tower_status_dire` int(10) NOT NULL,
  `barracks_status_radiant` int(10) NOT NULL,
  `barracks_status_dire` int(10) NOT NULL,
  `radiant_kills` decimal(32,0) DEFAULT NULL,
  `radiant_deaths` decimal(32,0) DEFAULT NULL,
  `radiant_assists` decimal(32,0) DEFAULT NULL,
  `radiant_totalgold` decimal(33,0) DEFAULT NULL,
  `radiant_last_hits` decimal(32,0) DEFAULT NULL,
  `radiant_gold_per_min` decimal(14,4) DEFAULT NULL,
  `radiant_xp_per_min` decimal(14,4) DEFAULT NULL,
  `dire_kills` decimal(32,0) DEFAULT NULL,
  `dire_deaths` decimal(32,0) DEFAULT NULL,
  `dire_assists` decimal(32,0) DEFAULT NULL,
  `dire_totalgold` decimal(33,0) DEFAULT NULL,
  `dire_last_hits` decimal(32,0) DEFAULT NULL,
  `dire_gold_per_min` decimal(14,4) DEFAULT NULL,
  `dire_xp_per_min` decimal(14,4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `players_temp` (
  `match_id` int(20) NOT NULL,
  `account_id` bigint(255) DEFAULT NULL,
  `player_slot` smallint(3) NOT NULL,
  `hero_id` smallint(3) NOT NULL DEFAULT '0',
  `item_0` int(10) NOT NULL DEFAULT '0',
  `item_1` int(10) NOT NULL DEFAULT '0',
  `item_2` int(10) NOT NULL DEFAULT '0',
  `item_3` int(10) NOT NULL DEFAULT '0',
  `item_4` int(10) NOT NULL DEFAULT '0',
  `item_5` int(10) NOT NULL DEFAULT '0',
  `kills` int(10) NOT NULL,
  `deaths` int(10) NOT NULL,
  `assists` int(10) NOT NULL,
  `leaver_status` tinyint(2) NOT NULL,
  `gold` int(10) NOT NULL,
  `last_hits` int(10) NOT NULL,
  `denies` int(10) NOT NULL,
  `gold_per_min` int(10) NOT NULL,
  `xp_per_min` int(10) NOT NULL,
  `gold_spent` int(10) NOT NULL,
  `hero_damage` int(10) NOT NULL,
  `tower_damage` int(10) NOT NULL,
  `hero_healing` int(10) NOT NULL,
  `level` tinyint(2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `players_test` (
  `match_id` int(20) NOT NULL,
  `lobby_type` tinyint(2) DEFAULT '0',
  `game_mode` tinyint(2),
  `win` int(1) NOT NULL DEFAULT '0',
  `hero_id` smallint(3) NOT NULL DEFAULT '0',
  `duration` int(10),
  `start_time` int(12),
  `cluster` int(10),
  `item_0` int(10) NOT NULL DEFAULT '0',
  `item_1` int(10) NOT NULL DEFAULT '0',
  `item_2` int(10) NOT NULL DEFAULT '0',
  `item_3` int(10) NOT NULL DEFAULT '0',
  `item_4` int(10) NOT NULL DEFAULT '0',
  `item_5` int(10) NOT NULL DEFAULT '0',
  `kills` int(10) NOT NULL,
  `deaths` int(10) NOT NULL,
  `assists` int(10) NOT NULL,
  `leaver_status` tinyint(2) NOT NULL,
  `gold` int(10) NOT NULL,
  `last_hits` int(10) NOT NULL,
  `denies` int(10) NOT NULL,
  `gold_per_min` int(10) NOT NULL,
  `xp_per_min` int(10) NOT NULL,
  `gold_spent` int(10) NOT NULL,
  `hero_damage` int(10) NOT NULL,
  `tower_damage` int(10) NOT NULL,
  `hero_healing` int(10) NOT NULL,
  `level` tinyint(2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `players_test2` (
  `match_id` int(20) NOT NULL,
  `account_id` bigint(255) DEFAULT NULL,
  `player_slot` smallint(3) NOT NULL,
  `hero_id` smallint(3) NOT NULL DEFAULT '0',
  `item_0` int(10) NOT NULL DEFAULT '0',
  `item_1` int(10) NOT NULL DEFAULT '0',
  `item_2` int(10) NOT NULL DEFAULT '0',
  `item_3` int(10) NOT NULL DEFAULT '0',
  `item_4` int(10) NOT NULL DEFAULT '0',
  `item_5` int(10) NOT NULL DEFAULT '0',
  `kills` int(10) NOT NULL,
  `deaths` int(10) NOT NULL,
  `assists` int(10) NOT NULL,
  `leaver_status` tinyint(2) NOT NULL,
  `gold` int(10) NOT NULL,
  `last_hits` int(10) NOT NULL,
  `denies` int(10) NOT NULL,
  `gold_per_min` int(10) NOT NULL,
  `xp_per_min` int(10) NOT NULL,
  `gold_spent` int(10) NOT NULL,
  `hero_damage` int(10) NOT NULL,
  `tower_damage` int(10) NOT NULL,
  `hero_healing` int(10) NOT NULL,
  `level` tinyint(2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `q1_lobby_types1` (
  `startTimeDate` date DEFAULT NULL,
  `lobby_type` tinyint(2) NOT NULL DEFAULT '0',
  `count` bigint(21) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `q1_lobby_types2` (
  `startTimeDate` date DEFAULT NULL,
  `lobby_type` tinyint(2) NOT NULL DEFAULT '0',
  `count` bigint(21) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `q2_player_match_db_details` (
  `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `match_count` bigint(255) DEFAULT NULL,
  `player_count_total` bigint(255) DEFAULT NULL,
  `player_count_registered` bigint(255) DEFAULT NULL,
  `player_count_distinct` bigint(255) DEFAULT NULL,
  `heroes_played` bigint(255) DEFAULT NULL,
  `date_oldest` bigint(20) DEFAULT NULL,
  `match_oldest` bigint(255) DEFAULT NULL,
  `date_recent` bigint(20) DEFAULT NULL,
  `match_recent` bigint(255) DEFAULT NULL,
  PRIMARY KEY (`date_added`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `q3_game_mode_breakdown` (
  `game_mode` tinyint(2) NOT NULL,
  `nice_name` varchar(50) NOT NULL,
  `name` varchar(50) NOT NULL,
  `total` bigint(21) NOT NULL DEFAULT '0',
  PRIMARY KEY (`game_mode`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `q4_game_mode_hero_picks` (
  `game_mode` tinyint(2) NOT NULL,
  `hero_id` int(255) NOT NULL DEFAULT '0',
  `localized_name` varchar(255) DEFAULT NULL,
  `games_total` bigint(255) NOT NULL DEFAULT '0',
  `radiant_wins` bigint(255) DEFAULT NULL,
  `dire_wins` bigint(255) DEFAULT NULL,
  PRIMARY KEY (`game_mode`,`hero_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `q5_cluster_breakdown` (
  `game_mode` tinyint(2) NOT NULL,
  `cluster` int(10) NOT NULL,
  `region` tinyint(2) NOT NULL,
  `region_name` varchar(255) DEFAULT NULL,
  `games` bigint(21) NOT NULL DEFAULT '0',
  PRIMARY KEY (`region`,`cluster`,`game_mode`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `q6_aggregate_winrate_breakdown` (
  `range_start` bigint(20) DEFAULT NULL,
  `range_end` bigint(20) NOT NULL DEFAULT '0',
  `radiant_wins` bigint(30) DEFAULT NULL,
  `dire_wins` bigint(30) DEFAULT NULL,
  PRIMARY KEY (`range_end`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `q7_winrate_breakdown_duration_date` (
  `match_date` date NOT NULL DEFAULT '0000-00-00',
  `range_start` bigint(20) DEFAULT NULL,
  `range_end` bigint(20) NOT NULL DEFAULT '0',
  `radiant_wins` bigint(30) DEFAULT NULL,
  `dire_wins` bigint(30) DEFAULT NULL,
  PRIMARY KEY (`match_date`,`range_end`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
