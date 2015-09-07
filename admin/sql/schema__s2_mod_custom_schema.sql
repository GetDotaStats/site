--
-- Table structure for table `s2_mod_custom_schema`
--

CREATE TABLE IF NOT EXISTS `s2_mod_custom_schema` (
`schemaID` int(255) NOT NULL,
  `modID` int(255) NOT NULL,
  `schemaAuth` varchar(16) COLLATE utf8_bin NOT NULL,
  `schemaVersion` int(11) NOT NULL,
  `schemaApproved` tinyint(1) NOT NULL DEFAULT ''0'',
  `schemaRejected` tinyint(1) NOT NULL DEFAULT ''0'',
  `schemaRejectedReason` text COLLATE utf8_bin,
  `schemaSubmitterUserID64` bigint(255) NOT NULL,
  `dateRecorded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `customGameValue1_display` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `customGameValue1_name` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `customGameValue1_objective` tinyint(1) DEFAULT NULL,
  `customGameValue2_display` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `customGameValue2_name` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `customGameValue2_objective` tinyint(1) DEFAULT NULL,
  `customGameValue3_display` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `customGameValue3_name` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `customGameValue3_objective` tinyint(1) DEFAULT NULL,
  `customGameValue4_display` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `customGameValue4_name` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `customGameValue4_objective` tinyint(1) DEFAULT NULL,
  `customGameValue5_display` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `customGameValue5_name` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `customGameValue5_objective` tinyint(1) DEFAULT NULL,
  `customPlayerValue1_display` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `customPlayerValue1_name` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `customPlayerValue1_objective` tinyint(1) DEFAULT NULL,
  `customPlayerValue2_display` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `customPlayerValue2_name` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `customPlayerValue2_objective` tinyint(1) DEFAULT NULL,
  `customPlayerValue3_display` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `customPlayerValue3_name` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `customPlayerValue3_objective` tinyint(1) DEFAULT NULL,
  `customPlayerValue4_display` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `customPlayerValue4_name` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `customPlayerValue4_objective` tinyint(1) DEFAULT NULL,
  `customPlayerValue5_display` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `customPlayerValue5_name` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `customPlayerValue5_objective` tinyint(1) DEFAULT NULL,
  `customPlayerValue6_display` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `customPlayerValue6_name` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `customPlayerValue6_objective` tinyint(1) DEFAULT NULL,
  `customPlayerValue7_display` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `customPlayerValue7_name` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `customPlayerValue7_objective` tinyint(1) DEFAULT NULL,
  `customPlayerValue8_display` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `customPlayerValue8_name` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `customPlayerValue8_objective` tinyint(1) DEFAULT NULL,
  `customPlayerValue9_display` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `customPlayerValue9_name` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `customPlayerValue9_objective` tinyint(1) DEFAULT NULL,
  `customPlayerValue10_display` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `customPlayerValue10_name` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `customPlayerValue10_objective` tinyint(1) DEFAULT NULL,
  `customPlayerValue11_display` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `customPlayerValue11_name` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `customPlayerValue11_objective` tinyint(1) DEFAULT NULL,
  `customPlayerValue12_display` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `customPlayerValue12_name` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `customPlayerValue12_objective` tinyint(1) DEFAULT NULL,
  `customPlayerValue13_display` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `customPlayerValue13_name` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `customPlayerValue13_objective` tinyint(1) DEFAULT NULL,
  `customPlayerValue14_display` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `customPlayerValue14_name` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `customPlayerValue14_objective` tinyint(1) DEFAULT NULL,
  `customPlayerValue15_display` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `customPlayerValue15_name` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `customPlayerValue15_objective` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `s2_mod_custom_schema`
--
ALTER TABLE `s2_mod_custom_schema`
 ADD PRIMARY KEY (`schemaID`), ADD UNIQUE KEY `index_modID_version` (`modID`,`schemaVersion`), ADD KEY `index_dateRecorded` (`dateRecorded`), ADD KEY `index_schemaApproved` (`schemaApproved`), ADD KEY `index_schemaAuth` (`schemaAuth`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `s2_mod_custom_schema`
--
ALTER TABLE `s2_mod_custom_schema`
MODIFY `schemaID` int(255) NOT NULL AUTO_INCREMENT;