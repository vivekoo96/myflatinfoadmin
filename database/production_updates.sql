-- Update: Add is_upi_enabled to buildings table
ALTER TABLE `buildings` ADD `is_upi_enabled` ENUM('Yes', 'No') NOT NULL DEFAULT 'Yes' AFTER `upi_qr_code`;
