-- QUICK POINT MANAGEMENT COMMANDS
-- Copy and paste these individual commands as needed

-- ===========================================
-- RESET ALL POINTS (DANGEROUS - USE WITH CAUTION)
-- ===========================================
UPDATE wallet SET current_balance = 0 WHERE is_active = 1;
UPDATE users SET total_points = 0 WHERE is_active = 1;

-- ===========================================
-- ADD POINTS TO SPECIFIC USER BY EMAIL
-- ===========================================
-- Replace 'stefanchan32@gmail.com' and 100 with your values
UPDATE wallet w 
JOIN users u ON w.user_id = u.user_id 
SET w.current_balance = w.current_balance + 100 
WHERE u.email = 'stefanchan32@gmail.com';

UPDATE users 
SET total_points = total_points + 100 
WHERE email = 'stefanchan32@gmail.com';

-- ===========================================
-- ADD POINTS TO SPECIFIC USER BY ID
-- ===========================================
-- Replace 1 and 100 with your values
UPDATE wallet SET current_balance = current_balance + 100 WHERE user_id = 1;
UPDATE users SET total_points = total_points + 100 WHERE user_id = 1;

-- ===========================================
-- ADD POINTS TO ALL USERS
-- ===========================================
-- Replace 50 with the number of points to add
UPDATE wallet SET current_balance = current_balance + 50 WHERE is_active = 1;
UPDATE users SET total_points = total_points + 50 WHERE is_active = 1;

-- ===========================================
-- CHECK USER POINTS
-- ===========================================
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
ORDER BY u.total_points DESC;

-- ===========================================
-- CHECK SPECIFIC USER POINTS
-- ===========================================
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

-- ===========================================
-- SET SPECIFIC USER POINTS TO EXACT AMOUNT
-- ===========================================
-- Replace 'stefanchan32@gmail.com' and 500 with your values
UPDATE wallet w 
JOIN users u ON w.user_id = u.user_id 
SET w.current_balance = 500 
WHERE u.email = 'stefanchan32@gmail.com';

UPDATE users 
SET total_points = 500 
WHERE email = 'stefanchan32@gmail.com';








