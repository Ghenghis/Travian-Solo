-- Global misc tables (not currently used by features, created for completeness)
USE travian_global;

-- payment_log: record payment transactions
CREATE TABLE IF NOT EXISTS `payment_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid` INT UNSIGNED NOT NULL,
  `provider` VARCHAR(32) NOT NULL,
  `txid` VARCHAR(64) NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `currency` CHAR(3) NOT NULL DEFAULT 'USD',
  `status` VARCHAR(16) NOT NULL,
  `created_at` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  UNIQUE KEY `uniq_txid` (`provider`,`txid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- voting: external voting integrations placeholder
CREATE TABLE IF NOT EXISTS `voting` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `source` VARCHAR(32) NOT NULL,
  `uid` INT UNSIGNED NULL,
  `payload` JSON NULL,
  `received_at` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `source_received_at` (`source`,`received_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
