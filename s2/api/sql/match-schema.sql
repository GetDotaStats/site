SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE IF NOT EXISTS `s2_match` (
  `matchID`            BIGINT(255)      NOT NULL AUTO_INCREMENT,
  `matchAuthKey`       VARCHAR(10)      NOT NULL,
  `modID`              VARCHAR(255)     NOT NULL,
  `matchHostSteamID32` BIGINT(100)      NOT NULL,
  `matchPhaseID`       TINYINT(1)       NOT NULL,
  `isDedicated`        TINYINT(1)       NOT NULL,
  `matchMapName`       VARCHAR(100)
                       CHARACTER SET utf8
                       COLLATE utf8_bin NOT NULL,
  `numPlayers`         INT(10)          NOT NULL,
  `numRounds`          INT(10)          NOT NULL DEFAULT '1',
  `matchWinningTeamID` TINYINT(2) DEFAULT NULL,
  `matchDuration`      INT(50) DEFAULT NULL,
  `schemaVersion`      INT(1)           NOT NULL,
  `dateUpdated`        TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `dateRecorded`       TIMESTAMP        NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`matchID`),
  KEY `indx_mod_numplayers` (`modID`, `numPlayers`),
  KEY `indx_mod_dedicated` (`modID`, `isDedicated`),
  KEY `indx_mod_duration` (`modID`, `matchDuration`),
  KEY `indx_mod_winner` (`modID`, `matchWinningTeamID`),
  KEY `indx_mod_map` (`modID`, `matchMapName`),
  KEY `indx_schemaVersion` (`schemaVersion`),
  KEY `indx_dateRecorded` (`dateRecorded`),
  KEY `modID` (`modID`, `isDedicated`)
)
  ENGINE =InnoDB
  DEFAULT CHARSET =latin1;

CREATE TABLE IF NOT EXISTS `s2_match_client_details` (
  `matchID`      BIGINT(255) NOT NULL,
  `modID`        VARCHAR(30) NOT NULL,
  `steamID32`    BIGINT(255) NOT NULL,
  `steamID64`    BIGINT(255) NOT NULL,
  `clientIP`     VARCHAR(30) NOT NULL,
  `isHost`       TINYINT(1)  NOT NULL,
  `dateRecorded` TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`matchID`, `steamID32`),
  KEY `indx_dateRecorded` (`dateRecorded`),
  KEY `indx_steamID32` (`steamID32`),
  KEY `indx_steamID64` (`steamID64`),
  KEY `indx_mod` (`modID`)
)
  ENGINE =InnoDB
  DEFAULT CHARSET =latin1;

CREATE TABLE IF NOT EXISTS `s2_match_flags` (
  `matchID`      BIGINT(255)      NOT NULL,
  `modID`        VARCHAR(255)     NOT NULL,
  `flagName`     VARCHAR(100)
                 CHARACTER SET utf8
                 COLLATE utf8_bin NOT NULL,
  `flagValue`    VARCHAR(100)
                 CHARACTER SET utf8
                 COLLATE utf8_bin NOT NULL,
  `dateRecorded` TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`matchID`, `flagName`),
  KEY `indx_dateRecorded` (`dateRecorded`),
  KEY `indx_mod_flag` (`modID`, `flagName`, `flagValue`)
)
  ENGINE =InnoDB
  DEFAULT CHARSET =latin1;

CREATE TABLE IF NOT EXISTS `s2_match_players` (
  `matchID`         BIGINT(255)      NOT NULL,
  `roundID`         INT(10)          NOT NULL DEFAULT '1',
  `modID`           VARCHAR(255)     NOT NULL,
  `steamID32`       BIGINT(255)      NOT NULL,
  `steamID64`       BIGINT(255)      NOT NULL,
  `playerName`      VARCHAR(255)
                    CHARACTER SET utf8
                    COLLATE utf8_bin NOT NULL,
  `teamID`          INT(10) DEFAULT NULL,
  `slotID`          INT(10)          NOT NULL,
  `heroID`          INT(255) DEFAULT NULL,
  `connectionState` TINYINT(1)       NOT NULL,
  `isWinner`        TINYINT(1) DEFAULT NULL,
  `dateRecorded`    TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`matchID`, `roundID`, `steamID32`),
  KEY `indx_match_team_slot` (`matchID`, `teamID`, `slotID`),
  KEY `indx_dateRecorded` (`dateRecorded`),
  KEY `indx_mod_connection` (`modID`, `connectionState`),
  KEY `indx_mod_hero` (`modID`, `heroID`)
)
  ENGINE =InnoDB
  DEFAULT CHARSET =latin1;

#############

