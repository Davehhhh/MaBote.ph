-- Sample rewards for testing the rewards system
-- Run this in phpMyAdmin to add test rewards

-- Insert sample rewards
INSERT INTO reward (reward_name, reward_description, points_required, quantity_available, is_active, created_at) VALUES
('Free Coffee', 'Get a free coffee from any partner cafe', 50, 100, 1, NOW()),
('Free Snack', 'Get a free snack from partner stores', 30, 50, 1, NOW()),
('Free Drink', 'Get a free drink from partner stores', 25, 75, 1, NOW()),
('Discount Voucher', 'Get 20% off on your next purchase', 100, 25, 1, NOW()),
('Free Meal', 'Get a free meal from partner restaurants', 200, 10, 1, NOW()),
('Gift Card', 'Get a P100 gift card from partner stores', 150, 20, 1, NOW()),
('Free Transportation', 'Get free jeepney ride for 1 day', 75, 30, 1, NOW()),
('Free Movie Ticket', 'Get a free movie ticket', 120, 15, 1, NOW()),
('Free Haircut', 'Get a free haircut from partner salons', 80, 12, 1, NOW()),
('Free Laundry', 'Get free laundry service for 1 week', 90, 8, 1, NOW());

-- Update existing rewards if they exist
UPDATE reward SET 
    reward_description = 'Get a free coffee from any partner cafe',
    points_required = 50,
    quantity_available = 100,
    is_active = 1
WHERE reward_name = 'Free Coffee';

UPDATE reward SET 
    reward_description = 'Get a free snack from partner stores',
    points_required = 30,
    quantity_available = 50,
    is_active = 1
WHERE reward_name = 'Free Snack';

UPDATE reward SET 
    reward_description = 'Get a free drink from partner stores',
    points_required = 25,
    quantity_available = 75,
    is_active = 1
WHERE reward_name = 'Free Drink';
