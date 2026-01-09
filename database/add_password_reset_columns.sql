-- Add password reset columns to users table
ALTER TABLE users ADD COLUMN reset_token VARCHAR(255) DEFAULT NULL AFTER bio;
ALTER TABLE users ADD COLUMN reset_token_expiry TIMESTAMP NULL DEFAULT NULL AFTER reset_token;

-- Add indexes for better performance
CREATE INDEX idx_users_reset_token ON users(reset_token);
CREATE INDEX idx_users_reset_token_expiry ON users(reset_token_expiry);
