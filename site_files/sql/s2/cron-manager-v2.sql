--
-- Table structure for table `cron_tasks`
--

CREATE TABLE IF NOT EXISTS `cron_tasks` (
  `cron_id` bigint(255) NOT NULL,
  `cron_task` varchar(100) NOT NULL,
  `cron_task_group` varchar(100) DEFAULT NULL,
  `cron_parameters` text,
  `cron_priority` tinyint(2) NOT NULL DEFAULT '1',
  `cron_blocking` tinyint(1) NOT NULL DEFAULT '1',
  `cron_user` bigint(255) DEFAULT NULL,
  `cron_status` tinyint(1) NOT NULL DEFAULT '0',
  `cron_duration` bigint(255) DEFAULT NULL,
  `cron_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cron_tasks`
--
ALTER TABLE `cron_tasks`
ADD PRIMARY KEY (`cron_id`), ADD KEY `index_active_priority` (`cron_status`,`cron_priority`), ADD KEY `index_active_duration` (`cron_status`,`cron_duration`), ADD KEY `index_date` (`cron_date`), ADD KEY `index_task_date` (`cron_task_group`,`cron_date`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cron_tasks`
--
ALTER TABLE `cron_tasks`
MODIFY `cron_id` bigint(255) NOT NULL AUTO_INCREMENT;