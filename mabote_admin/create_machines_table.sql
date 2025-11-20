CREATE TABLE IF NOT EXISTS machines (
    machine_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    location VARCHAR(255) NOT NULL,
    status ENUM('active', 'maintenance', 'offline') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_maintenance DATE,
    total_bottles INT DEFAULT 0,
    total_transactions INT DEFAULT 0
);

INSERT INTO machines (name, location, status) VALUES 
('Machine 1', 'Downtown Plaza', 'active'),
('Machine 2', 'Shopping Mall', 'active'),
('Machine 3', 'University Campus', 'maintenance'),
('Machine 4', 'City Park', 'active'),
('Machine 5', 'Community Center', 'offline');


