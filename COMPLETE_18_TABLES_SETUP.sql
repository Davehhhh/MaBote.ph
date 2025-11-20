-- ==============================================
-- COMPLETE 18 TABLES SETUP FOR MABOTE_DB
-- ==============================================

-- Create database
CREATE DATABASE IF NOT EXISTS mabote_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE mabote_db;

-- ==============================================
-- CORE USER TABLES
-- ==============================================

-- 1. Users table
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    barangay VARCHAR(100),
    city VARCHAR(100),
    qr_id VARCHAR(50) UNIQUE,
    is_active BOOLEAN DEFAULT TRUE,
    total_points INT DEFAULT 0,
    account_status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. Wallet table
CREATE TABLE IF NOT EXISTS wallet (
    wallet_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    current_balance DECIMAL(10,2) DEFAULT 0.00,
    total_earned DECIMAL(10,2) DEFAULT 0.00,
    is_active BOOLEAN DEFAULT TRUE,
    wallet_status ENUM('active', 'frozen', 'closed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- 3. QR Codes table
CREATE TABLE IF NOT EXISTS qr_codes (
    qr_id VARCHAR(50) PRIMARY KEY,
    user_id INT NOT NULL,
    qr_data TEXT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ==============================================
-- TRANSACTION TABLES
-- ==============================================

-- 4. Transactions table
CREATE TABLE IF NOT EXISTS transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    transaction_type ENUM('deposit', 'withdrawal', 'transfer', 'reward') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    points_earned INT DEFAULT 0,
    description TEXT,
    status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- 5. Deposit Session table
CREATE TABLE IF NOT EXISTS deposit_session (
    session_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    machine_id INT,
    start_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_time TIMESTAMP NULL,
    total_bottles INT DEFAULT 0,
    total_points INT DEFAULT 0,
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ==============================================
-- REWARD SYSTEM TABLES
-- ==============================================

-- 6. Reward table
CREATE TABLE IF NOT EXISTS reward (
    reward_id INT AUTO_INCREMENT PRIMARY KEY,
    reward_name VARCHAR(100) NOT NULL,
    description TEXT,
    points_required INT NOT NULL,
    reward_value DECIMAL(10,2),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 7. Redemption table
CREATE TABLE IF NOT EXISTS redemption (
    redemption_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    reward_id INT NOT NULL,
    points_used INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
    redemption_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (reward_id) REFERENCES reward(reward_id) ON DELETE CASCADE
);

-- ==============================================
-- MACHINE MANAGEMENT TABLES
-- ==============================================

-- 8. Machines table (MUST BE FIRST - no dependencies)
CREATE TABLE IF NOT EXISTS machines (
    machine_id INT AUTO_INCREMENT PRIMARY KEY,
    machine_name VARCHAR(100) NOT NULL,
    location VARCHAR(200),
    machine_type ENUM('bottle_deposit', 'general') DEFAULT 'bottle_deposit',
    status ENUM('active', 'maintenance', 'offline') DEFAULT 'active',
    last_maintenance DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 9. Smart Bin table (depends on machines)
CREATE TABLE IF NOT EXISTS smart_bin (
    bin_id INT AUTO_INCREMENT PRIMARY KEY,
    machine_id INT NOT NULL,
    bin_type ENUM('plastic', 'glass', 'metal', 'mixed') NOT NULL,
    capacity DECIMAL(5,2) NOT NULL,
    current_level DECIMAL(5,2) DEFAULT 0.00,
    status ENUM('empty', 'partial', 'full', 'overflow') DEFAULT 'empty',
    last_emptied TIMESTAMP NULL,
    FOREIGN KEY (machine_id) REFERENCES machines(machine_id) ON DELETE CASCADE
);

-- 10. Bin Sensor table (depends on smart_bin)
CREATE TABLE IF NOT EXISTS bin_sensor (
    sensor_id INT AUTO_INCREMENT PRIMARY KEY,
    bin_id INT NOT NULL,
    sensor_type ENUM('weight', 'ultrasonic', 'infrared') NOT NULL,
    sensor_value DECIMAL(10,2),
    reading_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (bin_id) REFERENCES smart_bin(bin_id) ON DELETE CASCADE
);

-- 11. Bin Maintenance table (depends on smart_bin)
CREATE TABLE IF NOT EXISTS bin_maintenance (
    maintenance_id INT AUTO_INCREMENT PRIMARY KEY,
    bin_id INT NOT NULL,
    maintenance_type ENUM('cleaning', 'repair', 'replacement', 'inspection') NOT NULL,
    description TEXT,
    maintenance_date DATE NOT NULL,
    next_maintenance DATE,
    status ENUM('scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'scheduled',
    FOREIGN KEY (bin_id) REFERENCES smart_bin(bin_id) ON DELETE CASCADE
);

-- ==============================================
-- SYSTEM TABLES
-- ==============================================

-- 12. Notification table
CREATE TABLE IF NOT EXISTS notification (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    notification_type ENUM('info', 'warning', 'success', 'error') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- 13. Sessions table
CREATE TABLE IF NOT EXISTS sessions (
    session_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- 14. Password Reset Tokens table
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    token_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- 15. Admin table
CREATE TABLE IF NOT EXISTS admin (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('super_admin', 'admin', 'moderator') DEFAULT 'admin',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 16. Points History table
CREATE TABLE IF NOT EXISTS points_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    points_change INT NOT NULL,
    change_type ENUM('earned', 'spent', 'bonus', 'penalty') NOT NULL,
    description TEXT,
    transaction_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- 17. Bottle Deposit Log table (depends on users and machines)
CREATE TABLE IF NOT EXISTS bottle_deposit_log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    machine_id INT NOT NULL,
    bottle_type ENUM('plastic', 'glass', 'metal') NOT NULL,
    bottle_count INT NOT NULL,
    points_earned INT NOT NULL,
    deposit_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    -- Note: machine_id foreign key removed temporarily to avoid compatibility issues
);

-- 18. System Config table
CREATE TABLE IF NOT EXISTS system_config (
    config_id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) UNIQUE NOT NULL,
    config_value TEXT NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ==============================================
-- INDEXES FOR PERFORMANCE
-- ==============================================

-- Create indexes for better performance (ignore errors if they already exist)
-- Note: If you get "Duplicate key name" errors, you can safely ignore them
-- or manually drop the indexes first using: DROP INDEX index_name ON table_name;

CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_qr_id ON users(qr_id);
CREATE INDEX idx_wallet_user_id ON wallet(user_id);
CREATE INDEX idx_transactions_user_id ON transactions(user_id);
CREATE INDEX idx_transactions_type ON transactions(transaction_type);
CREATE INDEX idx_transactions_status ON transactions(status);
CREATE INDEX idx_redemption_user_id ON redemption(user_id);
CREATE INDEX idx_redemption_status ON redemption(status);
CREATE INDEX idx_notification_user_id ON notification(user_id);
CREATE INDEX idx_notification_read ON notification(is_read);
CREATE INDEX idx_sessions_user_id ON sessions(user_id);
CREATE INDEX idx_sessions_token ON sessions(token);
CREATE INDEX idx_password_reset_user_id ON password_reset_tokens(user_id);
CREATE INDEX idx_password_reset_token ON password_reset_tokens(token);
CREATE INDEX idx_points_history_user_id ON points_history(user_id);
CREATE INDEX idx_bottle_deposit_user_id ON bottle_deposit_log(user_id);
CREATE INDEX idx_bottle_deposit_machine_id ON bottle_deposit_log(machine_id);
CREATE INDEX idx_smart_bin_machine_id ON smart_bin(machine_id);
CREATE INDEX idx_bin_sensor_bin_id ON bin_sensor(bin_id);
CREATE INDEX idx_bin_maintenance_bin_id ON bin_maintenance(bin_id);

-- ==============================================
-- SAMPLE DATA INSERTION
-- ==============================================

-- Insert sample users
INSERT INTO users (first_name, last_name, email, password_hash, phone, address, barangay, city, qr_id, total_points) VALUES
('John', 'Doe', 'john.doe@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '09123456789', '123 Main St', 'Barangay 1', 'Quezon City', 'QR001', 150),
('Jane', 'Smith', 'jane.smith@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '09123456790', '456 Oak Ave', 'Barangay 2', 'Manila', 'QR002', 200),
('Bob', 'Johnson', 'bob.johnson@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '09123456791', '789 Pine Rd', 'Barangay 3', 'Makati', 'QR003', 100);

-- Insert sample wallets
INSERT INTO wallet (user_id, current_balance, total_earned) VALUES
(1, 150.00, 150.00),
(2, 200.00, 200.00),
(3, 100.00, 100.00);

-- Insert sample QR codes
INSERT INTO qr_codes (qr_id, user_id, qr_data) VALUES
('QR001', 1, '{"user_id":1,"type":"user_qr"}'),
('QR002', 2, '{"user_id":2,"type":"user_qr"}'),
('QR003', 3, '{"user_id":3,"type":"user_qr"}');

-- Insert sample machines
INSERT INTO machines (machine_name, location, machine_type, status) VALUES
('Machine 1', 'Quezon City Mall', 'bottle_deposit', 'active'),
('Machine 2', 'Manila Bay Area', 'bottle_deposit', 'active'),
('Machine 3', 'Makati Business District', 'bottle_deposit', 'active'),
('Machine 4', 'Taguig City Center', 'bottle_deposit', 'active'),
('Machine 5', 'Pasig City Plaza', 'bottle_deposit', 'active');

-- Insert sample smart bins
INSERT INTO smart_bin (machine_id, bin_type, capacity, current_level, status) VALUES
(1, 'plastic', 100.00, 25.00, 'partial'),
(1, 'glass', 50.00, 10.00, 'partial'),
(2, 'plastic', 100.00, 75.00, 'partial'),
(2, 'metal', 75.00, 20.00, 'partial'),
(3, 'plastic', 100.00, 100.00, 'full');

-- Insert sample rewards
INSERT INTO reward (reward_name, description, points_required, reward_value) VALUES
('Gift Card 100', 'PHP 100 Gift Card', 100, 100.00),
('Gift Card 200', 'PHP 200 Gift Card', 200, 200.00),
('Gift Card 500', 'PHP 500 Gift Card', 500, 500.00),
('Gift Card 1000', 'PHP 1000 Gift Card', 1000, 1000.00),
('Gift Card 2000', 'PHP 2000 Gift Card', 2000, 2000.00);

-- Insert sample transactions
INSERT INTO transactions (user_id, transaction_type, amount, points_earned, description, status) VALUES
(1, 'deposit', 50.00, 50, 'Bottle deposit', 'completed'),
(2, 'deposit', 75.00, 75, 'Bottle deposit', 'completed'),
(3, 'deposit', 25.00, 25, 'Bottle deposit', 'completed'),
(1, 'deposit', 100.00, 100, 'Bottle deposit', 'completed');

-- Insert sample admin
INSERT INTO admin (username, password_hash, email, full_name, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@mabote.com', 'System Administrator', 'super_admin');

-- Insert sample system config
INSERT INTO system_config (config_key, config_value, description) VALUES
('points_per_bottle', '1', 'Points earned per bottle deposited'),
('max_daily_deposit', '100', 'Maximum bottles per day per user'),
('maintenance_interval', '7', 'Days between maintenance checks'),
('notification_enabled', 'true', 'Enable system notifications');

-- ==============================================
-- ADD MISSING FOREIGN KEY CONSTRAINTS
-- ==============================================

-- Add the machine_id foreign key constraint after all tables are created
-- This avoids compatibility issues during table creation
ALTER TABLE bottle_deposit_log 
ADD CONSTRAINT fk_bottle_deposit_machine 
FOREIGN KEY (machine_id) REFERENCES machines(machine_id) ON DELETE CASCADE;

-- ==============================================
-- VERIFICATION
-- ==============================================

-- Show all tables
SHOW TABLES;

-- Count tables
SELECT COUNT(*) as total_tables FROM information_schema.tables WHERE table_schema = 'mabote_db';
