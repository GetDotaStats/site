SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

CREATE TABLE IF NOT EXISTS `feeds_categories` (
  `category_id` int(255) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(255) NOT NULL,
  `date_recorded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=5 ;

CREATE TABLE IF NOT EXISTS `feeds_list` (
  `feed_id` int(255) NOT NULL AUTO_INCREMENT,
  `feed_title` varchar(255) NOT NULL,
  `feed_url` varchar(255) NOT NULL,
  `feed_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `feed_category` int(255) NOT NULL,
  `date_recorded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`feed_id`),
  UNIQUE KEY `feed_url` (`feed_url`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=13 ;

CREATE TABLE IF NOT EXISTS `mega_feed` (
  `item_guid` varchar(255) NOT NULL,
  `item_title` varchar(255) NOT NULL,
  `item_link` varchar(255) NOT NULL,
  `date_recorded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`date_recorded`,`item_title`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
