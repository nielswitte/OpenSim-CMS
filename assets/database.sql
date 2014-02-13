-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server versie:                5.5.8 - MySQL Community Server (GPL)
-- Server OS:                    Win32
-- HeidiSQL Versie:              8.3.0.4694
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

-- Databasestructuur van cms wordt geschreven
CREATE DATABASE IF NOT EXISTS `OpenSim-CMS` /*!40100 DEFAULT CHARACTER SET utf8 COLLATE utf8_bin */;
USE `OpenSim-CMS`;


-- Structuur van  tabel cms.presentations wordt geschreven
CREATE TABLE IF NOT EXISTS `presentations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_bin NOT NULL,
  `creationDate` timestamp NULL DEFAULT NULL,
  `modificationDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ownerUuid` varchar(50) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_presentations_users` (`ownerUuid`),
  CONSTRAINT `FK_presentations_users` FOREIGN KEY (`ownerUuid`) REFERENCES `users` (`uuid`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporteren was gedeselecteerd


-- Structuur van  tabel cms.presentation_slides wordt geschreven
CREATE TABLE IF NOT EXISTS `presentation_slides` (
  `number` int(11) NOT NULL AUTO_INCREMENT,
  `presentationId` int(11) NOT NULL DEFAULT '0',
  `uuid` varchar(50) COLLATE utf8_bin DEFAULT '0',
  `uuidUpdated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`number`,`presentationId`),
  KEY `FK_presentation_slides_presentations` (`presentationId`),
  CONSTRAINT `FK_presentation_slides_presentations` FOREIGN KEY (`presentationId`) REFERENCES `presentations` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporteren was gedeselecteerd


-- Structuur van  tabel cms.users wordt geschreven
CREATE TABLE IF NOT EXISTS `users` (
  `uuid` varchar(50) COLLATE utf8_bin NOT NULL DEFAULT '0',
  `userName` varchar(50) COLLATE utf8_bin NOT NULL DEFAULT '0',
  `firstName` varchar(50) COLLATE utf8_bin NOT NULL DEFAULT '',
  `lastName` varchar(50) COLLATE utf8_bin NOT NULL DEFAULT '',
  `email` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '',
  `password` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '0',
  PRIMARY KEY (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporteren was gedeselecteerd
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
