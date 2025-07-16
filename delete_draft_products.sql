
-- Script to delete all draft products and related data
-- IMPORTANT: This is a destructive operation. Please backup your database before running this script.

-- Start a transaction to ensure all operations are atomic
START TRANSACTION;

-- Create a table to store IDs of draft products (this will remain after the script completes)
DROP TABLE IF EXISTS deleted_draft_products;
CREATE TABLE deleted_draft_products (
    id INT PRIMARY KEY,
    name VARCHAR(255),
    sku VARCHAR(255),
    deleted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert IDs of all draft products into the table
INSERT INTO deleted_draft_products (id, name, sku)
SELECT id, name, sku FROM ec_products WHERE status = 'draft';

-- Show count of products to be deleted (for verification)
SELECT COUNT(*) AS products_to_delete FROM deleted_draft_products;

-- Delete product category relationships
DELETE FROM ec_product_category_product 
WHERE product_id IN (SELECT id FROM deleted_draft_products);

-- Delete product collection relationships
DELETE FROM ec_product_collection_products 
WHERE product_id IN (SELECT id FROM deleted_draft_products);

-- Delete product tag relationships
DELETE FROM ec_product_tag_product 
WHERE product_id IN (SELECT id FROM deleted_draft_products);

-- Delete product attribute relationships
DELETE FROM ec_product_with_attribute_set 
WHERE product_id IN (SELECT id FROM deleted_draft_products);

-- Delete product variations
DELETE FROM ec_product_variation_items 
WHERE variation_id IN (
    SELECT id FROM ec_product_variations 
    WHERE product_id IN (SELECT id FROM deleted_draft_products) 
    OR configurable_product_id IN (SELECT id FROM deleted_draft_products)
);

DELETE FROM ec_product_variations 
WHERE product_id IN (SELECT id FROM deleted_draft_products) 
OR configurable_product_id IN (SELECT id FROM deleted_draft_products);

-- Delete product cross sale relationships
DELETE FROM ec_product_cross_sale_relations 
WHERE from_product_id IN (SELECT id FROM deleted_draft_products) 
OR to_product_id IN (SELECT id FROM deleted_draft_products);

-- Delete product up sale relationships
DELETE FROM ec_product_up_sale_relations 
WHERE from_product_id IN (SELECT id FROM deleted_draft_products) 
OR to_product_id IN (SELECT id FROM deleted_draft_products);

-- Delete product related relationships
DELETE FROM ec_product_related_relations 
WHERE from_product_id IN (SELECT id FROM deleted_draft_products) 
OR to_product_id IN (SELECT id FROM deleted_draft_products);

-- Delete product grouped relationships
DELETE FROM ec_grouped_products 
WHERE parent_product_id IN (SELECT id FROM deleted_draft_products) 
OR product_id IN (SELECT id FROM deleted_draft_products);

-- Delete product views
DELETE FROM ec_product_views 
WHERE product_id IN (SELECT id FROM deleted_draft_products);

-- Delete product from discount products
DELETE FROM ec_discount_products 
WHERE product_id IN (SELECT id FROM deleted_draft_products);

-- Delete product from tax products
DELETE FROM ec_tax_products 
WHERE product_id IN (SELECT id FROM deleted_draft_products);

-- Delete product from wishlist
DELETE FROM ec_wish_lists 
WHERE product_id IN (SELECT id FROM deleted_draft_products);

-- Delete product from cart
DELETE FROM ec_cart 
WHERE product_id IN (SELECT id FROM deleted_draft_products);

-- Delete product from recently viewed
DELETE FROM ec_customer_recently_viewed_products 
WHERE product_id IN (SELECT id FROM deleted_draft_products);

-- Delete product translations if they exist
DELETE FROM ec_products_translations 
WHERE ec_products_id IN (SELECT id FROM deleted_draft_products);

-- Delete product files if they exist
DELETE FROM ec_product_files 
WHERE product_id IN (SELECT id FROM deleted_draft_products);

-- Delete product from specification table if it exists
DELETE FROM ec_product_specification_items
WHERE product_id IN (SELECT id FROM deleted_draft_products);

-- Finally, delete the draft products
DELETE FROM ec_products 
WHERE id IN (SELECT id FROM deleted_draft_products);

-- Show confirmation message
SELECT CONCAT('Deleted ', (SELECT COUNT(*) FROM deleted_draft_products), ' draft products') AS result;

-- Commit the transaction if everything is successful
COMMIT;

-- The table 'deleted_draft_products' will remain after script execution for reference
-- You can query it with: SELECT * FROM deleted_draft_products;

-- =============================================
-- QUERY TO FIND PRODUCT VARIATIONS WITH BLANK NAMES
-- =============================================

-- This query finds product variations with blank names
SELECT 
    pv.id AS variation_id,
    pv.product_id,
    pv.configurable_product_id,
    p.name AS product_name,
    p.sku AS product_sku,
    cp.name AS configurable_product_name,
    cp.sku AS configurable_product_sku
FROM 
    ec_product_variations pv
JOIN 
    ec_products p ON pv.product_id = p.id
LEFT JOIN 
    ec_products cp ON pv.configurable_product_id = cp.id
WHERE 
    p.name = '' OR p.name IS NULL
ORDER BY 
    pv.configurable_product_id, pv.id;

-- Alternative query to find products with blank names that are used in variations
SELECT 
    p.id,
    p.name,
    p.sku,
    p.status,
    p.is_variation,
    COUNT(pv.id) AS variation_count
FROM 
    ec_products p
LEFT JOIN 
    ec_product_variations pv ON p.id = pv.product_id
WHERE 
    p.name = '' OR p.name IS NULL
GROUP BY 
    p.id, p.name, p.sku, p.status, p.is_variation
ORDER BY 
    variation_count DESC;