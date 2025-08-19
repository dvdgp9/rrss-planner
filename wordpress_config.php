<?php
require_once 'includes/functions.php';
require_once 'includes/WordPressAPI.php';
require_authentication();

$db = getDbConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
                    $_SESSION['feedback_message'] = [
                        'tipo' => 'error',
                        'mensaje' => 'Error de conexión con WordPress: ' . $test_result['error']
                    ];
                } else {
                    // Connection successful, update database
                    $stmt = $db->prepare("
                        UPDATE lineas_negocio 
                        SET wordpress_url = ?, wordpress_username = ?, wordpress_app_password = ?, wordpress_enabled = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$wordpress_url, $wordpress_username, $wordpress_app_password, $wordpress_enabled, $linea_id]);
                    
                    $_SESSION['feedback_message'] = [
                        'tipo' => 'success',
                        'mensaje' => 'Configuración de WordPress actualizada exitosamente'
                    ];
                }
            } else {
                // Just update without testing (for disabling)
                $stmt = $db->prepare("
                    UPDATE lineas_negocio 
                    SET wordpress_url = ?, wordpress_username = ?, wordpress_app_password = ?, wordpress_enabled = ?
                    WHERE id = ?
                ");
                $stmt->execute([$wordpress_url, $wordpress_username, $wordpress_app_password, $wordpress_enabled, $linea_id]);
                
                $_SESSION['feedback_message'] = [
                    'tipo' => 'success',
                    'mensaje' => 'Configuración actualizada'
                ];
            }
        } catch (Exception $e) {
            $_SESSION['feedback_message'] = [
                'tipo' => 'error',
                'mensaje' => 'Error: ' . $e->getMessage()
            ];
        }
        
        header("Location: wordpress_config.php");
        exit;
    }
}

