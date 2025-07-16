<?php
// Establecer zona horaria
date_default_timezone_set('Europe/Madrid');

// Iniciar sesión en todas las páginas que incluyan este archivo
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

// ---- Autenticación ----
define('MASTER_PASSWORD_HASH', '$2y$12$CLIuTX.v/JWFu4dsytQvdOZHD/F7m8qREIy88Onb5EVBwXya6a.aq');

/**
 * Verificar si el usuario está autenticado
 * Funciona tanto con sistema nuevo (admins) como con sistema anterior (master password)
 */
function is_authenticated() {
    return isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
}

/**
 * Requiere autenticación para acceder a la página
 * Redirige a login.php si no está autenticado
 */
function require_authentication() {
    if (!is_authenticated()) {
        // Guardar la URL a la que se intentaba acceder para redirigir después del login
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI']; 
        header('Location: login.php');
        exit;
    }
}

/**
 * Autenticar usuario por email y contraseña (sistema nuevo)
 * @param string $email
 * @param string $password
 * @return bool|array False si falla, array con info del usuario si éxito
 */
function authenticate_user($email, $password) {
    $db = getDbConnection();
    try {
        $stmt = $db->prepare("SELECT id, nombre, email, password_hash, rol FROM admins WHERE email = ? AND activo = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Actualizar último login
            $stmt_update = $db->prepare("UPDATE admins SET ultimo_login = NOW() WHERE id = ?");
            $stmt_update->execute([$user['id']]);
            
            // Establecer sesión
            $_SESSION['authenticated'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nombre'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['rol'];
            $_SESSION['auth_method'] = 'user'; // Para distinguir del sistema anterior
            
            return $user;
        }
        return false;
    } catch (PDOException $e) {
        error_log("Error authenticating user: " . $e->getMessage());
        return false;
    }
}

/**
 * Autenticar con contraseña maestra (sistema anterior - compatibilidad temporal)
 * @param string $password
 * @return bool
 */
function authenticate_master_password($password) {
    if (password_verify($password, MASTER_PASSWORD_HASH)) {
        $_SESSION['authenticated'] = true;
        $_SESSION['auth_method'] = 'master'; // Para distinguir del sistema nuevo
        return true;
    }
    return false;
}

/**
 * Obtener información del usuario actual
 * @return array|null
 */
function get_current_admin_user() {
    if (!is_authenticated()) {
        return null;
    }
    
    // Si es autenticación nueva, devolver datos del usuario
    if (isset($_SESSION['auth_method']) && $_SESSION['auth_method'] === 'user') {
        return [
            'id' => $_SESSION['user_id'] ?? null,
            'nombre' => $_SESSION['user_name'] ?? 'Usuario',
            'email' => $_SESSION['user_email'] ?? '',
            'rol' => $_SESSION['user_role'] ?? 'admin',
            'auth_method' => 'user'
        ];
    }
    
    // Si es autenticación anterior, devolver datos genéricos
    return [
        'id' => null,
        'nombre' => 'Administrador',
        'email' => '',
        'rol' => 'admin',
        'auth_method' => 'master'
    ];
}

/**
 * Verificar si el usuario actual es superadmin
 * @return bool
 */
function is_superadmin() {
    $user = get_current_admin_user();
    return $user && $user['rol'] === 'superadmin';
}

/**
 * Verificar si el usuario puede acceder a una línea de negocio específica
 * Por ahora, todos los usuarios autenticados pueden acceder a todo
 * En el futuro se implementará control granular
 * @param int $linea_id
 * @return bool
 */
function user_can_access_linea($linea_id) {
    if (!is_authenticated()) {
        return false;
    }
    
    $user = get_current_admin_user();
    
    // Superadmin puede acceder a todo
    if ($user && $user['rol'] === 'superadmin') {
        return true;
    }
    
    // Sistema anterior: acceso completo
    if ($user && $user['auth_method'] === 'master') {
        return true;
    }
    
    // Por ahora, todos los admins pueden acceder a todo
    // TODO: Implementar control granular en Fase 2
    return true;
}

/**
 * Cerrar sesión de usuario
 */
function logout_user() {
    // Destruir todas las variables de sesión
    $_SESSION = array();
    
    // Destruir la cookie de sesión si existe
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destruir la sesión
    session_destroy();
}

/**
 * Cambiar contraseña del usuario
 * @param int $user_id ID del usuario
 * @param string $current_password Contraseña actual
 * @param string $new_password Nueva contraseña
 * @return bool|string True si éxito, mensaje de error si falla
 */
function change_password($user_id, $current_password, $new_password) {
    // Validar que el usuario ID esté presente
    if (!$user_id) {
        return 'Error: ID de usuario no válido.';
    }
    
    // Validar longitud de nueva contraseña
    if (strlen($new_password) < 6) {
        return 'La nueva contraseña debe tener al menos 6 caracteres.';
    }
    
    $db = getDbConnection();
    try {
        // Obtener la contraseña actual del usuario
        $stmt = $db->prepare("SELECT password_hash FROM admins WHERE id = ? AND activo = 1");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return 'Error: Usuario no encontrado o inactivo.';
        }
        
        // Verificar contraseña actual
        if (!password_verify($current_password, $user['password_hash'])) {
            return 'La contraseña actual es incorrecta.';
        }
        
        // Generar hash de la nueva contraseña
        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Actualizar la contraseña en la base de datos
        $stmt = $db->prepare("UPDATE admins SET password_hash = ?, updated_at = NOW() WHERE id = ?");
        $success = $stmt->execute([$new_password_hash, $user_id]);
        
        if ($success) {
            return true;
        } else {
            return 'Error al actualizar la contraseña en la base de datos.';
        }
        
    } catch (PDOException $e) {
        error_log("Error changing password: " . $e->getMessage());
        return 'Error interno al cambiar la contraseña.';
    }
}

