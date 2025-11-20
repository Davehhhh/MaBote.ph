-- Fix password_reset_tokens table structure
-- Add missing 'used' column if it doesn't exist

-- First, check if the table exists and add the column
ALTER TABLE password_reset_tokens ADD COLUMN used TINYINT(1) DEFAULT 0;

-- If the above fails, the table might not exist, so create it:
-- CREATE TABLE IF NOT EXISTS password_reset_tokens (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     user_id INT NOT NULL,
--     token VARCHAR(64) NOT NULL,
--     expires_at DATETIME NOT NULL,
--     used TINYINT(1) DEFAULT 0,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
-- );







