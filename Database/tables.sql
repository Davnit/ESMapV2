
DROP TABLE IF EXISTS `calls`;
CREATE TABLE `calls` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `source` int(11) NOT NULL,
  `cid` varchar(40) NOT NULL,
  `category` varchar(15) DEFAULT NULL,
  `geoid` int(11) DEFAULT NULL,
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expired` timestamp NULL DEFAULT NULL,
  `meta` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cid` (`cid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `sources`;
CREATE TABLE `sources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tag` varchar(10) NOT NULL,
  `url` varchar(255) CHARACTER SET ascii NOT NULL,
  `parser` varchar(25) CHARACTER SET ascii NOT NULL,
  `update_time` int(11) NOT NULL,
  `bounds` varchar(45) CHARACTER SET ascii DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `geocodes`;
CREATE TABLE `geocodes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `location` varchar(100) NOT NULL,
  `latitude` decimal(8,6) DEFAULT NULL,
  `longitude` float(9,6) DEFAULT NULL,
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `resolved` timestamp NULL DEFAULT NULL,
  `results` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `location` (`location`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;