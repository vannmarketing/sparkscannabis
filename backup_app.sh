#!/bin/bash

# Backup script for Sparks application
# Created: May 1, 2025
# This script creates a full backup of the application files and database

# Set variables
TIMESTAMP=$(date +"%Y-%m-%d")
BACKUP_DIR="/Users/vann/Downloads"
APP_DIR="/Users/vann/Local Sites/sparks"
BACKUP_FILENAME="sparks_backup_${TIMESTAMP}.zip"
TEMP_DIR="/tmp/sparks_backup_temp"
DB_BACKUP_FILE="${TEMP_DIR}/database_backup.sql"
INSTRUCTIONS_FILE="${TEMP_DIR}/restoration_instructions.md"

# Create temporary directory
echo "Creating temporary directory..."
mkdir -p "${TEMP_DIR}"

# Create restoration instructions
echo "Creating restoration instructions..."
cat > "${INSTRUCTIONS_FILE}" << 'EOF'
# Sparks Application Restoration Instructions

This backup contains a complete copy of the Sparks application, including all files and the database.

## Restoring the Files

1. Unzip the backup file:
   ```
   unzip sparks_backup_YYYY-MM-DD.zip
   ```

2. Copy the application files to your web server's document root or the appropriate directory:
   ```
   cp -r app_files/* /path/to/your/webserver/directory/
   ```

## Restoring the Database

1. Create a new database for the application:
   ```
   mysql -u root -p
   CREATE DATABASE your_database_name;
   GRANT ALL PRIVILEGES ON your_database_name.* TO 'your_database_user'@'localhost';
   FLUSH PRIVILEGES;
   EXIT;
   ```

2. Import the database backup:
   ```
   mysql -u your_database_user -p your_database_name < database_backup.sql
   ```

## Updating Configuration

