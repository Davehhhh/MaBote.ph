-- Fix total_points calculation for all users
-- This will recalculate total_points based on actual transactions

-- First, let's see the current state
SELECT 
    u.user_id,
    u.first_name,
    u.last_name,
    u.email,
    u.total_points as 'Current Total Points',
    w.current_balance as 'Current Balance',
    COALESCE(SUM(t.points_earned), 0) as 'Actual Total Earned from Transactions',
    COALESCE(SUM(r.points_used), 0) as 'Total Redeemed'
FROM users u
LEFT JOIN wallet w ON u.user_id = w.user_id
LEFT JOIN transactions t ON u.user_id = t.user_id
LEFT JOIN redemption r ON u.user_id = r.user_id
GROUP BY u.user_id, u.first_name, u.last_name, u.email, u.total_points, w.current_balance
ORDER BY u.user_id;

-- Fix total_points for all users based on actual transactions
UPDATE users u
SET total_points = (
    SELECT COALESCE(SUM(t.points_earned), 0)
    FROM transactions t
    WHERE t.user_id = u.user_id
);

-- Verify the fix
SELECT 
    u.user_id,
    u.first_name,
    u.last_name,
    u.email,
    u.total_points as 'Fixed Total Points',
    w.current_balance as 'Current Balance',
    COALESCE(SUM(r.points_used), 0) as 'Total Redeemed'
FROM users u
LEFT JOIN wallet w ON u.user_id = w.user_id
LEFT JOIN redemption r ON u.user_id = r.user_id
GROUP BY u.user_id, u.first_name, u.last_name, u.email, u.total_points, w.current_balance
ORDER BY u.user_id;








