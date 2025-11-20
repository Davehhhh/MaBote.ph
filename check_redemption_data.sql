-- Check if redemption was recorded in database
SELECT 
    r.redemption_id,
    r.user_id,
    r.reward_id,
    r.points_used,
    r.redemption_date,
    r.redemption_status,
    r.redemption_code,
    u.email,
    rew.reward_name
FROM redemption r
LEFT JOIN users u ON r.user_id = u.user_id
LEFT JOIN reward rew ON r.reward_id = rew.reward_id
ORDER BY r.redemption_date DESC
LIMIT 5;









