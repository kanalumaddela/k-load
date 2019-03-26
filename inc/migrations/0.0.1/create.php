<?php

Database::run("CREATE TABLE IF NOT EXISTS kload_users (
					id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(32) NULL COMMENT 'steam name',
					steamid BIGINT(20) NOT NULL COMMENT 'steamid, e.g. 76561198152390718',
					steamid2 VARCHAR(20) NOT NULL COMMENT 'steam2 id, e.g. STEAM_...',
					steamid3 VARCHAR(20) NOT NULL COMMENT 'steam3 id, e.g. [U:1:...',
					admin TINYINT(1) DEFAULT 0 COMMENT 'is the user an admin?',
					perms TEXT NULL COMMENT 'list of perms, inactive when admin = 0',
					settings TEXT NULL COMMENT 'user settings in JSON',
					custom_css VARCHAR(500) NULL COMMENT 'user css for styling',
					banned TINYINT(1) DEFAULT 0 COMMENT 'is the user banned?',
					registered TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'date when joined'
				) DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE utf8mb4_unicode_ci");
Database::run("CREATE UNIQUE INDEX kload_users_index ON kload_users(steamid, steamid2, steamid3)");

Database::run("CREATE TABLE IF NOT EXISTS kload_settings (
					name VARCHAR(50) NOT NULL UNIQUE,
					value TEXT NOT NULL
				) DEFAULT CHARSET=utf8mb4  DEFAULT COLLATE utf8mb4_unicode_ci");

Database::run("CREATE TABLE IF NOT EXISTS kload_sessions (
					steamid BIGINT(20) NOT NULL UNIQUE COMMENT 'steamid, e.g. 76561198152390718',
					token VARCHAR(64) NOT NULL UNIQUE COMMENT 'csrf token',
					expires TIMESTAMP NULL COMMENT 'date at which token is invalid'
				) DEFAULT CHARSET=utf8mb4");
Database::run("CREATE TRIGGER csrf_fix_insert BEFORE INSERT ON `kload_sessions` FOR EACH ROW SET NEW.expires = TIMESTAMPADD(DAY,1,CURRENT_TIMESTAMP) ");
Database::run("CREATE TRIGGER csrf_fix_update BEFORE UPDATE ON `kload_sessions` FOR EACH ROW SET NEW.expires = TIMESTAMPADD(DAY,1,CURRENT_TIMESTAMP) ");

?>
