-- ========================================================
-- Database Schema: Dynamic Template Parser System
-- MySQL / MariaDB SQL Dump with 3-Tier User Authentication
-- ========================================================

CREATE DATABASE IF NOT EXISTS `dynamic_parser` 
  DEFAULT CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci;

USE `dynamic_parser`;

-- --------------------------------------------------------
-- Structure for table `users`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `history_logs`;
DROP TABLE IF EXISTS `templates`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `role` ENUM('superadmin', 'admin', 'karyawan') NOT NULL DEFAULT 'karyawan',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Structure for table `templates`
-- --------------------------------------------------------

CREATE TABLE `templates` (
  `id` VARCHAR(36) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `method` ENUM('GET', 'POST') NOT NULL DEFAULT 'GET',
  `api_url` TEXT NOT NULL,
  `input_template` TEXT NULL,
  `input_parameter` TEXT NULL,
  `sample_web_response` TEXT NULL,
  `output_template` TEXT NOT NULL,
  `param_options` TEXT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Structure for table `history_logs`
-- --------------------------------------------------------

CREATE TABLE `history_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `template_id` VARCHAR(36) NULL,
  `template_name` VARCHAR(255) NOT NULL,
  `user_id` INT NULL,
  `request_params` JSON NULL,
  `raw_response` LONGTEXT NULL,
  `generated_output` LONGTEXT NULL,
  `status` VARCHAR(50) DEFAULT 'success',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_history_templates` 
    FOREIGN KEY (`template_id`) REFERENCES `templates`(`id`) 
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_history_users`
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Seed Data for `users`
-- (Passwords hashed with password_hash standard or plain text fallback supported)
-- Default passwords:
-- superadmin -> superadmin123
-- admin      -> admin123
-- karyawan   -> karyawan123
-- --------------------------------------------------------

INSERT INTO `users` (`username`, `password`, `full_name`, `role`, `created_at`) VALUES
('superadmin', '$2y$10$wNnQ1y/4dI/.qN1/N1Z.aO/K24/Nn.x7ZtZ2gQx1K7d0r7t/.w8Ky', 'Super Administrator', 'superadmin', NOW()),
('admin', '$2y$10$1P004KqZ4R7y6k0pQ2Z/aO8Q2P/v2n.x7ZtZ2gQx1K7d0r7t/.w8Ky', 'Operational Admin', 'admin', NOW()),
('karyawan', '$2y$10$m9X3r.k3z2q.L7Y/aO9Q2P/v2n.x7ZtZ2gQx1K7d0r7t/.w8Ky', 'Employee User', 'karyawan', NOW());

-- --------------------------------------------------------
-- Sample Data for `templates`
-- --------------------------------------------------------

INSERT INTO `templates` (`id`, `name`, `description`, `method`, `api_url`, `input_template`, `input_parameter`, `sample_web_response`, `output_template`, `active`, `created_at`, `updated_at`) VALUES
('c7a8b9f0-1234-4567-89ab-cdef01234567', 'Voucher Unlock Status', 'Check and parse unlock status for vouchers.', 'GET', 'https://server/api/check?serialNumber={serialNumber}', '', '', '', 'Serial Number : {data.serialNumber}\nStatus : {data.status}\nUnlock Date : {data.unlockDate}\nRetailer MSISDN : {data.unlockRetMSISDN}\nUnlock Price : {data.unlockPrice}\nProduct Name : {data.productName}\nVoucher Status : {data.voucherStatus}', 1, NOW(), NOW()),
('d8b9ca01-2345-5678-9abc-def012345678', 'Payment Verification', 'Parse billing verification data.', 'POST', 'https://server/api/payment?invoice={invoice}&channel={channel}', '', '', '', 'Invoice : {data.invoice}\n\nStatus : {data.status}\n\nChannel : {data.channel}\n\nAmount : {data.amount}', 1, NOW(), NOW());
