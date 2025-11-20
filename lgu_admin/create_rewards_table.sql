-- Create rewards table for LGU Admin
CREATE TABLE IF NOT EXISTS rewards (
    reward_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    points_required INT NOT NULL,
    description TEXT,
    category VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert sample rewards
INSERT INTO rewards (name, points_required, description, category) VALUES
('Free Coffee', 50, 'Get a free coffee at participating stores', 'Food'),
('Mobile Load', 100, 'P100 mobile load credit', 'Electronics'),
('Grocery Voucher', 200, 'P200 grocery voucher', 'Vouchers'),
('Laundry Service', 150, 'Free laundry service', 'Services'),
('Movie Ticket', 300, 'Free movie ticket', 'Vouchers'),
('Restaurant Meal', 250, 'Free meal at partner restaurant', 'Food');


