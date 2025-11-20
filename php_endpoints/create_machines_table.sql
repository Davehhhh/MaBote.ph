-- Machine Management Table
-- File: php_endpoints/create_machines_table.sql

CREATE TABLE IF NOT EXISTS machines (
    machine_id VARCHAR(50) PRIMARY KEY,
    location VARCHAR(255) NOT NULL,
    status ENUM('active', 'maintenance', 'offline') DEFAULT 'active',
    fill_level INT DEFAULT 0,
    battery_level INT DEFAULT 100,
    temperature DECIMAL(5,2) DEFAULT 25.00,
    last_maintenance DATE NULL,
    last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample machines
INSERT INTO machines (machine_id, location, status) VALUES
('BIN001', 'Mall Entrance', 'active'),
('BIN002', 'University Campus', 'active'),
('BIN003', 'Shopping Center', 'maintenance'),
('BIN004', 'Public Park', 'active'),
('BIN005', 'Office Building', 'offline');

-- Add machine_id column to transactions table if it doesn't exist
ALTER TABLE transactions 
ADD COLUMN IF NOT EXISTS machine_id VARCHAR(50) DEFAULT 'BIN001',
ADD INDEX idx_machine_id (machine_id);

-- Add foreign key constraint
ALTER TABLE transactions 
ADD CONSTRAINT fk_transactions_machine 
FOREIGN KEY (machine_id) REFERENCES machines(machine_id) 
ON DELETE SET NULL ON UPDATE CASCADE;







