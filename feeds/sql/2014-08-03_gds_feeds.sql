SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;


CREATE TABLE IF NOT EXISTS `feeds_categories` (
  `category_id` int(255) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(255) NOT NULL,
  `date_recorded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=5 ;

INSERT INTO `feeds_categories` (`category_id`, `category_name`, `date_recorded`) VALUES
(1, '2014 - 1 - Winter', '2014-08-03 05:49:52'),
(2, '2014 - 2 - Spring', '2014-08-03 05:49:52'),
(3, '2014 - 3 - Summer', '2014-08-03 05:49:52');

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

INSERT INTO `feeds_list` (`feed_id`, `feed_title`, `feed_url`, `feed_enabled`, `feed_category`, `date_recorded`) VALUES
(1, 'Sword Art Online II', 'http://www.nyaa.se/?page=rss&cats=1_37&filter=2&term=[HorribleSubs]%20Sword%20Art%20Online%20II%20%E2%80%93%20[720p].mkv', 1, 3, '2014-08-03 09:25:36'),
(2, 'Akame ga Kill!', 'http://www.nyaa.se/?page=rss&cats=1_37&filter=2&term=[HorribleSubs]%20Akame%20ga%20Kill!%20%E2%80%93%20[720p].mkv', 1, 3, '2014-08-03 09:26:03'),
(3, 'Hunter X Hunter', 'http://www.nyaa.se/?page=rss&term=%5BHorribleSubs%5D+Hunter+X+Hunter+-+720', 1, 1, '2014-08-03 09:26:37'),
(4, 'Ace of Diamond', 'http://www.nyaa.se/?page=rss&term=%5BCommie%5D+Ace+of+the+Diamond+-+.mkv', 1, 1, '2014-08-03 09:27:03'),
(5, 'Mahouka', 'http://www.nyaa.se/?page=rss&term=[HorribleSubs]%20Mahouka%20-%20[720p]', 1, 2, '2014-08-03 09:28:48'),
(6, 'Captain Earth', 'http://www.nyaa.se/?page=rss&term=[HorribleSubs]%20Captain%20Earth%20-%20[720p]', 1, 2, '2014-08-03 09:29:04'),
(7, 'Haikyuu!!', 'http://www.nyaa.se/?page=rss&term=[HorribleSubs]%20Haikyuu!!%20-%20[720p]', 1, 2, '2014-08-03 09:29:18'),
(8, 'Baby Steps', 'http://www.nyaa.se/?page=rss&term=[HorribleSubs]%20Baby%20Steps%20-%20[720p]', 1, 2, '2014-08-03 09:29:33'),
(9, 'Fairy Tail S2', 'http://www.nyaa.se/?page=rss&term=%5BHorribleSubs%5D+fairy%20tail%20s2+%5B720p%5D.mkv', 1, 2, '2014-08-03 09:29:52'),
(10, 'JoJo S2', 'http://www.nyaa.se/?page=rss&term=%5BHorribleSubs%5D+JoJo%27s%20Bizarre%20Adventure%20-%20Stardust%20Crusaders+%5B720p%5D.mkv', 1, 2, '2014-08-03 09:30:06'),
(11, 'Rowdy Sumo Wrestler Matsutaro', 'http://www.nyaa.se/?page=rss&term=[HorribleSubs]%20Rowdy%20Sumo%20Wrestler%20Matsutaro%20-%20[720p]', 1, 2, '2014-08-03 09:30:36'),
(12, 'Little Busters s2 - BD', 'http://www.nyaa.se/?page=rss&cats=1_37&filter=1&term=%5BRefrain+Subs%5D+Little+Busters%21+~Refrain~+BD+Vol+720p', 1, 1, '2014-08-03 09:31:20');

CREATE TABLE IF NOT EXISTS `mega_feed` (
  `item_guid` varchar(255) NOT NULL,
  `item_title` varchar(255) NOT NULL,
  `item_link` varchar(255) NOT NULL,
  `date_recorded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`date_recorded`,`item_title`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `mega_feed` (`item_guid`, `item_title`, `item_link`, `date_recorded`) VALUES
('http://www.nyaa.se/?page=view&tid=571514', '[HorribleSubs] Sword Art Online II - 01 [720p].mkv', 'http://www.nyaa.se/?page=download&tid=571514', '2014-07-05 16:58:15'),
('http://www.nyaa.se/?page=view&tid=571979', '[HorribleSubs] Akame ga Kill! - 01 [720p].mkv', 'http://www.nyaa.se/?page=download&tid=571979', '2014-07-06 16:09:25'),
('http://www.nyaa.se/?page=view&tid=574172', '[HorribleSubs] Sword Art Online II - 02 [720p].mkv', 'http://www.nyaa.se/?page=download&tid=574172', '2014-07-12 16:35:04'),
('http://www.nyaa.se/?page=view&tid=574636', '[HorribleSubs] Akame ga Kill! - 02 [720p].mkv', 'http://www.nyaa.se/?page=download&tid=574636', '2014-07-13 16:05:41'),
('http://www.nyaa.se/?page=view&tid=577119', '[HorribleSubs] Sword Art Online II - 03 [720p].mkv', 'http://www.nyaa.se/?page=download&tid=577119', '2014-07-19 16:36:36'),
('http://www.nyaa.se/?page=view&tid=577661', '[HorribleSubs] Akame ga Kill! - 03 [720p].mkv', 'http://www.nyaa.se/?page=download&tid=577661', '2014-07-20 17:12:53'),
('http://www.nyaa.se/?page=view&tid=580107', '[HorribleSubs] Sword Art Online II - 04 [720p].mkv', 'http://www.nyaa.se/?page=download&tid=580107', '2014-07-26 16:37:00'),
('http://www.nyaa.se/?page=view&tid=580546', '[HorribleSubs] Akame ga Kill! - 04 [720p].mkv', 'http://www.nyaa.se/?page=download&tid=580546', '2014-07-27 16:25:27'),
('http://www.nyaa.se/?page=view&tid=582880', '[HorribleSubs] Sword Art Online II - 05 [720p].mkv', 'http://www.nyaa.se/?page=download&tid=582880', '2014-08-02 16:36:31');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
