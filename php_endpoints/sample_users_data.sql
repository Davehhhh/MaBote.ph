-- Sample users for testing the app
-- Run this in phpMyAdmin to add test users

-- Insert sample users (password is 'password123' hashed)
INSERT INTO users (first_name, last_name, email, phone, password_hash, address, barangay, city, date_registered, total_points, qr_id, is_active, user_profile) VALUES
('John', 'Doe', 'john.doe@email.com', '09123456789', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '123 Main Street', 'Barangay 1', 'Manila', NOW() - INTERVAL 7 DAY, 150, 'QR001', 1, NULL),
('Jane', 'Smith', 'jane.smith@email.com', '09123456790', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '456 Oak Avenue', 'Barangay 2', 'Quezon City', NOW() - INTERVAL 5 DAY, 75, 'QR002', 1, NULL),
('Mike', 'Johnson', 'mike.johnson@email.com', '09123456791', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '789 Pine Road', 'Barangay 3', 'Makati', NOW() - INTERVAL 3 DAY, 200, 'QR003', 1, NULL),
('Sarah', 'Wilson', 'sarah.wilson@email.com', '09123456792', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '321 Elm Street', 'Barangay 4', 'Taguig', NOW() - INTERVAL 1 DAY, 50, 'QR004', 1, NULL),
('David', 'Brown', 'david.brown@email.com', '09123456793', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '654 Maple Drive', 'Barangay 5', 'Pasig', NOW() - INTERVAL 2 DAY, 125, 'QR005', 1, NULL);

-- Insert corresponding wallet entries
INSERT INTO wallet (user_id, current_balance, total_earned, last_transaction_date) VALUES
(1, 150, 150, NOW() - INTERVAL 1 HOUR),
(2, 75, 75, NOW() - INTERVAL 2 HOUR),
(3, 200, 200, NOW() - INTERVAL 30 MINUTE),
(4, 50, 50, NOW() - INTERVAL 3 HOUR),
(5, 125, 125, NOW() - INTERVAL 1 HOUR);

-- Insert sample transactions
INSERT INTO transactions (user_id, transaction_type, points_amount, transaction_code, description, created_at) VALUES
(1, 'earned', 25, 'TXN001', 'Bottle deposit - 5 bottles', NOW() - INTERVAL 1 HOUR),
(1, 'spent', 50, 'TXN002', 'Reward claimed - Free Coffee', NOW() - INTERVAL 2 HOUR),
(2, 'earned', 20, 'TXN003', 'Bottle deposit - 4 bottles', NOW() - INTERVAL 2 HOUR),
(3, 'earned', 30, 'TXN004', 'Bottle deposit - 6 bottles', NOW() - INTERVAL 30 MINUTE),
(3, 'spent', 25, 'TXN005', 'Reward claimed - Free Drink', NOW() - INTERVAL 1 HOUR),
(4, 'earned', 15, 'TXN006', 'Bottle deposit - 3 bottles', NOW() - INTERVAL 3 HOUR),
(5, 'earned', 35, 'TXN007', 'Bottle deposit - 7 bottles', NOW() - INTERVAL 1 HOUR),
(5, 'spent', 30, 'TXN008', 'Reward claimed - Free Snack', NOW() - INTERVAL 2 HOUR);
