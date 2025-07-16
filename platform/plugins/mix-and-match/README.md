# Mix and Match Plugin

A plugin for creating customizable product bundles where customers can select their own combination of products.

## Features

- Create container products that allow customers to build their own bundles
- Set minimum and maximum quantity limits for the entire container
- Set individual minimum and maximum quantities per child product
- Support for both fixed price and per-item pricing
- Track inventory per child product
- Seamless integration with the cart and checkout process
- Compatible with both parent products and variation products

## Installation

1. Upload the plugin to your `/platform/plugins/` directory
2. Activate the plugin from the Plugins page in the admin panel
3. The plugin will automatically run its migrations to set up the necessary database tables

## Usage

### Creating a Mix and Match Product

1. Create a new product or edit an existing one
2. In the product edit page, navigate to the "Mix and Match" tab
3. Check the "Is Mix and Match product" checkbox
4. Configure the container settings:
   - Minimum Container Size: The minimum number of items a customer must select
   - Maximum Container Size: The maximum number of items a customer can select (leave empty for unlimited)
   - Pricing Type: Choose between "Fixed Price" (set one price for the entire bundle) or "Per-Item Pricing" (calculate based on the sum of selected items)
   - If using Fixed Price, enter the price for the entire bundle
5. Add products to the container:
   - Use the search box to find products
   - For each product, set minimum and maximum quantities
   - Products with a minimum quantity greater than 0 will be required selections
6. Save the product

### Frontend Experience

Customers will see a special interface on Mix and Match product pages that allows them to:

1. Browse available products for the bundle
2. Select quantities of each product within the defined limits
3. See a summary of their selections
4. Add the complete bundle to their cart once all requirements are met

## Compatibility

This plugin is compatible with the latest version of your e-commerce platform and follows the same coding standards and practices.

## Support

For any issues or questions, please contact the plugin developer.
