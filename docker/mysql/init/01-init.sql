-- Create the database if it doesn't exist
CREATE DATABASE IF NOT EXISTS `${MYSQL_DATABASE}`;

-- Create the application user with appropriate permissions
CREATE USER IF NOT EXISTS '${MYSQL_USER}'@'%' IDENTIFIED BY '${MYSQL_PASSWORD}';
GRANT ALL PRIVILEGES ON `${MYSQL_DATABASE}`.* TO '${MYSQL_USER}'@'%';
FLUSH PRIVILEGES;

-- Select the database
USE `${MYSQL_DATABASE}`;
