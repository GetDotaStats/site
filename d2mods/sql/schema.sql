SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

CREATE DATABASE `dota2_test` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `dota2_test`;

CREATE TABLE IF NOT EXISTS `mod_list` (
  `mod_id` int(255) NOT NULL AUTO_INCREMENT,
  `steam_id64` bigint(255) NOT NULL,
  `mod_identifier` varchar(255) NOT NULL,
  `mod_name` varchar(255) NOT NULL,
  `mod_description` text,
  `mod_workshop_link` varchar(255) DEFAULT NULL,
  `mod_steam_group` varchar(255) DEFAULT NULL,
  `mod_public_key` text NOT NULL,
  `mod_private_key` text NOT NULL,
  `mod_active` tinyint(1) NOT NULL DEFAULT '1',
  `date_recorded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`mod_id`),
  KEY `user_id` (`steam_id64`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

CREATE TABLE IF NOT EXISTS `test_landing` (
  `test_id` bigint(255) NOT NULL AUTO_INCREMENT,
  `message` text NOT NULL,
  `remote_ip` varchar(50) NOT NULL,
  `date_recorded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`test_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