// ---- Fin Autenticación ----

// ---- Gestión de Tokens Compartir ----
function generate_share_token() {
    return bin2hex(random_bytes(32)); // Genera un token seguro de 64 caracteres
}

function get_linea_id_from_token($token) {
    $db = getDbConnection();
    try {
        $stmt = $db->prepare("SELECT linea_negocio_id FROM share_tokens WHERE token = ? AND is_active = TRUE");
        $stmt->execute([$token]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['linea_negocio_id'] : null;
    } catch (PDOException $e) {
        // En un entorno de producción, sería mejor loguear el error que mostrarlo
        error_log("Error validating share token: " . $e->getMessage());
        return null;
    }
}
// ---- Fin Gestión de Tokens Compartir ----

// Función auxiliar para depuración
function debug($data) {
    echo '<pre style="background-color: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 4px; overflow: auto;">';
    print_r($data);
    echo '</pre>';
}

// Obtener todas las líneas de negocio
function getAllLineasNegocio() {
    $db = getDbConnection();
    try {
        $stmt = $db->query("SELECT * FROM lineas_negocio ORDER BY nombre");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        echo '<div style="background-color: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 4px;">';
        echo '<strong>Error al obtener líneas de negocio:</strong><br>';
        echo $e->getMessage();
        echo '</div>';
        return [];
    }
}

// Obtener redes sociales asociadas a una línea de negocio
function getRedesByLinea($lineaId) {
    $db = getDbConnection();
    try {
        $stmt = $db->prepare("
            SELECT r.* 
            FROM redes_sociales r
            JOIN linea_negocio_red_social lnrs ON r.id = lnrs.red_social_id
            WHERE lnrs.linea_negocio_id = ?
            ORDER BY r.nombre
        ");
        $stmt->execute([$lineaId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        echo '<div style="background-color: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 4px;">';
        echo '<strong>Error al obtener redes sociales:</strong><br>';
        echo $e->getMessage();
        echo '</div>';
        return [];
    }
}

// Obtener publicaciones por línea de negocio y estado (opcional)
function getPublicaciones($lineaId, $estado = null) {
    $db = getDbConnection();
    $params = [$lineaId];
    $sql = "
        SELECT p.*, ln.nombre as linea_nombre 
        FROM publicaciones p
        JOIN lineas_negocio ln ON p.linea_negocio_id = ln.id
        WHERE p.linea_negocio_id = ?
    ";
    
    if ($estado) {
        $sql .= " AND p.estado = ?";
        $params[] = $estado;
    }
    
    $sql .= " ORDER BY p.fecha_programada DESC, p.id DESC";
    
    // Mostrar consulta para depuración
    echo '<div style="background-color: #e8f4f8; color: #0c5460; padding: 15px; margin: 10px 0; border-radius: 4px; font-family: monospace;">';
    echo '<strong>SQL Debug:</strong><br>';
    echo $sql . '<br>';
    echo 'Params: ';
    print_r($params);
    echo '</div>';
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetchAll();
        
        // Si no hay resultados, verificar si la línea existe realmente
        if (empty($result)) {
            $checkStmt = $db->prepare("SELECT * FROM lineas_negocio WHERE id = ?");
            $checkStmt->execute([$lineaId]);
            $lineaExists = $checkStmt->fetch();
            
            if (!$lineaExists) {
                echo '<div style="background-color: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 4px;">';
                echo '<strong>Advertencia:</strong> La línea de negocio con ID ' . $lineaId . ' no existe.';
                echo '</div>';
            } else {
                echo '<div style="background-color: #fff3cd; color: #856404; padding: 15px; margin: 10px 0; border-radius: 4px;">';
                echo '<strong>Nota:</strong> No hay publicaciones para esta línea de negocio' . ($estado ? ' con estado "' . $estado . '"' : '') . '.';
                echo '</div>';
            }
        }
        
        return $result;
    } catch (PDOException $e) {
        echo '<div style="background-color: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 4px;">';
        echo '<strong>Error al obtener publicaciones:</strong><br>';
        echo $e->getMessage();
        echo '</div>';
        return [];
    }
}

// Obtener redes sociales seleccionadas para una publicación
function getRedesPublicacion($publicacionId) {
    $db = getDbConnection();
    try {
        $stmt = $db->prepare("
            SELECT r.id
            FROM redes_sociales r
            JOIN publicacion_red_social prs ON r.id = prs.red_social_id
            WHERE prs.publicacion_id = ?
        ");
        $stmt->execute([$publicacionId]);
        $result = $stmt->fetchAll();
        
        // Extraer solo IDs para simplificar el uso
        $redesIds = [];
        foreach ($result as $row) {
            $redesIds[] = $row['id'];
        }
        
        return $redesIds;
    } catch (PDOException $e) {
        echo '<div style="background-color: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 4px;">';
        echo '<strong>Error al obtener redes de publicación:</strong><br>';
        echo $e->getMessage();
        echo '</div>';
        return [];
    }
}

// Sanitizar entradas para prevenir inyección SQL y XSS
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Formatear fecha para mostrar
function formatFecha($fechaDb) {
    if (!$fechaDb) return '';
    $fecha = new DateTime($fechaDb);
    return $fecha->format('d/m/Y H:i');
}

// Truncar texto para previsualizaciones
function truncateText($text, $length = 100) {
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . '...';
}

// ---- Gestión de Imágenes - Optimización de Almacenamiento ----

/**
 * Elimina una imagen de publicación del servidor de forma segura
 * Incluye validaciones, logging y manejo robusto de errores
 * 
 * @param string $imagePath Ruta completa de la imagen a borrar
 * @param string $logContext Contexto adicional para logging (ej: "Publication ID: 123")
 * @return bool true si se borró exitosamente o no existe, false si hubo error
 */
function deletePublicationImage($imagePath, $logContext = '') {
    // Validar entrada
    if (empty($imagePath)) {
        error_log("IMAGE_DELETE_WARNING: Empty image path - {$logContext}");
        return true; // No es error crítico
    }
    
    // Convertir a ruta absoluta si es relativa
    if (!file_exists($imagePath)) {
        // Intentar con ruta relativa desde raíz del proyecto
        $absolutePath = __DIR__ . '/../' . ltrim($imagePath, '/');
        if (file_exists($absolutePath)) {
            $imagePath = $absolutePath;
        } else {
            error_log("IMAGE_DELETE_INFO: Image not found for deletion: {$imagePath} - {$logContext}");
            return true; // No es error crítico si el archivo ya no existe
        }
    }
    
    // Validación de seguridad: verificar que está en directorio permitido
    $allowedDirs = [
        realpath(__DIR__ . '/../uploads/'),
        realpath(__DIR__ . '/../uploads/blog/')
    ];
    
    $realImagePath = realpath($imagePath);
    $isInAllowedDir = false;
    
    foreach ($allowedDirs as $allowedDir) {
        if ($allowedDir && $realImagePath && strpos($realImagePath, $allowedDir) === 0) {
            $isInAllowedDir = true;
            break;
        }
    }
    
    if (!$isInAllowedDir) {
        error_log("IMAGE_DELETE_SECURITY: Attempted to delete file outside allowed directories: {$imagePath} - {$logContext}");
        return false;
    }
    
    // Verificar permisos de escritura
    if (!is_writable($imagePath)) {
        error_log("IMAGE_DELETE_ERROR: Permission denied for image deletion: {$imagePath} - {$logContext}");
        return false;
    }
    
    // Verificar que es un archivo (no directorio)
    if (!is_file($imagePath)) {
        error_log("IMAGE_DELETE_WARNING: Path is not a file: {$imagePath} - {$logContext}");
        return false;
    }
    
    // Intentar borrar el archivo
    if (unlink($imagePath)) {
        error_log("IMAGE_DELETE_SUCCESS: Image deleted successfully: {$imagePath} - {$logContext}");
        return true;
    } else {
        error_log("IMAGE_DELETE_ERROR: Failed to delete image: {$imagePath} - {$logContext}");
        return false;
    }
}

/**
 * Procesa el borrado automático de imagen al cambiar estado a "publicado"
 * Mantiene consistencia entre filesystem y base de datos
 * 
 * @param PDO $db Conexión a la base de datos
 * @param string $table Nombre de la tabla (publicaciones o blog_posts)
 * @param string $imageField Nombre del campo de imagen (imagen_url o imagen_destacada)
 * @param int $id ID del registro
 * @param string $currentImagePath Ruta actual de la imagen
 * @param string $logContext Contexto para logging
 * @return bool true si se procesó correctamente (o no había imagen), false si hubo error
 */
function processImageDeletionOnPublish($db, $table, $imageField, $id, $currentImagePath, $logContext = '') {
    // Si no hay imagen, no hay nada que hacer
    if (empty($currentImagePath)) {
        return true;
    }
    
    try {
        // Comenzar transacción para consistencia
        $db->beginTransaction();
        
        // Intentar borrar el archivo físico
        $deletionSuccess = deletePublicationImage($currentImagePath, $logContext);
        
        if ($deletionSuccess) {
            // Solo actualizar BD si el borrado físico fue exitoso
            $stmt = $db->prepare("UPDATE {$table} SET {$imageField} = NULL WHERE id = ?");
            $updateSuccess = $stmt->execute([$id]);
            
            if ($updateSuccess) {
                $db->commit();
                error_log("IMAGE_DELETION_COMPLETE: Database updated after image deletion - {$logContext}");
                return true;
            } else {
                $db->rollback();
                error_log("IMAGE_DELETION_ERROR: Failed to update database after image deletion - {$logContext}");
                return false;
            }
        } else {
            // Si no se pudo borrar el archivo, no actualizar BD
            $db->rollback();
            error_log("IMAGE_DELETION_SKIPPED: Database not updated due to file deletion failure - {$logContext}");
            return false;
        }
        
    } catch (Exception $e) {
        $db->rollback();
        error_log("IMAGE_DELETION_EXCEPTION: Transaction failed - {$logContext} - Error: " . $e->getMessage());
        return false;
    }
}

// ---- Fin Gestión de Imágenes ---- 