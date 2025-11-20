-- QUICK ONE-LINER COMMANDS
-- Run these individually in phpMyAdmin SQL tab:

-- 1. Add 1000 points to Stefan's wallet
UPDATE wallet w 
JOIN users u ON w.user_id = u.user_id 
SET w.current_balance = w.current_balance + 1000 
WHERE u.email = 'stefanchan32@gmail.com';

-- 2. Add 1000 points to Stefan's total_points
UPDATE users 
SET total_points = total_points + 1000 
WHERE email = 'stefanchan32@gmail.com';

-- 3. Check the result
SELECT 
    u.user_id,
    u.first_name,
    u.last_name,
    u.email,
    u.total_points,
    w.current_balance
FROM users u
LEFT JOIN wallet w ON u.user_id = w.user_id
WHERE u.email = 'stefanchan32@gmail.com';








