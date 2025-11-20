-- Reset All Points and Data (Clean Slate)
-- This script resets all user points, transactions, and wallet data
-- WITHOUT adding notifications so we can test the notification system properly

-- Step 1: Reset all user points to 0
UPDATE users SET total_points = 0;

-- Step 2: Reset all wallet balances to 0
UPDATE wallet SET current_balance = 0;

-- Step 3: Delete all existing transactions
DELETE FROM transactions;

-- Step 4: Delete all existing redemptions
DELETE FROM redemption;

-- Step 5: Delete all existing notifications
DELETE FROM notification;

-- Step 6: Reset auto-increment counters
ALTER TABLE transactions AUTO_INCREMENT = 1;
ALTER TABLE redemption AUTO_INCREMENT = 1;
ALTER TABLE notification AUTO_INCREMENT = 1;

-- Step 7: Verify the reset
SELECT 
    u.user_id,
    u.email,
    u.first_name,
    u.last_name,
    u.total_points,
    w.current_balance,
    COUNT(t.transaction_id) as total_transactions,
    COUNT(r.redemption_id) as total_redemptions,
    COUNT(n.notification_id) as total_notifications
FROM users u
LEFT JOIN wallet w ON u.user_id = w.user_id
LEFT JOIN transactions t ON u.user_id = t.user_id
LEFT JOIN redemption r ON u.user_id = r.user_id
LEFT JOIN notification n ON u.user_id = n.user_id
GROUP BY u.user_id, u.email, u.first_name, u.last_name, u.total_points, w.current_balance
ORDER BY u.user_id;

-- All data should now show 0 points, 0 transactions, 0 redemptions, 0 notifications








