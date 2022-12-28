-- Adminer 4.8.1 MySQL 10.8.3-MariaDB dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

CREATE TABLE `blockedlayouts` (
  `user` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `blockee` mediumint(8) unsigned NOT NULL DEFAULT 0,
  KEY `user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE `categories` (
  `id` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `title` varchar(255) NOT NULL,
  `ord` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `categories` (`id`, `title`, `ord`) VALUES
(1,	'General',	2),
(2,	'Staff Forums',	0);

CREATE TABLE `forums` (
  `id` int(5) unsigned NOT NULL DEFAULT 0,
  `cat` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `ord` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `title` varchar(255) NOT NULL,
  `descr` varchar(255) NOT NULL,
  `threads` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `posts` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `lastdate` int(11) unsigned NOT NULL DEFAULT 0,
  `lastuser` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `lastid` int(11) unsigned NOT NULL DEFAULT 0,
  `minread` tinyint(4) NOT NULL DEFAULT -1,
  `minthread` tinyint(4) NOT NULL DEFAULT 1,
  `minreply` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `forums` (`id`, `cat`, `ord`, `title`, `descr`, `threads`, `posts`, `lastdate`, `lastuser`, `lastid`, `minread`, `minthread`, `minreply`) VALUES
(1,	1,	1,	'General Forum',	'General topics forum',	0,	0,	0,	0,	0,	-1,	1,	1),
(2,	2,	1,	'General Staff Forum',	'Generic Staff Forum',	0,	0,	0,	0,	0,	-1,	1,	1);

CREATE TABLE `forumsread` (
  `uid` mediumint(9) NOT NULL,
  `fid` int(5) NOT NULL,
  `time` int(11) NOT NULL,
  UNIQUE KEY `uid` (`uid`,`fid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE `guests` (
  `date` int(11) unsigned NOT NULL DEFAULT 0,
  `ip` varchar(15) NOT NULL,
  `bot` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `lastforum` int(10) unsigned DEFAULT NULL,
  UNIQUE KEY `ip` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE `ipbans` (
  `ipmask` varchar(15) NOT NULL,
  `expires` int(12) NOT NULL,
  `banner` varchar(25) NOT NULL,
  `reason` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE `misc` (
  `views` int(11) unsigned NOT NULL DEFAULT 0,
  `attention` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `misc` (`views`, `attention`) VALUES
(0,	'<b>The Voxelmanip Forums Codebase has been setup!</b><br>Make sure to not share a link yet and register quickly as the first user gets root administrator privileges.');

CREATE TABLE `pmsgs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `text` text NOT NULL,
  `date` int(11) unsigned NOT NULL DEFAULT 0,
  `ip` char(15) NOT NULL,
  `userto` mediumint(9) unsigned NOT NULL,
  `userfrom` mediumint(9) unsigned NOT NULL,
  `unread` tinyint(1) NOT NULL DEFAULT 1,
  `del_from` tinyint(1) NOT NULL DEFAULT 0,
  `del_to` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE `posts` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user` mediumint(9) unsigned NOT NULL DEFAULT 0,
  `thread` mediumint(9) unsigned NOT NULL DEFAULT 0,
  `date` int(11) unsigned NOT NULL DEFAULT 0,
  `revision` smallint(5) unsigned NOT NULL DEFAULT 1,
  `ip` char(15) NOT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `threadid` (`thread`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE `poststext` (
  `id` int(11) unsigned NOT NULL DEFAULT 0,
  `text` text NOT NULL,
  `revision` smallint(5) unsigned NOT NULL DEFAULT 1,
  `date` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`,`revision`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE `threads` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `posts` mediumint(8) unsigned NOT NULL DEFAULT 1,
  `views` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `forum` int(5) unsigned NOT NULL DEFAULT 0,
  `user` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `lastdate` int(11) unsigned NOT NULL DEFAULT 0,
  `lastuser` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `lastid` int(11) unsigned NOT NULL DEFAULT 0,
  `closed` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `sticky` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE `threadsread` (
  `uid` mediumint(9) unsigned NOT NULL,
  `tid` mediumint(9) unsigned NOT NULL,
  `time` int(11) unsigned NOT NULL,
  UNIQUE KEY `uid` (`uid`,`tid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE `users` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL,
  `password` varchar(64) NOT NULL,
  `token` varchar(64) DEFAULT NULL,
  `posts` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `threads` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `joined` int(10) unsigned NOT NULL DEFAULT 0,
  `lastpost` int(10) unsigned NOT NULL DEFAULT 0,
  `lastview` int(10) unsigned NOT NULL DEFAULT 0,
  `lastforum` int(10) unsigned NOT NULL DEFAULT 0,
  `ip` varchar(15) NOT NULL DEFAULT '0.0.0.0',
  `url` varchar(150) NOT NULL DEFAULT '',
  `tempbanned` int(10) unsigned NOT NULL DEFAULT 0,
  `powerlevel` tinyint(4) NOT NULL DEFAULT 1,
  `ppp` smallint(3) unsigned NOT NULL DEFAULT 20,
  `tpp` smallint(3) unsigned NOT NULL DEFAULT 20,
  `theme` varchar(32) DEFAULT NULL,
  `birthday` varchar(10) DEFAULT NULL,
  `rankset` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `colorset` tinyint(2) unsigned NOT NULL DEFAULT 2,
  `showemail` tinyint(1) unsigned DEFAULT NULL,
  `avatar` tinyint(1) unsigned DEFAULT NULL,
  `blocklayouts` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `customcolour` char(6) NOT NULL DEFAULT '000000',
  `timezone` varchar(32) DEFAULT NULL,
  `signsep` tinyint(1) unsigned DEFAULT NULL,
  `header` text DEFAULT NULL,
  `signature` text DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- 2022-10-31 13:43:32