CREATE TABLE IF NOT EXISTS `s2_match_players_custom_schema` (
  `modID`                  VARCHAR(255) NOT NULL,
  `schemaVersion`          INT(10)      NOT NULL,
  `customStatName01`       VARCHAR(255) NULL,
  `customStatNamePretty01` VARCHAR(255) NULL,
  `customStatName02`       VARCHAR(255) NULL,
  `customStatNamePretty02` VARCHAR(255) NULL,
  `customStatName03`       VARCHAR(255) NULL,
  `customStatNamePretty03` VARCHAR(255) NULL,
  `customStatName04`       VARCHAR(255) NULL,
  `customStatNamePretty04` VARCHAR(255) NULL,
  `customStatName05`       VARCHAR(255) NULL,
  `customStatNamePretty05` VARCHAR(255) NULL,
  `customStatName06`       VARCHAR(255) NULL,
  `customStatNamePretty06` VARCHAR(255) NULL,
  `customStatName07`       VARCHAR(255) NULL,
  `customStatNamePretty07` VARCHAR(255) NULL,
  `customStatName08`       VARCHAR(255) NULL,
  `customStatNamePretty08` VARCHAR(255) NULL,
  `customStatName09`       VARCHAR(255) NULL,
  `customStatNamePretty09` VARCHAR(255) NULL,
  `customStatName10`       VARCHAR(255) NULL,
  `customStatNamePretty10` VARCHAR(255) NULL,
  `customStatName11`       VARCHAR(255) NULL,
  `customStatNamePretty11` VARCHAR(255) NULL,
  `customStatName12`       VARCHAR(255) NULL,
  `customStatNamePretty12` VARCHAR(255) NULL,
  `customStatName13`       VARCHAR(255) NULL,
  `customStatNamePretty13` VARCHAR(255) NULL,
  `customStatName14`       VARCHAR(255) NULL,
  `customStatNamePretty14` VARCHAR(255) NULL,
  `customStatName15`       VARCHAR(255) NULL,
  `customStatNamePretty15` VARCHAR(255) NULL,
  `customStatName16`       VARCHAR(255) NULL,
  `customStatNamePretty16` VARCHAR(255) NULL,
  `customStatName17`       VARCHAR(255) NULL,
  `customStatNamePretty17` VARCHAR(255) NULL,
  `customStatName18`       VARCHAR(255) NULL,
  `customStatNamePretty18` VARCHAR(255) NULL,
  `customStatName19`       VARCHAR(255) NULL,
  `customStatNamePretty19` VARCHAR(255) NULL,
  `customStatName20`       VARCHAR(255) NULL,
  `customStatNamePretty20` VARCHAR(255) NULL,
  `dateRecorded`           TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`modID`, `schemaVersion`),
  KEY `indx_dateRecorded` (`dateRecorded`),
  KEY `indx_mod_schema` (`modID`, `schemaVersion`)
)
  ENGINE =InnoDB
  DEFAULT CHARSET =latin1;

CREATE TABLE IF NOT EXISTS `s2_match_players_custom` (
  `matchID`       BIGINT(255)  NOT NULL,
  `roundID`       INT(10)      NOT NULL DEFAULT '1',
  `modID`         VARCHAR(255) NOT NULL,
  `steamID32`     BIGINT(255)  NOT NULL,
  `steamID64`     BIGINT(255)  NOT NULL,
  `schemaVersion` INT(10)      NOT NULL,
  `customStat01`  VARCHAR(255) NULL,
  `customStat02`  VARCHAR(255) NULL,
  `customStat03`  VARCHAR(255) NULL,
  `customStat04`  VARCHAR(255) NULL,
  `customStat05`  VARCHAR(255) NULL,
  `customStat06`  VARCHAR(255) NULL,
  `customStat07`  VARCHAR(255) NULL,
  `customStat08`  VARCHAR(255) NULL,
  `customStat09`  VARCHAR(255) NULL,
  `customStat10`  VARCHAR(255) NULL,
  `customStat11`  VARCHAR(255) NULL,
  `customStat12`  VARCHAR(255) NULL,
  `customStat13`  VARCHAR(255) NULL,
  `customStat14`  VARCHAR(255) NULL,
  `customStat15`  VARCHAR(255) NULL,
  `customStat16`  VARCHAR(255) NULL,
  `customStat17`  VARCHAR(255) NULL,
  `customStat18`  VARCHAR(255) NULL,
  `customStat19`  VARCHAR(255) NULL,
  `customStat20`  VARCHAR(255) NULL,
  `isWinner`      TINYINT(1)   NOT NULL,
  `dateRecorded`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`matchID`, `roundID`, `steamID32`),
  KEY `indx_dateRecorded` (`dateRecorded`),
  KEY `indx_mod_schema` (`modID`, `schemaVersion`),
  KEY `indx_mod_customStat01_winner` (`modID`, `customStat01`, `isWinner`),
  KEY `indx_mod_customStat02_winner` (`modID`, `customStat02`, `isWinner`),
  KEY `indx_mod_customStat03_winner` (`modID`, `customStat03`, `isWinner`),
  KEY `indx_mod_customStat04_winner` (`modID`, `customStat04`, `isWinner`),
  KEY `indx_mod_customStat05_winner` (`modID`, `customStat05`, `isWinner`),
  KEY `indx_mod_customStat06_winner` (`modID`, `customStat06`, `isWinner`),
  KEY `indx_mod_customStat07_winner` (`modID`, `customStat07`, `isWinner`),
  KEY `indx_mod_customStat08_winner` (`modID`, `customStat08`, `isWinner`),
  KEY `indx_mod_customStat09_winner` (`modID`, `customStat09`, `isWinner`),
  KEY `indx_mod_customStat10_winner` (`modID`, `customStat10`, `isWinner`),
  KEY `indx_mod_customStat11_winner` (`modID`, `customStat11`, `isWinner`),
  KEY `indx_mod_customStat12_winner` (`modID`, `customStat12`, `isWinner`),
  KEY `indx_mod_customStat13_winner` (`modID`, `customStat13`, `isWinner`),
  KEY `indx_mod_customStat14_winner` (`modID`, `customStat14`, `isWinner`),
  KEY `indx_mod_customStat15_winner` (`modID`, `customStat15`, `isWinner`),
  KEY `indx_mod_customStat16_winner` (`modID`, `customStat16`, `isWinner`),
  KEY `indx_mod_customStat17_winner` (`modID`, `customStat17`, `isWinner`),
  KEY `indx_mod_customStat18_winner` (`modID`, `customStat18`, `isWinner`),
  KEY `indx_mod_customStat19_winner` (`modID`, `customStat19`, `isWinner`),
  KEY `indx_mod_customStat20_winner` (`modID`, `customStat20`, `isWinner`)
)
  ENGINE =InnoDB
  DEFAULT CHARSET =latin1;