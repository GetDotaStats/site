--
-- Table structure for table `cron_services`
--

CREATE TABLE IF NOT EXISTS `cron_services` (
`instance_id` bigint(255) NOT NULL,
  `service_name` varchar(255) NOT NULL,
  `execution_time` bigint(255) NOT NULL,
  `performance_index1` bigint(255) NOT NULL,
  `performance_index2` bigint(255) DEFAULT NULL,
  `performance_index3` bigint(255) DEFAULT NULL,
  `date_recorded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `cron_services`
 ADD PRIMARY KEY (`instance_id`), ADD KEY `index_service_date` (`service_name`,`date_recorded`), ADD KEY `index_service_execution` (`service_name`,`execution_time`), ADD KEY `index_service_performance1` (`service_name`,`performance_index1`), ADD KEY `index_service_performance2` (`service_name`,`performance_index2`), ADD KEY `index_service_performance3` (`service_name`,`performance_index3`), ADD KEY `index_date` (`date_recorded`);

ALTER TABLE `cron_services`
MODIFY `instance_id` bigint(255) NOT NULL AUTO_INCREMENT;