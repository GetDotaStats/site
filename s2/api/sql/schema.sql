SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

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
  `playerName` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `teamID` int(10) DEFAULT NULL,
  `slotID` int(10) NOT NULL,
  `heroID` int(255) DEFAULT NULL,
  `connectionState` tinyint(1) NOT NULL,
  `isWinner` tinyint(1) DEFAULT NULL,
  `dateRecorded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`matchID`,`roundID`,`steamID32`),
  KEY `indx_match_team_slot` (`matchID`,`teamID`,`slotID`),
  KEY `indx_dateRecorded` (`dateRecorded`),
  KEY `indx_mod_connection` (`modID`,`connectionState`),
  KEY `indx_mod_hero` (`modID`,`heroID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;