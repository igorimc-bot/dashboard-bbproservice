-- New table for rate limiting
USE `dashboard_database`;

CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ip_address` VARCHAR(45) NOT NULL,
    `attempt_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (`ip_address`, `attempt_time`)
) ENGINE=InnoDB;
