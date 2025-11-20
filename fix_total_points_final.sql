-- CRITICAL FIX: Reset and recalculate total_points correctly
-- This ensures total_points NEVER decreases (it's cumulative earned points)

-- Step 1: Reset all total_points to 0
UPDATE users SET total_points = 0;

-- Step 2: Recalculate total_points from actual transactions (cumulative earned)
UPDATE users u
SET total_points = (
    SELECT COALESCE(SUM(t.points_earned), 0)
    FROM transactions t
    WHERE t.user_id = u.user_id
);

-- Step 3: Verify the fix
SELECT 
    u.user_id,
    u.first_name,
    u.last_name,
    u.email,
    u.total_points as 'Total Points Earned (CUMULATIVE)',
    w.current_balance as 'Current Balance (Available)',
    COALESCE(SUM(r.points_used), 0) as 'Total Redeemed (Spent)',
    COUNT(DISTINCT t.transaction_id) as 'Total Deposits',
    COUNT(DISTINCT r.redemption_id) as 'Total Redemptions'
FROM users u
LEFT JOIN wallet w ON u.user_id = w.user_id
LEFT JOIN transactions t ON u.user_id = t.user_id
LEFT JOIN redemption r ON u.user_id = r.user_id
GROUP BY u.user_id, u.first_name, u.last_name, u.email, u.total_points, w.current_balance
ORDER BY u.user_id;

-- IMPORTANT NOTES:
-- total_points = CUMULATIVE points earned (NEVER decreases)
-- current_balance = Available points to spend (decreases when redeemed)
-- total_redeemed = Total points spent on rewards








