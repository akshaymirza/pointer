-- ========================================================
-- Database Schema: Dynamic Template Parser & Voucher System
-- MySQL / MariaDB SQL Dump (Idempotent - Safe to Re-run)
-- Semua tabel menggunakan `CREATE TABLE IF NOT EXISTS`
-- sehingga jika tabel sudah ada maka otomatis dilewati (skip).
-- ========================================================

CREATE DATABASE IF NOT EXISTS `dynamic_parser` 
  DEFAULT CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci;

USE `dynamic_parser`;

-- --------------------------------------------------------
-- 1. Structure for table `users`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `role` ENUM('superadmin', 'admin', 'karyawan') NOT NULL DEFAULT 'karyawan',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 2. Structure for table `templates` (Template Parser Halaman 1)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `templates` (
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
  `param_defaults` TEXT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 3. Structure for table `history_logs` (Log Eksekusi Template)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `history_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `template_id` VARCHAR(36) NULL,
  `template_name` VARCHAR(255) NOT NULL,
  `user_id` INT NULL,
  `request_params` JSON NULL,
  `raw_response` LONGTEXT NULL,
  `generated_output` LONGTEXT NULL,
  `status` VARCHAR(50) DEFAULT 'success',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_history_template_id` (`template_id`),
  INDEX `idx_history_user_id` (`user_id`),
  CONSTRAINT `fk_history_templates` 
    FOREIGN KEY (`template_id`) REFERENCES `templates`(`id`) 
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_history_users`
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 4. Structure for table `voucher_config` (Pengaturan Tunggal Voucher)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `voucher_config` (
  `id` INT PRIMARY KEY DEFAULT 1,
  `base_target_url` TEXT NOT NULL,
  `success_keyword` VARCHAR(255) NULL,
  `success_custom_message` TEXT NULL,
  `failed_keyword` VARCHAR(255) NULL,
  `failed_custom_message` TEXT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 5. Structure for table `voucher_templates` (Multi-Template Voucher & Redeem)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `voucher_templates` (
  `id` VARCHAR(50) PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `description` TEXT NULL,
  `base_target_url` TEXT NOT NULL,
  `default_sender` VARCHAR(50) NULL,
  `trx_id_variable` VARCHAR(50) NULL,
  `trx_id_prefix` VARCHAR(50) NULL,
  `trx_id_last_number` INT NOT NULL DEFAULT 1000,
  `allowed_usernames` TEXT NULL,
  `target_numbers` TEXT NULL,
  `success_keyword` VARCHAR(255) NULL,
  `success_custom_message` TEXT NULL,
  `pending_keyword` VARCHAR(255) NULL,
  `pending_custom_message` TEXT NULL,
  `failed_keyword` VARCHAR(255) NULL,
  `failed_custom_message` TEXT NULL,
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 6. Structure for table `redeem_logs` (Riwayat Transaksi Redeem Voucher)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `redeem_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `template_id` VARCHAR(50) NULL,
  `template_name` VARCHAR(150) NULL,
  `trx_no` VARCHAR(100) NULL,
  `pengirim` VARCHAR(50) NOT NULL,
  `tujuan` VARCHAR(50) NOT NULL,
  `voucher` VARCHAR(100) NOT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'PENDING',
  `saldo` VARCHAR(50) NULL,
  `message` TEXT NULL,
  `raw_response` LONGTEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_created_at` (`created_at`),
  INDEX `idx_voucher` (`voucher`),
  INDEX `idx_tujuan` (`tujuan`),
  INDEX `idx_template_id` (`template_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 7. Structure for table `sender_accounts` (Daftar Pengirim / Akun)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `sender_accounts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `label` VARCHAR(100) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 8. Safe Column Migration / Backfill
-- Menjamin jika database sudah memiliki tabel lama tanpa kolom baru,
-- kolom baru akan ditambahkan tanpa error dan tanpa menghapus data.
-- --------------------------------------------------------

DELIMITER $$

DROP PROCEDURE IF EXISTS `AddColumnIfNotExists` $$
CREATE PROCEDURE `AddColumnIfNotExists`(
    IN in_table_name VARCHAR(64),
    IN in_column_name VARCHAR(64),
    IN in_column_definition TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS 
        WHERE table_schema = DATABASE() 
          AND table_name = in_table_name 
          AND column_name = in_column_name
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', in_table_name, '` ADD COLUMN `', in_column_name, '` ', in_column_definition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END $$

DELIMITER ;

-- Kolom baru di templates
CALL AddColumnIfNotExists('templates', 'param_options', 'TEXT NULL AFTER `output_template`');
CALL AddColumnIfNotExists('templates', 'param_defaults', 'TEXT NULL AFTER `param_options`');

-- Kolom baru di voucher_templates
CALL AddColumnIfNotExists('voucher_templates', 'default_sender', 'VARCHAR(50) NULL AFTER `base_target_url`');
CALL AddColumnIfNotExists('voucher_templates', 'trx_id_variable', 'VARCHAR(50) NULL AFTER `default_sender`');
CALL AddColumnIfNotExists('voucher_templates', 'trx_id_prefix', 'VARCHAR(50) NULL AFTER `trx_id_variable`');
CALL AddColumnIfNotExists('voucher_templates', 'trx_id_last_number', 'INT NOT NULL DEFAULT 1000 AFTER `trx_id_prefix`');
CALL AddColumnIfNotExists('voucher_templates', 'allowed_usernames', 'TEXT NULL AFTER `trx_id_last_number`');
CALL AddColumnIfNotExists('voucher_templates', 'target_numbers', 'TEXT NULL AFTER `allowed_usernames`');
CALL AddColumnIfNotExists('voucher_templates', 'pending_keyword', 'VARCHAR(255) NULL AFTER `success_custom_message`');
CALL AddColumnIfNotExists('voucher_templates', 'pending_custom_message', 'TEXT NULL AFTER `pending_keyword`');

-- Kolom baru di redeem_logs
CALL AddColumnIfNotExists('redeem_logs', 'template_id', 'VARCHAR(50) NULL AFTER `user_id`');
CALL AddColumnIfNotExists('redeem_logs', 'template_name', 'VARCHAR(150) NULL AFTER `template_id`');

DROP PROCEDURE IF EXISTS `AddColumnIfNotExists`;

-- --------------------------------------------------------
-- 9. Seed Data Awal (Menggunakan INSERT IGNORE / ON DUPLICATE)
-- --------------------------------------------------------

-- Default Users:
-- superadmin -> superadmin123
-- admin      -> admin123
-- karyawan   -> karyawan123
INSERT IGNORE INTO `users` (`id`, `username`, `password`, `full_name`, `role`, `created_at`) VALUES
(1, 'superadmin', '$2y$10$wNnQ1y/4dI/.qN1/N1Z.aO/K24/Nn.x7ZtZ2gQx1K7d0r7t/.w8Ky', 'Super Administrator', 'superadmin', NOW()),
(2, 'admin', '$2y$10$1P004KqZ4R7y6k0pQ2Z/aO8Q2P/v2n.x7ZtZ2gQx1K7d0r7t/.w8Ky', 'Operational Admin', 'admin', NOW()),
(3, 'karyawan', '$2y$10$m9X3r.k3z2q.L7Y/aO9Q2P/v2n.x7ZtZ2gQx1K7d0r7t/.w8Ky', 'Employee User', 'karyawan', NOW());

-- Sample Data templates
INSERT IGNORE INTO `templates` (`id`, `name`, `description`, `method`, `api_url`, `input_template`, `input_parameter`, `sample_web_response`, `output_template`, `param_options`, `param_defaults`, `active`, `created_at`, `updated_at`) VALUES
('c7a8b9f0-1234-4567-89ab-cdef01234567', 'Voucher Unlock Status', 'Check and parse unlock status for vouchers.', 'GET', 'https://server/api/check?serialNumber={serialNumber}', '', '', '', 'Serial Number : {data.serialNumber}\nStatus : {data.status}\nUnlock Date : {data.unlockDate}\nRetailer MSISDN : {data.unlockRetMSISDN}\nUnlock Price : {data.unlockPrice}\nProduct Name : {data.productName}\nVoucher Status : {data.voucherStatus}', NULL, NULL, 1, NOW(), NOW()),
('d8b9ca01-2345-5678-9abc-def012345678', 'Payment Verification', 'Parse billing verification data.', 'POST', 'https://server/api/payment?invoice={invoice}&channel={channel}', '', '', '', 'Invoice : {data.invoice}\n\nStatus : {data.status}\n\nChannel : {data.channel}\n\nAmount : {data.amount}', NULL, NULL, 1, NOW(), NOW());

-- Default voucher_config
INSERT INTO `voucher_config` (`id`, `base_target_url`, `success_keyword`, `success_custom_message`, `failed_keyword`, `failed_custom_message`, `updated_at`) 
VALUES (
  1, 
  'https://example.com/check-voucher?voucher={voucher}', 
  'SUKSES', 
  'Voucher berhasil divalidasi dan aktif.', 
  'GAGAL', 
  'Voucher tidak valid atau sudah kadaluarsa.', 
  NOW()
) ON DUPLICATE KEY UPDATE `id` = `id`;

-- Default voucher_templates
INSERT INTO `voucher_templates` (
  `id`, `name`, `description`, `base_target_url`, `default_sender`, 
  `trx_id_variable`, `trx_id_prefix`, `trx_id_last_number`, 
  `allowed_usernames`, `target_numbers`, 
  `success_keyword`, `success_custom_message`, 
  `pending_keyword`, `pending_custom_message`, 
  `failed_keyword`, `failed_custom_message`, 
  `is_default`, `updated_at`
) VALUES 
(
  'vt-tri-direct', 
  'Three (Tri) Voucher Check', 
  'Pengecekan status voucher Three langsung ke server API/Web.',
  'https://example.com/check-voucher?username={username}&voucher={voucher}', 
  '089523098721',
  NULL, NULL, 1000,
  '["089523098721"]', '["0895639154834"]',
  'SUKSES', 'Voucher Tri berhasil divalidasi dan aktif.', 
  'menunggu', 'Voucher sedang dalam antrian / proses.',
  'GAGAL', 'Voucher Tri tidak valid atau sudah kadaluarsa.', 
  1, NOW()
),
(
  'vt-google-search-demo', 
  'Google Search Demo Target', 
  'Target demo pencarian web untuk pengujian respons.',
  'https://www.google.com/search?q=kenapa+gemini+pro+3.1+di+{voucher}', 
  '',
  NULL, NULL, 1000,
  '[]', '[]',
  'gemini', 'Kata kunci berhasil ditemukan pada halaman web!', 
  'menunggu', 'Voucher sedang dalam antrian / proses.',
  'tidak ditemukan', 'Halaman tidak memuat informasi yang dicari.', 
  0, NOW()
)
ON DUPLICATE KEY UPDATE `id` = `id`;

-- Default sender_accounts
INSERT IGNORE INTO `sender_accounts` (`username`, `label`, `created_at`) 
VALUES ('089523098721', 'Akun Utama Tri', NOW());
