-- MySQL Initialization Script for SparksCannabis
-- This script will run when the MySQL container starts for the first time

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS sparks_cannabis CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user if it doesn't exist
CREATE USER IF NOT EXISTS 'sparks'@'%' IDENTIFIED BY 'password';

-- Grant privileges
GRANT ALL PRIVILEGES ON sparks_cannabis.* TO 'sparks'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON sparks_cannabis.* TO 'sparks'@'%';

-- Flush privileges
FLUSH PRIVILEGES;

-- Use the database
USE sparks_cannabis;

-- Create some basic indexes for performance (optional)
-- You can add your specific tables and indexes here

-- Example cannabis-specific tables (adjust according to your needs)
-- CREATE TABLE IF NOT EXISTS products (
--     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
--     name VARCHAR(255) NOT NULL,
--     category VARCHAR(100) NOT NULL,
--     strain_type ENUM('indica', 'sativa', 'hybrid') NULL,
--     thc_percentage DECIMAL(5,2) NULL,
--     cbd_percentage DECIMAL(5,2) NULL,
--     price DECIMAL(10,2) NOT NULL,
--     inventory_count INT DEFAULT 0,
--     is_active BOOLEAN DEFAULT TRUE,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
--     INDEX idx_category (category),
--     INDEX idx_strain_type (strain_type),
--     INDEX idx_active (is_active)
-- );

-- CREATE TABLE IF NOT EXISTS users (
--     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
--     name VARCHAR(255) NOT NULL,
--     email VARCHAR(255) UNIQUE NOT NULL,
--     email_verified_at TIMESTAMP NULL,
--     password VARCHAR(255) NOT NULL,
--     age_verified BOOLEAN DEFAULT FALSE,
--     license_verified BOOLEAN DEFAULT FALSE,
--     remember_token VARCHAR(100) NULL,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
--     INDEX idx_email (email),
--     INDEX idx_verified (age_verified, license_verified)
-- );

SELECT 'Database initialization completed!' as message;