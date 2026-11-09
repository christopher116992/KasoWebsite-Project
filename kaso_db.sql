-- Create the database called kaso_db (only if it does not exist yet)
CREATE DATABASE IF NOT EXISTS kaso_db;
-- Tell MySQL we want to work inside kaso_db
USE kaso_db;

-- Create the table that stores Drone Academy accounts
CREATE TABLE IF NOT EXISTS drone_users (
  id INT AUTO_INCREMENT PRIMARY KEY,           -- Unique number for each account (adds itself: 1, 2, 3...)
  username VARCHAR(50) NOT NULL UNIQUE,        -- Username (max 50 letters, cannot be empty, no two the same)
  password VARCHAR(255) NOT NULL,              -- The password, saved scrambled (hashed) not as plain text
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP -- Date and time the account was created (adds itself)
);
