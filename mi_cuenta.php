<?php
require_once 'includes/functions.php';
require_authentication();

// Obtener información del usuario actual
$current_user = get_current_admin_user();

// Procesar el formulario de cambio de contraseña
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validaciones
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $message = 'Todos los campos son obligatorios.';
        $message_type = 'error';
    } elseif ($new_password !== $confirm_password) {
        $message = 'La nueva contraseña y su confirmación no coinciden.';
        $message_type = 'error';
    } elseif (strlen($new_password) < 6) {
        $message = 'La nueva contraseña debe tener al menos 6 caracteres.';
        $message_type = 'error';
    } else {
        // Intentar cambiar la contraseña
        $result = change_password($current_user['id'], $current_password, $new_password);
        if ($result === true) {
            $message = 'Contraseña cambiada exitosamente.';
            $message_type = 'success';
        } else {
            $message = $result; // El mensaje de error específico
            $message_type = 'error';
        }
    }
}

// Formatear la fecha del último login si existe
$ultimo_login = '';
if ($current_user['auth_method'] === 'user') {
    try {
        $db = getDbConnection();
        $stmt = $db->prepare("SELECT ultimo_login FROM admins WHERE id = ?");
        $stmt->execute([$current_user['id']]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user_data && $user_data['ultimo_login']) {
            $ultimo_login = date('d/m/Y H:i', strtotime($user_data['ultimo_login']));
        }
    } catch (PDOException $e) {
        // Manejar error silenciosamente
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Cuenta - Lööp</title>
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
        
        <div class="container" style="max-width: 800px; margin: 0 auto; padding: 20px;">
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-user"></i> Mi Cuenta
                </h1>
                <p class="page-subtitle">Información del usuario y configuración de cuenta</p>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <!-- Información del Usuario -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-info-circle"></i> Información del Usuario
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="user-info">
                                <div class="info-item">
                                    <label>Nombre:</label>
                                    <span><?php echo htmlspecialchars($current_user['nombre']); ?></span>
                                </div>
                                
                                <?php if ($current_user['auth_method'] === 'user'): ?>
                                    <div class="info-item">
                                        <label>Email:</label>
                                        <span><?php echo htmlspecialchars($current_user['email']); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="info-item">
                                    <label>Rol:</label>
                                    <span class="badge badge-<?php echo $current_user['rol'] === 'superadmin' ? 'success' : 'info'; ?>">
                                        <?php echo $current_user['rol'] === 'superadmin' ? 'Super Administrador' : 'Administrador'; ?>
                                    </span>
                                </div>
                                
                                <div class="info-item">
                                    <label>Tipo de autenticación:</label>
                                    <span class="badge badge-<?php echo $current_user['auth_method'] === 'user' ? 'success' : 'warning'; ?>">
                                        <?php echo $current_user['auth_method'] === 'user' ? 'Sistema nuevo' : 'Sistema anterior'; ?>
                                    </span>
                                </div>
                                
                                <?php if ($ultimo_login): ?>
                                    <div class="info-item">
                                        <label>Último acceso:</label>
                                        <span><?php echo $ultimo_login; ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Cambio de Contraseña -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-key"></i> Cambiar Contraseña
                            </h3>
                        </div>
                        <div class="card-body">
                            <?php if ($current_user['auth_method'] === 'user'): ?>
                                <form method="POST">
                                    <div class="form-group">
                                        <label for="current_password">Contraseña Actual:</label>
                                        <input type="password" 
                                               id="current_password" 
                                               name="current_password" 
                                               class="form-control" 
                                               required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="new_password">Nueva Contraseña:</label>
                                        <input type="password" 
                                               id="new_password" 
                                               name="new_password" 
                                               class="form-control" 
                                               minlength="6" 
                                               required>
                                        <small class="form-text text-muted">
                                            Mínimo 6 caracteres
                                        </small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="confirm_password">Confirmar Nueva Contraseña:</label>
                                        <input type="password" 
                                               id="confirm_password" 
                                               name="confirm_password" 
                                               class="form-control" 
                                               required>
                                    </div>
                                    
                                    <button type="submit" name="change_password" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Cambiar Contraseña
                                    </button>
                                </form>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    <strong>Sistema anterior:</strong> Para cambiar la contraseña, contacta con el administrador del sistema.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Validación en tiempo real de coincidencia de contraseñas
        document.addEventListener('DOMContentLoaded', function() {
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');
            
            if (newPassword && confirmPassword) {
                function validatePasswordMatch() {
                    if (confirmPassword.value && newPassword.value !== confirmPassword.value) {
                        confirmPassword.setCustomValidity('Las contraseñas no coinciden');
                    } else {
                        confirmPassword.setCustomValidity('');
                    }
                }
                
                newPassword.addEventListener('input', validatePasswordMatch);
                confirmPassword.addEventListener('input', validatePasswordMatch);
            }
        });
    </script>
</body>
</html> 