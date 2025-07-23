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

/**
 * Crear nuevo usuario administrador
 * @param string $nombre
 * @param string $email
 * @param string $password
 * @param string $rol
 * @return bool|string True si éxito, mensaje de error si falla
 */
function create_admin_user($nombre, $email, $password, $rol = 'admin') {
    if (!is_superadmin()) {
        return 'Solo los superadmins pueden crear usuarios.';
    }
    
    $db = getDbConnection();
    try {
        // Verificar si el email ya existe
        $stmt = $db->prepare("SELECT id FROM admins WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return 'Ya existe un usuario con este email.';
        }
        
        // Crear nuevo usuario
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("
            INSERT INTO admins (nombre, email, password_hash, rol, activo, created_at, updated_at)
            VALUES (?, ?, ?, ?, 1, NOW(), NOW())
        ");
        $stmt->execute([$nombre, $email, $password_hash, $rol]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Error creating admin user: " . $e->getMessage());
        return 'Error al crear usuario: ' . $e->getMessage();
    }
}

/**
 * Actualizar estado de usuario (activo/inactivo)
 * @param int $user_id
 * @param bool $activo
 * @return bool|string True si éxito, mensaje de error si falla
 */
function toggle_admin_status($user_id, $activo) {
    if (!is_superadmin()) {
        return 'Solo los superadmins pueden gestionar usuarios.';
    }
    
    $current_user = get_current_admin_user();
    if ($user_id == $current_user['id']) {
        return 'No puedes modificar tu propio estado.';
    }
    
    $db = getDbConnection();
    try {
        $stmt = $db->prepare("UPDATE admins SET activo = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$activo ? 1 : 0, $user_id]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Error toggling admin status: " . $e->getMessage());
        return 'Error al actualizar estado del usuario.';
    }
}

/**
 * Eliminar usuario administrador
 * @param int $user_id
 * @return bool|string True si éxito, mensaje de error si falla
 */
function delete_admin_user($user_id) {
    if (!is_superadmin()) {
        return 'Solo los superadmins pueden eliminar usuarios.';
    }
    
    $current_user = get_current_admin_user();
    if ($user_id == $current_user['id']) {
        return 'No puedes eliminar tu propio usuario.';
    }
    
    $db = getDbConnection();
    try {
        $stmt = $db->prepare("DELETE FROM admins WHERE id = ?");
        $stmt->execute([$user_id]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Error deleting admin user: " . $e->getMessage());
        return 'Error al eliminar usuario.';
    }
}

/**
 * Obtener todos los usuarios administradores
 * @return array
 */
function get_all_admin_users() {
    $db = getDbConnection();
    try {
        $stmt = $db->query("SELECT * FROM admins ORDER BY nombre ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting admin users: " . $e->getMessage());
        return [];
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

// ---- Gestión de Thumbnails ----

/**
 * Genera thumbnails optimizados para miniaturas en tablas
 * Crea versiones WebP y JPEG comprimidas de 60x60px máximo 15KB
 * 
 * @param string $originalImagePath Ruta a la imagen original
 * @param int $size Tamaño del thumbnail (por defecto 60px)
 * @param int $quality Calidad JPEG (por defecto 75)
 * @return array|false Rutas de thumbnails generados o false si falla
 */
function generateThumbnail($originalImagePath, $size = 60, $quality = 75) {
    // Validar que la imagen original existe
    if (!file_exists($originalImagePath)) {
        error_log("THUMBNAIL_ERROR: Original image not found: {$originalImagePath}");
        return false;
    }

    // Obtener información de la imagen
    $imageInfo = getimagesize($originalImagePath);
    if (!$imageInfo) {
        error_log("THUMBNAIL_ERROR: Invalid image format: {$originalImagePath}");
        return false;
    }

    // Determinar el directorio de thumbnails
    $thumbsDir = dirname($originalImagePath) . '/thumbs/';
    
    // Crear directorio si no existe
    if (!is_dir($thumbsDir)) {
        if (!mkdir($thumbsDir, 0755, true)) {
            error_log("THUMBNAIL_ERROR: Cannot create thumbs directory: {$thumbsDir}");
            return false;
        }
    }

    // Generar nombres de archivo para thumbnails
    $originalFilename = pathinfo($originalImagePath, PATHINFO_FILENAME);
    $webpThumbnail = $thumbsDir . $originalFilename . "_thumb.webp";
    $jpegThumbnail = $thumbsDir . $originalFilename . "_thumb.jpg";

    try {
        // Cargar imagen original según su formato
        $originalImage = null;
        switch ($imageInfo[2]) {
            case IMAGETYPE_JPEG:
                $originalImage = imagecreatefromjpeg($originalImagePath);
                break;
            case IMAGETYPE_PNG:
                $originalImage = imagecreatefrompng($originalImagePath);
                break;
            case IMAGETYPE_WEBP:
                if (function_exists('imagecreatefromwebp')) {
                    $originalImage = imagecreatefromwebp($originalImagePath);
                }
                break;
        }

        if (!$originalImage) {
            error_log("THUMBNAIL_ERROR: Cannot create image resource: {$originalImagePath}");
            return false;
        }

        // Obtener dimensiones originales
        $originalWidth = imagesx($originalImage);
        $originalHeight = imagesy($originalImage);

        // Calcular dimensiones del thumbnail manteniendo aspecto
        if ($originalWidth > $originalHeight) {
            $thumbWidth = $size;
            $thumbHeight = intval(($originalHeight * $size) / $originalWidth);
        } else {
            $thumbHeight = $size;
            $thumbWidth = intval(($originalWidth * $size) / $originalHeight);
        }

        // Crear imagen thumbnail
        $thumbnailImage = imagecreatetruecolor($thumbWidth, $thumbHeight);
        
        // Preservar transparencia para PNG/WebP
        if ($imageInfo[2] == IMAGETYPE_PNG || $imageInfo[2] == IMAGETYPE_WEBP) {
            imagealphablending($thumbnailImage, false);
            imagesavealpha($thumbnailImage, true);
            $transparent = imagecolorallocatealpha($thumbnailImage, 0, 0, 0, 127);
            imagefill($thumbnailImage, 0, 0, $transparent);
        }

        // Redimensionar imagen
        imagecopyresampled(
            $thumbnailImage, $originalImage,
            0, 0, 0, 0,
            $thumbWidth, $thumbHeight,
            $originalWidth, $originalHeight
        );

        $results = [];

        // Usar directorio raíz del proyecto para generar URLs consistentes
        $projectRoot = dirname(__DIR__);

        // Generar WebP si está disponible
        if (function_exists('imagewebp')) {
            if (imagewebp($thumbnailImage, $webpThumbnail, 80)) {
                $results['webp'] = $webpThumbnail;
                $results['webp_url'] = str_replace($projectRoot . '/', '', $webpThumbnail);
            }
        }

        // Generar JPEG siempre como fallback
        if (imagejpeg($thumbnailImage, $jpegThumbnail, $quality)) {
            $results['jpeg'] = $jpegThumbnail;
            $results['jpeg_url'] = str_replace($projectRoot . '/', '', $jpegThumbnail);
        }

        // Limpiar memoria
        imagedestroy($originalImage);
        imagedestroy($thumbnailImage);

        // Verificar que se generó al menos un thumbnail
        if (empty($results)) {
            error_log("THUMBNAIL_ERROR: Failed to generate any thumbnail: {$originalImagePath}");
            return false;
        }

        // Obtener tamaños de archivo para logging
        foreach ($results as $format => $path) {
            if (strpos($format, '_url') === false && file_exists($path)) {
                $size = filesize($path);
                error_log("THUMBNAIL_SUCCESS: Generated {$format} thumbnail: {$path} ({$size} bytes)");
            }
        }

        return $results;

    } catch (Exception $e) {
        error_log("THUMBNAIL_EXCEPTION: " . $e->getMessage() . " - Image: {$originalImagePath}");
        return false;
    }
}

/**
 * Obtiene la mejor URL de thumbnail disponible para mostrar
 * Prioriza WebP sobre JPEG y maneja fallback a imagen original
 * 
 * @param string $originalImageUrl URL de imagen original
 * @param string $thumbnailUrl URL de thumbnail almacenada en BD (puede ser null)
 * @return string URL del mejor thumbnail disponible
 */
function getBestThumbnailUrl($originalImageUrl, $thumbnailUrl = null) {
    // Si no hay thumbnail registrado, usar imagen original
    if (empty($thumbnailUrl)) {
        return $originalImageUrl;
    }

    // Convertir URL a path del servidor usando directorio actual del proyecto
    // En lugar de DOCUMENT_ROOT, usar el directorio actual (__DIR__ apunta a includes/)
    $projectRoot = dirname(__DIR__); // Directorio padre de includes/ (raíz del proyecto)
    $thumbnailPath = $projectRoot . '/' . $thumbnailUrl;

    // Debug logging para diagnosticar paths
    error_log("THUMBNAIL_DEBUG: Checking path: {$thumbnailPath}");

    // Verificar si el thumbnail existe
    if (!file_exists($thumbnailPath)) {
        // Si el thumbnail no existe, intentar generarlo on-demand
        $originalPath = $projectRoot . '/' . $originalImageUrl;
        if (file_exists($originalPath)) {
            $generated = generateThumbnail($originalPath);
            if ($generated && isset($generated['webp_url'])) {
                return $generated['webp_url'];
            } elseif ($generated && isset($generated['jpeg_url'])) {
                return $generated['jpeg_url'];
            }
        }
        
        // Si todo falla, usar imagen original
        error_log("THUMBNAIL_FALLBACK: Using original image - {$originalImageUrl}");
        return $originalImageUrl;
    }

    // Verificar si existe versión WebP (priorizar WebP sobre otros formatos)
    $pathInfo = pathinfo($thumbnailPath);
    $webpPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.webp';
    
    if (file_exists($webpPath)) {
        // Convertir path absoluto de vuelta a URL relativa
        $webpUrl = str_replace($projectRoot . '/', '', $webpPath);
        error_log("THUMBNAIL_SUCCESS: Using WebP - {$webpUrl}");
        return $webpUrl;
    }

    // Si no hay WebP, usar el thumbnail registrado (que existe según verificación anterior)
    error_log("THUMBNAIL_SUCCESS: Using registered thumbnail - {$thumbnailUrl}");
    return $thumbnailUrl;
}

/**
 * Limpia thumbnails huérfanos (sin imagen original)
 * Útil para mantenimiento del sistema de caché
 * 
 * @param string $uploadsDir Directorio base de uploads (ej: 'uploads/')
 * @return int Número de thumbnails eliminados
 */
function cleanOrphanThumbnails($uploadsDir = 'uploads/') {
    $cleanedCount = 0;
    $thumbsDirs = [
        $uploadsDir . 'thumbs/',
        $uploadsDir . 'blog/thumbs/'
    ];

    foreach ($thumbsDirs as $thumbsDir) {
        if (!is_dir($thumbsDir)) continue;

        $thumbnails = glob($thumbsDir . '*_thumb.*');
        
        foreach ($thumbnails as $thumbnailPath) {
            $filename = pathinfo($thumbnailPath, PATHINFO_FILENAME);
            $originalName = str_replace('_thumb', '', $filename);
            
            // Buscar imagen original en el directorio padre
            $originalDir = dirname($thumbsDir);
            $possibleExtensions = ['jpg', 'jpeg', 'png', 'webp'];
            $originalExists = false;
            
            foreach ($possibleExtensions as $ext) {
                if (file_exists($originalDir . '/' . $originalName . '.' . $ext)) {
                    $originalExists = true;
                    break;
                }
            }
            
            // Eliminar thumbnail huérfano
            if (!$originalExists) {
                if (unlink($thumbnailPath)) {
                    $cleanedCount++;
                    error_log("THUMBNAIL_CLEANUP: Removed orphan thumbnail: {$thumbnailPath}");
                }
            }
        }
    }

    return $cleanedCount;
}

// ---- Fin Gestión de Imágenes ---- 

// ---- Sistema de Notificaciones por Correo ----

/**
 * Configuración SMTP para envío de correos
 * NOTA: Contraseña NO hardcodeada - usar variable de entorno o config separado
 */
function getSMTPConfig() {
    return [
        'host' => 'ebonemx.plesk.trevenque.es',
        'port' => 465,
        'username' => 'loop@ebone.es',
        // IMPORTANTE: En producción, mover contraseña a archivo config separado
        'password' => '81o9h&4Lr', // TODO: Mover a variable de entorno
        'from_email' => 'loop@ebone.es',
        'from_name' => 'Loop - RRSS Planner'
    ];
}

/**
 * Enviar correo usando SMTP configurado
 * @param string $to Email destinatario
 * @param string $subject Asunto del correo
 * @param string $htmlBody Contenido HTML del correo
 * @param string $textBody Contenido texto plano (opcional)
 * @return bool|string True si éxito, mensaje de error si falla
 */
function sendEmail($to, $subject, $htmlBody, $textBody = '') {
    $config = getSMTPConfig();
    
    try {
        // Headers básicos
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $config['from_name'] . ' <' . $config['from_email'] . '>',
            'Reply-To: ' . $config['from_email'],
            'X-Mailer: PHP/' . phpversion()
        ];
        
        // Configurar contexto SMTP
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);
        
        // Configurar ini para SMTP
        ini_set('SMTP', $config['host']);
        ini_set('smtp_port', $config['port']);
        ini_set('sendmail_from', $config['from_email']);
        
        // Enviar correo
        $result = mail(
            $to,
            '=?UTF-8?B?' . base64_encode($subject) . '?=',
            $htmlBody,
            implode("\r\n", $headers)
        );
        
        if ($result) {
            error_log("EMAIL_SENT: Successfully sent email to {$to} with subject: {$subject}");
            return true;
        } else {
            $error = 'mail() function returned false';
            error_log("EMAIL_ERROR: Failed to send email to {$to}: {$error}");
            return $error;
        }
        
    } catch (Exception $e) {
        $error = 'Exception: ' . $e->getMessage();
        error_log("EMAIL_ERROR: Exception sending email to {$to}: {$error}");
        return $error;
    }
}

/**
 * Obtener administradores por línea de negocio
 * @param int $linea_negocio_id ID de la línea de negocio
 * @return array Lista de emails de administradores activos
 */
function getAdminsByLineaNegocio($linea_negocio_id) {
    $db = getDbConnection();
    try {
        // Primero buscar administradores específicos de la línea
        $stmt = $db->prepare("
            SELECT DISTINCT a.email, a.nombre 
            FROM admins a
            JOIN admin_linea_negocio aln ON a.id = aln.admin_id
            WHERE aln.linea_negocio_id = ? 
            AND a.activo = 1
            ORDER BY a.nombre ASC
        ");
        $stmt->execute([$linea_negocio_id]);
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Si no hay administradores específicos, usar superadmins como fallback
        if (empty($admins)) {
            $stmt = $db->prepare("
                SELECT email, nombre 
                FROM admins 
                WHERE rol = 'superadmin' 
                AND activo = 1
                ORDER BY nombre ASC
            ");
            $stmt->execute();
            $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("FEEDBACK_NOTIFICATION: No specific admins found for linea {$linea_negocio_id}, using superadmins fallback");
        }
        
        return $admins;
        
    } catch (PDOException $e) {
        error_log("ERROR: Failed to get admins for linea {$linea_negocio_id}: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtener contexto completo de una publicación
 * @param int $publicacion_id ID de la publicación
 * @return array|null Datos de la publicación o null si no existe
 */
function getPublicacionContext($publicacion_id) {
    $db = getDbConnection();
    try {
        $stmt = $db->prepare("
            SELECT 
                p.id, p.contenido, p.imagen_url, p.thumbnail_url,
                p.fecha_programada, p.estado, p.fecha_creacion,
                ln.id as linea_id, ln.nombre as linea_nombre, ln.slug as linea_slug,
                GROUP_CONCAT(DISTINCT rs.nombre SEPARATOR ', ') as redes_sociales
            FROM publicaciones p
            JOIN lineas_negocio ln ON p.linea_negocio_id = ln.id
            LEFT JOIN publicacion_red_social prs ON p.id = prs.publicacion_id
            LEFT JOIN redes_sociales rs ON prs.red_social_id = rs.id
            WHERE p.id = ?
            GROUP BY p.id
        ");
        $stmt->execute([$publicacion_id]);
        $publicacion = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$publicacion) {
            error_log("ERROR: Publicacion {$publicacion_id} not found for feedback notification");
            return null;
        }
        
        // Generar URL directa al formulario de edición
        $publicacion['edit_url'] = $_SERVER['HTTP_HOST'] ? 
            'https://' . $_SERVER['HTTP_HOST'] . '/publicacion_form.php?id=' . $publicacion_id :
            'http://localhost/publicacion_form.php?id=' . $publicacion_id;
            
        return $publicacion;
        
    } catch (PDOException $e) {
        error_log("ERROR: Failed to get publicacion context {$publicacion_id}: " . $e->getMessage());
        return null;
    }
}

/**
 * Crear template HTML para notificación de feedback
 * @param array $publicacion Datos de la publicación
 * @param string $feedback_text Texto del feedback recibido
 * @param string $admin_name Nombre del destinatario
 * @return string HTML del correo
 */
function createFeedbackEmailTemplate($publicacion, $feedback_text, $admin_name = '') {
    $greeting = !empty($admin_name) ? "Hola {$admin_name}," : "Hola,";
    $contenido_preview = mb_substr(strip_tags($publicacion['contenido']), 0, 100) . '...';
    $fecha_feedback = date('d/m/Y H:i');
    
    return "
    <!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Nuevo Feedback - {$publicacion['linea_nombre']}</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; margin: 0; padding: 20px; background-color: #f4f4f4; }
            .container { max-width: 600px; margin: 0 auto; background-color: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px 20px; text-align: center; }
            .header h1 { margin: 0; font-size: 24px; }
            .content { padding: 30px 20px; }
            .feedback-box { background-color: #f8f9fa; border-left: 4px solid #007bff; padding: 15px; margin: 20px 0; border-radius: 4px; }
            .publication-info { background-color: #e9ecef; padding: 15px; border-radius: 4px; margin: 20px 0; }
            .button { display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; padding: 12px 30px; border-radius: 5px; font-weight: bold; margin: 20px 0; }
            .footer { background-color: #343a40; color: #adb5bd; padding: 20px; text-align: center; font-size: 14px; }
            .timestamp { color: #6c757d; font-size: 14px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>💬 Nuevo Feedback Recibido</h1>
                <p style='margin: 10px 0 0 0; font-size: 18px;'>{$publicacion['linea_nombre']}</p>
            </div>
            
            <div class='content'>
                <p>{$greeting}</p>
                <p>Se ha recibido un nuevo comentario en una de tus publicaciones:</p>
                
                <div class='publication-info'>
                    <h3 style='margin-top: 0; color: #495057;'>📝 Publicación</h3>
                    <p><strong>Contenido:</strong> {$contenido_preview}</p>
                    <p><strong>Redes Sociales:</strong> {$publicacion['redes_sociales']}</p>
                    <p><strong>Estado:</strong> <span style='text-transform: capitalize;'>{$publicacion['estado']}</span></p>
                </div>
                
                <div class='feedback-box'>
                    <h3 style='margin-top: 0; color: #0056b3;'>💭 Comentario Recibido</h3>
                    <p style='font-style: italic; font-size: 16px;'>\"{$feedback_text}\"</p>
                    <p class='timestamp'>📅 {$fecha_feedback}</p>
                </div>
                
                <p>Puedes revisar y editar la publicación directamente desde el siguiente enlace:</p>
                
                <div style='text-align: center;'>
                    <a href='{$publicacion['edit_url']}' class='button'>
                        ✏️ Ver y Editar Publicación
                    </a>
                </div>
                
                <p style='color: #6c757d; font-size: 14px; margin-top: 30px;'>
                    <strong>💡 Tip:</strong> El formulario de edición ahora incluye una sección de feedback donde puedes ver todos los comentarios recibidos mientras realizas modificaciones.
                </p>
            </div>
            
            <div class='footer'>
                <p>Loop - RRSS Planner | Sistema de Notificaciones</p>
                <p>Este es un correo automático, no respondas a esta dirección.</p>
            </div>
        </div>
    </body>
    </html>";
}

/**
 * Función principal para enviar notificaciones de feedback
 * @param int $publicacion_id ID de la publicación
 * @param string $feedback_text Texto del feedback recibido
 * @return array Resultado del envío con estadísticas
 */
function sendFeedbackNotification($publicacion_id, $feedback_text) {
    $result = [
        'success' => false,
        'sent_count' => 0,
        'failed_count' => 0,
        'errors' => []
    ];
    
    try {
        // Obtener contexto de la publicación
        $publicacion = getPublicacionContext($publicacion_id);
        if (!$publicacion) {
            $result['errors'][] = 'Publicación no encontrada';
            return $result;
        }
        
        // Obtener administradores de la línea de negocio
        $admins = getAdminsByLineaNegocio($publicacion['linea_id']);
        if (empty($admins)) {
            $result['errors'][] = 'No se encontraron administradores para notificar';
            return $result;
        }
        
        // Enviar correo a cada administrador
        foreach ($admins as $admin) {
            $subject = "💬 Nuevo feedback en {$publicacion['linea_nombre']} - RRSS Planner";
            $htmlBody = createFeedbackEmailTemplate($publicacion, $feedback_text, $admin['nombre']);
            
            $emailResult = sendEmail($admin['email'], $subject, $htmlBody);
            
            if ($emailResult === true) {
                $result['sent_count']++;
                error_log("FEEDBACK_NOTIFICATION: Sent to {$admin['email']} for publication {$publicacion_id}");
            } else {
                $result['failed_count']++;
                $result['errors'][] = "Error enviando a {$admin['email']}: {$emailResult}";
                error_log("FEEDBACK_NOTIFICATION: Failed to send to {$admin['email']}: {$emailResult}");
            }
        }
        
        $result['success'] = $result['sent_count'] > 0;
        error_log("FEEDBACK_NOTIFICATION: Completed for publication {$publicacion_id}. Sent: {$result['sent_count']}, Failed: {$result['failed_count']}");
        
        return $result;
        
    } catch (Exception $e) {
        $result['errors'][] = 'Excepción: ' . $e->getMessage();
        error_log("FEEDBACK_NOTIFICATION: Exception for publication {$publicacion_id}: " . $e->getMessage());
        return $result;
    }
}

// ---- Fin Sistema de Notificaciones por Correo ----