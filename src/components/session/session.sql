CREATE TABLE `sessions` (
  `id` varchar(64) collate utf8_unicode_ci NOT NULL,
  `expire` datetime NOT NULL,
  `value` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `expire` (`expire`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;