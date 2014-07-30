CREATE TABLE IF NOT EXISTS `match_players` (
  `match_id` varchar(255) NOT NULL,
  `team_id` int(255) NOT NULL,
  `player_slot` int(255) NOT NULL,
  `account_id` bigint(255) NOT NULL,
  `steam_id` bigint(255) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `kills` int(255) NOT NULL,
  `assists` int(255) NOT NULL,
  `deaths` int(255) NOT NULL,
  `claimed_denies` int(255) NOT NULL,
  `claimed_farm_gold` int(255) NOT NULL,
  `denies` int(255) NOT NULL,
  `gold` int(255) NOT NULL,
  `gold_per_min` int(255) NOT NULL,
  `hero_damage` int(255) NOT NULL,
  `hero_healing` int(255) NOT NULL,
  `hero_id` int(255) NOT NULL,
  `last_hits` int(255) NOT NULL,
  `leaver_status` int(255) NOT NULL,
  `level` int(255) NOT NULL,
  `tower_damage` int(255) NOT NULL,
  `xp_per_minute` int(255) NOT NULL,
  `item1` int(255) NOT NULL,
  `item2` int(255) NOT NULL,
  `item3` int(255) NOT NULL,
  `item4` int(255) NOT NULL,
  `item5` int(255) NOT NULL,
  `item6` int(255) NOT NULL,
  PRIMARY KEY (`match_id`,`team_id`,`player_slot`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `match_stats`
--

CREATE TABLE IF NOT EXISTS `match_stats` (
  `_id` varchar(255) NOT NULL,
  `match_id` varchar(255) NOT NULL,
  `mod` varchar(255) NOT NULL,
  `automatic_surrender` tinyint(1) NOT NULL,
  `match_date` int(255) NOT NULL,
  `duration` smallint(5) NOT NULL,
  `first_blood_time` smallint(5) NOT NULL,
  `good_guys_win` tinyint(1) NOT NULL,
  `mass_disconnect` tinyint(1) NOT NULL,
  `num_teams` tinyint(2) NOT NULL,
  `num_players` tinyint(2) NOT NULL,
  `server_addr` varchar(255) NOT NULL,
  `server_version` int(255) NOT NULL,
  `match_ended` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`_id`),
  KEY `idx_date_mod` (`match_ended`,`mod`),
  KEY `idx_mod` (`mod`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `stats_production`
--

CREATE TABLE IF NOT EXISTS `stats_production` (
  `lobby_total` int(255) NOT NULL DEFAULT '0',
  `lobby_wait` int(255) NOT NULL DEFAULT '0',
  `lobby_play` int(255) NOT NULL DEFAULT '0',
  `lobby_queue` int(255) NOT NULL DEFAULT '0',
  `date_recorded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`date_recorded`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `stats_production_mods`
--

CREATE TABLE IF NOT EXISTS `stats_production_mods` (
  `mod_id` int(255) NOT NULL AUTO_INCREMENT,
  `mod_name` varchar(255) NOT NULL,
  `mod_version` varchar(255) NOT NULL,
  `mod_lobbies` int(255) NOT NULL,
  `date_recorded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`mod_id`),
  UNIQUE KEY `index_date_name` (`date_recorded`,`mod_name`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=16469 ;

-- --------------------------------------------------------

--
-- Table structure for table `stats_production_regions`
--

CREATE TABLE IF NOT EXISTS `stats_production_regions` (
  `region_name` varchar(255) NOT NULL,
  `region_id` int(255) NOT NULL,
  `region_servercount` int(255) NOT NULL,
  `region_playing` int(255) NOT NULL,
  `date_recorded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`date_recorded`,`region_name`),
  KEY `rn_dc` (`region_name`,`date_recorded`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `stats_production_servers`
--

CREATE TABLE IF NOT EXISTS `stats_production_servers` (
  `region_id` int(255) NOT NULL,
  `server_name` varchar(255) NOT NULL,
  `server_ip` varchar(255) NOT NULL,
  `server_activeinstances` int(255) NOT NULL,
  `server_maxinstances` int(255) NOT NULL,
  `date_recorded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`date_recorded`,`server_name`,`region_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;