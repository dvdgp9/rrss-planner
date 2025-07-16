<?php
// Iniciar sesión
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/functions.php';

$error = '';
$password = '';

// Si ya está autenticado, redirigir al dashboard
if (is_authenticated()) {
    header('Location: index.php');
    exit;
}

// Procesar el formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $authenticated = false;
    
    // Solo permitir autenticación por email/password
    if (!empty($email) && !empty($password)) {
        $user = authenticate_user($email, $password);
        if ($user) {
            $authenticated = true;
        } else {
            $error = 'Email o contraseña incorrectos.';
        }
    } else {
        $error = 'Por favor, ingrese email y contraseña.';
    }
    
    // Si la autenticación fue exitosa, redirigir
    if ($authenticated) {
        // Redirigir a la URL original o al dashboard si no hay URL guardada
        $redirect_url = $_SESSION['redirect_url'] ?? 'index.php';
        unset($_SESSION['redirect_url']); // Limpiar la URL guardada
        header('Location: ' . $redirect_url);
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Planificador RRSS</title>
    <link rel="icon" type="image/png" href="assets/images/logos/isotipo-ebone.png">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Estilos propios (podemos usar los mismos o crear uno específico para login) -->
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        /* Estilos modernos para la página de login */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body.login-page {
            font-family: 'Open Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            backdrop-filter: blur(10px);
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 420px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #23AAC5, #1976d2, #23AAC5);
            background-size: 200% 100%;
            animation: shimmer 3s linear infinite;
        }
        
        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
        
        .login-logo {
            max-width: 180px;
            margin-bottom: 2rem;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.1));
        }
        
        .login-container h1 {
            font-size: 1.8rem;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        .login-subtitle {
            color: #7f8c8d;
            margin-bottom: 2rem;
            font-size: 0.95rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
            text-align: left;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #34495e;
            font-size: 0.9rem;
        }
        
        .form-group input[type="email"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 1rem 1.2rem;
            border: 2px solid #e1e8ed;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background-color: #f8f9fa;
            color: #2c3e50;
        }
        
        .form-group input[type="email"]:focus,
        .form-group input[type="password"]:focus {
            outline: none;
            border-color: #23AAC5;
            background-color: white;
            box-shadow: 0 0 0 3px rgba(35, 170, 197, 0.1);
            transform: translateY(-2px);
        }
        
        .form-group input[type="email"]::placeholder,
        .form-group input[type="password"]::placeholder {
            color: #95a5a6;
            font-weight: 400;
        }
        
        .btn-login {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #23AAC5 0%, #1976d2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(35, 170, 197, 0.3);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-login:hover::before {
            left: 100%;
        }
        
        .error-message {
            background: linear-gradient(135deg, #ff6b6b, #ee5a6f);
            color: white;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: 500;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.2);
        }
        
        .info-box {
            margin-top: 1.5rem;
            padding: 1.2rem;
            background: linear-gradient(135deg, #e3f2fd, #f3e5f5);
            border-radius: 12px;
            font-size: 0.85rem;
            color: #1976d2;
            border-left: 4px solid #23AAC5;
        }
        
        .info-box strong {
            color: #1565c0;
        }
        
        .info-box em {
            color: #666;
            font-style: normal;
        }
        
        /* Animaciones de entrada */
        .login-container {
            animation: slideUp 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Responsive */
        @media (max-width: 480px) {
            .login-container {
                padding: 2rem;
                margin: 1rem;
            }
            
            .login-logo {
                max-width: 150px;
            }
            
            .login-container h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body class="login-page">
    <div class="login-container">
        <img src="assets/images/logos/loop-logo.png" alt="Loop Logo" class="login-logo"> 
        <h1>Iniciar Sesión</h1>
        <p class="login-subtitle">Planificador de Redes Sociales</p>

        <?php if ($error): ?>
            <div class="error-message">
                <strong>🚫 Error:</strong> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="email">📧 Email del Administrador</label>
                <input type="email" id="email" name="email" placeholder="Ingresa tu email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="password">🔒 Contraseña</label>
                <input type="password" id="password" name="password" placeholder="Ingresa tu contraseña" required autofocus>
            </div>
            <button type="submit" class="btn btn-login">
                <span>Acceder al Sistema</span>
            </button>
        </form>
    </div>
</body>
</html> 