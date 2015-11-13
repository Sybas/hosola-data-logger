CREATE TABLE IF NOT EXISTS `hosola-data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `inverter` varchar(255) DEFAULT NULL,
  `timestamp` int(10) NULL DEFAULT NULL,
  `header` int(10) unsigned DEFAULT NULL,
  `generated_id_1` int(10) unsigned DEFAULT NULL,
  `generated_id_2` int(10) unsigned DEFAULT NULL,
  `unk_1` int(10) unsigned DEFAULT NULL,
  `inverter_id` int(10) unsigned DEFAULT NULL,
  `temperature` float DEFAULT NULL,
  `vpv1` float DEFAULT NULL,
  `vpv2` int(10) unsigned DEFAULT NULL,
  `vpv3` float DEFAULT NULL,
  `ipv1` float DEFAULT NULL,
  `ipv2` float DEFAULT NULL,
  `ipv3` float DEFAULT NULL,
  `iac1` float DEFAULT NULL,
  `iac2` float DEFAULT NULL,
  `iac3` float DEFAULT NULL,
  `vac1` float DEFAULT NULL,
  `vac2` float DEFAULT NULL,
  `vac3` float DEFAULT NULL,
  `fac1` float DEFAULT NULL,
  `pac1` float DEFAULT NULL,
  `fac2` float DEFAULT NULL,
  `pac2` float DEFAULT NULL,
  `fac3` float DEFAULT NULL,
  `pac3` float DEFAULT NULL,
  `etoday` float DEFAULT NULL,
  `etotal` float DEFAULT NULL,
  `htotal` float DEFAULT NULL,
  `unk_2` int(10) unsigned DEFAULT NULL,
  `raw_data_string_base64` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;