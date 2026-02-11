-- Database Schema for Dashboard Stats

CREATE DATABASE IF NOT EXISTS `dashboard_database` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `dashboard_database`;

-- Users table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('superadmin', 'user') NOT NULL DEFAULT 'user',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Sites table
CREATE TABLE IF NOT EXISTS `sites` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `url` VARCHAR(255) NOT NULL,
    `owner_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`owner_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Daily Stats table
CREATE TABLE IF NOT EXISTS `daily_stats` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `site_id` INT NOT NULL,
    `date` DATE NOT NULL,
    `visits` INT DEFAULT 0,
    `leads` INT DEFAULT 0,
    UNIQUE KEY `site_date` (`site_id`, `date`),
    FOREIGN KEY (`site_id`) REFERENCES `sites`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Insert default superadmin (password: admin123)
-- In production, the password should be hashed with password_hash()
INSERT INTO `users` (`username`, `password`, `role`) 
VALUES ('admin', '$2y$10$N5h0FoHkD7hbb/CPIYB0bejPE9e7zGN3hXig547L4olh7iTboUMaK', 'superadmin')
ON DUPLICATE KEY UPDATE id=id;
