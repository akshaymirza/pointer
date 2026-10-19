-- ========================================================
-- Migration: Add table voucher_templates for Multi-Template support
-- ========================================================

CREATE TABLE IF NOT EXISTS `voucher_templates` (
  `id` VARCHAR(50) PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `description` TEXT NULL,
  `base_target_url` TEXT NOT NULL,
  `success_keyword` VARCHAR(255) NULL,
  `success_custom_message` TEXT NULL,
  `failed_keyword` VARCHAR(255) NULL,
  `failed_custom_message` TEXT NULL,
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Initial Seed Multi Templates
INSERT INTO `voucher_templates` (`id`, `name`, `description`, `base_target_url`, `success_keyword`, `success_custom_message`, `failed_keyword`, `failed_custom_message`, `is_default`, `updated_at`) 
VALUES 
(
  'vt-tri-direct', 
  'Three (Tri) Voucher Check', 
  'Pengecekan status voucher Three langsung ke server API/Web.',
  'https://example.com/check-voucher?voucher={voucher}', 
  'SUKSES', 
  'Voucher Tri berhasil divalidasi dan aktif.', 
  'GAGAL', 
  'Voucher Tri tidak valid atau sudah kadaluarsa.', 
  1,
  NOW()
),
(
  'vt-google-search-demo', 
  'Google Search Demo Target', 
  'Target demo pencarian web untuk pengujian respons.',
  'https://www.google.com/search?q=kenapa+gemini+pro+3.1+di+{voucher}', 
  'gemini', 
  'Kata kunci berhasil ditemukan pada halaman web!', 
  'tidak ditemukan', 
  'Halaman tidak memuat informasi yang dicari.', 
  0,
  NOW()
)
ON DUPLICATE KEY UPDATE `updated_at` = NOW();
