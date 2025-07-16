# Sparks Cannabis SendPortal Integration Guide

This guide explains how to integrate your Botble CMS featured products with SendPortal email templates using the API token.

## Overview

The integration allows you to:
1. Generate an API token from your Botble CMS admin panel
2. Fetch featured products using the API
3. Display these products in your SendPortal email templates
4. Automatically update your email templates with the latest featured products

## Files Included

- `botble-api-helper.php` - Helper script to generate/test API tokens and view integration examples
- `sendportal-products-fetcher.php` - Script to fetch featured products and generate HTML
- `sendportal-featured-products-template.html` - Example email template with product placeholder
- `update-sendportal-template.php` - Script to automatically update the template with latest products
- `sendportal-integration-examples.php` - Various code examples for different integration approaches

## Step 1: Generate an API Token

1. Log in to your Botble CMS admin panel
2. Navigate to 'Settings' > 'API Tokens' or 'Personal Access Tokens'
3. Click 'Create New Token'
4. Enter a name for your token (e.g., 'SendPortal Featured Products')
5. Select the necessary permissions (at minimum 'products:read')
6. Click 'Generate Token'
7. Copy the generated token and store it securely

## Step 2: Store Your API Token

Add your token to your `.env` file:

```
BOTBLE_API_TOKEN=your_api_token_here
```

## Step 3: Test Your API Token

Run the helper script to test your token:

```bash
php botble-api-helper.php
```

Choose option 3 and enter your API token when prompted.

## Step 4: Update the Email Template

There are two approaches to integrate with SendPortal:

### Option A: Pre-generated HTML Approach (Recommended)

1. Run the update script to generate the latest featured products HTML:

```bash
php update-sendportal-template.php YOUR_API_TOKEN
```

2. This will create an HTML file at `storage/sendportal/featured-products-email.html`
3. In SendPortal, create a new template and import this HTML file
4. Schedule the update script to run daily/weekly to keep products current

### Option B: API Integration Approach

If SendPortal supports external API calls in templates:

1. Use the code examples in `sendportal-integration-examples.php`
2. Create a custom template variable in SendPortal that fetches products via API
3. Use this variable in your email templates

## Setting Up Automatic Updates

To keep your email templates updated with the latest featured products:

1. Set up a cron job to run the update script regularly:

```
# Run daily at 1 AM
0 1 * * * cd /var/www/vhosts/sparkscannabis.net/httpdocs && php update-sendportal-template.php
```

2. The script will:
   - Fetch the latest featured products from your Botble CMS
   - Update the email template with these products
   - Log the process for monitoring

## Troubleshooting

If you encounter issues:

1. Check the log file at `storage/logs/sendportal-update.log`
2. Verify your API token is valid using the helper script
3. Ensure your Botble CMS has featured products available
4. Check file permissions for the output directories

## Need Help?

If you need further assistance with this integration, please contact your developer or refer to the SendPortal documentation for more information on custom template variables and API integrations.
