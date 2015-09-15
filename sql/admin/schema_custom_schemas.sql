CREATE TABLE IF NOT EXISTS `s2_mod_custom_schema` (
`schemaID` int(255) NOT NULL,
  `modID` int(255) NOT NULL,
  `schemaAuth` varchar(16) NOT NULL,
  `schemaVersion` int(11) NOT NULL,
  `schemaApproved` tinyint(1) NOT NULL,
  `schemaRejected` tinyint(1) NOT NULL,
  `schemaRejectedReason` text,
  `schemaSubmitterUserID64` bigint(20) NOT NULL,
  `schemaApproverUserID64` bigint(20) DEFAULT NULL,
  `dateRecorded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `s2_mod_custom_schema_fields` (
  `schemaID` int(255) NOT NULL,
  `fieldType` tinyint(1) NOT NULL,
  `fieldOrder` tinyint(1) NOT NULL,
  `customValueObjective` tinyint(1) NOT NULL,
  `customValueDisplay` varchar(100) COLLATE utf8_bin NOT NULL,
  `customValueName` varchar(100) COLLATE utf8_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


ALTER TABLE `s2_mod_custom_schema`
 ADD PRIMARY KEY (`schemaID`), ADD UNIQUE KEY `index_modID_version` (`modID`,`schemaVersion`), ADD KEY `index_dateRecorded` (`dateRecorded`), ADD KEY `index_schemaApproved` (`schemaApproved`), ADD KEY `index_schemaAuth` (`schemaAuth`), ADD KEY `index_schemaRejected` (`schemaRejected`), ADD KEY `index_schemaSubmitterUserID64` (`schemaSubmitterUserID64`), ADD KEY `index_schemaApproverUserID64` (`schemaApproverUserID64`);

ALTER TABLE `s2_mod_custom_schema_fields`
 ADD PRIMARY KEY (`schemaID`,`fieldType`,`fieldOrder`), ADD KEY `index_schema_objective` (`schemaID`,`customValueObjective`);


ALTER TABLE `s2_mod_custom_schema`
MODIFY `schemaID` int(255) NOT NULL AUTO_INCREMENT;