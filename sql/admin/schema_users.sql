CREATE TABLE IF NOT EXISTS `chat_users` (
  `user_id32` bigint(255) NOT NULL,
  `user_id64` bigint(255) NOT NULL,
  `chat_salt` text NOT NULL,
  `chat_permission` tinyint(1) NOT NULL DEFAULT '0',
  `chat_permissor` bigint(255) NOT NULL,
  `date_recorded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `gds_users` (
  `user_id64` bigint(255) NOT NULL,
  `user_id32` bigint(255) NOT NULL,
  `user_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `user_avatar` varchar(255) DEFAULT NULL,
  `user_avatar_medium` varchar(255) DEFAULT NULL,
  `user_avatar_large` varchar(255) DEFAULT NULL,
  `date_recorded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `gds_users_mmr` (
  `user_id32` bigint(255) NOT NULL,
  `user_id64` bigint(255) NOT NULL,
  `user_name` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `user_games` int(20) NOT NULL,
  `user_mmr_solo` int(10) NOT NULL,
  `user_mmr_party` int(10) NOT NULL,
  `user_stats_disabled` tinyint(1) NOT NULL DEFAULT '0',
  `date_recorded` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `gds_users_options` (
  `user_id32` bigint(20) NOT NULL,
  `user_id64` bigint(20) NOT NULL,
  `mmr_public` tinyint(1) NOT NULL DEFAULT '0',
  `date_updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `date_recorded` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `gds_users_sessions` (
  `user_id64` varchar(255) NOT NULL,
  `user_cookie` varchar(255) NOT NULL,
  `remote_ip` varchar(255) NOT NULL,
  `date_recorded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `gds_users_options` (
  `user_id32` bigint(20) NOT NULL,
  `user_id64` bigint(20) NOT NULL,
  `user_email` varchar(255) DEFAULT NULL,
  `sub_dev_news` tinyint(1) NOT NULL DEFAULT '0',
  `mmr_public` tinyint(1) NOT NULL DEFAULT '0',
  `date_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `date_recorded` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `chat_users`
 ADD PRIMARY KEY (`user_id32`);

ALTER TABLE `gds_users`
 ADD PRIMARY KEY (`user_id64`);

ALTER TABLE `gds_users_mmr`
 ADD PRIMARY KEY (`user_id32`,`date_recorded`), ADD KEY `user_id64` (`user_id64`), ADD KEY `date_recorded` (`date_recorded`), ADD KEY `user_mmr_solo` (`user_mmr_solo`), ADD KEY `user_mmr_party` (`user_mmr_party`), ADD KEY `user_stats_disabled` (`user_stats_disabled`), ADD KEY `user_games` (`user_games`), ADD KEY `user_id32` (`user_id32`);

ALTER TABLE `gds_users_options`
 ADD PRIMARY KEY (`user_id64`), ADD KEY `user_id32` (`user_id32`), ADD KEY `mmr_public` (`mmr_public`), ADD KEY `date_updated` (`date_updated`), ADD KEY `date_recorded` (`date_recorded`);

ALTER TABLE `gds_users_sessions`
 ADD PRIMARY KEY (`user_id64`,`date_recorded`);

ALTER TABLE `gds_users_options`
 ADD PRIMARY KEY (`user_id64`), ADD UNIQUE KEY `user_email` (`user_email`), ADD KEY `user_id32` (`user_id32`), ADD KEY `mmr_public` (`mmr_public`), ADD KEY `date_updated` (`date_updated`), ADD KEY `date_recorded` (`date_recorded`), ADD KEY `sub_dev_news` (`sub_dev_news`);
