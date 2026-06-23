ALTER TABLE payment_records
ADD COLUMN proof_file VARCHAR(255) DEFAULT NULL AFTER payment_status;
