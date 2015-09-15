CREATE TABLE IF NOT EXISTS `s2_match` (
  `matchID` bigint(255) NOT NULL AUTO_INCREMENT,
  `matchAuthKey` varchar(10) NOT NULL,
  `modID` varchar(255) NOT NULL,
  `matchHostSteamID32` bigint(100) NOT NULL,
  `matchPhaseID` tinyint(1) NOT NULL,
  `isDedicated` tinyint(1) NOT NULL,
  `matchMapName` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `numPlayers` int(10) NOT NULL,
  `numRounds` int(10) NOT NULL DEFAULT '1',
  `matchWinningTeamID` tinyint(2) DEFAULT NULL,
  `matchDuration` int(50) DEFAULT NULL,
  `schemaVersion` int(1) NOT NULL,
  `dateUpdated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `dateRecorded` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`matchID`),
  KEY `indx_mod_numplayers` (`modID`,`numPlayers`),
  KEY `indx_mod_dedicated` (`modID`,`isDedicated`),
  KEY `indx_mod_duration` (`modID`,`matchDuration`),
  KEY `indx_mod_winner` (`modID`,`matchWinningTeamID`),
  KEY `indx_mod_map` (`modID`,`matchMapName`),
  KEY `indx_schemaVersion` (`schemaVersion`),
  KEY `indx_dateRecorded` (`dateRecorded`),
  KEY `modID` (`modID`,`isDedicated`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `s2_match_client_details` (
  `matchID` bigint(255) NOT NULL,
  `modID` varchar(30) NOT NULL,
  `steamID32` bigint(255) NOT NULL,
  `steamID64` bigint(255) NOT NULL,
  `clientIP` varchar(30) NOT NULL,
  `isHost` tinyint(1) NOT NULL,
  `dateRecorded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`matchID`,`steamID32`),
  KEY `indx_dateRecorded` (`dateRecorded`),
  KEY `indx_steamID32` (`steamID32`),
  KEY `indx_steamID64` (`steamID64`),
  KEY `indx_mod` (`modID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `s2_match_custom` (
  `matchID` int(255) NOT NULL,
  `modID` int(255) NOT NULL,
  `schemaID` int(255) NOT NULL,
  `round` tinyint(1) NOT NULL,
  `fieldOrder` tinyint(1) NOT NULL,
  `fieldValue` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`matchID`,`round`,`fieldOrder`),
  KEY `index_mod_schema_round_order` (`modID`,`schemaID`,`round`,`fieldOrder`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `s2_match_flags` (
  `matchID` bigint(255) NOT NULL,
  `modID` varchar(255) NOT NULL,
  `flagName` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `flagValue` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `dateRecorded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`matchID`,`flagName`),
  KEY `indx_dateRecorded` (`dateRecorded`),
  KEY `indx_mod_flag` (`modID`,`flagName`,`flagValue`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `s2_match_players` (
  `matchID` bigint(255) NOT NULL,
  `roundID` int(10) NOT NULL DEFAULT '1',
  `modID` varchar(255) NOT NULL,
  `steamID32` bigint(255) NOT NULL,
  `steamID64` bigint(255) NOT NULL,
  `connectionState` tinyint(1) NOT NULL,
  `isWinner` tinyint(1) DEFAULT NULL,
  `dateRecorded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`matchID`,`roundID`,`steamID32`),
  KEY `indx_match_team_slot` (`matchID`),
  KEY `indx_dateRecorded` (`dateRecorded`),
  KEY `indx_mod_connection` (`modID`,`connectionState`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `s2_match_players_custom` (
  `matchID` int(255) NOT NULL,
  `modID` int(255) NOT NULL,
  `schemaID` int(255) NOT NULL,
  `round` tinyint(1) NOT NULL,
  `userID32` bigint(255) NOT NULL,
  `fieldOrder` tinyint(1) NOT NULL,
  `fieldValue` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`matchID`,`round`,`userID32`,`fieldOrder`),
  KEY `index_mod_user` (`modID`,`userID32`),
  KEY `index_mod_schema_round_order` (`modID`,`schemaID`,`round`,`fieldOrder`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `s2_match_players_name` (
  `steamID32` bigint(255) NOT NULL,
  `steamID64` bigint(255) NOT NULL,
  `playerName` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `dateUpdated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`steamID32`),
  KEY `index_steam_id64` (`steamID64`),
  KEY `index_player_name` (`playerName`),
  KEY `index_date_updated` (`dateUpdated`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `s2_mod_custom_schema` (
  `schemaID` int(255) NOT NULL AUTO_INCREMENT,
  `modID` int(255) NOT NULL,
  `schemaAuth` varchar(16) NOT NULL,
  `schemaVersion` int(11) NOT NULL,
  `schemaApproved` tinyint(1) NOT NULL,
  `schemaRejected` tinyint(1) NOT NULL,
  `schemaRejectedReason` text,
  `schemaSubmitterUserID64` bigint(20) NOT NULL,
  `schemaApproverUserID64` bigint(20) DEFAULT NULL,
  `dateRecorded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`schemaID`),
  UNIQUE KEY `index_modID_version` (`modID`,`schemaVersion`),
  UNIQUE KEY `index_schemaAuth` (`schemaAuth`),
  KEY `index_dateRecorded` (`dateRecorded`),
  KEY `index_schemaApproved` (`schemaApproved`),
  KEY `index_schemaRejected` (`schemaRejected`),
  KEY `index_schemaSubmitterUserID64` (`schemaSubmitterUserID64`),
  KEY `index_schemaApproverUserID64` (`schemaApproverUserID64`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `s2_mod_custom_schema_fields` (
  `schemaID` int(255) NOT NULL,
  `fieldType` tinyint(1) NOT NULL,
  `fieldOrder` tinyint(1) NOT NULL,
  `customValueObjective` tinyint(1) NOT NULL,
  `customValueDisplay` varchar(100) COLLATE utf8_bin NOT NULL,
  `customValueName` varchar(100) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`schemaID`,`fieldType`,`fieldOrder`),
  KEY `index_schema_objective` (`schemaID`,`customValueObjective`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;