SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

CREATE TABLE IF NOT EXISTS `s2_match` (
`matchID` bigint(255) NOT NULL,
  `matchAuthKey` varchar(10) NOT NULL,
  `modID` varchar(255) NOT NULL,
  `matchHostSteamID32` bigint(100) NOT NULL,
  `matchPhaseID` tinyint(1) NOT NULL,
  `isDedicated` tinyint(1) NOT NULL,
  `numPlayers` int(10) NOT NULL,
  `numRounds` int(10) NOT NULL DEFAULT '1',
  `matchWinningTeamID` tinyint(2) DEFAULT NULL,
  `matchDuration` int(50) DEFAULT NULL,
  `schemaVersion` int(1) NOT NULL,
  `dateUpdated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `dateRecorded` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `s2_match_flags` (
  `matchID` bigint(255) NOT NULL,
  `modID` varchar(255) NOT NULL,
  `flag` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `dateRecorded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `s2_match_players` (
  `matchID` bigint(255) NOT NULL,
  `roundID` int(10) NOT NULL DEFAULT '1',
  `modID` varchar(255) NOT NULL,
  `steamID32` bigint(255) NOT NULL,
  `steamID64` bigint(255) NOT NULL,
  `playerName` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `teamID` int(10) NOT NULL,
  `slotID` int(10) NOT NULL,
  `heroID` int(255) NOT NULL,
  `connectionState` tinyint(1) NOT NULL,
  `isWinner` tinyint(1) DEFAULT NULL,
  `dateRecorded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


ALTER TABLE `s2_match`
 ADD PRIMARY KEY (`matchID`), ADD KEY `indx_mod_numplayers` (`modID`,`numPlayers`), ADD KEY `indx_mod_dedicated` (`modID`,`isDedicated`), ADD KEY `indx_mod_duration` (`modID`,`matchDuration`), ADD KEY `indx_mod_winner` (`modID`,`matchWinningTeamID`), ADD KEY `indx_schemaVersion` (`schemaVersion`), ADD KEY `indx_dateRecorded` (`dateRecorded`), ADD KEY `modID` (`modID`,`isDedicated`);

ALTER TABLE `s2_match_flags`
 ADD PRIMARY KEY (`matchID`,`flag`), ADD KEY `indx_modID` (`modID`), ADD KEY `indx_dateRecorded` (`dateRecorded`);

ALTER TABLE `s2_match_players`
 ADD PRIMARY KEY (`matchID`,`roundID`,`steamID32`), ADD KEY `indx_match_team_slot` (`matchID`,`teamID`,`slotID`), ADD KEY `indx_dateRecorded` (`dateRecorded`), ADD KEY `indx_mod_connection` (`modID`,`connectionState`), ADD KEY `indx_mod_hero` (`modID`,`heroID`);


ALTER TABLE `s2_match`
MODIFY `matchID` bigint(255) NOT NULL AUTO_INCREMENT;