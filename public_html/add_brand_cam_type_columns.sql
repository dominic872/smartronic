-- Add brand and cam_type columns to orders table
ALTER TABLE `orders` ADD COLUMN `brand` VARCHAR(50) NULL AFTER `resolution`;
ALTER TABLE `orders` ADD COLUMN `cam_type` VARCHAR(50) NULL AFTER `brand`;
