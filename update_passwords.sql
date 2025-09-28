-- Update the sample passwords to be properly hashed
-- Run this after creating the initial database to fix the sample passwords

USE farmer_auction_system;

-- Update farmer passwords (password is 'password123' for all)
-- This is a proper bcrypt hash for 'password123'
UPDATE Farmers SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE email = 'john@example.com';
UPDATE Farmers SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE email = 'jane@example.com';

-- Update buyer passwords (password is 'password123' for all)
UPDATE Buyers SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE email = 'alice@example.com';
UPDATE Buyers SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE email = 'bob@example.com';