-- Table for storing redeem voucher transactions history
CREATE TABLE IF NOT EXISTS `redeem_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
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
  INDEX `idx_tujuan` (`tujuan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
