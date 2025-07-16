-- ======================================================
-- MIGRACIÓN: Sistema de Autenticación de Administradores
-- Fecha: 2025-01-23
-- Propósito: Implementar login por email/contraseña para múltiples admins
-- ======================================================

-- Crear tabla de administradores
CREATE TABLE admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'superadmin') DEFAULT 'admin',
    activo TINYINT(1) DEFAULT 1,
    ultimo_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_activo (activo),
    INDEX idx_rol (rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear tabla de permisos por línea de negocio (preparación futura)
CREATE TABLE admin_linea_negocio (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    linea_negocio_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
    FOREIGN KEY (linea_negocio_id) REFERENCES lineas_negocio(id) ON DELETE CASCADE,
    UNIQUE KEY unique_admin_linea (admin_id, linea_negocio_id),
    INDEX idx_admin (admin_id),
    INDEX idx_linea_negocio (linea_negocio_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar superadmin inicial
-- Contraseña temporal: admin123! (CAMBIAR INMEDIATAMENTE)
-- Hash generado con password_hash('admin123!', PASSWORD_DEFAULT)
INSERT INTO admins (nombre, email, password_hash, rol, activo) 
VALUES ('Super Administrador', 'admin@ebone.es', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'superadmin', 1);

-- Obtener ID del superadmin creado
SET @superadmin_id = LAST_INSERT_ID();

-- Asignar acceso del superadmin a todas las líneas de negocio existentes
INSERT INTO admin_linea_negocio (admin_id, linea_negocio_id)
SELECT @superadmin_id, id FROM lineas_negocio;

-- ======================================================
-- VERIFICACIÓN DE LA MIGRACIÓN
-- ======================================================

-- Verificar que las tablas se crearon correctamente
SELECT 'Tabla admins creada' AS status, COUNT(*) as registros FROM admins;
SELECT 'Tabla admin_linea_negocio creada' AS status, COUNT(*) as registros FROM admin_linea_negocio;

-- Mostrar el superadmin creado
SELECT 
    'SUPERADMIN CREADO' AS info,
    nombre, 
    email, 
    rol, 
    activo,
    created_at
FROM admins 
WHERE rol = 'superadmin';

-- Mostrar permisos asignados al superadmin
SELECT 
    'PERMISOS ASIGNADOS' AS info,
    a.nombre as admin_nombre,
    a.email as admin_email,
    ln.nombre as linea_negocio,
    ln.slug as linea_slug
FROM admin_linea_negocio aln
JOIN admins a ON aln.admin_id = a.id
JOIN lineas_negocio ln ON aln.linea_negocio_id = ln.id
WHERE a.rol = 'superadmin';

-- ======================================================
-- INFORMACIÓN IMPORTANTE
-- ======================================================

/*
CREDENCIALES INICIALES DEL SUPERADMIN:
- Email: admin@ebone.es
- Contraseña: admin123!

¡IMPORTANTE!
1. Cambiar la contraseña inmediatamente después de la primera conexión
2. El superadmin tiene acceso a todas las líneas de negocio
3. La contraseña temporal es: admin123!

PRÓXIMOS PASOS:
1. Ejecutar este SQL en tu base de datos
2. Verificar que las tablas se crearon correctamente
3. Confirmar que el superadmin puede hacer login con las credenciales temporales
4. Proceder con Task 1.2: Modificar functions.php
*/

-- ======================================================
-- ROLLBACK (SI ES NECESARIO)
-- ======================================================

/*
Si necesitas hacer rollback, ejecuta estos comandos:

DROP TABLE IF EXISTS admin_linea_negocio;
DROP TABLE IF EXISTS admins;

Esto eliminará las tablas y toda la información relacionada.
*/ 