-- Demo data for testing rewards, transactions, and notifications
-- Run this in phpMyAdmin after creating your tables

-- Insert demo rewards
INSERT INTO reward (reward_name, description, points_required, quantity_available, category, reward_image, is_active) VALUES
('Free Coffee', 'Get a free coffee at any partner cafe', 50, 100, 'Food & Beverage', 'coffee.jpg', 1),
('Eco Bag', 'Reusable eco-friendly shopping bag', 30, 200, 'Accessories', 'ecobag.jpg', 1),
('Plant Seed Pack', 'Assorted vegetable seeds for home gardening', 25, 150, 'Gardening', 'seeds.jpg', 1),
('Water Bottle', 'Insulated stainless steel water bottle', 75, 80, 'Accessories', 'bottle.jpg', 1),
('Gift Card ₱100', '₱100 gift card for partner stores', 100, 50, 'Gift Cards', 'giftcard.jpg', 1),
('Tree Planting Certificate', 'Certificate for tree planted in your name', 200, 30, 'Environment', 'certificate.jpg', 1);

-- Insert demo smart_bin records first (minimal columns)
INSERT INTO smart_bin (bin_id, bin_name) VALUES
(1, 'Main Campus Bin'),
(2, 'Library Bin'),
(3, 'Cafeteria Bin');

-- Insert demo transactions (replace user_id with actual user)
INSERT INTO transactions (user_id, bin_id, transaction_code, bottle_deposited, points_earned, transaction_date, transaction_status) VALUES
(1, 1, 'TRX-A1B2C3', 3, 15, '2024-01-15 10:30:00', 'completed'),
(1, 2, 'TRX-D4E5F6', 5, 25, '2024-01-14 14:20:00', 'completed'),
(1, 1, 'TRX-G7H8I9', 2, 10, '2024-01-13 09:15:00', 'completed'),
(1, 3, 'TRX-J0K1L2', 4, 20, '2024-01-12 16:45:00', 'completed'),
(1, 2, 'TRX-M3N4O5', 6, 30, '2024-01-11 11:30:00', 'completed');

-- Insert demo notifications (replace user_id with actual user)
INSERT INTO notification (user_id, notification_type, title, message, sent_at, is_read, priority) VALUES
(1, 'points', 'Points Earned!', 'You earned 15 points from your recent bottle deposit', '2024-01-15 10:35:00', 0, 'medium'),
(1, 'reward', 'New Reward Available', 'Check out the new Eco Bag reward - only 30 points!', '2024-01-14 08:00:00', 0, 'low'),
(1, 'system', 'Welcome to MaBote!', 'Thank you for joining our recycling community. Start earning points today!', '2024-01-10 12:00:00', 1, 'high'),
(1, 'transaction', 'Transaction Complete', 'Your deposit of 5 bottles has been processed successfully', '2024-01-14 14:25:00', 1, 'medium'),
(1, 'reward', 'Reward Reminder', 'You have enough points to redeem a Free Coffee!', '2024-01-13 15:00:00', 0, 'low');

-- Update wallet with demo data (replace user_id with actual user)
UPDATE wallet SET 
  current_balance = 100,
  total_earned = 100,
  last_transaction_date = '2024-01-15 10:30:00'
WHERE user_id = 1;

-- Update users total_points (replace user_id with actual user)
UPDATE users SET total_points = 100 WHERE user_id = 1;
