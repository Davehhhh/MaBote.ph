-- Fix users table to add password_hash column if it doesn't exist
USE mabote_db;

-- Check if password_hash column exists, if not add it
-- If your table has 'password' instead of 'password_hash', this will add password_hash
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS password_hash VARCHAR(255) AFTER email;

-- If you have 'password' column and want to rename it
-- ALTER TABLE users CHANGE COLUMN password password_hash VARCHAR(255);

-- Verify the table structure
DESCRIBE users;






