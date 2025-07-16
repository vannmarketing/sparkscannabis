# Sparks Application Restore Instructions

## Backup Information
- **Date Created:** April 27, 2025
- **Application:** Sparks E-commerce Platform

This archive contains the following:
1. Application files (excluding vendor, node_modules, and cache directories)
2. Database dump (MySQL format)

## Restoration Steps

### 1. Extract Application Files
```bash
unzip sparks-backup.zip
```

### 2. Database Setup
1. Create a new MySQL database
2. Import the database:
```bash
mysql -u [username] -p [database_name] < database-backup.sql
```

### 3. Install Dependencies
```bash
# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install
npm run dev
```

### 4. Environment Configuration
1. Copy `.env.example` to `.env`
2. Update the following in your `.env` file:
   - `APP_URL`: Set to your domain
   - `DB_HOST`: Your database host
   - `DB_DATABASE`: Your database name
   - `DB_USERNAME`: Your database username
   - `DB_PASSWORD`: Your database password
   - Any other environment-specific settings

### 5. Clear Application Cache
```bash
php artisan optimize:clear
```

### 6. Set File Permissions
```bash
# Set proper permissions for storage and cache
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 7. Web Server Configuration
1. Configure your web server (Apache/Nginx) to point to the `public` directory
2. Ensure the web server has proper permissions to access the application files
3. Enable required PHP extensions (check composer.json for requirements)

### Common Issues
1. If you see a white screen, check the storage/logs directory for errors
2. Make sure all required PHP extensions are installed
3. Verify database credentials in .env file
4. Ensure proper file permissions on storage and bootstrap/cache directories

## Support
If you encounter any issues during restoration, please check the Laravel documentation or contact your system administrator.
