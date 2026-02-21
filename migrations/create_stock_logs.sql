-- =====================================================
-- STOCK LOGS TABLE - For Stock In/Out/Adjustments/Transfers
-- Run this to create the missing stock_logs table
-- =====================================================

USE `stocksathi`;

-- Stock Logs Table (centralized stock movement tracking)
CREATE TABLE IF NOT EXISTS `stock_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `type` enum('in','out','adjustment','transfer') NOT NULL,
  `quantity` int(11) NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `warehouse_id` int(11) DEFAULT NULL,
  `store_id` int(11) DEFAULT NULL,
  `from_location_id` int(11) DEFAULT NULL,
  `to_location_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `type` (`type`),
  KEY `warehouse_id` (`warehouse_id`),
  KEY `store_id` (`store_id`),
  KEY `created_by` (`created_by`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `stock_logs_product_fk` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_logs_warehouse_fk` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `stock_logs_store_fk` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE SET NULL,
  CONSTRAINT `stock_logs_user_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add refund_status column to sales_returns if it doesn't exist
ALTER TABLE `sales_returns` 
ADD COLUMN IF NOT EXISTS `refund_status` enum('pending','processing','completed') DEFAULT 'pending' AFTER `status`;

-- Update sales_returns to set refund_status from status if needed
UPDATE `sales_returns` SET `refund_status` = CASE 
  WHEN `status` = 'refunded' THEN 'completed'
  WHEN `status` = 'approved' THEN 'processing'
  ELSE 'pending'
END WHERE `refund_status` IS NULL OR `refund_status` = '';

SELECT '✅ Stock logs table created successfully!' AS message;
