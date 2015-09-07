CREATE TABLE IF NOT EXISTS `stat_highscore_mods` (
  `modID` varchar(255) NOT NULL,
  `highscoreID` varchar(255) NOT NULL,
  `steamID32` bigint(255) NOT NULL,
  `highscoreAuthKey` varchar(255) NOT NULL,
  `userName` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `highscoreValue` int(20) NOT NULL,
  `date_recorded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `stat_highscore_mods_schema` (
  `highscoreIdentifier` int(11) NOT NULL,
  `highscoreID` varchar(255) NOT NULL,
  `modID` varchar(255) NOT NULL,
  `highscoreName` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `highscoreDescription` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `highscoreActive` tinyint(1) NOT NULL DEFAULT '0',
  `highscoreObjective` varchar(10) NOT NULL DEFAULT 'max',
  `highscoreOperator` varchar(10) NOT NULL DEFAULT 'multiply',
  `highscoreFactor` decimal(10,2) NOT NULL DEFAULT '1.00',
  `highscoreDecimals` int(2) NOT NULL DEFAULT '2',
  `date_recorded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


ALTER TABLE `stat_highscore_mods`
 ADD PRIMARY KEY (`modID`,`highscoreID`,`steamID32`), ADD KEY `highscoreID_value` (`highscoreID`,`highscoreValue`), ADD KEY `date_recorded` (`date_recorded`), ADD KEY `highscoreAuthKey` (`highscoreAuthKey`);

ALTER TABLE `stat_highscore_mods_schema`
 ADD PRIMARY KEY (`highscoreIdentifier`), ADD KEY `highscoreActive` (`highscoreActive`), ADD KEY `highscoreObjective` (`highscoreObjective`), ADD KEY `highscoreOperator` (`highscoreOperator`), ADD KEY `highscoreFactor` (`highscoreFactor`), ADD KEY `date_recorded` (`date_recorded`), ADD KEY `modID` (`modID`), ADD KEY `highscoreID` (`highscoreID`);

ALTER TABLE `stat_highscore_mods_schema`
MODIFY `highscoreIdentifier` int(11) NOT NULL AUTO_INCREMENT;