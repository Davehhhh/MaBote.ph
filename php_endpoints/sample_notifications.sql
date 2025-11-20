-- Sample notifications for testing the notification system
-- Run this in phpMyAdmin to add test notifications

-- Insert sample notifications for user_id = 1 (replace with actual user ID)
INSERT INTO notification (user_id, notification_type, title, message, sent_at, is_read, priority) VALUES
(1, 'system', '🎉 Welcome to MaBote.ph!', 'Start earning points by recycling plastic bottles. Show your QR code to any MaBote machine!', NOW() - INTERVAL 1 DAY, 0, 'high'),
(1, 'points', '🎉 Points Earned!', 'You earned 25 points from 5 bottle(s)! Your new total is 35 points.', NOW() - INTERVAL 2 HOUR, 0, 'high'),
(1, 'reward', '🎁 Reward Claimed!', 'You claimed "Free Coffee" for 50 points! Redemption code: RWD123456', NOW() - INTERVAL 1 HOUR, 0, 'high'),
(1, 'system', '📱 App Update Available', 'New features added! Check out the improved notification system.', NOW() - INTERVAL 30 MINUTE, 0, 'medium'),
(1, 'points', '🎉 Bottle Deposit Successful!', 'You deposited 3 bottle(s) and earned 15 points! Your new total is 50 points.', NOW() - INTERVAL 15 MINUTE, 0, 'high'),
(1, 'reward', '🎁 Reward Claimed Successfully!', 'You claimed "Free Snack" for 30 points! Redemption code: RWD789012', NOW() - INTERVAL 5 MINUTE, 0, 'high');

-- Insert sample notifications for user_id = 2 (if exists)
INSERT INTO notification (user_id, notification_type, title, message, sent_at, is_read, priority) VALUES
(2, 'system', '🎉 Welcome to MaBote.ph!', 'Start earning points by recycling plastic bottles. Show your QR code to any MaBote machine!', NOW() - INTERVAL 2 DAY, 1, 'high'),
(2, 'points', '🎉 Points Earned!', 'You earned 20 points from 4 bottle(s)! Your new total is 20 points.', NOW() - INTERVAL 1 HOUR, 0, 'high'),
(2, 'system', '📱 App Update Available', 'New features added! Check out the improved notification system.', NOW() - INTERVAL 45 MINUTE, 0, 'medium');

-- Insert sample notifications for user_id = 3 (if exists)
INSERT INTO notification (user_id, notification_type, title, message, sent_at, is_read, priority) VALUES
(3, 'system', '🎉 Welcome to MaBote.ph!', 'Start earning points by recycling plastic bottles. Show your QR code to any MaBote machine!', NOW() - INTERVAL 3 DAY, 1, 'high'),
(3, 'points', '🎉 Points Earned!', 'You earned 30 points from 6 bottle(s)! Your new total is 30 points.', NOW() - INTERVAL 2 HOUR, 0, 'high'),
(3, 'reward', '🎁 Reward Claimed!', 'You claimed "Free Drink" for 25 points! Redemption code: RWD345678', NOW() - INTERVAL 1 HOUR, 0, 'high'),
(3, 'system', '📱 App Update Available', 'New features added! Check out the improved notification system.', NOW() - INTERVAL 20 MINUTE, 0, 'medium');
