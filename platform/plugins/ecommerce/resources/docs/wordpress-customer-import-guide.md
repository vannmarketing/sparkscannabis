# WordPress Customer Import Guide

This guide explains how to import customers from WordPress to your Botble E-commerce platform while preserving their passwords.

## Prerequisites

1. A CSV export of your WordPress customers with the following fields:
   - Email (required)
   - Password (required, WordPress hashed format)
   - Name or First Name/Last Name
   - Other customer data (address, phone, etc.)

## CSV Format Requirements

Your CSV file should include at minimum these columns:
- `email` or `user_email` - Customer's email address
- `password` or `user_pass` - WordPress hashed password
- `name` or `display_name` or `first_name`/`last_name` - Customer's name

Example CSV format:
```
email,password,name,phone,address,city,state,country,zip
customer@example.com,$P$B7G5EFuOvL9X0EZ7VVZ0vz3Hvs1234,John Doe,123-456-7890,123 Main St,Anytown,CA,US,12345
```

## Running the Import

1. Place your CSV file on the server
2. Run the import command:

```bash
php artisan ecommerce:import-wp-customers /path/to/your/customers.csv
```

## How It Works

1. The importer reads your CSV file and maps the columns to customer fields
2. For each customer:
   - If the customer already exists (by email), only the password is updated
   - If the customer is new, a complete record is created
3. WordPress passwords are preserved with a special prefix (`wp:`) that allows customers to log in with their existing passwords
4. When a customer logs in successfully with a WordPress password, it's automatically converted to the Laravel format for future logins

## Password Handling

The importer handles these WordPress password formats:
- Bcrypt hashes (starting with `$2y$`)
- PHPass hashes (starting with `$P$` or `$H$`)

When a customer logs in with their WordPress password for the first time, the password is automatically migrated to Laravel's native hashing format.

## Troubleshooting

If you encounter issues during import:

1. Check your CSV format - ensure it has the required columns
2. Verify file permissions - the import command needs read access to the CSV file
3. Check for duplicate emails - the importer will skip customers with duplicate emails
4. For password issues, ensure the WordPress passwords are in their original hashed format

## Command Options

The import command supports these options:

- No additional options currently available

## Notes

- This importer preserves WordPress passwords, allowing customers to log in without resetting their passwords
- The first login will automatically migrate passwords to Laravel's format
- Customer data beyond basic fields (email, name, password) is imported if available in the CSV
