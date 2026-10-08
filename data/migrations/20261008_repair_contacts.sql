-- Run once on an existing MySQL installation if the application account cannot ALTER tables.
-- Check whether either column already exists before executing that statement.
ALTER TABLE repair_requests ADD COLUMN user_id INT DEFAULT NULL AFTER id;
ALTER TABLE repair_requests ADD COLUMN customer_zalo VARCHAR(50) DEFAULT NULL AFTER customer_phone;
