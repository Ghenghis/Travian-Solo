#!/bin/bash
# Create databases for each game world

# Read environment variables
MYSQL_ROOT_PASSWORD="${MYSQL_ROOT_PASSWORD}"

# Create testworld database
mysql -u root -p"${MYSQL_ROOT_PASSWORD}" -e "CREATE DATABASE IF NOT EXISTS travian_testworld CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Create demo database
mysql -u root -p"${MYSQL_ROOT_PASSWORD}" -e "CREATE DATABASE IF NOT EXISTS travian_demo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Create users for game world databases
mysql -u root -p"${MYSQL_ROOT_PASSWORD}" -e "CREATE USER IF NOT EXISTS 'travian_user'@'%' IDENTIFIED BY 'travian_password123';"

# Grant permissions on game world databases
mysql -u root -p"${MYSQL_ROOT_PASSWORD}" -e "GRANT ALL PRIVILEGES ON travian_testworld.* TO 'travian_user'@'%';"
mysql -u root -p"${MYSQL_ROOT_PASSWORD}" -e "GRANT ALL PRIVILEGES ON travian_demo.* TO 'travian_user'@'%';"

# Apply changes
mysql -u root -p"${MYSQL_ROOT_PASSWORD}" -e "FLUSH PRIVILEGES;"

echo "Game world databases created successfully"
