-- ADDITIONAL TABLES ONLY (5 New Tables)
-- Run this if you already have the 13 core tables
-- Based on your screenshot requirements

USE mabote_db;

-- ==============================================
-- NEW TABLES (5 Additional Tables)
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

SELECT '5 additional tables created successfully!' AS status;
SELECT 'New tables: admin, bin_maintenance, bin_sensor, bottle_deposit_log, points_history, system_config' AS tables_created;
SELECT 'Total: 18 tables (13 existing + 5 new)' AS total_tables;



