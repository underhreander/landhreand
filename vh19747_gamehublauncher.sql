-- Database: gamehub_launcher

CREATE DATABASE IF NOT EXISTS `vh18085_gamehublauncher` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `vh18085_gamehublauncher`;

-- Table structure for table `admins`
CREATE TABLE IF NOT EXISTS `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL COMMENT 'Should use password_hash() PHP function',
  `role` enum('admin','superadmin') NOT NULL DEFAULT 'admin',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for table `visits`
CREATE TABLE IF NOT EXISTS `visits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text NOT NULL,
  `page_visited` varchar(255) NOT NULL,
  `referrer` varchar(255) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'Unknown',
  `visit_time` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_visits_ip` (`ip_address`),
  KEY `idx_visits_time` (`visit_time`),
  KEY `idx_visits_country` (`country`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for table `downloads` (updated with new structure)
CREATE TABLE IF NOT EXISTS `downloads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text,
  `country` varchar(100) DEFAULT 'Unknown',
  `trial_code` varchar(50) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `download_time` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_downloads_ip` (`ip_address`),
  KEY `idx_downloads_time` (`download_time`),
  KEY `idx_downloads_code` (`trial_code`),
  KEY `idx_downloads_country` (`country`),
  KEY `idx_ip_date` (`ip_address`, `download_time`),
  KEY `idx_trial_code` (`trial_code`),
  KEY `idx_download_time` (`download_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for table `games`
CREATE TABLE IF NOT EXISTS `games` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `image` varchar(255) NOT NULL COMMENT 'Path relative to images/games/',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `added_date` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_games_active` (`is_active`),
  KEY `idx_games_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for table `settings`
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `value` text NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for table `admin_logs`
CREATE TABLE IF NOT EXISTS `admin_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `action` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `action_time` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_logs_admin` (`admin_id`),
  KEY `idx_logs_time` (`action_time`),
  CONSTRAINT `fk_logs_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample data for table `games`
INSERT INTO `games` (`name`, `image`, `display_order`) VALUES
('World of Fantasy', 'images/games/world-of-fantasy.png', 1),
('Space Adventures', 'images/games/space-adventures.webp', 2),
('Racing Legends', 'images/games/racing-legends.png', 3),
('Battle Royale', 'images/games/battle-royale.webp', 4),
('Puzzle Master', 'images/games/puzzle-master.png', 5);

-- Secure admin user (password: Admin@1234)
INSERT INTO `admins` (`username`, `password_hash`, `role`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'superadmin');

-- Default settings
INSERT INTO `settings` (`name`, `value`, `description`) VALUES
('download_link', 'downloads/GameHubLauncher.exe', 'Main launcher download link'),
('max_trial_codes', '1000', 'Maximum trial codes per day'),
('contact_email', 'support@gamehub.com', 'Support contact email');

-- Create view for daily stats
CREATE OR REPLACE VIEW `daily_stats` AS
SELECT 
    DATE(v.visit_time) AS date,
    COUNT(DISTINCT v.ip_address) AS unique_visitors,
    COUNT(v.id) AS total_visits,
    COUNT(d.id) AS downloads
FROM 
    visits v
LEFT JOIN 
    downloads d ON DATE(d.download_time) = DATE(v.visit_time)
GROUP BY 
    DATE(v.visit_time)
ORDER BY 
    date DESC;

-- Create procedure for cleaning old data
DELIMITER //
CREATE PROCEDURE `clean_old_data`(IN days_keep INT)
BEGIN
    DELETE FROM visits WHERE visit_time < DATE_SUB(NOW(), INTERVAL days_keep DAY);
    DELETE FROM downloads WHERE download_time < DATE_SUB(NOW(), INTERVAL days_keep DAY);
    DELETE FROM admin_logs WHERE action_time < DATE_SUB(NOW(), INTERVAL days_keep DAY);
END //
DELIMITER ;