-- Add points to all active users
-- Replace 50 with the number of points to add to everyone

SET @points_to_add = 50;

-- Add points to all users' wallets
UPDATE wallet 
SET current_balance = current_balance + @points_to_add
WHERE is_active = 1;

-- Add points to all users' total_points
UPDATE users 
SET total_points = total_points + @points_to_add
WHERE is_active = 1;

-- Create transaction records for all users
INSERT INTO transactions (
    user_id, 
    transaction_code, 
    bottle_deposited, 
    points_earned, 
    transaction_date, 
    qr_code_scanned, 
    transaction_status
)
SELECT 
    u.user_id,
    CONCAT('BONUS-', u.user_id, '-', UNIX_TIMESTAMP()),
    0, -- No bottles for bonus
    @points_to_add,
    NOW(),
    CONCAT('BONUS-', u.user_id, '-', UNIX_TIMESTAMP()),
    'completed'
FROM users u
WHERE u.is_active = 1;

-- Create notifications for all users
INSERT INTO notification (
    user_id,
    notification_type,
    title,
    message,
    sent_at,
    is_read,
    priority
)
SELECT 
    u.user_id,
    'points',
    '🎁 Bonus Points!',
    CONCAT('You received ', @points_to_add, ' bonus points! Thank you for being part of MaBote.ph!'),
    NOW(),
    0,
    'high'
FROM users u
WHERE u.is_active = 1;

-- Show updated results
SELECT 
    u.user_id,
    u.first_name,
    u.last_name,
    u.email,
    u.total_points,
    w.current_balance,
    @points_to_add AS bonus_points_added
FROM users u
LEFT JOIN wallet w ON u.user_id = w.user_id
WHERE u.is_active = 1
ORDER BY u.user_id;








