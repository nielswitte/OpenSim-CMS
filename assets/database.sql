-- --------------------------------------------------------
-- Host:                         localhost
-- Server versie:                5.5.35-1ubuntu1 - (Ubuntu)
-- Server OS:                    debian-linux-gnu
-- HeidiSQL Versie:              8.3.0.4756
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

-- Structuur van  tabel OpenSim-CMS.avatars wordt geschreven
CREATE TABLE IF NOT EXISTS `avatars` (
  `userId` int(11) NOT NULL DEFAULT '0',
  `gridId` int(11) NOT NULL DEFAULT '0',
  `uuid` varchar(36) COLLATE utf8_bin NOT NULL DEFAULT '',
  `confirmed` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`gridId`,`uuid`),
  KEY `FK_avatars_users` (`userId`),
  CONSTRAINT `FK_avatars_grids` FOREIGN KEY (`gridId`) REFERENCES `grids` (`id`),
  CONSTRAINT `FK_avatars_users` FOREIGN KEY (`userId`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporteren was gedeselecteerd


-- Structuur van  tabel OpenSim-CMS.cached_assets wordt geschreven
CREATE TABLE IF NOT EXISTS `cached_assets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gridId` int(11) NOT NULL,
  `uuid` varchar(50) COLLATE utf8_bin DEFAULT NULL,
  `uuidExpires` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`,`gridId`),
  KEY `FK_cached_assets_grids` (`gridId`),
  CONSTRAINT `FK_cached_assets_grids` FOREIGN KEY (`gridId`) REFERENCES `grids` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporteren was gedeselecteerd


-- Structuur van  tabel OpenSim-CMS.chats wordt geschreven
CREATE TABLE IF NOT EXISTS `chats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gridId` int(11) DEFAULT NULL,
  `userId` int(11) DEFAULT NULL,
  `message` mediumtext COLLATE utf8_bin,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fromCMS` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `FK__users` (`userId`),
  KEY `FK_chats_grids` (`gridId`),
  CONSTRAINT `FK_chats_grids` FOREIGN KEY (`gridId`) REFERENCES `grids` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK__users` FOREIGN KEY (`userId`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporteren was gedeselecteerd


-- Structuur van  tabel OpenSim-CMS.comments wordt geschreven
CREATE TABLE IF NOT EXISTS `comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parentId` int(11) DEFAULT NULL,
  `type` varchar(50) COLLATE utf8_bin DEFAULT NULL,
  `itemId` int(11) DEFAULT NULL,
  `userId` int(11) DEFAULT NULL,
  `message` mediumtext COLLATE utf8_bin,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `editTimestamp` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_comments_users` (`userId`),
  KEY `FK_comments_comments` (`parentId`),
  CONSTRAINT `FK_comments_comments` FOREIGN KEY (`parentId`) REFERENCES `comments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_comments_users` FOREIGN KEY (`userId`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporteren was gedeselecteerd


-- Structuur van  tabel OpenSim-CMS.documents wordt geschreven
CREATE TABLE IF NOT EXISTS `documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(50) COLLATE utf8_bin NOT NULL DEFAULT '0',
  `title` varchar(255) COLLATE utf8_bin NOT NULL,
  `creationDate` timestamp NULL DEFAULT NULL,
  `modificationDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ownerId` int(11) NOT NULL,
  `file` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_presentations_users` (`ownerId`),
  CONSTRAINT `FK_documents_users` FOREIGN KEY (`ownerId`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporteren was gedeselecteerd


-- Structuur van  tabel OpenSim-CMS.document_files_cache wordt geschreven
CREATE TABLE IF NOT EXISTS `document_files_cache` (
  `fileId` int(11) NOT NULL AUTO_INCREMENT,
  `cacheId` int(11) NOT NULL,
  PRIMARY KEY (`fileId`,`cacheId`),
  KEY `FK_document_files_cache_cached_assets` (`cacheId`),
  KEY `fileId` (`fileId`),
  CONSTRAINT `FK_document_files_cache_cached_assets` FOREIGN KEY (`cacheId`) REFERENCES `cached_assets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_document_files_cache_documents` FOREIGN KEY (`fileId`) REFERENCES `documents` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporteren was gedeselecteerd


-- Structuur van  tabel OpenSim-CMS.document_pages wordt geschreven
CREATE TABLE IF NOT EXISTS `document_pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `documentId` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `FK_document_pages_documents` (`documentId`),
  CONSTRAINT `FK_document_pages_documents` FOREIGN KEY (`documentId`) REFERENCES `documents` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporteren was gedeselecteerd


-- Structuur van  tabel OpenSim-CMS.document_pages_cache wordt geschreven
CREATE TABLE IF NOT EXISTS `document_pages_cache` (
  `pageId` int(11) NOT NULL,
  `cacheId` int(11) NOT NULL,
  PRIMARY KEY (`pageId`,`cacheId`),
  KEY `FK_document_pages_cache_cached_assets` (`cacheId`),
  KEY `pageId` (`pageId`),
  CONSTRAINT `FK_document_pages_cache_cached_assets` FOREIGN KEY (`cacheId`) REFERENCES `cached_assets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_document_pages_cache_document_pages` FOREIGN KEY (`pageId`) REFERENCES `document_pages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporteren was gedeselecteerd


-- Structuur van  tabel OpenSim-CMS.document_slides wordt geschreven
CREATE TABLE IF NOT EXISTS `document_slides` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `documentId` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `FK_presentation_slides_presentations` (`documentId`),
  CONSTRAINT `FK_document_slides_documents` FOREIGN KEY (`documentId`) REFERENCES `documents` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporteren was gedeselecteerd


-- Structuur van  tabel OpenSim-CMS.document_slides_cache wordt geschreven
CREATE TABLE IF NOT EXISTS `document_slides_cache` (
  `slideId` int(11) NOT NULL AUTO_INCREMENT,
  `cacheId` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`slideId`,`cacheId`),
  KEY `FK_document_slides_cache_cached_assets` (`cacheId`),
  KEY `slideId` (`slideId`),
  CONSTRAINT `FK_document_slides_cache_cached_assets` FOREIGN KEY (`cacheId`) REFERENCES `cached_assets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_document_slides_cache_document_slides` FOREIGN KEY (`slideId`) REFERENCES `document_slides` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporteren was gedeselecteerd


-- Structuur van  tabel OpenSim-CMS.grids wordt geschreven
CREATE TABLE IF NOT EXISTS `grids` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `osProtocol` varchar(10) COLLATE utf8_bin DEFAULT 'http',
  `osIp` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `osPort` int(11) unsigned DEFAULT NULL,
  `raUrl` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `raPort` int(11) unsigned DEFAULT NULL,
  `raPassword` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `dbUrl` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `dbPort` int(11) unsigned DEFAULT NULL,
  `dbUsername` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `dbPassword` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `dbName` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `cacheTime` varchar(50) COLLATE utf8_bin DEFAULT NULL,
  `defaultRegionUuid` varchar(50) COLLATE utf8_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_grids_grid_regions` (`defaultRegionUuid`),
  CONSTRAINT `FK_grids_grid_regions` FOREIGN KEY (`defaultRegionUuid`) REFERENCES `grid_regions` (`uuid`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporteren was gedeselecteerd


-- Structuur van  tabel OpenSim-CMS.grid_regions wordt geschreven
CREATE TABLE IF NOT EXISTS `grid_regions` (
  `gridId` int(11) NOT NULL,
  `uuid` varchar(36) COLLATE utf8_bin NOT NULL,
  `name` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  PRIMARY KEY (`uuid`,`gridId`),
  KEY `FK_grid_regions_grids` (`gridId`),
  CONSTRAINT `FK_grid_regions_grids` FOREIGN KEY (`gridId`) REFERENCES `grids` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporteren was gedeselecteerd


-- Structuur van  tabel OpenSim-CMS.groups wordt geschreven
CREATE TABLE IF NOT EXISTS `groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporteren was gedeselecteerd


-- Structuur van  tabel OpenSim-CMS.group_documents wordt geschreven
CREATE TABLE IF NOT EXISTS `group_documents` (
  `documentId` int(11) NOT NULL AUTO_INCREMENT,
  `groupId` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`documentId`,`groupId`),
  KEY `FK_group_documents_groups` (`groupId`),
  CONSTRAINT `FK_group_documents_documents` FOREIGN KEY (`documentId`) REFERENCES `documents` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_group_documents_groups` FOREIGN KEY (`groupId`) REFERENCES `groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Data exporteren was gedeselecteerd


-- Structuur van  tabel OpenSim-CMS.group_users wordt geschreven
CREATE TABLE IF NOT EXISTS `group_users` (
  `groupId` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  PRIMARY KEY (`groupId`,`userId`),
  KEY `FK_group_users_users` (`userId`),
  CONSTRAINT `FK_group_users_groups` FOREIGN KEY (`groupId`) REFERENCES `groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_group_users_users` FOREIGN KEY (`userId`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporteren was gedeselecteerd


-- Structuur van  tabel OpenSim-CMS.meetings wordt geschreven
CREATE TABLE IF NOT EXISTS `meetings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) DEFAULT NULL,
  `startDate` timestamp NULL DEFAULT NULL,
  `endDate` timestamp NULL DEFAULT NULL,
  `roomId` int(11) DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_meetings_users` (`userId`),
  KEY `FK_meetings_meeting_rooms` (`roomId`),
  CONSTRAINT `FK_meetings_meeting_rooms` FOREIGN KEY (`roomId`) REFERENCES `meeting_rooms` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `FK_meetings_users` FOREIGN KEY (`userId`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporteren was gedeselecteerd


-- Structuur van  tabel OpenSim-CMS.meeting_agenda_items wordt geschreven
CREATE TABLE IF NOT EXISTS `meeting_agenda_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `meetingId` int(11) NOT NULL,
  `parentId` int(11) DEFAULT NULL,
  `sort` int(11) DEFAULT NULL,
  `value` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  PRIMARY KEY (`id`,`meetingId`),
  KEY `FK_meeting_agenda_items_meetings` (`meetingId`),
  KEY `FK_meeting_agenda_items_meeting_agenda_items` (`parentId`),
  CONSTRAINT `FK_meeting_agenda_items_meetings` FOREIGN KEY (`meetingId`) REFERENCES `meetings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_meeting_agenda_items_meeting_agenda_items` FOREIGN KEY (`parentId`) REFERENCES `meeting_agenda_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporteren was gedeselecteerd


-- Structuur van  tabel OpenSim-CMS.meeting_documents wordt geschreven
CREATE TABLE IF NOT EXISTS `meeting_documents` (
  `meetingId` int(11) NOT NULL AUTO_INCREMENT,
  `documentId` int(11) NOT NULL,
  `agendaId` int(11) NOT NULL,
  PRIMARY KEY (`meetingId`,`documentId`),
  KEY `FK__documents` (`documentId`),
  CONSTRAINT `FK__documents` FOREIGN KEY (`documentId`) REFERENCES `documents` (`id`),
  CONSTRAINT `FK__meetings` FOREIGN KEY (`meetingId`) REFERENCES `meetings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporteren was gedeselecteerd


-- Structuur van  tabel OpenSim-CMS.meeting_minutes wordt geschreven
CREATE TABLE IF NOT EXISTS `meeting_minutes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `meetingId` int(11) DEFAULT '0',
  `agendaId` int(11) DEFAULT '0',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `uuid` varchar(50) COLLATE utf8_bin DEFAULT '0',
  `name` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `message` mediumtext COLLATE utf8_bin,
  PRIMARY KEY (`id`),
  KEY `FK_meeting_minutes_meetings` (`meetingId`),
  CONSTRAINT `FK_meeting_minutes_meetings` FOREIGN KEY (`meetingId`) REFERENCES `meetings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporteren was gedeselecteerd


-- Structuur van  tabel OpenSim-CMS.meeting_participants wordt geschreven
CREATE TABLE IF NOT EXISTS `meeting_participants` (
  `meetingId` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  PRIMARY KEY (`meetingId`,`userId`),
  KEY `FK_meeting_participants_users` (`userId`),
  CONSTRAINT `FK_meeting_participants_meetings` FOREIGN KEY (`meetingId`) REFERENCES `meetings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_meeting_participants_users` FOREIGN KEY (`userId`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporteren was gedeselecteerd


-- Structuur van  tabel OpenSim-CMS.meeting_rooms wordt geschreven
CREATE TABLE IF NOT EXISTS `meeting_rooms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gridId` int(11) DEFAULT NULL,
  `regionUuid` varchar(36) COLLATE utf8_bin DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `description` text COLLATE utf8_bin,
  `x` float unsigned DEFAULT '0',
  `y` float unsigned DEFAULT '0',
  `z` float unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `FK_meeting_rooms_grid_regions` (`regionUuid`),
  KEY `FK_meeting_rooms_grids` (`gridId`),
  CONSTRAINT `FK_meeting_rooms_grids` FOREIGN KEY (`gridId`) REFERENCES `grids` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_meeting_rooms_grid_regions` FOREIGN KEY (`regionUuid`) REFERENCES `grid_regions` (`uuid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporteren was gedeselecteerd


-- Structuur van  tabel OpenSim-CMS.tokens wordt geschreven
CREATE TABLE IF NOT EXISTS `tokens` (
  `token` varchar(50) COLLATE utf8_bin NOT NULL DEFAULT '',
  `userId` int(11) DEFAULT NULL,
  `ip` varchar(64) COLLATE utf8_bin DEFAULT NULL,
  `expires` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`token`),
  KEY `userId` (`userId`),
  CONSTRAINT `FK_tokens_users` FOREIGN KEY (`userId`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporteren was gedeselecteerd


-- Structuur van  tabel OpenSim-CMS.users wordt geschreven
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8_bin NOT NULL DEFAULT '0',
  `firstName` varchar(50) COLLATE utf8_bin NOT NULL DEFAULT '',
  `lastName` varchar(50) COLLATE utf8_bin NOT NULL DEFAULT '',
  `email` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '',
  `password` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '0',
  `lastLogin` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `userName` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporteren was gedeselecteerd


-- Structuur van  tabel OpenSim-CMS.user_permissions wordt geschreven
CREATE TABLE IF NOT EXISTS `user_permissions` (
  `userId` int(11) NOT NULL AUTO_INCREMENT,
  `auth` tinyint(1) unsigned NOT NULL,
  `chat` tinyint(1) unsigned NOT NULL,
  `comment` tinyint(1) unsigned NOT NULL,
  `document` tinyint(1) unsigned NOT NULL,
  `file` tinyint(1) unsigned NOT NULL,
  `grid` tinyint(1) unsigned NOT NULL,
  `meeting` tinyint(1) unsigned NOT NULL,
  `meetingroom` tinyint(1) unsigned NOT NULL,
  `presentation` tinyint(1) unsigned NOT NULL,
  `user` tinyint(1) unsigned NOT NULL,
  PRIMARY KEY (`userId`),
  CONSTRAINT `user_permissions_ibfk_1` FOREIGN KEY (`userId`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporteren was gedeselecteerd
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
