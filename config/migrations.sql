-- Database Migrations for Chatbot Upgrade
-- Import this file to add support for advanced features

-- 1. Add stock management to products
ALTER TABLE products ADD COLUMN stock INT DEFAULT 10;
ALTER TABLE products ADD COLUMN is_available BOOLEAN DEFAULT 1;

-- 2. Add rating system for sellers
ALTER TABLE sellers ADD COLUMN rating DECIMAL(2,1) DEFAULT 4.5;

-- 3. Add serving size info for better recommendations
ALTER TABLE products ADD COLUMN serving_size INT DEFAULT 1;
ALTER TABLE products ADD COLUMN preparation_time INT DEFAULT 15; -- in minutes

-- 4. Initial Data Population (Sample)
UPDATE products SET stock = 20 WHERE category = 'rice';
UPDATE products SET stock = 50 WHERE category = 'drink';
UPDATE sellers SET rating = 4.8 WHERE id = 1;
UPDATE sellers SET rating = 4.2 WHERE id = 2;
