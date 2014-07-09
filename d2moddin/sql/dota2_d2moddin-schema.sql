SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;


CREATE TABLE IF NOT EXISTS `stats_production` (
  `production_id` int(255) NOT NULL AUTO_INCREMENT,
  `lobby_total` int(255) NOT NULL,
  `lobby_wait` int(255) NOT NULL,
  `lobby_play` int(255) NOT NULL,
  `lobby_queue` int(255) NOT NULL,
  `date_recorded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`production_id`),
  KEY `date_recorded` (`date_recorded`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=403 ;

CREATE TABLE IF NOT EXISTS `stats_production_mods` (
  `mod_id` int(255) NOT NULL AUTO_INCREMENT,
  `mod_name` varchar(255) NOT NULL,
  `mod_version` varchar(255) NOT NULL,
  `mod_lobbies` int(255) NOT NULL,
  `date_recorded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`mod_id`),
  UNIQUE KEY `index_date_name` (`date_recorded`,`mod_name`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=15 ;

CREATE TABLE IF NOT EXISTS `stats_production_regions` (
  `region_name` varchar(255) NOT NULL,
  `region_id` int(255) NOT NULL,
  `region_servercount` int(255) NOT NULL,
  `region_playing` int(255) NOT NULL,
  `date_recorded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`date_recorded`,`region_name`),
  KEY `rn_dc` (`region_name`,`date_recorded`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `stats_production_servers` (
  `region_id` int(255) NOT NULL,
  `server_name` varchar(255) NOT NULL,
  `server_ip` varchar(255) NOT NULL,
  `server_activeinstances` int(255) NOT NULL,
  `server_maxinstances` int(255) NOT NULL,
  `date_recorded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`date_recorded`,`server_name`,`region_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
