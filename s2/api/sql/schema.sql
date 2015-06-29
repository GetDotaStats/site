SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE IF NOT EXISTS `s2_match` (
  `matchID` bigint(255) NOT NULL AUTO_INCREMENT,
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
  `dateRecorded` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`matchID`),
  KEY `indx_mod_numplayers` (`modID`,`numPlayers`),
  KEY `indx_mod_dedicated` (`modID`,`isDedicated`),
  KEY `indx_mod_duration` (`modID`,`matchDuration`),
  KEY `indx_mod_winner` (`modID`,`matchWinningTeamID`),
  KEY `indx_schemaVersion` (`schemaVersion`),
  KEY `indx_dateRecorded` (`dateRecorded`),
  KEY `modID` (`modID`,`isDedicated`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;

INSERT INTO `s2_match` (`matchID`, `matchAuthKey`, `modID`, `matchHostSteamID32`, `matchPhaseID`, `isDedicated`, `numPlayers`, `numRounds`, `matchWinningTeamID`, `matchDuration`, `schemaVersion`, `dateUpdated`, `dateRecorded`) VALUES
(1, 'RW9JOU1TSS', '7adfki234jlk23', 2875155, 3, 1, 4, 2, 5, 3954, 1, '2015-06-28 16:22:39', '2015-06-28 16:20:18'),
(2, 'ZTSNQPLHCX', '7adfki234jlk23', 2875155, 1, 1, 4, 1, NULL, NULL, 1, '2015-06-29 01:21:22', '2015-06-29 01:21:22'),
(3, 'WGCHMTJWD2', '7adfki234jlk23', 2875155, 3, 1, 4, 2, 5, 3954, 1, '2015-06-29 01:25:35', '2015-06-29 01:23:14');

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

INSERT INTO `s2_match_flags` (`matchID`, `modID`, `flag`, `dateRecorded`) VALUES
(1, '7adfki234jlk23', 'ctf15', '2015-06-28 16:20:18'),
(1, '7adfki234jlk23', 'kill50', '2015-06-28 16:20:18'),
(3, '7adfki234jlk23', 'ctf15', '2015-06-29 01:24:47'),
(3, '7adfki234jlk23', 'kill50', '2015-06-29 01:24:47');

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

INSERT INTO `s2_match_players` (`matchID`, `roundID`, `modID`, `steamID32`, `steamID64`, `playerName`, `teamID`, `slotID`, `heroID`, `connectionState`, `isWinner`, `dateRecorded`) VALUES
(1, 1, '7adfki234jlk23', 2875155, 76561197963140883, 'jimmydorry', 2, 1, 15, 2, NULL, '2015-06-28 16:20:18'),
(1, 1, '7adfki234jlk23', 2875156, 76561197963140884, 'ash47', 3, 2, 22, 2, NULL, '2015-06-28 16:20:18'),
(1, 1, '7adfki234jlk23', 2875157, 76561197963140885, 'BMD', 4, 3, 33, 2, NULL, '2015-06-28 16:20:18'),
(1, 1, '7adfki234jlk23', 2875158, 76561197963140886, 'sinz', 5, 4, 2, 2, NULL, '2015-06-28 16:20:18'),
(1, 2, '7adfki234jlk23', 2875155, 76561197963140883, 'jimmydorry', 2, 1, 15, 2, NULL, '2015-06-28 16:22:39'),
(1, 2, '7adfki234jlk23', 2875156, 76561197963140884, 'ash47', 3, 2, 22, 2, NULL, '2015-06-28 16:22:39'),
(1, 2, '7adfki234jlk23', 2875157, 76561197963140885, 'BMD', 4, 3, 33, 2, NULL, '2015-06-28 16:22:39'),
(1, 2, '7adfki234jlk23', 2875158, 76561197963140886, 'sinz', 5, 4, 2, 2, NULL, '2015-06-28 16:22:39'),
(3, 1, '7adfki234jlk23', 2875155, 76561197963140883, 'jimmydorry', 2, 1, 15, 2, 0, '2015-06-29 01:23:14'),
(3, 1, '7adfki234jlk23', 2875156, 76561197963140884, 'ash47', 3, 2, 22, 2, 0, '2015-06-29 01:23:14'),
(3, 1, '7adfki234jlk23', 2875157, 76561197963140885, 'BMD', 4, 3, 33, 2, 0, '2015-06-29 01:23:14'),
(3, 1, '7adfki234jlk23', 2875158, 76561197963140886, 'sinz', 5, 4, 2, 2, 1, '2015-06-29 01:23:14'),
(3, 2, '7adfki234jlk23', 2875155, 76561197963140883, 'jimmydorry', 2, 1, 15, 2, 0, '2015-06-29 01:25:36'),
(3, 2, '7adfki234jlk23', 2875156, 76561197963140884, 'ash47', 3, 2, 22, 2, 0, '2015-06-29 01:25:36'),
(3, 2, '7adfki234jlk23', 2875157, 76561197963140885, 'BMD', 4, 3, 33, 2, 0, '2015-06-29 01:25:36'),
(3, 2, '7adfki234jlk23', 2875158, 76561197963140886, 'sinz', 5, 4, 2, 2, 1, '2015-06-29 01:25:36');
