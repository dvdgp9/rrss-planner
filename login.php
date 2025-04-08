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
    $password = $_POST['password'] ?? '';

    if (password_verify($password, MASTER_PASSWORD_HASH)) {
        // Contraseña correcta: iniciar sesión y redirigir
        $_SESSION['authenticated'] = true;
        
        // Redirigir a la URL original o al dashboard si no hay URL guardada
        $redirect_url = $_SESSION['redirect_url'] ?? 'index.php';
        unset($_SESSION['redirect_url']); // Limpiar la URL guardada
        header('Location: ' . $redirect_url);
        exit;
    } else {
        // Contraseña incorrecta
        $error = 'Contraseña incorrecta.';
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
        /* Estilos específicos para la página de login */
        body.login-page {
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .login-container {
            background-color: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        .login-logo {
            max-width: 200px;
            margin-bottom: 30px;
        }
        .login-container h1 {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
        }
        .form-group input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 1rem;
        }
        .btn-login {
            width: 100%;
            padding: 12px;
            background-color: var(--primary-color, #23AAC5); /* Usar color primario de styles.css */
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .btn-login:hover {
            background-color: var(--primary-dark, #1a8da5);
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
            text-align: center;
        }
    </style>
</head>
<body class="login-page">
    <div class="login-container">
        <img src="assets/images/logos/logo-grupo-completo.png" alt="Logo" class="login-logo"> 
        <h1>Iniciar Sesión</h1>

        <?php if ($error): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="password">Contraseña Maestra:</label>
                <input type="password" id="password" name="password" required autofocus>
            </div>
            <button type="submit" class="btn btn-login">Acceder</button>
        </form>
    </div>
</body>
</html> 