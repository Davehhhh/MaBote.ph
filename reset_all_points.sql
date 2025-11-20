-- Reset all user points to 0
-- This will reset both wallet balance and total points

-- Reset wallet current_balance to 0 for all users
UPDATE wallet 
SET current_balance = 0 
WHERE is_active = 1;

-- Reset users total_points to 0 for all users
UPDATE users 
SET total_points = 0 
WHERE is_active = 1;

-- Optional: Clear all transactions (uncomment if you want to start fresh)
-- DELETE FROM transactions;

-- Optional: Clear all redemptions (uncomment if you want to start fresh)
-- DELETE FROM redemption;

-- Optional: Clear all notifications (uncomment if you want to start fresh)
-- DELETE FROM notification;

-- Show updated results
SELECT 
    u.user_id,
    u.first_name,
    u.last_name,
    u.email,
    u.total_points,
    w.current_balance
FROM users u
LEFT JOIN wallet w ON u.user_id = w.user_id
WHERE u.is_active = 1
ORDER BY u.user_id;

