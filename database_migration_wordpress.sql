-- WordPress Integration Migration
-- Add WordPress configuration fields to lineas_negocio table

ALTER TABLE lineas_negocio ADD COLUMN wordpress_url VARCHAR(255) NULL;
ALTER TABLE lineas_negocio ADD COLUMN wordpress_username VARCHAR(100) NULL;
ALTER TABLE lineas_negocio ADD COLUMN wordpress_app_password VARCHAR(255) NULL;
ALTER TABLE lineas_negocio ADD COLUMN wordpress_enabled BOOLEAN DEFAULT FALSE;
ALTER TABLE lineas_negocio ADD COLUMN wordpress_last_sync TIMESTAMP NULL;

-- Update existing business lines with their WordPress URLs
UPDATE lineas_negocio SET 
    wordpress_url = 'https://ebone.es/',
    wordpress_enabled = TRUE
WHERE slug = 'ebone';

UPDATE lineas_negocio SET 
    wordpress_url = 'https://www.cubofit.es/',
    wordpress_enabled = TRUE
WHERE slug = 'cubofit';

UPDATE lineas_negocio SET 
    wordpress_url = 'https://uniges3.net/',
    wordpress_enabled = TRUE
WHERE slug = 'uniges';

UPDATE lineas_negocio SET 
    wordpress_url = 'https://ebone.es/catedra/',
    wordpress_enabled = TRUE
WHERE slug = 'cide';

-- Teia doesn't have WordPress site, so wordpress_enabled remains FALSE

-- Add wp_post_id field to blog_posts for synchronization tracking
ALTER TABLE blog_posts ADD COLUMN wp_post_id INT NULL;
ALTER TABLE blog_posts ADD COLUMN wp_sync_status ENUM('pending', 'synced', 'error') DEFAULT 'pending';
ALTER TABLE blog_posts ADD COLUMN wp_sync_error TEXT NULL;
ALTER TABLE blog_posts ADD COLUMN wp_last_sync TIMESTAMP NULL; 