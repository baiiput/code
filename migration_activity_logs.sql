-- Add Manager role and Activity Logs system

-- 1. Create activity_logs table
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `log_id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `module` VARCHAR(50) NOT NULL,
  `description` TEXT,
  `ip_address` VARCHAR(45),
  `user_agent` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user_id (`user_id`),
  INDEX idx_module (`module`),
  INDEX idx_created_at (`created_at`),
  INDEX idx_action (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Update users table to support manager role (if needed, check existing roles)
-- Note: No ALTER needed if using ENUM, just add 'manager' to application code

-- Sample log entries (optional)
-- INSERT INTO activity_logs (user_id, action, module, description, ip_address) VALUES
-- (1, 'LOGIN', 'auth', 'User logged in successfully', '127.0.0.1'),
-- (1, 'CREATE', 'stock_in', 'Created stock in transaction: SI-20240101-1234', '127.0.0.1');
