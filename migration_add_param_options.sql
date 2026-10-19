-- Jalankan sekali di phpMyAdmin / HeidiSQL Laragon (database `dynamic_parser`)
-- untuk menambahkan kolom preset nilai parameter ke tabel templates yang sudah ada.

ALTER TABLE `templates`
  ADD COLUMN `param_options` TEXT NULL AFTER `output_template`;
