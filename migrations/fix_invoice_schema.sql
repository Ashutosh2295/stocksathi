-- Fix invoice schema missing columns and enum types
-- Added payment_mode_id
ALTER TABLE invoices ADD COLUMN payment_mode_id INT(11) NULL AFTER paid_amount;

-- Update payment_status to support 'pending'
ALTER TABLE invoices MODIFY COLUMN payment_status VARCHAR(50) DEFAULT 'pending';

-- Update status to support 'finalized'
ALTER TABLE invoices MODIFY COLUMN status VARCHAR(50) DEFAULT 'draft';
