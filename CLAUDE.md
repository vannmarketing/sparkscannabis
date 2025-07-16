# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel-based e-commerce application (Sparks Cannabis) built on the Botble CMS platform v1.23.4. It's designed for a cannabis retail business with extensive customization through plugins and themes.

## Architecture

### Platform Structure
- **Core**: `platform/core/` - Botble CMS core functionality
- **Plugins**: `platform/plugins/` - Modular feature extensions
- **Themes**: `platform/themes/farmart/` - Frontend theme
- **Laravel App**: Standard Laravel structure in root

### Key Plugin Categories
- **E-commerce**: Core ecommerce functionality, marketplace, payments (Stripe, PayPal, Mollie, etc.)
- **Content**: Blog, FAQ, simple slider, newsletter
- **Security**: 2FA, IP blocker, honeypot, captcha, abuse detection
- **Tools**: Analytics, backup, audit log, email logging, translation
- **Custom Features**: Mix-and-match products, free gifts, floating buttons, PWA support

## Development Commands

### PHP/Laravel Commands
```bash
# Install dependencies
composer install

# Run database migrations
php artisan migrate

# Clear application cache
php artisan optimize:clear

# Publish CMS assets
php artisan cms:publish:assets

# Test schema (custom command)
php artisan test:schema

# Run tests
vendor/bin/phpunit
# or specific test suite
vendor/bin/phpunit tests/Feature
vendor/bin/phpunit tests/Unit
```

### Frontend Build Commands
```bash
# Install dependencies
npm install

# Development build
npm run dev
npm run development

# Watch for changes
npm run watch
npm run watch-poll

# Production build
npm run prod
npm run production

# Hot reload
npm run hot

# Format code
npm run format
```

### Docker Development
```bash
# Start services
docker-compose up -d

# Stop services
docker-compose down

# App container access
docker-compose exec app bash
```

### Plugin-Specific Builds
```bash
# Build specific plugin assets
npm_config_plugin=ecommerce npm run dev
npm_config_theme=farmart npm run dev
npm_config_core=true npm run dev
```

## Code Quality & Testing

### Linting and Formatting
- **PHP**: Uses Laravel Pint (`vendor/bin/pint`)
- **JavaScript**: Prettier for formatting (`npm run format`)
- **Static Analysis**: PHPStan via Larastan

### Testing Structure
- Unit tests: `tests/Unit/`
- Feature tests: `tests/Feature/`
- Plugin tests: Individual plugins may have their own test directories
- Uses SQLite in-memory database for testing

## Database

### Migrations
- Laravel migrations: `database/migrations/`
- Plugin migrations: `platform/plugins/*/database/migrations/`
- Backup SQL available: `sparks_db.sql`

### Seeders
Comprehensive seeders for demo data in `database/seeders/`:
- Products, categories, brands, customers
- Blog content, FAQs, menus
- Store settings and theme options

## Key Configuration

### Environment Setup
1. Copy `.env.example` to `.env`
2. Configure database, mail, and application settings
3. Set proper file permissions on `storage/` and `bootstrap/cache/`

### Plugin System
- Plugin configurations: `platform/plugins/*/config/`
- Plugin permissions: `*/config/permissions.php`
- Plugin routes: `*/routes/web.php`, `*/routes/api.php`

### Asset Compilation
- Main webpack config loads individual plugin webpack configs
- Supports selective building of specific plugins/themes
- Uses Laravel Mix for asset compilation

## Custom Features

### Mix and Match Products
Special product bundling functionality for cannabis products with custom pricing and quantity rules.

### Multi-Payment Integration
Supports multiple payment gateways including Stripe, PayPal, Mollie, Razorpay, PayStack, and SSL Commerce.

### Advanced Security
Multiple security layers including 2FA, IP blocking, honeypot protection, and Cloudflare Turnstile.

### Email Marketing
Integration with SendGrid and SendPortal for customer communications and product promotions.

## File Permissions

When deploying or after restoration:
```bash
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

## Important Notes

- This is a production e-commerce application - be careful with database changes
- Plugin system allows for modular development - respect plugin boundaries
- Uses Botble CMS conventions - follow existing patterns when extending
- Docker setup available for local development
- Extensive logging available in `storage/logs/`