<?php
require_once 'includes/functions.php';
require_authentication();

if (!is_superadmin()) {
    die('Solo superadmins pueden ejecutar este debug.');
}

echo "<h2>🔍 Debug SMTP - Diagnóstico Rápido</h2>";

// 1. Verificar configuración
echo "<h3>1. Configuración SMTP:</h3>";
try {
    $config = getSMTPConfig();
    echo "<pre>";
    echo "Host: " . ($config['host'] ?? 'NOT SET') . "\n";
    echo "Port: " . ($config['port'] ?? 'NOT SET') . "\n";
    echo "Username: " . ($config['username'] ?? 'NOT SET') . "\n";
    echo "Password: " . (isset($config['password']) && !empty($config['password']) ? '[SET - ' . strlen($config['password']) . ' chars]' : '[NOT SET]') . "\n";
    echo "From Email: " . ($config['from_email'] ?? 'NOT SET') . "\n";
    echo "From Name: " . ($config['from_name'] ?? 'NOT SET') . "\n";
    echo "</pre>";
    
    // Verificar fuente de configuración
    if (getenv('SMTP_HOST')) {
        echo "<p style='color: green;'>✅ Usando variables de entorno</p>";
    } elseif (file_exists(__DIR__ . '/config/smtp.php')) {
        echo "<p style='color: blue;'>ℹ️ Usando archivo config/smtp.php</p>";
    } else {
        echo "<p style='color: red;'>❌ Usando configuración por defecto - PROBLEMA AQUÍ</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error obteniendo configuración: " . $e->getMessage() . "</p>";
}

// 2. Test básico de envío
if (isset($_POST['test_send'])) {
    echo "<h3>2. Test de Envío:</h3>";
    
    $testEmail = 'admin@ebone.es';
    $testSubject = 'Test Debug - ' . date('H:i:s');
    $testBody = '<h2>Test Email</h2><p>Fecha: ' . date('Y-m-d H:i:s') . '</p>';
    
    echo "<p>Enviando a: {$testEmail}</p>";
    
    $result = sendEmail($testEmail, $testSubject, $testBody);
    
    if ($result === true) {
        echo "<p style='color: green;'>✅ Email enviado exitosamente</p>";
    } else {
        echo "<p style='color: red;'>❌ Error: {$result}</p>";
    }
}

// 3. Verificar archivo config
echo "<h3>3. Verificación de Archivos:</h3>";
$configFile = __DIR__ . '/config/smtp.php';
$configFileFromFunctions = __DIR__ . '/config/smtp.php'; // Desde raíz
$configFileFromIncludes = __DIR__ . '/../config/smtp.php'; // Desde includes/

echo "<p><strong>Debugging rutas de configuración:</strong></p>";

// Mostrar diferentes rutas posibles
$paths = [
    'Desde raíz' => __DIR__ . '/config/smtp.php',
    'Como functions.php busca' => __DIR__ . '/includes/../config/smtp.php',  
    'Ruta absoluta alternativa' => dirname(__DIR__) . '/config/smtp.php',
    'Con realpath desde includes' => realpath(__DIR__ . '/includes/../config/smtp.php')
];

foreach ($paths as $desc => $path) {
    $exists = file_exists($path);
    $readable = $exists ? is_readable($path) : false;
    echo "<p><strong>{$desc}:</strong><br>";
    echo "  Ruta: {$path}<br>";
    echo "  Estado: " . ($exists ? "✅ EXISTE" : "❌ NO EXISTE");
    if ($exists) {
        echo " | " . ($readable ? "✅ LEGIBLE" : "❌ NO LEGIBLE");
    }
    echo "</p>";
}

// Mostrar contenido del directorio config
echo "<h4>Contenido del directorio config:</h4>";
$configDir = __DIR__ . '/config';
if (is_dir($configDir)) {
    $files = scandir($configDir);
    echo "<pre>";
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            echo "- {$file}\n";
        }
    }
    echo "</pre>";
} else {
    echo "<p style='color: red;'>❌ Directorio config/ no existe</p>";
}
if (file_exists($configFile)) {
    echo "<p style='color: green;'>✅ Archivo config/smtp.php existe</p>";
    if (is_readable($configFile)) {
        echo "<p style='color: green;'>✅ Archivo es legible</p>";
    } else {
        echo "<p style='color: red;'>❌ Archivo no es legible - problema de permisos</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Archivo config/smtp.php NO EXISTE</p>";
    echo "<p><strong>SOLUCIÓN:</strong> Crear el archivo manualmente:</p>";
    echo "<pre>
cat > config/smtp.php << 'EOF'
<?php
return [
    'host' => 'ebonemx.plesk.trevenque.es',
    'port' => 465,
    'username' => 'loop@ebone.es',
    'password' => '81o9h&4Lr',
    'from_email' => 'loop@ebone.es',
    'from_name' => 'Loop - RRSS Planner'
];
EOF
</pre>";
}

// 4. Test de funciones
echo "<h3>4. Test de Funciones:</h3>";
if (function_exists('getSMTPConfig')) {
    echo "<p style='color: green;'>✅ Función getSMTPConfig() existe</p>";
} else {
    echo "<p style='color: red;'>❌ Función getSMTPConfig() NO existe</p>";
}

if (function_exists('sendEmail')) {
    echo "<p style='color: green;'>✅ Función sendEmail() existe</p>";
} else {
    echo "<p style='color: red;'>❌ Función sendEmail() NO existe</p>";
}

?>

<form method="POST">
    <button type="submit" name="test_send" style="background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px;">
        🚀 Probar Envío de Email
    </button>
</form>

<hr>
<p><a href="configuracion.php?tab=notificaciones">📊 Ver Panel Configuración</a></p>
<p><a href="index.php">🏠 Volver al Dashboard</a></p> 