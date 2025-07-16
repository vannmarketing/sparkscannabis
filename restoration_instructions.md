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
