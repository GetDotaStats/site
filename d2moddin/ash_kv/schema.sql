--
-- Table structure for table `kv_lod_bans`
--

CREATE TABLE IF NOT EXISTS `kv_lod_bans` (
  `ability1` varchar(255) NOT NULL,
  `ability2` varchar(255) NOT NULL,
  `date_recorded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ability1`,`ability2`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
