-- Complete Database Setup for MaBote System
-- Run this in phpMyAdmin to create the entire database structure

-- Create database
CREATE DATABASE IF NOT EXISTS mabote_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE mabote_db;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    total_points INT DEFAULT 0,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active TINYINT(1) DEFAULT 1,
    profile_image VARCHAR(255),
    qr_code VARCHAR(255) UNIQUE
);

-- Smart bins table (legacy)
CREATE TABLE IF NOT EXISTS smart_bin (
    bin_id INT AUTO_INCREMENT PRIMARY KEY,
    bin_name VARCHAR(100) NOT NULL,
    location VARCHAR(255),
    status ENUM('active', 'maintenance', 'offline') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Machines table (new)
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

-- Transactions table
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
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (machine_id) REFERENCES machines(machine_id) ON DELETE SET NULL,
    FOREIGN KEY (bin_id) REFERENCES smart_bin(bin_id) ON DELETE SET NULL
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

-- Wallet table
CREATE TABLE IF NOT EXISTS wallet (
    wallet_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    current_balance INT DEFAULT 0,
    total_earned INT DEFAULT 0,
    total_spent INT DEFAULT 0,
    last_transaction_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Notifications table
CREATE TABLE IF NOT EXISTS notification (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    notification_type ENUM('points', 'reward', 'system', 'transaction') NOT NULL,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_read TINYINT(1) DEFAULT 0,
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
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

-- Insert sample data
INSERT INTO users (first_name, last_name, email, password, total_points) VALUES
('John', 'Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 150),
('Jane', 'Smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 200),
('Admin', 'User', 'admin@mabote.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0);

INSERT INTO machines (machine_id, location, status) VALUES
('BIN001', 'Mall Entrance', 'active'),
('BIN002', 'University Campus', 'active'),
('BIN003', 'Shopping Center', 'maintenance'),
('BIN004', 'Public Park', 'active'),
('BIN005', 'Office Building', 'offline');

INSERT INTO smart_bin (bin_name, location) VALUES
('Main Campus Bin', 'University Main Gate'),
('Library Bin', 'Library Entrance'),
('Cafeteria Bin', 'Student Cafeteria');

INSERT INTO reward (reward_name, description, points_required, quantity_available, category) VALUES
('Free Coffee', 'Get a free coffee at any partner cafe', 50, 100, 'Food & Beverage'),
('Eco Bag', 'Reusable eco-friendly shopping bag', 30, 200, 'Accessories'),
('Plant Seed Pack', 'Assorted vegetable seeds for home gardening', 25, 150, 'Gardening'),
('Water Bottle', 'Insulated stainless steel water bottle', 75, 80, 'Accessories'),
('Gift Card ₱100', '₱100 gift card for partner stores', 100, 50, 'Gift Cards');

INSERT INTO transactions (user_id, machine_id, bottle_deposited, points_earned, transaction_date) VALUES
(1, 'BIN001', 3, 15, '2024-01-15 10:30:00'),
(1, 'BIN002', 5, 25, '2024-01-14 14:20:00'),
(2, 'BIN001', 2, 10, '2024-01-13 09:15:00'),
(2, 'BIN003', 4, 20, '2024-01-12 16:45:00');

INSERT INTO wallet (user_id, current_balance, total_earned) VALUES
(1, 150, 150),
(2, 200, 200);

INSERT INTO lgu_admin (username, password, email, lgu_name) VALUES
('lgu_admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'lgu@example.com', 'Sample LGU');

-- Create indexes for better performance
CREATE INDEX idx_transactions_user_id ON transactions(user_id);
CREATE INDEX idx_transactions_date ON transactions(transaction_date);
CREATE INDEX idx_notifications_user_id ON notification(user_id);
CREATE INDEX idx_wallet_user_id ON wallet(user_id);


