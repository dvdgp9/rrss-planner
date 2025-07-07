# Estructura de Base de Datos - RRSS Planner

## Información General
- **Base de Datos**: `rrss_ebone`
- **Motor**: MySQL 5.7+
- **Charset**: utf8mb4
- **Fecha de Última Actualización**: 2025-01-23

---

## Tablas del Sistema

### 1. `lineas_negocio`
**Descripción**: Tabla principal que almacena las diferentes líneas de negocio/marcas
```sql
CREATE TABLE lineas_negocio (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(255) NOT NULL,
    logo_filename VARCHAR(255),
    slug VARCHAR(255) UNIQUE,
    wordpress_url VARCHAR(500),
    wordpress_username VARCHAR(255),
    wordpress_app_password VARCHAR(255),
    wordpress_enabled TINYINT(1) DEFAULT 0,
    wordpress_last_sync TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**Datos Actuales**:
- Ebone Servicios (slug: ebone)
- CUBOFIT (slug: cubofit)
- Uniges-3 (slug: uniges)
- Teia (slug: teia)
- CIDE (slug: cide)

---

### 2. `redes_sociales`
**Descripción**: Catálogo de redes sociales disponibles
```sql
CREATE TABLE redes_sociales (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL UNIQUE
);
```

**Datos Actuales**:
- Instagram (id: 1)
- Facebook (id: 2)
- Twitter (X) (id: 3)
- LinkedIn (id: 4)

---

### 3. `linea_negocio_red_social`
**Descripción**: Relación muchos-a-muchos entre líneas de negocio y redes sociales
```sql
CREATE TABLE linea_negocio_red_social (
    id INT PRIMARY KEY AUTO_INCREMENT,
    linea_negocio_id INT NOT NULL,
    red_social_id INT NOT NULL,
    FOREIGN KEY (linea_negocio_id) REFERENCES lineas_negocio(id) ON DELETE CASCADE,
    FOREIGN KEY (red_social_id) REFERENCES redes_sociales(id) ON DELETE CASCADE,
    UNIQUE KEY unique_linea_red_social (linea_negocio_id, red_social_id)
);
```

---

### 4. `publicaciones`
**Descripción**: Publicaciones de redes sociales
```sql
CREATE TABLE publicaciones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    linea_negocio_id INT NOT NULL,
    titulo VARCHAR(255),
    contenido TEXT,
    imagen_url VARCHAR(500),
    fecha_programada DATETIME,
    estado ENUM('borrador', 'programado', 'publicado') DEFAULT 'borrador',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (linea_negocio_id) REFERENCES lineas_negocio(id) ON DELETE CASCADE,
    INDEX idx_linea_estado (linea_negocio_id, estado),
    INDEX idx_fecha_programada (fecha_programada)
);
```

**Estados Posibles**:
- `borrador`: Publicación en proceso de creación
- `programado`: Publicación programada para una fecha futura
- `publicado`: Publicación ya realizada

---

### 5. `publicacion_red_social`
**Descripción**: Relación muchos-a-muchos entre publicaciones y redes sociales
```sql
CREATE TABLE publicacion_red_social (
    id INT PRIMARY KEY AUTO_INCREMENT,
    publicacion_id INT NOT NULL,
    red_social_id INT NOT NULL,
    FOREIGN KEY (publicacion_id) REFERENCES publicaciones(id) ON DELETE CASCADE,
    FOREIGN KEY (red_social_id) REFERENCES redes_sociales(id) ON DELETE CASCADE,
    UNIQUE KEY unique_publicacion_red_social (publicacion_id, red_social_id)
);
```

---

### 6. `blog_categorias`
**Descripción**: Categorías para los blog posts por línea de negocio
```sql
CREATE TABLE blog_categorias (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    descripcion TEXT,
    linea_negocio_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (linea_negocio_id) REFERENCES lineas_negocio(id) ON DELETE CASCADE,
    UNIQUE KEY unique_slug_per_linea (slug, linea_negocio_id)
);
```

**Categorías Predeterminadas**:
- General (slug: general)
- Noticias (slug: noticias)
- Tutoriales (slug: tutoriales)

---

### 7. `blog_tags`
**Descripción**: Etiquetas para los blog posts por línea de negocio
```sql
CREATE TABLE blog_tags (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    descripcion TEXT,
    linea_negocio_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (linea_negocio_id) REFERENCES lineas_negocio(id) ON DELETE CASCADE,
    UNIQUE KEY unique_slug_per_linea (slug, linea_negocio_id)
);
```

**Tags Predeterminados**:
- Importante (slug: importante)
- Actualización (slug: actualizacion)

---

### 8. `blog_posts`
**Descripción**: Artículos de blog por línea de negocio
```sql
CREATE TABLE blog_posts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    titulo VARCHAR(255) NOT NULL,
    contenido LONGTEXT,
    excerpt TEXT,
    slug VARCHAR(255) NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_publicacion DATETIME,
    estado ENUM('draft', 'programado', 'publicado') DEFAULT 'draft',
    imagen_destacada VARCHAR(500),
    meta_desc TEXT,
    linea_negocio_id INT NOT NULL,
    wordpress_post_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (linea_negocio_id) REFERENCES lineas_negocio(id) ON DELETE CASCADE,
    UNIQUE KEY unique_slug_per_linea (slug, linea_negocio_id),
    INDEX idx_linea_estado (linea_negocio_id, estado),
    INDEX idx_fecha_publicacion (fecha_publicacion)
);
```

**Estados Posibles**:
- `draft`: Borrador en proceso de creación
- `programado`: Programado para publicación futura
- `publicado`: Publicado en WordPress

---

### 9. `blog_post_categoria`
**Descripción**: Relación muchos-a-muchos entre blog posts y categorías
```sql
CREATE TABLE blog_post_categoria (
    id INT PRIMARY KEY AUTO_INCREMENT,
    blog_post_id INT NOT NULL,
    categoria_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (blog_post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (categoria_id) REFERENCES blog_categorias(id) ON DELETE CASCADE,
    UNIQUE KEY unique_post_categoria (blog_post_id, categoria_id)
);
```

---

### 10. `blog_post_tag`
**Descripción**: Relación muchos-a-muchos entre blog posts y tags
```sql
CREATE TABLE blog_post_tag (
    id INT PRIMARY KEY AUTO_INCREMENT,
    blog_post_id INT NOT NULL,
    tag_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (blog_post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES blog_tags(id) ON DELETE CASCADE,
    UNIQUE KEY unique_post_tag (blog_post_id, tag_id)
);
```

---

### 11. `share_tokens`
**Descripción**: Tokens para compartir vistas públicas de líneas de negocio
```sql
CREATE TABLE share_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    token VARCHAR(128) NOT NULL UNIQUE,
    linea_negocio_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (linea_negocio_id) REFERENCES lineas_negocio(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_linea_active (linea_negocio_id, is_active)
);
```

---

### 12. `publicacion_share_tokens`
**Descripción**: Tokens para compartir publicaciones individuales
```sql
CREATE TABLE publicacion_share_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    token VARCHAR(128) NOT NULL UNIQUE,
    publicacion_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (publicacion_id) REFERENCES publicaciones(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_publicacion_active (publicacion_id, is_active)
);
```

---

### 13. `publicacion_feedback`
**Descripción**: Feedback/comentarios sobre publicaciones compartidas
```sql
CREATE TABLE publicacion_feedback (
    id INT PRIMARY KEY AUTO_INCREMENT,
    publicacion_id INT NOT NULL,
    share_token VARCHAR(128) NOT NULL,
    feedback_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (publicacion_id) REFERENCES publicaciones(id) ON DELETE CASCADE,
    INDEX idx_publicacion (publicacion_id),
    INDEX idx_share_token (share_token)
);
```

---

## Relaciones Principales

### Diagrama de Relaciones
```
lineas_negocio (1) ←→ (N) publicaciones
lineas_negocio (1) ←→ (N) blog_posts
lineas_negocio (1) ←→ (N) blog_categorias
lineas_negocio (1) ←→ (N) blog_tags
lineas_negocio (1) ←→ (N) share_tokens

publicaciones (N) ←→ (N) redes_sociales [via publicacion_red_social]
lineas_negocio (N) ←→ (N) redes_sociales [via linea_negocio_red_social]

blog_posts (N) ←→ (N) blog_categorias [via blog_post_categoria]
blog_posts (N) ←→ (N) blog_tags [via blog_post_tag]

publicaciones (1) ←→ (N) publicacion_share_tokens
publicaciones (1) ←→ (N) publicacion_feedback
```

---

## Índices y Optimizaciones

### Índices Principales
- `lineas_negocio.slug` (UNIQUE)
- `publicaciones.linea_negocio_id, estado`
- `publicaciones.fecha_programada`
- `blog_posts.linea_negocio_id, estado`
- `blog_posts.fecha_publicacion`
- `share_tokens.token` (UNIQUE)
- `publicacion_share_tokens.token` (UNIQUE)

### Claves Foráneas
- Todas las relaciones tienen restricciones FK con `ON DELETE CASCADE`
- Esto asegura integridad referencial automática

---

## Consideraciones Técnicas

### Charset y Collation
- **Charset**: `utf8mb4`
- **Collation**: `utf8mb4_unicode_ci`
- Soporte completo para emojis y caracteres especiales

### Timestamps
- Todas las tablas principales tienen `created_at` y `updated_at`
- Uso de `TIMESTAMP` para auditoría automática

### Validación de Datos
- Estados definidos como `ENUM` para validación automática
- Slugs únicos por línea de negocio
- Tokens únicos globalmente

---

## Notas de Migración

### WordPress Integration
- `wordpress_post_id` en `blog_posts` para mapear con WordPress
- `wordpress_last_sync` en `lineas_negocio` para tracking de sincronización
- `wordpress_enabled` permite habilitar/deshabilitar integración por línea

### Tokens de Compartir
- Sistema dual: tokens por línea de negocio y por publicación individual
- Tokens seguros de 128 caracteres
- Campo `is_active` permite desactivar sin eliminar

---

## Changelog
- **2025-01-23**: Documentación inicial basada en estructura actual
- **2025-01-23**: Planificación de optimización de almacenamiento - Las imágenes de publicaciones se borrarán automáticamente cuando el estado cambie a "publicado" (tanto en `publicaciones.imagen_url` como `blog_posts.imagen_destacada`)
- **Próximas actualizaciones**: Se documentarán aquí todos los cambios

---

## Funcionalidades Planificadas

### Optimización de Almacenamiento de Imágenes
**Estado**: En planificación
**Descripción**: Implementación de borrado automático de imágenes cuando las publicaciones cambien a estado "publicado"

**Impacto en Tablas**:
- `publicaciones.imagen_url`: Se pondrá a NULL después del borrado
- `blog_posts.imagen_destacada`: Se pondrá a NULL después del borrado

**Comportamiento**:
- Triggers en `publicacion_update_estado.php` y `blog_update_estado.php`
- Borrado físico de archivos en `/uploads/` y `/uploads/blog/`
- Actualización automática de campos de imagen a NULL
- Logging de eventos de borrado para auditoría

**Objetivo**: Reducir significativamente el uso de almacenamiento del servidor

---

*Documento mantenido por el equipo de desarrollo*
*Última revisión: 2025-01-23* 