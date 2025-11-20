-- DATABASE TABLES ONLY (No Data Insertion)
-- Run this first to create all table structures
-- Based on actual mabote_api structure

-- Create database
CREATE DATABASE IF NOT EXISTS mabote_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE mabote_db;

-- ==============================================
-- CORE TABLES (Based on actual mabote_api)
-- ==============================================

-- Users table (Exact structure from signup_extended.php)
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
    total_points INT DEFAULT 0,
    qr_id VARCHAR(50) UNIQUE,
    user_profile VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Wallet table (Exact structure from signup_extended.php)
CREATE TABLE IF NOT EXISTS wallet (
    wallet_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    current_balance INT DEFAULT 0,
    total_earned INT DEFAULT 0,
    total_spent INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    wallet_status ENUM('active', 'suspended', 'inactive') DEFAULT 'active',
    last_transaction_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Transactions table (Based on SQL files)
CREATE TABLE IF NOT EXISTS transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    machine_id VARCHAR(50) DEFAULT 'BIN001',
    bin_id INT,
    transaction_code VARCHAR(50) UNIQUE,
    bottle_deposited INT DEFAULT 0,
    points_earned INT DEFAULT 0,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    transaction_status ENUM('pending', 'completed', 'failed') DEFAULT 'completed',
    qr_code_scanned VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Rewards table
CREATE TABLE IF NOT EXISTS reward (
    reward_id INT AUTO_INCREMENT PRIMARY KEY,
    reward_name VARCHAR(100) NOT NULL,
    description TEXT,
    points_required INT NOT NULL,
    quantity_available INT DEFAULT 0,
    category VARCHAR(50),
    reward_image VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Redemption table
CREATE TABLE IF NOT EXISTS redemption (
    redemption_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    reward_id INT NOT NULL,
    points_used INT NOT NULL,
    redemption_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (reward_id) REFERENCES reward(reward_id) ON DELETE CASCADE
);

-- Notifications table
CREATE TABLE IF NOT EXISTS notification (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    notification_type ENUM('points', 'reward', 'system', 'transaction') NOT NULL,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_read TINYINT(1) DEFAULT 0,
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Machines table
CREATE TABLE IF NOT EXISTS machines (
    machine_id VARCHAR(50) PRIMARY KEY,
    location VARCHAR(255) NOT NULL,
    status ENUM('active', 'maintenance', 'offline') DEFAULT 'active',
    fill_level INT DEFAULT 0,
    battery_level INT DEFAULT 100,
    temperature DECIMAL(5,2) DEFAULT 25.00,
    last_maintenance DATE NULL,
    last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Smart bins table (legacy)
CREATE TABLE IF NOT EXISTS smart_bin (
    bin_id INT AUTO_INCREMENT PRIMARY KEY,
    bin_name VARCHAR(100) NOT NULL,
    location VARCHAR(255),
    status ENUM('active', 'maintenance', 'offline') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- LGU Admin table
CREATE TABLE IF NOT EXISTS lgu_admin (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    lgu_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==============================================
-- ADDITIONAL TABLES (From comprehensive check)
-- ==============================================

-- QR Codes table (Referenced in comprehensive check)
CREATE TABLE IF NOT EXISTS qr_codes (
    qr_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    qr_code VARCHAR(255) UNIQUE NOT NULL,
    qr_data TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_used TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_qr_code (qr_code),
    INDEX idx_user_id (user_id)
);

-- Deposit session table
CREATE TABLE IF NOT EXISTS deposit_session (
    session_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    bin_id INT NOT NULL,
    session_token VARCHAR(32) NOT NULL UNIQUE,
    status ENUM('open', 'closed') NOT NULL DEFAULT 'open',
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    INDEX idx_session_token (session_token),
    INDEX idx_user_status (user_id, status),
    INDEX idx_expires (expires_at)
);

-- Password reset tokens table
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    token_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_expires_at (expires_at),
    INDEX idx_user_id (user_id)
);

-- Sessions table (From signup_extended.php)
CREATE TABLE IF NOT EXISTS sessions (
    session_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
);

-- ==============================================
-- INDEXES FOR PERFORMANCE (Compatible Creation)
-- ==============================================

-- Create indexes (will skip if already exists due to CREATE TABLE IF NOT EXISTS)
-- Note: If you get duplicate index errors, you can safely ignore them
-- or manually drop the indexes first using: DROP INDEX index_name ON table_name;

CREATE INDEX idx_transactions_user_id ON transactions(user_id);
CREATE INDEX idx_transactions_date ON transactions(transaction_date);
CREATE INDEX idx_notifications_user_id ON notification(user_id);
CREATE INDEX idx_wallet_user_id ON wallet(user_id);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_qr_id ON users(qr_id);

-- ==============================================
-- ADDITIONAL TABLES (From Screenshot)
-- ==============================================

-- Admin table (renamed from lgu_admin)
CREATE TABLE IF NOT EXISTS admin (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    admin_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bin maintenance table
CREATE TABLE IF NOT EXISTS bin_maintenance (
    maintenance_id INT AUTO_INCREMENT PRIMARY KEY,
    bin_id INT NOT NULL,
    maintenance_type ENUM('cleaning', 'repair', 'inspection', 'replacement') NOT NULL,
    maintenance_date DATE NOT NULL,
    description TEXT,
    cost DECIMAL(10,2) DEFAULT 0.00,
    technician VARCHAR(100),
    status ENUM('scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bin_id) REFERENCES smart_bin(bin_id) ON DELETE CASCADE
);

-- Bin sensor table
CREATE TABLE IF NOT EXISTS bin_sensor (
    sensor_id INT AUTO_INCREMENT PRIMARY KEY,
    bin_id INT NOT NULL,
    sensor_type ENUM('weight', 'fill_level', 'temperature', 'humidity', 'motion') NOT NULL,
    sensor_value DECIMAL(10,2) NOT NULL,
    sensor_unit VARCHAR(20),
    reading_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive', 'error') DEFAULT 'active',
    FOREIGN KEY (bin_id) REFERENCES smart_bin(bin_id) ON DELETE CASCADE,
    INDEX idx_bin_sensor (bin_id, sensor_type),
    INDEX idx_reading_date (reading_date)
);

-- Bottle deposit log table
CREATE TABLE IF NOT EXISTS bottle_deposit_log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    bin_id INT NOT NULL,
    transaction_id INT,
    bottle_count INT NOT NULL,
    bottle_weight DECIMAL(8,2),
    deposit_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    machine_response TEXT,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (bin_id) REFERENCES smart_bin(bin_id) ON DELETE CASCADE,
    FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id) ON DELETE SET NULL,
    INDEX idx_user_deposit (user_id, deposit_date),
    INDEX idx_bin_deposit (bin_id, deposit_date)
);

-- Points history table
CREATE TABLE IF NOT EXISTS points_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    transaction_type ENUM('earned', 'spent', 'bonus', 'penalty', 'refund') NOT NULL,
    points_amount INT NOT NULL,
    description TEXT,
    reference_id INT, -- Can reference transaction_id or redemption_id
    reference_type ENUM('transaction', 'redemption', 'bonus', 'manual') DEFAULT 'transaction',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_history (user_id, created_at),
    INDEX idx_transaction_type (transaction_type)
);

-- System configuration table
CREATE TABLE IF NOT EXISTS system_config (
    config_id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) UNIQUE NOT NULL,
    config_value TEXT,
    config_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==============================================
-- COMPLETION MESSAGE
-- ==============================================

SELECT 'All 18 tables created successfully!' AS status;
SELECT 'Tables: users, wallet, transactions, reward, redemption, notification, machines, smart_bin, admin, qr_codes, deposit_session, password_reset_tokens, sessions, bin_maintenance, bin_sensor, bottle_deposit_log, points_history, system_config' AS tables_created;
SELECT 'Ready for data insertion!' AS next_step;
