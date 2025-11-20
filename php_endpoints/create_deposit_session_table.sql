-- Run this in phpMyAdmin to create the deposit_session table
CREATE TABLE IF NOT EXISTS deposit_session (
  session_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  bin_id INT NOT NULL,
  session_token VARCHAR(32) NOT NULL UNIQUE,
  status ENUM('open', 'closed') NOT NULL DEFAULT 'open',
  expires_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id),
  INDEX idx_session_token (session_token),
  INDEX idx_user_status (user_id, status),
  INDEX idx_expires (expires_at)
);
