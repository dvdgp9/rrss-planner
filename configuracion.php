<?php
require_once 'includes/functions.php';
require_once 'includes/WordPressAPI.php';
require_authentication();

// Verificar que solo superadmins puedan acceder
if (!is_superadmin()) {
    header('Location: index.php');
    exit;
}

$db = getDbConnection();
$current_user = get_current_admin_user();

// Mensajes de feedback
$message = '';
$message_type = '';

// Determinar tab activo
$active_tab = $_GET['tab'] ?? 'wordpress';

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
                                        <?php if ($usuario['id'] != $current_user['id']): ?>
                                            <div class="user-card-actions">
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
                                            </div>
                                        <?php else: ?>
                                            <div class="user-card-actions">
                                                <span class="current-user-badge">
                                                    <i class="fas fa-user-check"></i>
                                                    Usuario actual
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
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
</body>
</html> 