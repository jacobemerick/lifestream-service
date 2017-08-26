# ------------------------------------------------------------
# Database schema for lifestream service
# ------------------------------------------------------------

DROP DATABASE IF EXISTS `lifestream_service`;

CREATE DATABASE `lifestream_service`;

SHOW WARNINGS;

USE `lifestream_service`;

# ------------------------------------------------------------

DROP TABLE IF EXISTS `blog`;

CREATE TABLE `blog` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `permalink` varchar(250) NOT NULL DEFAULT '',
  `datetime` datetime NOT NULL,
  `metadata` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

SHOW WARNINGS;

# ------------------------------------------------------------

DROP TABLE IF EXISTS `blog_comment`;

CREATE TABLE `blog_comment` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `permalink` varchar(250) NOT NULL DEFAULT '',
  `datetime` datetime NOT NULL,
  `metadata` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

SHOW WARNINGS;

# ------------------------------------------------------------

DROP TABLE IF EXISTS `book`;

CREATE TABLE `book` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `permalink` varchar(250) NOT NULL DEFAULT '',
  `book_id` bigint(20) unsigned NOT NULL,
  `datetime` datetime NOT NULL,
  `metadata` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

SHOW WARNINGS;

# ------------------------------------------------------------

DROP TABLE IF EXISTS `code`;

CREATE TABLE `code` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` bigint(20) unsigned NOT NULL,
  `type` varchar(20) NOT NULL DEFAULT '',
  `datetime` datetime NOT NULL,
  `metadata` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

SHOW WARNINGS;

# ------------------------------------------------------------

DROP TABLE IF EXISTS `distance`;

CREATE TABLE `distance` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `entry_id` int(11) unsigned NOT NULL,
  `type` varchar(20) NOT NULL DEFAULT '',
  `datetime` datetime NOT NULL,
  `metadata` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

SHOW WARNINGS;

# ------------------------------------------------------------

DROP TABLE IF EXISTS `event`;

CREATE TABLE `event` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `description` text NOT NULL,
  `description_html` text NOT NULL,
  `datetime` datetime NOT NULL,
  `metadata` text NOT NULL,
  `user` tinyint(4) NOT NULL,
  `type` tinyint(4) NOT NULL,
  `type_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `type_ix` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

SHOW WARNINGS;

# ------------------------------------------------------------

DROP TABLE IF EXISTS `twitter`;

CREATE TABLE `twitter` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tweet_id` bigint(20) unsigned NOT NULL,
  `datetime` datetime NOT NULL,
  `metadata` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

SHOW WARNINGS;

# ------------------------------------------------------------

DROP TABLE IF EXISTS `type`;

CREATE TABLE `type` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(10) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

SHOW WARNINGS;

LOCK TABLES `type` WRITE;

INSERT INTO `type` (`id`, `name`)
VALUES
	(1,'blog'),
	(2,'books'),
	(3,'github'),
	(4,'hiking'),
	(5,'hulu'),
	(6,'instagram'),
	(7,'run'),
	(8,'twitter'),
	(9,'youtube');

UNLOCK TABLES;

SHOW WARNINGS;

# ------------------------------------------------------------

DROP TABLE IF EXISTS `user`;

CREATE TABLE `user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `user` WRITE;

INSERT INTO `user` (`id`, `name`)
VALUES
	(1,'Jacob Emerick');

UNLOCK TABLES;

SHOW WARNINGS;

# ------------------------------------------------------------

DROP TABLE IF EXISTS `youtube`;

CREATE TABLE `youtube` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `video_id` varchar(15) NOT NULL DEFAULT '',
  `datetime` datetime NOT NULL,
  `metadata` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

SHOW WARNINGS;
