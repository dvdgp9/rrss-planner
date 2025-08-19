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
    <!-- Fuente Geist cargada globalmente desde assets/css/styles.css -->
    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Estilos propios -->
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="app-simple">
        <?php require 'includes/nav.php'; ?>
        
        <div class="container" style="max-width: 1000px; margin: 0 auto; padding: 20px;">
            <!-- Header profesional -->
            <div class="account-header">
                <div class="account-header-content">
                    <div class="account-header-avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <div class="account-header-info">
                        <h1 class="account-header-title">Mi Cuenta</h1>
                        <p class="account-header-subtitle">Gestiona tu perfil y configuración de seguridad</p>
                    </div>
                </div>
            </div>
            
            <?php if ($message): ?>
                <div class="account-notification <?php echo $message_type === 'success' ? 'notification-success' : 'notification-error'; ?>">
                    <div class="notification-icon">
                        <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                    </div>
                    <div class="notification-content">
                        <strong><?php echo $message_type === 'success' ? '¡Éxito!' : 'Error'; ?></strong>
                        <p><?php echo htmlspecialchars($message); ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="account-grid">
                <!-- Información del Usuario -->
                <div class="account-card">
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h2>
                                <i class="fas fa-info-circle"></i>
                                Información del Usuario
                            </h2>
                            <div class="icon">
                                <i class="fas fa-user"></i>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="user-profile">
                                <div class="profile-item">
                                    <div class="profile-label">
                                        <i class="fas fa-user"></i>
                                        Nombre completo
                                    </div>
                                    <div class="profile-value"><?php echo htmlspecialchars($current_user['nombre']); ?></div>
                                </div>
                                
                                <?php if ($current_user['auth_method'] === 'user'): ?>
                                    <div class="profile-item">
                                        <div class="profile-label">
                                            <i class="fas fa-envelope"></i>
                                            Correo electrónico
                                        </div>
                                        <div class="profile-value"><?php echo htmlspecialchars($current_user['email']); ?></div>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="profile-item">
                                    <div class="profile-label">
                                        <i class="fas fa-shield-alt"></i>
                                        Nivel de acceso
                                    </div>
                                    <div class="profile-value">
                                        <span class="role-badge role-<?php echo $current_user['rol']; ?>">
                                            <i class="fas fa-<?php echo $current_user['rol'] === 'superadmin' ? 'crown' : 'user-cog'; ?>"></i>
                                            <?php echo $current_user['rol'] === 'superadmin' ? 'Super Administrador' : 'Administrador'; ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <?php if ($ultimo_login): ?>
                                    <div class="profile-item">
                                        <div class="profile-label">
                                            <i class="fas fa-clock"></i>
                                            Último acceso
                                        </div>
                                        <div class="profile-value"><?php echo $ultimo_login; ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Cambio de Contraseña -->
                <div class="account-card">
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h2>
                                <i class="fas fa-key"></i>
                                Seguridad
                            </h2>
                            <div class="icon">
                                <i class="fas fa-lock"></i>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if ($current_user['auth_method'] === 'user'): ?>
                                <form method="POST" class="password-form">
                                    <div class="form-group">
                                        <label for="current_password">
                                            <i class="fas fa-unlock"></i>
                                            Contraseña Actual
                                        </label>
                                        <input type="password" 
                                               id="current_password" 
                                               name="current_password" 
                                               class="form-control" 
                                               placeholder="Ingresa tu contraseña actual"
                                               required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="new_password">
                                            <i class="fas fa-key"></i>
                                            Nueva Contraseña
                                        </label>
                                        <input type="password" 
                                               id="new_password" 
                                               name="new_password" 
                                               class="form-control" 
                                               placeholder="Mínimo 6 caracteres"
                                               minlength="6" 
                                               required>
                                        <div class="password-strength">
                                            <div class="password-strength-bar">
                                                <div class="password-strength-fill"></div>
                                            </div>
                                            <small class="password-strength-text">Seguridad de la contraseña</small>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="confirm_password">
                                            <i class="fas fa-check-double"></i>
                                            Confirmar Nueva Contraseña
                                        </label>
                                        <input type="password" 
                                               id="confirm_password" 
                                               name="confirm_password" 
                                               class="form-control" 
                                               placeholder="Confirma tu nueva contraseña"
                                               required>
                                    </div>
                                    
                                    <button type="submit" name="change_password" class="btn btn-primary btn-password-change">
                                        <i class="fas fa-save"></i> 
                                        Cambiar Contraseña
                                    </button>
                                </form>
                            <?php else: ?>
                                <div class="legacy-auth-info">
                                    <div class="legacy-auth-icon">
                                        <i class="fas fa-info-circle"></i>
                                    </div>
                                    <div class="legacy-auth-content">
                                        <h3>Sistema anterior</h3>
                                        <p>Tu cuenta utiliza el sistema de autenticación anterior. Para cambiar tu contraseña, contacta con el administrador del sistema.</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Funcionalidad mejorada del formulario de cuenta
        document.addEventListener('DOMContentLoaded', function() {
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');
            const passwordStrengthFill = document.querySelector('.password-strength-fill');
            const passwordStrengthText = document.querySelector('.password-strength-text');
            const passwordForm = document.querySelector('.password-form');
            
            // Validación en tiempo real de coincidencia de contraseñas
            if (newPassword && confirmPassword) {
                function validatePasswordMatch() {
                    if (confirmPassword.value && newPassword.value !== confirmPassword.value) {
                        confirmPassword.setCustomValidity('Las contraseñas no coinciden');
                        confirmPassword.style.borderColor = '#dc3545';
                    } else {
                        confirmPassword.setCustomValidity('');
                        confirmPassword.style.borderColor = '#28a745';
                    }
                }
                
                newPassword.addEventListener('input', validatePasswordMatch);
                confirmPassword.addEventListener('input', validatePasswordMatch);
            }
            
            // Indicador de fuerza de contraseña
            if (newPassword && passwordStrengthFill && passwordStrengthText) {
                function updatePasswordStrength(password) {
                    let strength = 0;
                    let strengthText = '';
                    
                    if (password.length >= 6) strength += 25;
                    if (password.length >= 8) strength += 25;
                    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 25;
                    if (/[0-9]/.test(password)) strength += 12.5;
                    if (/[^A-Za-z0-9]/.test(password)) strength += 12.5;
                    
                    if (strength < 30) {
                        strengthText = 'Débil';
                        passwordStrengthFill.style.background = '#dc3545';
                    } else if (strength < 60) {
                        strengthText = 'Media';
                        passwordStrengthFill.style.background = '#ffc107';
                    } else if (strength < 85) {
                        strengthText = 'Fuerte';
                        passwordStrengthFill.style.background = '#28a745';
                    } else {
                        strengthText = 'Muy fuerte';
                        passwordStrengthFill.style.background = '#20c997';
                    }
                    
                    passwordStrengthFill.style.width = strength + '%';
                    passwordStrengthText.textContent = `Seguridad: ${strengthText}`;
                }
                
                newPassword.addEventListener('input', function() {
                    updatePasswordStrength(this.value);
                });
            }
            
            // Efectos visuales para el formulario
            if (passwordForm) {
                const formInputs = passwordForm.querySelectorAll('.form-control');
                
                formInputs.forEach(input => {
                    input.addEventListener('focus', function() {
                        this.parentElement.style.transform = 'translateY(-2px)';
                    });
                    
                    input.addEventListener('blur', function() {
                        this.parentElement.style.transform = 'translateY(0)';
                    });
                });
            }
            
            // Animación de carga para notificaciones
            const notifications = document.querySelectorAll('.account-notification');
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
        });
    </script>
</body>
</html> 