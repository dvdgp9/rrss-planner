<?php
require_once 'includes/functions.php';
require_once 'includes/WordPressAPI.php';
require_authentication();

// Verificar que solo superadmins puedan acceder
if (!is_superadmin()) {
    header('Location: index.php');
    exit;
}

// Inicialización de DB y usuario actual antes de cualquier manejador
$db = getDbConnection();
$current_user = get_current_admin_user();

// Mensajes de feedback
$message = '';
$message_type = '';

// Editar usuario existente (nombre, email, contraseña opcional, rol)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_user') {
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $rol = $_POST['rol'] ?? null;

    if (!$user_id) {
        $message = 'Usuario no válido';
        $message_type = 'error';
    } elseif (empty($nombre) || empty($email)) {
        $message = 'Nombre y email son obligatorios';
        $message_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'El email no es válido';
        $message_type = 'error';
    } elseif ($password !== '' && strlen($password) < 6) {
        $message = 'La contraseña debe tener al menos 6 caracteres';
        $message_type = 'error';
    } elseif (!in_array($rol, ['admin', 'superadmin'], true)) {
        $message = 'Rol no válido';
        $message_type = 'error';
    } else {
        try {
            // Obtener rol actual del usuario objetivo
            $stmt_curr = $db->prepare("SELECT rol, activo FROM admins WHERE id = ?");
            $stmt_curr->execute([$user_id]);
            $targetUser = $stmt_curr->fetch(PDO::FETCH_ASSOC);
            if (!$targetUser) {
                $message = 'Usuario no encontrado';
                $message_type = 'error';
            } else {
                $can_proceed = true;
                // Si se intenta bajar de superadmin a admin, asegurar que queda al menos un superadmin activo
                if ($targetUser['rol'] === 'superadmin' && $rol === 'admin') {
                    $stmt_count = $db->prepare("SELECT COUNT(*) AS c FROM admins WHERE rol = 'superadmin' AND activo = 1 AND id <> ?");
                    $stmt_count->execute([$user_id]);
                    $row = $stmt_count->fetch(PDO::FETCH_ASSOC);
                    if ((int)$row['c'] === 0) {
                        $message = 'Debe quedar al menos un superadministrador activo.';
                        $message_type = 'error';
                        $can_proceed = false;
                    }
                }

                if ($can_proceed) {
                    // Comprobar que el email no está usado por otro usuario
                    $stmt = $db->prepare("SELECT id FROM admins WHERE email = ? AND id <> ?");
                    $stmt->execute([$email, $user_id]);
                    if ($stmt->fetch()) {
                        $message = 'Ya existe otro usuario con este email';
                        $message_type = 'error';
                    } else {
                        if ($password !== '') {
                            $password_hash = password_hash($password, PASSWORD_DEFAULT);
                            $stmt_upd = $db->prepare("UPDATE admins SET nombre = ?, email = ?, password_hash = ?, rol = ?, updated_at = NOW() WHERE id = ?");
                            $stmt_upd->execute([$nombre, $email, $password_hash, $rol, $user_id]);
                        } else {
                            $stmt_upd = $db->prepare("UPDATE admins SET nombre = ?, email = ?, rol = ?, updated_at = NOW() WHERE id = ?");
                            $stmt_upd->execute([$nombre, $email, $rol, $user_id]);
                        }
                        $message = 'Usuario actualizado correctamente';
                        $message_type = 'success';
                    }
                }
            }
        } catch (PDOException $e) {
            $message = 'Error al actualizar usuario: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
    $active_tab = 'usuarios';
}

// Determinar tab activo si no fue seteado por POST
if (!isset($active_tab) || $active_tab === '') {
    $active_tab = $_GET['tab'] ?? 'wordpress';
}

// === PROCESAMIENTO DE WORDPRESS ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_wordpress') {
    $linea_id = filter_input(INPUT_POST, 'linea_id', FILTER_VALIDATE_INT);
    $wordpress_url = trim($_POST['wordpress_url'] ?? '');
    $wordpress_username = trim($_POST['wordpress_username'] ?? '');
    $wordpress_app_password = trim($_POST['wordpress_app_password'] ?? '');
    $wordpress_enabled = isset($_POST['wordpress_enabled']) ? 1 : 0;
    
    if ($linea_id) {
        try {
            // Test connection if credentials provided
            if ($wordpress_enabled && $wordpress_url && $wordpress_username && $wordpress_app_password) {
                $wp_api = new WordPressAPI($wordpress_url, $wordpress_username, $wordpress_app_password);
                $test_result = $wp_api->testConnection();
                
                if (!$test_result['success']) {
                    $message = 'Error de conexión con WordPress: ' . $test_result['error'];
                    $message_type = 'error';
                } else {
                    // Connection successful, update database
                    $stmt = $db->prepare("
                        UPDATE lineas_negocio 
                        SET wordpress_url = ?, wordpress_username = ?, wordpress_app_password = ?, wordpress_enabled = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$wordpress_url, $wordpress_username, $wordpress_app_password, $wordpress_enabled, $linea_id]);
                    
                    $message = 'Configuración de WordPress actualizada exitosamente';
                    $message_type = 'success';
                }
            } else {
                // Just update without testing (for disabling)
                $stmt = $db->prepare("
                    UPDATE lineas_negocio 
                    SET wordpress_url = ?, wordpress_username = ?, wordpress_app_password = ?, wordpress_enabled = ?
                    WHERE id = ?
                ");
                $stmt->execute([$wordpress_url, $wordpress_username, $wordpress_app_password, $wordpress_enabled, $linea_id]);
                
                $message = 'Configuración actualizada correctamente';
                $message_type = 'success';
            }
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// === PROCESAMIENTO DE USUARIOS ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_user') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $rol = $_POST['rol'] ?? 'admin';
    
    if (empty($nombre) || empty($email) || empty($password)) {
        $message = 'Todos los campos son obligatorios';
        $message_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'El email no es válido';
        $message_type = 'error';
    } elseif (strlen($password) < 6) {
        $message = 'La contraseña debe tener al menos 6 caracteres';
        $message_type = 'error';
    } else {
        try {
            // Verificar si el email ya existe
            $stmt = $db->prepare("SELECT id FROM admins WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $message = 'Ya existe un usuario con este email';
                $message_type = 'error';
            } else {
                // Crear nuevo usuario
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("
                    INSERT INTO admins (nombre, email, password_hash, rol, activo, created_at, updated_at)
                    VALUES (?, ?, ?, ?, 1, NOW(), NOW())
                ");
                $stmt->execute([$nombre, $email, $password_hash, $rol]);
                
                $message = 'Usuario creado exitosamente';
                $message_type = 'success';
            }
        } catch (PDOException $e) {
            $message = 'Error al crear usuario: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
    $active_tab = 'usuarios';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_user') {
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $activo = filter_input(INPUT_POST, 'activo', FILTER_VALIDATE_INT);
    
    if ($user_id && $user_id != $current_user['id']) { // No puede desactivarse a sí mismo
        try {
            $stmt = $db->prepare("UPDATE admins SET activo = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$activo, $user_id]);
            
            $message = $activo ? 'Usuario activado correctamente' : 'Usuario desactivado correctamente';
            $message_type = 'success';
        } catch (PDOException $e) {
            $message = 'Error al actualizar usuario: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
    $active_tab = 'usuarios';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_user') {
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    
    if ($user_id && $user_id != $current_user['id']) { // No puede eliminarse a sí mismo
        try {
            $stmt = $db->prepare("DELETE FROM admins WHERE id = ?");
            $stmt->execute([$user_id]);
            
            $message = 'Usuario eliminado correctamente';
            $message_type = 'success';
        } catch (PDOException $e) {
            $message = 'Error al eliminar usuario: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
    $active_tab = 'usuarios';
}

// Obtener líneas de negocio para WordPress
$stmt = $db->query("SELECT * FROM lineas_negocio ORDER BY nombre ASC");
$lineas_negocio = $stmt->fetchAll();

// Obtener usuarios para gestión
$stmt = $db->query("SELECT * FROM admins ORDER BY nombre ASC");
$usuarios = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - Lööp</title>
    <link rel="icon" type="image/png" href="assets/images/logos/Loop-favicon.png">
    <!-- Google Fonts - Open Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Estilos propios -->
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="app-simple">
        <?php require 'includes/nav.php'; ?>
        
        <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 20px;">
            <!-- Header profesional -->
            <div class="config-header">
                <div class="config-header-content">
                    <div class="config-header-avatar">
                        <i class="fas fa-cog"></i>
                    </div>
                    <div class="config-header-info">
                        <h1 class="config-header-title">Configuración</h1>
                        <p class="config-header-subtitle">Gestiona las conexiones de WordPress y los usuarios del sistema</p>
                        <div class="config-header-meta">
                            <span class="config-status">
                                <i class="fas fa-shield-alt"></i>
                                Solo Super Administradores
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if ($message): ?>
                <div class="config-notification <?php echo $message_type === 'success' ? 'notification-success' : 'notification-error'; ?>">
                    <div class="notification-icon">
                        <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                    </div>
                    <div class="notification-content">
                        <strong><?php echo $message_type === 'success' ? '¡Éxito!' : 'Error'; ?></strong>
                        <p><?php echo htmlspecialchars($message); ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Sistema de tabs -->
            <div class="config-tabs">
                <div class="tabs-nav">
                    <button class="tab-button <?php echo $active_tab === 'wordpress' ? 'active' : ''; ?>" 
                            onclick="switchTab('wordpress')">
                        <i class="fab fa-wordpress"></i>
                        Conexiones WordPress
                    </button>
                    <button class="tab-button <?php echo $active_tab === 'usuarios' ? 'active' : ''; ?>" 
                            onclick="switchTab('usuarios')">
                        <i class="fas fa-users"></i>
                        Gestión de Usuarios
                    </button>
                    <button class="tab-button <?php echo $active_tab === 'notificaciones' ? 'active' : ''; ?>" 
                            onclick="switchTab('notificaciones')">
                        <i class="fas fa-bell"></i>
                        Notificaciones
                    </button>
                </div>
                
                <!-- Tab: Conexiones WordPress -->
                <div class="tab-content <?php echo $active_tab === 'wordpress' ? 'active' : ''; ?>" id="tab-wordpress">
                    <div class="tab-description">
                        <h2><i class="fab fa-wordpress"></i> Conexiones WordPress</h2>
                        <p>Configura las credenciales de WordPress para publicar automáticamente en cada línea de negocio</p>
                    </div>
                    
                    <div class="wordpress-cards">
                        <?php foreach ($lineas_negocio as $linea): ?>
                            <div class="wordpress-card">
                                <div class="dashboard-card">
                                    <div class="card-header">
                                        <h2>
                                            <img src="assets/images/logos/<?php echo htmlspecialchars($linea['logo_filename'] ?: 'default.png'); ?>" 
                                                 alt="<?php echo htmlspecialchars($linea['nombre']); ?>"
                                                 style="width: 24px; height: 24px; border-radius: 4px; margin-right: 10px;">
                                            <?php echo htmlspecialchars($linea['nombre']); ?>
                                        </h2>
                                        <div class="icon">
                                            <span class="wp-status-badge <?php echo $linea['wordpress_enabled'] ? 'enabled' : 'disabled'; ?>">
                                                <?php echo $linea['wordpress_enabled'] ? 'Habilitado' : 'Deshabilitado'; ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST" class="wordpress-form">
                                            <input type="hidden" name="action" value="save_wordpress">
                                            <input type="hidden" name="linea_id" value="<?php echo $linea['id']; ?>">
                                            
                                            <div class="form-group">
                                                <label class="toggle-label">
                                                    <input type="checkbox" 
                                                           name="wordpress_enabled" 
                                                           <?php echo $linea['wordpress_enabled'] ? 'checked' : ''; ?>>
                                                    <span class="toggle-switch"></span>
                                                    Habilitar publicación en WordPress
                                                </label>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label>
                                                    <i class="fas fa-link"></i>
                                                    URL del sitio WordPress
                                                </label>
                                                <input type="url" 
                                                       name="wordpress_url" 
                                                       value="<?php echo htmlspecialchars($linea['wordpress_url'] ?? ''); ?>"
                                                       class="form-control"
                                                       placeholder="https://ejemplo.com/">
                                            </div>
                                            
                                            <div class="form-group">
                                                <label>
                                                    <i class="fas fa-user"></i>
                                                    Nombre de usuario
                                                </label>
                                                <input type="text" 
                                                       name="wordpress_username" 
                                                       value="<?php echo htmlspecialchars($linea['wordpress_username'] ?? ''); ?>"
                                                       class="form-control"
                                                       placeholder="admin">
                                            </div>
                                            
                                            <div class="form-group">
                                                <label>
                                                    <i class="fas fa-key"></i>
                                                    Application Password
                                                </label>
                                                <input type="password" 
                                                       name="wordpress_app_password" 
                                                       value="<?php echo htmlspecialchars($linea['wordpress_app_password'] ?? ''); ?>"
                                                       class="form-control"
                                                       placeholder="xxxx xxxx xxxx xxxx xxxx xxxx">
                                            </div>
                                            
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save"></i> 
                                                Guardar Configuración
                                            </button>
                                            
                                            <?php if ($linea['wordpress_last_sync']): ?>
                                                <p class="last-sync">
                                                    <i class="fas fa-clock"></i> 
                                                    Última sincronización: <?php echo date('d/m/Y H:i', strtotime($linea['wordpress_last_sync'])); ?>
                                                </p>
                                            <?php endif; ?>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="wordpress-help">
                        <h3><i class="fas fa-question-circle"></i> Cómo configurar Application Passwords</h3>
                        <p>Para habilitar la publicación automática en WordPress, necesitas crear un Application Password:</p>
                        <ol>
                            <li>Ve a tu sitio WordPress → Usuarios → Tu Perfil</li>
                            <li>Desplázate hasta la sección "Application Passwords"</li>
                            <li>Ingresa un nombre para la aplicación (ej: "Lööp Planner")</li>
                            <li>Haz clic en "Add New Application Password"</li>
                            <li>Copia la contraseña generada y pégala en el campo de arriba</li>
                        </ol>
                        <p><strong>Nota:</strong> La contraseña solo se muestra una vez, guárdala en un lugar seguro.</p>
                    </div>
                </div>
                
                <!-- Tab: Gestión de Usuarios -->
                <div class="tab-content <?php echo $active_tab === 'usuarios' ? 'active' : ''; ?>" id="tab-usuarios">
                    <div class="tab-description">
                        <h2><i class="fas fa-users"></i> Gestión de Usuarios</h2>
                        <p>Administra los usuarios del sistema, crea nuevos administradores y gestiona permisos</p>
                    </div>
                    
                    <div class="users-section">
                        <!-- Crear nuevo usuario -->
                        <div class="create-user-card">
                            <div class="dashboard-card">
                                <div class="card-header">
                                    <h2>
                                        <i class="fas fa-user-plus"></i>
                                        Crear Nuevo Usuario
                                    </h2>
                                    <div class="icon">
                                        <i class="fas fa-plus"></i>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <form method="POST" class="user-form">
                                        <input type="hidden" name="action" value="create_user">
                                        
                                        <div class="user-form-row">
                                            <div class="form-group">
                                                <label>
                                                    <i class="fas fa-user"></i>
                                                    Nombre completo
                                                </label>
                                                <input type="text" 
                                                       name="nombre" 
                                                       class="form-control"
                                                       placeholder="Nombre del usuario"
                                                       required>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label>
                                                    <i class="fas fa-envelope"></i>
                                                    Email
                                                </label>
                                                <input type="email" 
                                                       name="email" 
                                                       class="form-control"
                                                       placeholder="email@ejemplo.com"
                                                       required>
                                            </div>
                                        </div>
                                        
                                        <div class="user-form-row">
                                            <div class="form-group">
                                                <label>
                                                    <i class="fas fa-lock"></i>
                                                    Contraseña
                                                </label>
                                                <input type="password" 
                                                       name="password" 
                                                       class="form-control"
                                                       placeholder="Mínimo 6 caracteres"
                                                       minlength="6"
                                                       required>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label>
                                                    <i class="fas fa-shield-alt"></i>
                                                    Rol
                                                </label>
                                                <select name="rol" class="form-control">
                                                    <option value="admin">Administrador</option>
                                                    <option value="superadmin">Super Administrador</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-user-plus"></i> 
                                            Crear Usuario
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Lista de usuarios -->
                        <div class="users-list">
                            <h3><i class="fas fa-list"></i> Usuarios Existentes</h3>
                            <div class="users-grid">
                                <?php foreach ($usuarios as $usuario): ?>
                                    <div class="user-card">
                                        <div class="user-card-header">
                                            <div class="user-avatar">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <div class="user-info">
                                                <h4><?php echo htmlspecialchars($usuario['nombre']); ?></h4>
                                                <p><?php echo htmlspecialchars($usuario['email']); ?></p>
                                                <span class="role-badge role-<?php echo $usuario['rol']; ?>">
                                                    <i class="fas fa-<?php echo $usuario['rol'] === 'superadmin' ? 'crown' : 'user-cog'; ?>"></i>
                                                    <?php echo $usuario['rol'] === 'superadmin' ? 'Super Admin' : 'Admin'; ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="user-card-body">
                                            <div class="user-status">
                                                <span class="status-badge <?php echo $usuario['activo'] ? 'status-active' : 'status-inactive'; ?>">
                                                    <i class="fas fa-circle"></i>
                                                    <?php echo $usuario['activo'] ? 'Activo' : 'Inactivo'; ?>
                                                </span>
                                            </div>
                                            <div class="user-meta">
                                                <small>
                                                    <i class="fas fa-calendar"></i>
                                                    Creado: <?php echo date('d/m/Y', strtotime($usuario['created_at'])); ?>
                                                </small>
                                                <?php if ($usuario['ultimo_login']): ?>
                                                    <small>
                                                        <i class="fas fa-clock"></i>
                                                        Último acceso: <?php echo date('d/m/Y H:i', strtotime($usuario['ultimo_login'])); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="user-card-actions">
                                            <button type="button" 
                                                    class="btn btn-sm btn-primary btn-edit-usuario"
                                                    data-user-id="<?php echo $usuario['id']; ?>"
                                                    data-nombre="<?php echo htmlspecialchars($usuario['nombre']); ?>"
                                                    data-email="<?php echo htmlspecialchars($usuario['email']); ?>"
                                                    data-rol="<?php echo htmlspecialchars($usuario['rol']); ?>">
                                                <i class="fas fa-edit"></i>
                                                Editar
                                            </button>
                                            <?php if ($usuario['id'] != $current_user['id']): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="toggle_user">
                                                    <input type="hidden" name="user_id" value="<?php echo $usuario['id']; ?>">
                                                    <input type="hidden" name="activo" value="<?php echo $usuario['activo'] ? 0 : 1; ?>">
                                                    <button type="submit" class="btn btn-sm btn-secondary">
                                                        <i class="fas fa-<?php echo $usuario['activo'] ? 'pause' : 'play'; ?>"></i>
                                                        <?php echo $usuario['activo'] ? 'Desactivar' : 'Activar'; ?>
                                                    </button>
                                                </form>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('¿Estás seguro de eliminar este usuario?');">
                                                    <input type="hidden" name="action" value="delete_user">
                                                    <input type="hidden" name="user_id" value="<?php echo $usuario['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                        Eliminar
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="current-user-badge">
                                                    <i class="fas fa-user-check"></i>
                                                    Usuario actual
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal: Editar Usuario -->
                <div id="modalEditarUsuario" class="modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2>Editar Usuario</h2>
                            <span class="close-button" id="closeEditarUsuario">&times;</span>
                        </div>
                        <div class="modal-body">
                            <form method="POST" id="formEditarUsuario">
                                <input type="hidden" name="action" value="update_user">
                                <input type="hidden" name="user_id" id="editUserId" value="">

                                <div class="form-group">
                                    <label for="editNombre"><i class="fas fa-user"></i> Nombre completo</label>
                                    <input type="text" id="editNombre" name="nombre" class="form-control" required>
                                </div>

                                <div class="form-group">
                                    <label for="editEmail"><i class="fas fa-envelope"></i> Email</label>
                                    <input type="email" id="editEmail" name="email" class="form-control" required>
                                </div>

                                <div class="form-group">
                                    <label for="editRol"><i class="fas fa-shield-alt"></i> Rol</label>
                                    <select id="editRol" name="rol" class="form-control">
                                        <option value="admin">Administrador</option>
                                        <option value="superadmin">Super Administrador</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="editPassword"><i class="fas fa-lock"></i> Nueva contraseña (opcional)</label>
                                    <input type="password" id="editPassword" name="password" class="form-control" placeholder="Dejar en blanco para no cambiar">
                                </div>

                                <div class="form-actions">
                                    <button type="button" class="btn btn-secondary" id="cancelEditarUsuario">Cancelar</button>
                                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- Fin Modal: Editar Usuario -->
                <!-- Tab: Notificaciones -->
                <div class="tab-content <?php echo $active_tab === 'notificaciones' ? 'active' : ''; ?>" id="tab-notificaciones">
                    <div class="tab-description">
                        <h2><i class="fas fa-bell"></i> Notificaciones por Correo</h2>
                        <p>Configura las notificaciones automáticas que se envían cuando se recibe feedback en las publicaciones</p>
                    </div>
                    
                    <div class="notifications-section">
                        <!-- Configuración Global -->
                        <div class="config-card">
                            <div class="card-header">
                                <h3><i class="fas fa-cog"></i> Configuración Global</h3>
                                <p>Configuración general del sistema de notificaciones</p>
                            </div>
                            <div class="card-content">
                                <div class="config-row">
                                    <div class="config-item">
                                        <label><strong>Servidor SMTP:</strong></label>
                                        <span><?php $config = getSMTPConfig(); echo $config['host'] . ':' . $config['port']; ?></span>
                                    </div>
                                    <div class="config-item">
                                        <label><strong>Remitente:</strong></label>
                                        <span><?php echo $config['from_email']; ?></span>
                                    </div>
                                </div>
                                <div class="config-row">
                                    <div class="config-item">
                                        <label><strong>Configuración:</strong></label>
                                        <span><?php 
                                        if (getenv('SMTP_HOST')) {
                                            echo 'Variables de entorno del servidor ✅';
                                        } elseif (file_exists(__DIR__ . '/config/smtp.php')) {
                                            echo 'Archivo config/smtp.php ✅';
                                        } else {
                                            echo 'Configuración por defecto ⚠️';
                                        }
                                        ?></span>
                                    </div>
                                    <div class="config-item">
                                        <label><strong>Credenciales:</strong></label>
                                        <span>Protegidas (no hardcodeadas) 🔒</span>
                                    </div>
                                </div>
                                <div class="config-status">
                                    <i class="fas fa-check-circle text-success"></i>
                                    <span>Sistema de correo configurado y operativo</span>
                                </div>
                            </div>
                        </div>

                        <!-- Estado de Notificaciones por Línea -->
                        <div class="config-card">
                            <div class="card-header">
                                <h3><i class="fas fa-business-time"></i> Estado por Línea de Negocio</h3>
                                <p>Supervisa qué administradores reciben notificaciones para cada línea</p>
                            </div>
                            <div class="card-content">
                                <?php 
                                foreach ($lineas_negocio as $linea): 
                                    // Obtener administradores de esta línea
                                    $stmt_admins = $db->prepare("
                                        SELECT DISTINCT a.nombre, a.email, a.rol
                                        FROM admins a
                                        JOIN admin_linea_negocio aln ON a.id = aln.admin_id
                                        WHERE aln.linea_negocio_id = ? AND a.activo = 1
                                        ORDER BY a.nombre ASC
                                    ");
                                    $stmt_admins->execute([$linea['id']]);
                                    $linea_admins = $stmt_admins->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    // Si no hay admins específicos, mostrar superadmins
                                    if (empty($linea_admins)) {
                                        $stmt_superadmins = $db->prepare("
                                            SELECT nombre, email, rol
                                            FROM admins 
                                            WHERE rol = 'superadmin' AND activo = 1
                                            ORDER BY nombre ASC
                                        ");
                                        $stmt_superadmins->execute();
                                        $linea_admins = $stmt_superadmins->fetchAll(PDO::FETCH_ASSOC);
                                    }
                                ?>
                                    <div class="linea-notification-card">
                                        <div class="linea-header">
                                            <div class="linea-info">
                                                <img src="assets/images/logos/<?php echo htmlspecialchars($linea['logo_filename'] ?: 'default.png'); ?>" 
                                                     alt="<?php echo htmlspecialchars($linea['nombre']); ?>"
                                                     class="linea-logo">
                                                <div>
                                                    <h4><?php echo htmlspecialchars($linea['nombre']); ?></h4>
                                                    <p class="linea-slug">Slug: <?php echo htmlspecialchars($linea['slug']); ?></p>
                                                </div>
                                            </div>
                                            <div class="notification-status">
                                                <span class="status-badge active">
                                                    <i class="fas fa-bell"></i>
                                                    Activas
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="admins-list">
                                            <h5><i class="fas fa-users"></i> Administradores que reciben notificaciones:</h5>
                                            <?php if (!empty($linea_admins)): ?>
                                                <div class="admin-tags">
                                                    <?php foreach ($linea_admins as $admin): ?>
                                                        <span class="admin-tag <?php echo $admin['rol']; ?>">
                                                            <i class="fas fa-user"></i>
                                                            <?php echo htmlspecialchars($admin['nombre']); ?>
                                                            <small>(<?php echo htmlspecialchars($admin['email']); ?>)</small>
                                                        </span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <p class="no-admins">
                                                    <i class="fas fa-exclamation-triangle text-warning"></i>
                                                    No hay administradores asignados a esta línea
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Estadísticas de Notificaciones -->
                        <div class="config-card">
                            <div class="card-header">
                                <h3><i class="fas fa-chart-bar"></i> Estadísticas de Notificaciones</h3>
                                <p>Información sobre el envío de notificaciones (últimas 24 horas)</p>
                            </div>
                            <div class="card-content">
                                <div class="stats-grid">
                                    <div class="stat-item">
                                        <div class="stat-icon success">
                                            <i class="fas fa-paper-plane"></i>
                                        </div>
                                        <div class="stat-content">
                                            <h4>Sistema Operativo</h4>
                                            <p>Las notificaciones se envían automáticamente</p>
                                        </div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-icon info">
                                            <i class="fas fa-info-circle"></i>
                                        </div>
                                        <div class="stat-content">
                                            <h4>Configuración Automática</h4>
                                            <p>Los destinatarios se determinan por línea de negocio</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="info-box">
                                    <div class="info-icon">
                                        <i class="fas fa-lightbulb"></i>
                                    </div>
                                    <div class="info-content">
                                        <h5>¿Cómo funcionan las notificaciones?</h5>
                                        <ul>
                                            <li>Se envían automáticamente cuando alguien deja feedback en una publicación</li>
                                            <li>Los destinatarios son los administradores asignados a cada línea de negocio</li>
                                            <li>Si no hay administradores específicos, se envían a todos los superadmins</li>
                                            <li>El correo incluye un enlace directo para editar la publicación</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Funcionalidad del sistema de tabs
        function switchTab(tabName) {
            // Ocultar todos los contenidos
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Desactivar todos los botones
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });
            
            // Activar el tab seleccionado
            document.getElementById('tab-' + tabName).classList.add('active');
            document.querySelector(`[onclick="switchTab('${tabName}')"]`).classList.add('active');
            
            // Actualizar URL sin recargar página
            const url = new URL(window.location);
            url.searchParams.set('tab', tabName);
            window.history.replaceState({}, '', url);
        }
        
        // Animaciones y efectos
        document.addEventListener('DOMContentLoaded', function() {
            // Animación de entrada para notificaciones
            const notifications = document.querySelectorAll('.config-notification');
            notifications.forEach(notification => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateY(-20px)';
                
                setTimeout(() => {
                    notification.style.transition = 'all 0.5s ease';
                    notification.style.opacity = '1';
                    notification.style.transform = 'translateY(0)';
                }, 100);
            });
            
            // Auto-hide de notificaciones de éxito
            const successNotifications = document.querySelectorAll('.notification-success');
            successNotifications.forEach(notification => {
                setTimeout(() => {
                    notification.style.transition = 'all 0.5s ease';
                    notification.style.opacity = '0';
                    notification.style.transform = 'translateY(-20px)';
                    
                    setTimeout(() => {
                        notification.style.display = 'none';
                    }, 500);
                }, 4000);
            });
            
            // Efectos hover para cards
            const cards = document.querySelectorAll('.dashboard-card, .user-card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>

    <!-- Estilos específicos para el tab de notificaciones -->
    <style>
        .notifications-section {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .config-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .config-card:hover {
            transform: translateY(-2px);
        }

        .config-card .card-header {
            background: linear-gradient(135deg, rgba(97, 27, 70, 1) 30%, rgba(227, 117, 0, 1) 100%);
            color: white;
            padding: 20px;
        }

        .config-card .card-header h3 {
            margin: 0 0 8px 0;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.3rem;
        }

        .config-card .card-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 0.95rem;
        }

        .config-card .card-content {
            padding: 25px;
        }

        .config-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .config-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .config-item label {
            color: #495057;
            font-size: 0.9rem;
        }

        .config-item span {
            color: #007bff;
            font-family: 'Courier New', monospace;
            background: #f8f9fa;
            padding: 8px 12px;
            border-radius: 4px;
            border: 1px solid #e9ecef;
        }

        .config-status {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px;
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 6px;
            color: #155724;
        }

        .linea-notification-card {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            background: #fafbfc;
        }

        .linea-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .linea-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .linea-logo {
            width: 40px;
            height: 40px;
            border-radius: 6px;
            object-fit: cover;
        }

        .linea-info h4 {
            margin: 0;
            color: #495057;
            font-size: 1.2rem;
        }

        .linea-slug {
            margin: 0;
            color: #6c757d;
            font-size: 0.85rem;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .status-badge.active {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .admins-list h5 {
            color: #495057;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1rem;
        }

        .admin-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .admin-tag {
            background: #e9ecef;
            color: #495057;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 6px;
            border: 1px solid #dee2e6;
        }

        .admin-tag.superadmin {
            background: linear-gradient(135deg, rgba(97, 27, 70, 1) 30%, rgba(227, 117, 0, 1) 100%);
            color: white;
            border-color: #667eea;
        }

        .admin-tag small {
            opacity: 0.8;
            font-size: 0.75rem;
        }

        .no-admins {
            color: #856404;
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 12px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-icon.success {
            background: #d4edda;
            color: #155724;
        }

        .stat-icon.info {
            background: #cce7ff;
            color: #004085;
        }

        .stat-content h4 {
            margin: 0 0 5px 0;
            color: #495057;
            font-size: 1.1rem;
        }

        .stat-content p {
            margin: 0;
            color: #6c757d;
            font-size: 0.9rem;
        }

        .info-box {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 8px;
            padding: 20px;
            display: flex;
            gap: 15px;
        }

        .info-icon {
            color: #004085;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .info-content h5 {
            margin: 0 0 10px 0;
            color: #004085;
            font-size: 1.1rem;
        }

        .info-content ul {
            margin: 0;
            padding-left: 20px;
            color: #495057;
        }

        .info-content li {
            margin-bottom: 5px;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .text-success {
            color: #28a745 !important;
        }

        .text-warning {
            color: #ffc107 !important;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .config-row {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .linea-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .admin-tags {
                flex-direction: column;
            }
        }
    </style>
    <script src="assets/js/main.js"></script>
</body>
</html>