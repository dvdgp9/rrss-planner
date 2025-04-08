<?php
// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'rrss_ebone');
define('DB_USER', 'admin_rrss');
define('DB_PASS', '7ma37%S4s');
define('DB_CHARSET', 'utf8mb4');

// Función para obtener la conexión a la base de datos
function getDbConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // Mostrar información detallada del error para depuración
        echo '<div style="background-color: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 4px;">';
        echo '<strong>Error de conexión a la base de datos:</strong><br>';
        echo $e->getMessage();
        echo '</div>';
        die();
    }
} 