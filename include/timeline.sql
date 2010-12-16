CREATE TABLE `users` (
  `user_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `urlname` varchar(100) DEFAULT NULL,
  `flickr_nsid` varchar(255) DEFAULT NULL,
  `twitter_screenname` varchar(255) DEFAULT NULL,
  `flickr_last_run_id` bigint(20) DEFAULT NULL,
  `twitter_last_run_id` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `urlname` (`urlname`)

) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8
CREATE TABLE `geopoints` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,
  `source` int(11) NOT NULL,
  `source_id` varchar(50) NOT NULL,
  `lat` float(10,6) NOT NULL,
  `lon` float(10,6) NOT NULL,
  `event_time` datetime NOT NULL,
  `url` varchar(255) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `title` text,
  `description` text,
  `posted` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_source_source_id` (`user_id`,`source`,`source_id`),
  KEY `lon` (`lon`) USING BTREE,
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8
