-- Add inventory management fields to products
ALTER TABLE products ADD COLUMN IF NOT EXISTS onhand_qty INTEGER DEFAULT 0;
ALTER TABLE products ADD COLUMN IF NOT EXISTS allocated_qty INTEGER DEFAULT 0;
ALTER TABLE products ADD COLUMN IF NOT EXISTS available_qty INTEGER DEFAULT 0;

-- Initialize values from existing stock_quantity
UPDATE products SET 
    onhand_qty = stock_quantity,
    allocated_qty = 0,
    available_qty = stock_quantity
WHERE onhand_qty = 0;

-- Add payment tracking to orders
ALTER TABLE orders ADD COLUMN IF NOT EXISTS paid_at TIMESTAMP WITH TIME ZONE;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS paid_amount DECIMAL(12, 2) DEFAULT 0;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_method VARCHAR(50);
ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_reference VARCHAR(100);
ALTER TABLE orders ADD COLUMN IF NOT EXISTS notes TEXT;

-- Create payment_methods lookup table
CREATE TABLE IF NOT EXISTS payment_methods (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    name VARCHAR(50) NOT NULL,
    code VARCHAR(20) UNIQUE NOT NULL,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Insert default payment methods
INSERT INTO payment_methods (name, code) VALUES
('Cash', 'cash'),
('Credit Card', 'credit_card'),
('Bank Transfer', 'bank_transfer'),
('PayPal', 'paypal'),
('Check', 'check')
ON CONFLICT (code) DO NOTHING;

-- Trigger to automatically calculate available_qty
CREATE OR REPLACE FUNCTION update_available_qty()
RETURNS TRIGGER AS $$
BEGIN
    NEW.available_qty = NEW.onhand_qty - NEW.allocated_qty;
    IF NEW.available_qty < 0 THEN
        NEW.available_qty = 0;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS products_available_qty_trigger ON products;
CREATE TRIGGER products_available_qty_trigger
    BEFORE INSERT OR UPDATE ON products
    FOR EACH ROW
    EXECUTE FUNCTION update_available_qty();
