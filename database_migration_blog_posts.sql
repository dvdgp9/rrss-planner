-- Database Migration: Blog Posts WordPress-Compatible Tables
-- Date: December 30, 2024
-- Purpose: Add blog post functionality with WordPress REST API compatibility

-- 1. Create blog_posts table (similar to wp_posts structure)
CREATE TABLE IF NOT EXISTS `blog_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) NOT NULL,
  `contenido` longtext NOT NULL,
  `excerpt` text,
  `slug` varchar(200) NOT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_publicacion` datetime NULL,
  `estado` enum('draft','publish','scheduled') NOT NULL DEFAULT 'draft',
  `imagen_destacada` varchar(500) NULL,
  `meta_description` varchar(160) NULL,
  `linea_negocio_id` int(11) NOT NULL,
  `wp_post_id` int(11) NULL COMMENT 'For future WordPress synchronization',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug_linea_unique` (`slug`, `linea_negocio_id`),
  KEY `idx_linea_negocio` (`linea_negocio_id`),
  KEY `idx_estado` (`estado`),
  KEY `idx_fecha_publicacion` (`fecha_publicacion`),
  KEY `idx_wp_post_id` (`wp_post_id`),
  CONSTRAINT `fk_blog_posts_linea_negocio` FOREIGN KEY (`linea_negocio_id`) REFERENCES `lineas_negocio` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Create blog_categorias table (similar to wp_terms)
CREATE TABLE IF NOT EXISTS `blog_categorias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `descripcion` text NULL,
  `linea_negocio_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug_linea_unique` (`slug`, `linea_negocio_id`),
  KEY `idx_linea_negocio` (`linea_negocio_id`),
  CONSTRAINT `fk_blog_categorias_linea_negocio` FOREIGN KEY (`linea_negocio_id`) REFERENCES `lineas_negocio` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Create blog_tags table (similar to wp_terms)
CREATE TABLE IF NOT EXISTS `blog_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `descripcion` text NULL,
  `linea_negocio_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug_linea_unique` (`slug`, `linea_negocio_id`),
  KEY `idx_linea_negocio` (`linea_negocio_id`),
  CONSTRAINT `fk_blog_tags_linea_negocio` FOREIGN KEY (`linea_negocio_id`) REFERENCES `lineas_negocio` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Create blog_post_categoria relationship table
CREATE TABLE IF NOT EXISTS `blog_post_categoria` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `blog_post_id` int(11) NOT NULL,
  `categoria_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `post_categoria_unique` (`blog_post_id`, `categoria_id`),
  KEY `idx_blog_post` (`blog_post_id`),
  KEY `idx_categoria` (`categoria_id`),
  CONSTRAINT `fk_blog_post_categoria_post` FOREIGN KEY (`blog_post_id`) REFERENCES `blog_posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_blog_post_categoria_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `blog_categorias` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Create blog_post_tag relationship table
CREATE TABLE IF NOT EXISTS `blog_post_tag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `blog_post_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `post_tag_unique` (`blog_post_id`, `tag_id`),
  KEY `idx_blog_post` (`blog_post_id`),
  KEY `idx_tag` (`tag_id`),
  CONSTRAINT `fk_blog_post_tag_post` FOREIGN KEY (`blog_post_id`) REFERENCES `blog_posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_blog_post_tag_tag` FOREIGN KEY (`tag_id`) REFERENCES `blog_tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Insert some example categories and tags (user can create specific ones as needed)
-- Note: Each business line will manage their own categories/tags
-- Examples for demonstration - can be customized per business line needs

-- Example categories (these are just suggestions)
INSERT IGNORE INTO `blog_categorias` (`nombre`, `slug`, `descripcion`, `linea_negocio_id`)
VALUES 
  ('General', 'general', 'Contenido general', 1),
  ('Noticias', 'noticias', 'Noticias y actualizaciones', 1),
  ('Tutoriales', 'tutoriales', 'Guías y tutoriales', 1);

-- Example tags (these are just suggestions)  
INSERT IGNORE INTO `blog_tags` (`nombre`, `slug`, `descripcion`, `linea_negocio_id`)
VALUES 
  ('Importante', 'importante', 'Contenido destacado', 1),
  ('Actualización', 'actualizacion', 'Actualizaciones del servicio', 1);

-- Migration completed successfully
-- Tables created:
-- - blog_posts (WordPress wp_posts compatible)
-- - blog_categorias (WordPress wp_terms compatible)
-- - blog_tags (WordPress wp_terms compatible)
-- - blog_post_categoria (relationship table)
-- - blog_post_tag (relationship table)
-- 
-- Example data inserted:
-- - Sample categories: General, Noticias, Tutoriales (for business line 1)
-- - Sample tags: Importante, Actualización (for business line 1)
-- 
-- Note: Each business line manages their own categories/tags
-- Posts go to their respective websites (Ebone posts → Ebone website, etc.)
-- 
-- Ready for WordPress REST API integration in the future! 