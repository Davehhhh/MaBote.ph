-- Add Test Bottle Transactions for All Users
-- This script adds realistic bottle deposit transactions to test the app

-- First, let's see current users and their points
SELECT 
    u.user_id,
    u.email,
    u.first_name,
    u.last_name,
    u.total_points,
    w.current_balance,
    COUNT(t.transaction_id) as current_transactions
FROM users u
LEFT JOIN wallet w ON u.user_id = w.user_id
LEFT JOIN transactions t ON u.user_id = t.user_id
GROUP BY u.user_id, u.email, u.first_name, u.last_name, u.total_points, w.current_balance
ORDER BY u.user_id;

-- Add test transactions for each user
-- Each user gets 3-5 random bottle deposits with different dates

-- User 1: FF FF f fttt (warren.jacaban@gmail.com)
INSERT INTO transactions (user_id, transaction_code, bottle_deposited, points_earned, transaction_date, qr_code_scanned, transaction_status) VALUES
(1, 'TXN_001_001', 5, 25, '2024-12-20 10:30:00', 'QR_001', 'completed'),
(1, 'TXN_001_002', 8, 40, '2024-12-18 14:15:00', 'QR_002', 'completed'),
(1, 'TXN_001_003', 3, 15, '2024-12-15 09:45:00', 'QR_003', 'completed'),
(1, 'TXN_001_004', 12, 60, '2024-12-12 16:20:00', 'QR_004', 'completed');

-- User 2: warren jacaban (warren.jacaban@gmail.com)
INSERT INTO transactions (user_id, transaction_code, bottle_deposited, points_earned, transaction_date, qr_code_scanned, transaction_status) VALUES
(2, 'TXN_002_001', 7, 35, '2024-12-19 11:30:00', 'QR_005', 'completed'),
(2, 'TXN_002_002', 4, 20, '2024-12-17 13:45:00', 'QR_006', 'completed'),
(2, 'TXN_002_003', 9, 45, '2024-12-14 08:15:00', 'QR_007', 'completed'),
(2, 'TXN_002_004', 6, 30, '2024-12-11 15:30:00', 'QR_008', 'completed'),
(2, 'TXN_002_005', 11, 55, '2024-12-08 12:00:00', 'QR_009', 'completed');

-- User 3: Tepe tepe (tepe.tepe@gmail.com)
INSERT INTO transactions (user_id, transaction_code, bottle_deposited, points_earned, transaction_date, qr_code_scanned, transaction_status) VALUES
(3, 'TXN_003_001', 6, 30, '2024-12-21 09:15:00', 'QR_010', 'completed'),
(3, 'TXN_003_002', 10, 50, '2024-12-19 14:30:00', 'QR_011', 'completed'),
(3, 'TXN_003_003', 5, 25, '2024-12-16 11:45:00', 'QR_012', 'completed');

-- User 4: jammy rivas (jammy.rivas@gmail.com)
INSERT INTO transactions (user_id, transaction_code, bottle_deposited, points_earned, transaction_date, qr_code_scanned, transaction_status) VALUES
(4, 'TXN_004_001', 8, 40, '2024-12-20 16:45:00', 'QR_013', 'completed'),
(4, 'TXN_004_002', 3, 15, '2024-12-18 10:30:00', 'QR_014', 'completed'),
(4, 'TXN_004_003', 7, 35, '2024-12-15 13:15:00', 'QR_015', 'completed'),
(4, 'TXN_004_004', 9, 45, '2024-12-12 15:45:00', 'QR_016', 'completed');

-- User 5: loki chan (loki.chan@gmail.com)
INSERT INTO transactions (user_id, transaction_code, bottle_deposited, points_earned, transaction_date, qr_code_scanned, transaction_status) VALUES
(5, 'TXN_005_001', 4, 20, '2024-12-21 12:30:00', 'QR_017', 'completed'),
(5, 'TXN_005_002', 6, 30, '2024-12-19 08:45:00', 'QR_018', 'completed'),
(5, 'TXN_005_003', 8, 40, '2024-12-17 14:20:00', 'QR_019', 'completed'),
(5, 'TXN_005_004', 5, 25, '2024-12-14 11:00:00', 'QR_020', 'completed');

-- User 6: stefan chan (stefanchan32@gmail.com)
INSERT INTO transactions (user_id, transaction_code, bottle_deposited, points_earned, transaction_date, qr_code_scanned, transaction_status) VALUES
(6, 'TXN_006_001', 15, 75, '2024-12-20 10:00:00', 'QR_021', 'completed'),
(6, 'TXN_006_002', 12, 60, '2024-12-18 15:30:00', 'QR_022', 'completed'),
(6, 'TXN_006_003', 8, 40, '2024-12-16 09:15:00', 'QR_023', 'completed'),
(6, 'TXN_006_004', 20, 100, '2024-12-13 16:45:00', 'QR_024', 'completed'),
(6, 'TXN_006_005', 6, 30, '2024-12-10 12:30:00', 'QR_025', 'completed');

-- Update wallet balances based on new transactions
UPDATE wallet w
JOIN (
    SELECT 
        user_id,
        SUM(points_earned) as total_earned_points
    FROM transactions 
    GROUP BY user_id
) t ON w.user_id = t.user_id
SET w.current_balance = t.total_earned_points;

-- Update users total_points based on new transactions
UPDATE users u
JOIN (
    SELECT 
        user_id,
        SUM(points_earned) as total_earned_points
    FROM transactions 
    GROUP BY user_id
) t ON u.user_id = t.user_id
SET u.total_points = t.total_earned_points;

-- Add notifications for the transactions
INSERT INTO notification (user_id, title, message, notification_type, is_read, created_at) VALUES
(1, 'Bottles Deposited!', 'You deposited 5 bottles and earned 25 points!', 'points', 0, NOW()),
(2, 'Bottles Deposited!', 'You deposited 7 bottles and earned 35 points!', 'points', 0, NOW()),
(3, 'Bottles Deposited!', 'You deposited 6 bottles and earned 30 points!', 'points', 0, NOW()),
(4, 'Bottles Deposited!', 'You deposited 8 bottles and earned 40 points!', 'points', 0, NOW()),
(5, 'Bottles Deposited!', 'You deposited 4 bottles and earned 20 points!', 'points', 0, NOW()),
(6, 'Bottles Deposited!', 'You deposited 15 bottles and earned 75 points!', 'points', 0, NOW());

-- Verify the results
SELECT 
    u.user_id,
    u.email,
    u.first_name,
    u.last_name,
    u.total_points,
    w.current_balance,
    COUNT(t.transaction_id) as total_transactions,
    SUM(t.bottle_deposited) as total_bottles,
    SUM(t.points_earned) as total_points_earned
FROM users u
LEFT JOIN wallet w ON u.user_id = w.user_id
LEFT JOIN transactions t ON u.user_id = t.user_id
GROUP BY u.user_id, u.email, u.first_name, u.last_name, u.total_points, w.current_balance
ORDER BY u.total_points DESC;