1. Update the database connection settings in the `.env` file:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=your_database_name
   DB_USERNAME=your_database_user
   DB_PASSWORD=your_database_password
   ```

2. Update any other environment-specific settings in the `.env` file as needed.

3. Clear the application cache:
   ```
   php artisan config:clear
   php artisan cache:clear
   php artisan view:clear
   ```

4. Set appropriate permissions:
   ```
   chmod -R 755 /path/to/your/application
   chmod -R 777 /path/to/your/application/storage
   chmod -R 777 /path/to/your/application/bootstrap/cache
   ```

5. If you're using Apache, make sure the .htaccess file is properly configured and mod_rewrite is enabled.

## Troubleshooting

If you encounter any issues during restoration:

1. Check the web server error logs
2. Ensure all required PHP extensions are installed
3. Verify database connection settings
4. Make sure file permissions are set correctly

For Laravel-specific issues, refer to the Laravel documentation at https://laravel.com/docs
EOF

# Extract database connection details from .env file
echo "Reading database configuration..."
if [ -f "${APP_DIR}/.env" ]; then
    # Get database details from .env file
    DB_CONNECTION=$(grep DB_CONNECTION "${APP_DIR}/.env" | cut -d '=' -f2)
    DB_HOST=$(grep DB_HOST "${APP_DIR}/.env" | cut -d '=' -f2 | tr -d '\r')
    DB_PORT=$(grep DB_PORT "${APP_DIR}/.env" | cut -d '=' -f2 | tr -d '\r')
    DB_DATABASE=$(grep DB_DATABASE "${APP_DIR}/.env" | cut -d '=' -f2 | tr -d '\r')
    DB_USERNAME=$(grep DB_USERNAME "${APP_DIR}/.env" | cut -d '=' -f2 | tr -d '\r')
    DB_PASSWORD=$(grep DB_PASSWORD "${APP_DIR}/.env" | cut -d '=' -f2 | tr -d '\r')
    
    # Clean up DB_HOST if it contains comments or multiple values
    if [[ "$DB_HOST" == *"#"* ]]; then
        DB_HOST=$(echo "$DB_HOST" | cut -d '#' -f1 | tr -d ' ')
    fi
    
    # If DB_HOST contains multiple lines or values, take the first one
    DB_HOST=$(echo "$DB_HOST" | head -n1 | awk '{print $1}')
    
    echo "Database connection: $DB_CONNECTION"
    echo "Database host: $DB_HOST"
    echo "Database port: $DB_PORT"
    echo "Database name: $DB_DATABASE"
    echo "Database user: $DB_USERNAME"
else
    echo "Error: .env file not found!"
    exit 1
fi

# Copy .env file to backup for reference
cp "${APP_DIR}/.env" "${TEMP_DIR}/.env.backup"

# Create a file-only backup without the database if mysqldump fails
create_files_only_backup() {
    echo "WARNING: Database backup failed. Creating files-only backup..."
    echo "NOTE: You will need to manually export your database for a complete backup."
    
    # Create note about missing database
    echo "# WARNING: Database backup is not included in this archive" > "${TEMP_DIR}/DATABASE_BACKUP_MISSING.txt"
    echo "The database backup failed during the backup process." >> "${TEMP_DIR}/DATABASE_BACKUP_MISSING.txt"
    echo "You will need to manually export your database using phpMyAdmin or MySQL command line tools." >> "${TEMP_DIR}/DATABASE_BACKUP_MISSING.txt"
    
    # Continue with the rest of the backup process
    echo "Continuing with file backup only..."
}

# Backup the database
echo "Backing up database ${DB_DATABASE}..."
mysqldump -h "${DB_HOST}" -P "${DB_PORT}" -u "${DB_USERNAME}" -p"${DB_PASSWORD}" "${DB_DATABASE}" > "${DB_BACKUP_FILE}" 2>/dev/null

if [ $? -ne 0 ]; then
    echo "First database backup attempt failed. Trying alternative connection method..."
    
    # Try with localhost instead of 127.0.0.1 or vice versa
    ALT_DB_HOST="localhost"
    if [ "$DB_HOST" = "localhost" ]; then
        ALT_DB_HOST="127.0.0.1"
    fi
    
    echo "Trying with host: $ALT_DB_HOST"
    mysqldump -h "${ALT_DB_HOST}" -P "${DB_PORT}" -u "${DB_USERNAME}" -p"${DB_PASSWORD}" "${DB_DATABASE}" > "${DB_BACKUP_FILE}" 2>/dev/null
    
    if [ $? -ne 0 ]; then
        echo "Error: Database backup failed with both connection methods."
        create_files_only_backup
    else
        echo "Database backup successful using alternative host."
    fi
else
    echo "Database backup successful."
fi

# Copy application files to temp directory
echo "Copying application files..."
mkdir -p "${TEMP_DIR}/app_files"
cp -R "${APP_DIR}/"* "${APP_DIR}/".* "${TEMP_DIR}/app_files/" 2>/dev/null || true

# Remove unnecessary files from the backup
echo "Removing unnecessary files from backup..."
rm -rf "${TEMP_DIR}/app_files/storage/logs/*.log"
rm -rf "${TEMP_DIR}/app_files/storage/framework/cache/data/*"
rm -rf "${TEMP_DIR}/app_files/storage/framework/sessions/*"
rm -rf "${TEMP_DIR}/app_files/storage/framework/views/*.php"
rm -rf "${TEMP_DIR}/app_files/.git"

# Create empty directories that might have been removed
mkdir -p "${TEMP_DIR}/app_files/storage/logs"
mkdir -p "${TEMP_DIR}/app_files/storage/framework/cache/data"
mkdir -p "${TEMP_DIR}/app_files/storage/framework/sessions"
mkdir -p "${TEMP_DIR}/app_files/storage/framework/views"

# Create zip archive
echo "Creating backup archive..."
cd "${TEMP_DIR}" && zip -r "${BACKUP_DIR}/${BACKUP_FILENAME}" .

if [ $? -ne 0 ]; then
    echo "Error: Failed to create backup archive!"
    rm -rf "${TEMP_DIR}"
    exit 1
fi

# Clean up
echo "Cleaning up temporary files..."
rm -rf "${TEMP_DIR}"

echo "Backup completed successfully!"
echo "Backup saved to: ${BACKUP_DIR}/${BACKUP_FILENAME}"