// Get all business lines
$stmt = $db->query("SELECT * FROM lineas_negocio ORDER BY nombre ASC");
$lineas_negocio = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lööp - Configuración WordPress</title>
    <link rel="icon" type="image/png" href="assets/images/logos/Loop-favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/simple.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .wp-config-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .wp-config-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .wp-config-header {
            background: linear-gradient(135deg, rgba(97, 27, 70, 1) 30%, rgba(227, 117, 0, 1) 100%);
            color: white;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .wp-config-header img {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: white;
            padding: 5px;
        }
        
        .wp-config-body {
            padding: 20px;
        }
        
        .wp-form-group {
            margin-bottom: 15px;
        }
        
        .wp-form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }
        
        .wp-form-group input[type="text"],
        .wp-form-group input[type="url"],
        .wp-form-group input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 2px solid #e1e1e1;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .wp-form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .wp-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .wp-toggle input[type="checkbox"] {
            width: 20px;
            height: 20px;
        }
        
        .wp-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .wp-status.enabled {
            background: #d4edda;
            color: #155724;
        }
        
        .wp-status.disabled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .wp-btn {
            background: linear-gradient(135deg, rgba(97, 27, 70, 1) 30%, rgba(227, 117, 0, 1) 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 4px 12px rgba(97, 27, 70, 0.25);
        }
        
        .wp-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(97, 27, 70, 0.3);
        }
        
        .wp-help {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin-top: 20px;
            border-radius: 0 6px 6px 0;
        }
        
        .wp-help h4 {
            margin: 0 0 10px 0;
            color: #667eea;
        }
        
        .wp-help ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        
        .wp-help li {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <?php require 'includes/nav.php'; ?>
    
    <div class="wp-config-container">
        <div class="page-header">
            <h1><i class="fab fa-wordpress"></i> Configuración WordPress</h1>
            <p>Configura las credenciales de WordPress para cada línea de negocio</p>
        </div>
        
        <?php if (isset($_SESSION['feedback_message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['feedback_message']['tipo']; ?>">
                <?php echo htmlspecialchars($_SESSION['feedback_message']['mensaje']); ?>
            </div>
            <?php unset($_SESSION['feedback_message']); ?>
        <?php endif; ?>
        
        <?php foreach ($lineas_negocio as $linea): ?>
            <div class="wp-config-card">
                <div class="wp-config-header">
                    <img src="assets/images/logos/<?php echo htmlspecialchars($linea['logo_filename'] ?: 'default.png'); ?>" 
                         alt="<?php echo htmlspecialchars($linea['nombre']); ?>">
                    <div>
                        <h3><?php echo htmlspecialchars($linea['nombre']); ?></h3>
                        <span class="wp-status <?php echo $linea['wordpress_enabled'] ? 'enabled' : 'disabled'; ?>">
                            <?php echo $linea['wordpress_enabled'] ? 'Habilitado' : 'Deshabilitado'; ?>
                        </span>
                    </div>
                </div>
                
                <div class="wp-config-body">
                    <form method="POST">
                        <input type="hidden" name="linea_id" value="<?php echo $linea['id']; ?>">
                        
                        <div class="wp-toggle">
                            <input type="checkbox" 
                                   id="wordpress_enabled_<?php echo $linea['id']; ?>" 
                                   name="wordpress_enabled" 
                                   <?php echo $linea['wordpress_enabled'] ? 'checked' : ''; ?>>
                            <label for="wordpress_enabled_<?php echo $linea['id']; ?>">
                                Habilitar publicación en WordPress
                            </label>
                        </div>
                        
                        <div class="wp-form-group">
                            <label for="wordpress_url_<?php echo $linea['id']; ?>">URL del sitio WordPress</label>
                            <input type="url" 
                                   id="wordpress_url_<?php echo $linea['id']; ?>" 
                                   name="wordpress_url" 
                                   value="<?php echo htmlspecialchars($linea['wordpress_url'] ?? ''); ?>"
                                   placeholder="https://ejemplo.com/">
                        </div>
                        
                        <div class="wp-form-group">
                            <label for="wordpress_username_<?php echo $linea['id']; ?>">Nombre de usuario</label>
                            <input type="text" 
                                   id="wordpress_username_<?php echo $linea['id']; ?>" 
                                   name="wordpress_username" 
                                   value="<?php echo htmlspecialchars($linea['wordpress_username'] ?? ''); ?>"
                                   placeholder="admin">
                        </div>
                        
                        <div class="wp-form-group">
                            <label for="wordpress_app_password_<?php echo $linea['id']; ?>">Application Password</label>
                            <input type="password" 
                                   id="wordpress_app_password_<?php echo $linea['id']; ?>" 
                                   name="wordpress_app_password" 
                                   value="<?php echo htmlspecialchars($linea['wordpress_app_password'] ?? ''); ?>"
                                   placeholder="xxxx xxxx xxxx xxxx xxxx xxxx">
                        </div>
                        
                        <button type="submit" class="wp-btn">
                            <i class="fas fa-save"></i> Guardar Configuración
                        </button>
                        
                        <?php if ($linea['wordpress_last_sync']): ?>
                            <p style="margin-top: 10px; color: #666; font-size: 14px;">
                                <i class="fas fa-clock"></i> 
                                Última sincronización: <?php echo date('d/m/Y H:i', strtotime($linea['wordpress_last_sync'])); ?>
                            </p>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
        
        <div class="wp-help">
            <h4><i class="fas fa-question-circle"></i> Cómo configurar Application Passwords</h4>
            <p>Para habilitar la publicación automática en WordPress, necesitas crear un Application Password:</p>
            <ul>
                <li>Ve a tu sitio WordPress → Usuarios → Tu Perfil</li>
                <li>Desplázate hasta la sección "Application Passwords"</li>
                <li>Ingresa un nombre para la aplicación (ej: "Lööp Planner")</li>
                <li>Haz clic en "Add New Application Password"</li>
                <li>Copia la contraseña generada y pégala en el campo de arriba</li>
            </ul>
            <p><strong>Nota:</strong> La contraseña solo se muestra una vez, guárdala en un lugar seguro.</p>
        </div>
    </div>
    
    <script src="assets/js/main.js" defer></script>
</body>
</html> 