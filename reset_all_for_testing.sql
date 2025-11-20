-- RESET ALL USER POINTS FOR TESTING
-- This will reset everyone's points to 0

-- Reset wallet current_balance to 0 for all users
UPDATE wallet 
SET current_balance = 0 
WHERE is_active = 1;

-- Reset users total_points to 0 for all users
UPDATE users 
SET total_points = 0 
WHERE is_active = 1;

-- Clear all transactions
DELETE FROM transactions;

-- Clear all redemptions
DELETE FROM redemption;

-- Clear all notifications
DELETE FROM notification;

-- Reset all rewards quantities (optional)
UPDATE reward 
SET quantity_available = 100 
WHERE is_active = 1;

-- Show results
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








