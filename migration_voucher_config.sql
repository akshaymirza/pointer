-- ========================================================
-- Migration: Add table voucher_config for Page 2 configuration
-- ========================================================

CREATE TABLE IF NOT EXISTS `voucher_config` (
  `id` INT PRIMARY KEY DEFAULT 1,
  `base_target_url` TEXT NOT NULL,
  `success_keyword` VARCHAR(255) NULL,
  `success_custom_message` TEXT NULL,
  `failed_keyword` VARCHAR(255) NULL,
  `failed_custom_message` TEXT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Initial Seed Data
INSERT INTO `voucher_config` (`id`, `base_target_url`, `success_keyword`, `success_custom_message`, `failed_keyword`, `failed_custom_message`, `updated_at`) 
VALUES (
  1, 
  'https://example.com/check-voucher?voucher={voucher}', 
  'SUKSES', 
  'Voucher berhasil divalidasi dan aktif.', 
  'GAGAL', 
  'Voucher tidak valid atau sudah kadaluarsa.', 
  NOW()
) ON DUPLICATE KEY UPDATE `updated_at` = NOW();
