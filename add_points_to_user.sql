-- Add points to a specific user
-- Replace 'stefanchan32@gmail.com' with the target user's email
-- Replace 100 with the number of points to add

SET @target_email = 'stefanchan32@gmail.com';
SET @points_to_add = 100;

-- Get user ID
SET @user_id = (SELECT user_id FROM users WHERE email = @target_email LIMIT 1);

-- Check if user exists
SELECT 
    CASE 
        WHEN @user_id IS NULL THEN 'User not found!'
        ELSE CONCAT('User found: ', @user_id)
    END AS status;

-- Add points to wallet (current balance)
UPDATE wallet 
SET current_balance = current_balance + @points_to_add
WHERE user_id = @user_id;

-- Add points to total_points (cumulative earned)
UPDATE users 
SET total_points = total_points + @points_to_add
WHERE user_id = @user_id;

-- Create a transaction record
INSERT INTO transactions (
    user_id, 
    transaction_code, 
    bottle_deposited, 
    points_earned, 
    transaction_date, 
    qr_code_scanned, 
    transaction_status
) VALUES (
    @user_id,
    CONCAT('MANUAL-', @user_id, '-', UNIX_TIMESTAMP()),
    0, -- No bottles for manual addition
    @points_to_add,
    NOW(),
    CONCAT('MANUAL-', @user_id, '-', UNIX_TIMESTAMP()),
    'completed'
);

-- Create notification
INSERT INTO notification (
    user_id,
    notification_type,
    title,
    message,
    sent_at,
    is_read,
    priority
) VALUES (
    @user_id,
    'points',
    '🎉 Points Added!',
    CONCAT('You received ', @points_to_add, ' points manually. Thank you for using MaBote.ph!'),
    NOW(),
    0,
    'high'
);

-- Show updated results
SELECT 
    u.user_id,
    u.first_name,
    u.last_name,
    u.email,
    u.total_points,
    w.current_balance,
    @points_to_add AS points_added
FROM users u
LEFT JOIN wallet w ON u.user_id = w.user_id
WHERE u.user_id = @user_id;








