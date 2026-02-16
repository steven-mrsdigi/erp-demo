-- Create tax_rates table
CREATE TABLE IF NOT EXISTS tax_rates (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    name TEXT NOT NULL,
    rate NUMERIC(5,2) NOT NULL DEFAULT 0,
    description TEXT,
    is_default BOOLEAN DEFAULT false,
    status TEXT DEFAULT 'active',
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- Add comments
COMMENT ON TABLE tax_rates IS 'Tax rates configuration table';
COMMENT ON COLUMN tax_rates.rate IS 'Tax rate percentage (e.g., 5 for 5%)';

-- Create index
CREATE INDEX IF NOT EXISTS idx_tax_rates_status ON tax_rates(status);

-- Insert some default tax rates
INSERT INTO tax_rates (name, rate, description, is_default) VALUES
    ('No Tax', 0, 'Tax exempt', false),
    ('GST 5%', 5, 'Goods and Services Tax', true),
    ('PST 7%', 7, 'Provincial Sales Tax', false),
    ('HST 13%', 13, 'Harmonized Sales Tax', false)
ON CONFLICT DO NOTHING;

-- Update products table to use tax_rate_id
-- Note: Run this after creating tax_rates table
-- ALTER TABLE products ADD COLUMN IF NOT EXISTS tax_rate_id UUID REFERENCES tax_rates(id);
