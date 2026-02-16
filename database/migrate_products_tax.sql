-- Migration: Update products table to use tax_rate_id

-- Step 1: Add tax_rate_id column to products table
ALTER TABLE products 
ADD COLUMN IF NOT EXISTS tax_rate_id UUID REFERENCES tax_rates(id);

-- Step 2: Migrate existing data (if any products have old tax_rate column)
-- First, get the default tax rate ID
DO $$
DECLARE
    default_tax_id UUID;
    no_tax_id UUID;
BEGIN
    -- Find default tax rate
    SELECT id INTO default_tax_id FROM tax_rates WHERE is_default = true LIMIT 1;
    
    -- If no default, find 'No Tax' or first tax rate
    IF default_tax_id IS NULL THEN
        SELECT id INTO default_tax_id FROM tax_rates WHERE rate = 0 LIMIT 1;
    END IF;
    
    IF default_tax_id IS NULL THEN
        SELECT id INTO default_tax_id FROM tax_rates LIMIT 1;
    END IF;
    
    -- Update existing products to link to appropriate tax rate
    -- This assumes you might have had different tax rates before
    UPDATE products 
    SET tax_rate_id = (
        SELECT id FROM tax_rates 
        WHERE rate = COALESCE(products.tax_rate, 0) 
        LIMIT 1
    )
    WHERE tax_rate_id IS NULL;
    
    -- For any remaining products without a match, use default
    UPDATE products 
    SET tax_rate_id = default_tax_id
    WHERE tax_rate_id IS NULL;
    
END $$;

-- Step 3: Remove old tax_rate column (optional - only after confirming migration works)
-- ALTER TABLE products DROP COLUMN IF EXISTS tax_rate;

-- Step 4: Add index for performance
CREATE INDEX IF NOT EXISTS idx_products_tax_rate_id ON products(tax_rate_id);
