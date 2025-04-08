# Planificador de Redes Sociales - Ebone

Aplicación web para planificar publicaciones en redes sociales para diferentes líneas de negocio.

## Requisitos

- PHP 7.4 o superior
- MySQL 5.7 o superior
- Servidor web (Apache, Nginx, etc.)

## Instalación

1. Clonar o descargar el repositorio en el directorio del servidor web.

2. Configurar la base de datos:

   - Crear una base de datos en MySQL
   - Ejecutar el script `db_setup.sql` para crear las tablas e insertar datos iniciales:
     ```
     mysql -u usuario -p < db_setup.sql
     ```
   
3. Configurar los parámetros de conexión a la base de datos en `config/db.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'rrss_ebone');
   define('DB_USER', 'tu_usuario');
   define('DB_PASS', 'tu_contraseña');
   define('DB_CHARSET', 'utf8mb4');
   ```

4. Asegurarse de que el directorio `uploads/` tenga permisos de escritura:
   ```
   chmod 777 uploads/
   ```

5. Acceder a la aplicación desde el navegador:
   ```
   http://localhost/planificador-rrss/
   ```

## Características

- Gestión de publicaciones para diferentes líneas de negocio
- Cada línea de negocio tiene asociadas sus propias redes sociales
- Creación, edición y eliminación de publicaciones
- Carga de imágenes para las publicaciones
- Filtrado de publicaciones por estado (borrador, programado, publicado)
- Dashboard con estadísticas básicas

## Tecnologías utilizadas

- PHP
- MySQL
- HTML/CSS
- JavaScript
- Font Awesome
- Google Fonts (Open Sans)

## Estructura de archivos

```
planificador-rrss/
├── assets/
│   ├── css/
│   │   └── styles.css
│   ├── js/
│   │   └── main.js
│   └── images/
├── components/
│   ├── dashboard_stats.php
│   └── publicaciones_tabla.php
├── config/
│   └── db.php
├── includes/
│   ├── functions.php
│   ├── header.php
│   └── footer.php
├── uploads/
├── db_setup.sql
├── index.php
├── publicacion_form.php
├── publicacion_delete.php
└── README.md
``` 