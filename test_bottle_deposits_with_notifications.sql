-- Test Bottle Deposits WITH Notifications
-- This script adds bottle transactions and tests the notification system

-- Add test transactions for each user (with notifications)
-- User 1: FF FF f fttt
INSERT INTO transactions (user_id, transaction_code, bottle_deposited, points_earned, transaction_date, qr_code_scanned, transaction_status) VALUES
(1, 'TXN_001_001', 5, 25, NOW(), 'QR_001', 'completed');

-- Add notification for this transaction
INSERT INTO notification (user_id, title, message, notification_type, is_read, created_at) VALUES
(1, '🎉 Bottles Deposited!', 'You deposited 5 bottles and earned 25 points! Keep up the great work!', 'points', 0, NOW());

-- User 2: warren jacaban
INSERT INTO transactions (user_id, transaction_code, bottle_deposited, points_earned, transaction_date, qr_code_scanned, transaction_status) VALUES
(2, 'TXN_002_001', 8, 40, NOW(), 'QR_002', 'completed');

INSERT INTO notification (user_id, title, message, notification_type, is_read, created_at) VALUES
(2, '🎉 Bottles Deposited!', 'You deposited 8 bottles and earned 40 points! Great job!', 'points', 0, NOW());

-- User 3: Tepe tepe
INSERT INTO transactions (user_id, transaction_code, bottle_deposited, points_earned, transaction_date, qr_code_scanned, transaction_status) VALUES
(3, 'TXN_003_001', 3, 15, NOW(), 'QR_003', 'completed');

INSERT INTO notification (user_id, title, message, notification_type, is_read, created_at) VALUES
(3, '🎉 Bottles Deposited!', 'You deposited 3 bottles and earned 15 points! Every bottle counts!', 'points', 0, NOW());

-- User 4: jammy rivas
INSERT INTO transactions (user_id, transaction_code, bottle_deposited, points_earned, transaction_date, qr_code_scanned, transaction_status) VALUES
(4, 'TXN_004_001', 12, 60, NOW(), 'QR_004', 'completed');

INSERT INTO notification (user_id, title, message, notification_type, is_read, created_at) VALUES
(4, '🎉 Bottles Deposited!', 'You deposited 12 bottles and earned 60 points! Amazing work!', 'points', 0, NOW());

-- User 5: loki chan
INSERT INTO transactions (user_id, transaction_code, bottle_deposited, points_earned, transaction_date, qr_code_scanned, transaction_status) VALUES
(5, 'TXN_005_001', 7, 35, NOW(), 'QR_005', 'completed');

INSERT INTO notification (user_id, title, message, notification_type, is_read, created_at) VALUES
(5, '🎉 Bottles Deposited!', 'You deposited 7 bottles and earned 35 points! Keep it up!', 'points', 0, NOW());

-- User 6: stefan chan (top recycler)
INSERT INTO transactions (user_id, transaction_code, bottle_deposited, points_earned, transaction_date, qr_code_scanned, transaction_status) VALUES
(6, 'TXN_006_001', 15, 75, NOW(), 'QR_006', 'completed');

INSERT INTO notification (user_id, title, message, notification_type, is_read, created_at) VALUES
(6, '🎉 Bottles Deposited!', 'You deposited 15 bottles and earned 75 points! You are a recycling champion!', 'points', 0, NOW());

-- Update wallet balances
UPDATE wallet w
JOIN (
    SELECT 
        user_id,
        SUM(points_earned) as total_earned_points
    FROM transactions 
    GROUP BY user_id
) t ON w.user_id = t.user_id
SET w.current_balance = t.total_earned_points;

-- Update users total_points
UPDATE users u
JOIN (
    SELECT 
        user_id,
        SUM(points_earned) as total_earned_points
    FROM transactions 
    GROUP BY user_id
) t ON u.user_id = t.user_id
SET u.total_points = t.total_earned_points;

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
    COUNT(n.notification_id) as unread_notifications
FROM users u
LEFT JOIN wallet w ON u.user_id = w.user_id
LEFT JOIN transactions t ON u.user_id = t.user_id
LEFT JOIN notification n ON u.user_id = n.user_id AND n.is_read = 0
GROUP BY u.user_id, u.email, u.first_name, u.last_name, u.total_points, w.current_balance
ORDER BY u.total_points DESC;








