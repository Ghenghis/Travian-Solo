-- TravianT4.6 Global MySQL Database Schema
-- This handles user registration, server list, and global configuration

CREATE DATABASE IF NOT EXISTS travian_global CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE travian_global;

-- Game Servers Table
CREATE TABLE IF NOT EXISTS `gameServers` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `worldId` VARCHAR(50) NOT NULL,
  `speed` INT(11) NOT NULL DEFAULT 1,
  `name` VARCHAR(255) NOT NULL,
  `version` VARCHAR(10) DEFAULT 'T4.6',
  `gameWorldUrl` VARCHAR(255) NOT NULL,
  `startTime` INT(10) UNSIGNED NOT NULL,
  `roundLength` INT(11) NOT NULL DEFAULT 365,
  `finished` TINYINT(1) NOT NULL DEFAULT 0,
  `registerClosed` TINYINT(1) NOT NULL DEFAULT 0,
  `activation` TINYINT(1) NOT NULL DEFAULT 1,
  `preregistration_key_only` TINYINT(1) NOT NULL DEFAULT 0,
  `hidden` TINYINT(1) NOT NULL DEFAULT 0,
  `promoted` TINYINT(1) DEFAULT 0,
  `configFileLocation` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `worldId` (`worldId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Activation Table
CREATE TABLE IF NOT EXISTS `activation` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `wid` INT(11) UNSIGNED NOT NULL,
  `name` VARCHAR(20) NOT NULL,
  `password` VARCHAR(40) NOT NULL,
  `email` VARCHAR(99) NOT NULL DEFAULT '',
  `token` VARCHAR(32) NOT NULL,
  `refUid` INT(11) NOT NULL DEFAULT 0,
  `time` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  `reminded` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `wid` (`wid`),
  KEY `name` (`name`),
  KEY `email` (`email`),
  KEY `token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Global Configurations
CREATE TABLE IF NOT EXISTS `configurations` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `key` VARCHAR(100) NOT NULL,
  `value` TEXT,
  `description` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- IP Ban List
CREATE TABLE IF NOT EXISTS `banIP` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip` VARCHAR(45) NOT NULL,
  `reason` TEXT,
  `banned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `expires_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ip` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Email Blacklist
CREATE TABLE IF NOT EXISTS `email_blacklist` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL,
  `domain` VARCHAR(255) DEFAULT NULL,
  `reason` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `domain` (`domain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mail Server Queue
CREATE TABLE IF NOT EXISTS `mailserver` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `to_email` VARCHAR(255) NOT NULL,
  `from_email` VARCHAR(255) NOT NULL,
  `subject` VARCHAR(500) NOT NULL,
  `body` TEXT NOT NULL,
  `status` ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
  `attempts` INT(11) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `sent_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Password Recovery
CREATE TABLE IF NOT EXISTS `passwordRecovery` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid` INT(11) UNSIGNED NOT NULL,
  `wid` INT(11) UNSIGNED NOT NULL,
  `recoveryCode` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `wid` (`wid`),
  KEY `recoveryCode` (`recoveryCode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Sample Game Servers
INSERT INTO `gameServers` (`worldId`, `speed`, `name`, `gameWorldUrl`, `startTime`, `roundLength`, `finished`, `registerClosed`, `activation`, `configFileLocation`)
VALUES
  ('testworld', 100, 'Test Server 100x', 'http://testworld.travian.local/', UNIX_TIMESTAMP(), 365, 0, 0, 1, '/var/www/html/sections/servers/testworld/include/connection.php'),
  ('demo', 5, 'Demo Server 5x', 'http://demo.travian.local/', UNIX_TIMESTAMP(), 180, 0, 0, 1, '/var/www/html/sections/servers/demo/include/connection.php');

-- Insert Default Configurations
INSERT INTO `configurations` (`key`, `value`, `description`)
VALUES
  ('site_title', 'Travian Legends', 'Website title'),
  ('registration_open', '1', 'Enable user registration'),
  ('maintenance_mode', '0', 'Enable maintenance mode'),
  ('recaptcha_enabled', '0', 'Enable reCAPTCHA'),
  ('max_registrations_per_day', '100', 'Maximum registrations per day'),
  ('session_timeout', '3600', 'Session timeout in seconds');
