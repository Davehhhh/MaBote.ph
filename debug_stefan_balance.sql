-- Check Stefan's current data to debug the balance issue
SELECT 
    u.user_id,
    u.first_name,
    u.last_name,
    u.email,
    u.total_points as 'Total Earned (from users table)',
    w.current_balance as 'Available Balance (from wallet table)',
    COALESCE(SUM(r.points_used), 0) as 'Total Redeemed',
    COUNT(DISTINCT t.transaction_id) as 'Total Deposits',
    COUNT(DISTINCT r.redemption_id) as 'Total Redemptions'
FROM users u
LEFT JOIN wallet w ON u.user_id = w.user_id
LEFT JOIN transactions t ON u.user_id = t.user_id
LEFT JOIN redemption r ON u.user_id = r.user_id
WHERE u.email = 'stefanchan32@gmail.com'
GROUP BY u.user_id, u.first_name, u.last_name, u.email, u.total_points, w.current_balance;








