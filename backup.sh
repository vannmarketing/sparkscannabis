#!/bin/bash

# Set variables
BACKUP_DATE=$(date +"%Y-%m-%d-%H%M%S")
APP_NAME="sparks"
BACKUP_FILENAME="${APP_NAME}_backup_${BACKUP_DATE}.zip"
DOWNLOADS_DIR="/Users/vann/Downloads"
APP_DIR="/Users/vann/Local Sites/sparks"
DB_NAME="sparks"
DB_USER="root"
DB_PASS="lenguyen"
DB_HOST="127.0.0.1"
DB_PORT="3306"
SQL_FILENAME="${APP_NAME}_${BACKUP_DATE}.sql"
RESTORE_INSTRUCTIONS_FILE="restore_instructions.txt"

# Create a temporary directory for the backup
TEMP_DIR=$(mktemp -d)
echo "Creating backup in temporary directory: $TEMP_DIR"

# Export the database
echo "Exporting database..."
mysqldump --host=$DB_HOST --port=$DB_PORT --user=$DB_USER --password=$DB_PASS $DB_NAME > "$TEMP_DIR/$SQL_FILENAME"

if [ $? -ne 0 ]; then
    echo "Error: Database export failed!"
    exit 1
fi

echo "Database exported successfully to $SQL_FILENAME"

# Create restoration instructions
echo "Creating restoration instructions..."
cat > "$TEMP_DIR/$RESTORE_INSTRUCTIONS_FILE" << 'EOL'
=======================================================================
RESTORATION INSTRUCTIONS FOR SPARKS APPLICATION BACKUP
=======================================================================

This backup contains a complete copy of your Sparks application, including:
1. All application files and folders
2. A complete database export

FOLLOW THESE STEPS TO RESTORE ON A NEW SERVER:

1. PREREQUISITES:
   - PHP 8.0 or higher
   - MySQL/MariaDB 5.7 or higher
   - Composer
   - Web server (Apache/Nginx)

2. EXTRACT THE BACKUP:
   - Unzip the backup file to your web server's document root
   - Example: unzip sparks_backup_YYYY-MM-DD.zip -d /var/www/html/sparks

3. RESTORE THE DATABASE:
   - Create a new MySQL database
   - Import the SQL file using the following command:
     mysql -u [username] -p [new_database_name] < sparks_YYYY-MM-DD.sql

4. UPDATE ENVIRONMENT CONFIGURATION:
   - Copy the .env.example file to .env if .env doesn't exist
   - Update the database connection settings in the .env file:
     DB_CONNECTION=mysql
     DB_HOST=127.0.0.1
     DB_PORT=3306
     DB_DATABASE=[new_database_name]
     DB_USERNAME=[username]
     DB_PASSWORD=[password]

5. INSTALL DEPENDENCIES:
   - Run: composer install

6. SET PROPER PERMISSIONS:
   - Run: chmod -R 755 ./
   - Run: chmod -R 777 ./storage
   - Run: chmod -R 777 ./bootstrap/cache

7. GENERATE APPLICATION KEY:
   - Run: php artisan key:generate

8. CLEAR CACHES:
   - Run: php artisan config:clear
   - Run: php artisan cache:clear
   - Run: php artisan view:clear

9. OPTIMIZE THE APPLICATION:
   - Run: php artisan optimize

10. SET UP WEB SERVER:
    - Configure your web server to point to the public directory
    - For Apache, ensure mod_rewrite is enabled
    - For Nginx, configure the server block to handle Laravel routing

TROUBLESHOOTING:
- If you encounter permission issues, ensure the web server user has proper access
- If assets are missing, run: php artisan storage:link
- For database connection issues, verify your .env settings

This backup was created on: $(date)

For additional help, refer to the Laravel documentation at https://laravel.com/docs
=======================================================================
EOL

# Create a zip archive of the application files and database
echo "Creating zip archive of application files and database..."
cd "$APP_DIR"
zip -r "$TEMP_DIR/$BACKUP_FILENAME" . -x "node_modules/*" "vendor/*" "storage/logs/*" "storage/framework/cache/*" "storage/framework/sessions/*" "storage/framework/views/*"

if [ $? -ne 0 ]; then
    echo "Error: Failed to create zip archive of application files!"
    exit 1
fi

# Add the SQL file and restoration instructions to the zip archive
echo "Adding database export and restoration instructions to the zip archive..."
cd "$TEMP_DIR"
zip -u "$BACKUP_FILENAME" "$SQL_FILENAME" "$RESTORE_INSTRUCTIONS_FILE"

if [ $? -ne 0 ]; then
    echo "Error: Failed to add files to the zip archive!"
    exit 1
fi

# Move the backup to the Downloads folder
echo "Moving backup to Downloads folder..."
mv "$TEMP_DIR/$BACKUP_FILENAME" "$DOWNLOADS_DIR/"

if [ $? -ne 0 ]; then
    echo "Error: Failed to move backup to Downloads folder!"
    exit 1
fi

BACKUP_SIZE=$(du -h "$DOWNLOADS_DIR/$BACKUP_FILENAME" | cut -f1)

echo "===================================================="
echo "✅ BACKUP COMPLETED SUCCESSFULLY!"
echo "===================================================="
echo "📦 Backup file: $DOWNLOADS_DIR/$BACKUP_FILENAME"
echo "📊 Backup size: $BACKUP_SIZE"
echo "🗓️ Backup date: $(date)"
echo "===================================================="
echo "The backup includes:"
echo "  - All application files and folders"
echo "  - Complete database export"
echo "  - Detailed restoration instructions"
echo "===================================================="
echo "To restore this backup on another server, simply extract"
echo "the zip file and follow the instructions in the"
echo "restore_instructions.txt file included in the backup."
echo "===================================================="

# Clean up
rm -rf "$TEMP_DIR"
