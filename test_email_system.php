<?php
require_once 'includes/functions.php';
require_authentication();

// Solo superadmins pueden ejecutar este test
if (!is_superadmin()) {
    die('Solo superadmins pueden ejecutar este test.');
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Test Sistema Email - Diagnóstico</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        .test-section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .success { background: #d4edda; border-color: #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .warning { background: #fff3cd; border-color: #ffeaa7; color: #856404; }
        .info { background: #cce7ff; border-color: #b3d9ff; color: #004085; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; }
        .btn { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Diagnóstico del Sistema de Email</h1>
        <p><strong>Objetivo:</strong> Verificar por qué no llegan los correos de notificación.</p>

        <?php
        $testEmail = 'admin@ebone.es'; // Email para testing
        
        echo "<div class='test-section info'>";
        echo "<h3>📋 Test 1: Verificar Configuración SMTP</h3>";
        $config = getSMTPConfig();
        echo "<pre>";
        echo "Host: " . $config['host'] . "\n";
        echo "Port: " . $config['port'] . "\n";
        echo "Username: " . $config['username'] . "\n";
        echo "From Email: " . $config['from_email'] . "\n";
        echo "From Name: " . $config['from_name'] . "\n";
        echo "Password: " . (strlen($config['password']) > 0 ? '[SET - ' . strlen($config['password']) . ' chars]' : '[NOT SET]') . "\n";
        echo "</pre>";
        echo "</div>";

        echo "<div class='test-section info'>";
        echo "<h3>📋 Test 2: Verificar Función mail() de PHP</h3>";
        if (function_exists('mail')) {
            echo "<p class='success'>✅ Función mail() está disponible</p>";
            
            // Verificar configuración PHP mail
            echo "<pre>";
            echo "SMTP: " . ini_get('SMTP') . "\n";
            echo "smtp_port: " . ini_get('smtp_port') . "\n";
            echo "sendmail_from: " . ini_get('sendmail_from') . "\n";
            echo "</pre>";
        } else {
            echo "<p class='error'>❌ Función mail() no está disponible</p>";
        }
        echo "</div>";

        echo "<div class='test-section info'>";
        echo "<h3>📋 Test 3: Verificar Administradores en Base de Datos</h3>";
        $db = getDbConnection();
        
        // Verificar administradores activos
        $stmt = $db->query("SELECT id, nombre, email, rol, activo FROM admins ORDER BY nombre");
        $admins = $stmt->fetchAll();
        
        echo "<p><strong>Administradores en el sistema:</strong></p>";
        echo "<pre>";
        foreach ($admins as $admin) {
            $status = $admin['activo'] ? 'ACTIVO' : 'INACTIVO';
            echo sprintf("ID: %d | %s (%s) | %s | %s\n", 
                $admin['id'], 
                $admin['nombre'], 
                $admin['email'], 
                $admin['rol'], 
                $status
            );
        }
        echo "</pre>";
        echo "</div>";

        echo "<div class='test-section info'>";
        echo "<h3>📋 Test 4: Verificar Relaciones admin_linea_negocio</h3>";
        
        // Verificar relaciones
        $stmt = $db->query("
            SELECT aln.id, a.nombre as admin_nombre, a.email, ln.nombre as linea_nombre, ln.id as linea_id
            FROM admin_linea_negocio aln
            JOIN admins a ON aln.admin_id = a.id  
            JOIN lineas_negocio ln ON aln.linea_negocio_id = ln.id
            WHERE a.activo = 1
            ORDER BY ln.nombre, a.nombre
        ");
        $relaciones = $stmt->fetchAll();
        
        if (empty($relaciones)) {
            echo "<p class='warning'>⚠️ No hay relaciones admin_linea_negocio configuradas</p>";
            echo "<p>Esto significa que se usarán superadmins como fallback</p>";
        } else {
            echo "<p><strong>Relaciones configuradas:</strong></p>";
            echo "<pre>";
            foreach ($relaciones as $rel) {
                echo sprintf("Línea: %s | Admin: %s (%s)\n", 
                    $rel['linea_nombre'], 
                    $rel['admin_nombre'], 
                    $rel['email']
                );
            }
            echo "</pre>";
        }
        echo "</div>";

        // Test 5: Simular obtención de destinatarios
        echo "<div class='test-section info'>";
        echo "<h3>📋 Test 5: Simular Obtención de Destinatarios</h3>";
        
        $stmt = $db->query("SELECT id, nombre FROM lineas_negocio LIMIT 1");
        $linea = $stmt->fetch();
        
        if ($linea) {
            echo "<p><strong>Probando con línea: {$linea['nombre']} (ID: {$linea['id']})</strong></p>";
            $admins_linea = getAdminsByLineaNegocio($linea['id']);
            
            if (empty($admins_linea)) {
                echo "<p class='warning'>⚠️ No se encontraron administradores para esta línea</p>";
            } else {
                echo "<p class='success'>✅ Administradores encontrados:</p>";
                echo "<pre>";
                foreach ($admins_linea as $admin) {
                    echo sprintf("- %s (%s)\n", $admin['nombre'], $admin['email']);
                }
                echo "</pre>";
            }
        }
        echo "</div>";

        // Test 6: Envío real de correo
        echo "<div class='test-section'>";
        echo "<h3>📋 Test 6: Envío Real de Correo</h3>";
        
        if (isset($_POST['test_email'])) {
            echo "<p><strong>Ejecutando test de envío...</strong></p>";
            
            $testSubject = "🧪 Test RRSS Planner - " . date('Y-m-d H:i:s');
            $testBody = "
            <html>
            <body style='font-family: Arial, sans-serif;'>
                <h2>Test de Sistema de Correo</h2>
                <p>Este es un correo de prueba del sistema RRSS Planner.</p>
                <p><strong>Fecha:</strong> " . date('Y-m-d H:i:s') . "</p>
                <p><strong>IP:</strong> " . $_SERVER['REMOTE_ADDR'] . "</p>
                <p>Si recibes este correo, el sistema SMTP está funcionando correctamente.</p>
            </body>
            </html>";
            
            $result = sendEmail($testEmail, $testSubject, $testBody);
            
            if ($result === true) {
                echo "<div class='success'>";
                echo "<p>✅ <strong>¡Correo enviado exitosamente!</strong></p>";
                echo "<p>Destinatario: {$testEmail}</p>";
                echo "<p>Asunto: {$testSubject}</p>";
                echo "<p>Revisa la bandeja de entrada (y spam) en unos minutos.</p>";
                echo "</div>";
            } else {
                echo "<div class='error'>";
                echo "<p>❌ <strong>Error al enviar correo:</strong></p>";
                echo "<p>{$result}</p>";
                echo "</div>";
            }
        } else {
            echo "<form method='POST'>";
            echo "<p>Enviar correo de prueba a: <strong>{$testEmail}</strong></p>";
            echo "<button type='submit' name='test_email' class='btn'>🚀 Enviar Test Email</button>";
            echo "</form>";
        }
        echo "</div>";

        // Logs recientes
        echo "<div class='test-section info'>";
        echo "<h3>📋 Test 7: Logs Recientes del Sistema</h3>";
        echo "<p><em>Buscando logs recientes relacionados con email...</em></p>";
        
        // Intentar leer logs de error de PHP
        $possibleLogPaths = [
            '/var/log/php_errors.log',
            '/tmp/php_errors.log', 
            ini_get('error_log'),
            './error.log'
        ];
        
        $logsFound = false;
        foreach ($possibleLogPaths as $logPath) {
            if ($logPath && file_exists($logPath) && is_readable($logPath)) {
                $logsFound = true;
                echo "<p><strong>Log encontrado:</strong> {$logPath}</p>";
                
                // Leer últimas 20 líneas que contengan "EMAIL" o "FEEDBACK"
                $logContent = file_get_contents($logPath);
                $lines = explode("\n", $logContent);
                $relevantLines = array_filter($lines, function($line) {
                    return stripos($line, 'EMAIL') !== false || 
                           stripos($line, 'FEEDBACK') !== false ||
                           stripos($line, 'mail') !== false;
                });
                
                if (!empty($relevantLines)) {
                    echo "<pre>";
                    echo implode("\n", array_slice($relevantLines, -10)); // Últimas 10 líneas relevantes
                    echo "</pre>";
                } else {
                    echo "<p>No se encontraron logs relacionados con email.</p>";
                }
                break;
            }
        }
        
        if (!$logsFound) {
            echo "<p class='warning'>⚠️ No se pudieron encontrar archivos de log accesibles</p>";
            echo "<p>Paths intentados: " . implode(', ', array_filter($possibleLogPaths)) . "</p>";
        }
        echo "</div>";
        ?>

        <div class='test-section'>
            <h3>🎯 Conclusiones y Siguientes Pasos</h3>
            <p><strong>Basado en los tests anteriores:</strong></p>
            <ol>
                <li>Si el <strong>Test 6</strong> funciona ✅ → El problema puede estar en los datos o lógica</li>
                <li>Si el <strong>Test 6</strong> falla ❌ → El problema está en la configuración SMTP</li>
                <li>Si no hay relaciones admin_linea_negocio ⚠️ → Solo superadmins recibirán correos</li>
                <li>Revisa los logs para errores específicos de SMTP</li>
            </ol>
        </div>

        <div class='test-section info'>
            <h3>🔧 Acciones Rápidas</h3>
            <p><a href="configuracion.php?tab=notificaciones" class='btn'>📊 Ver Panel Notificaciones</a></p>
            <p><a href="javascript:location.reload()" class='btn'>🔄 Recargar Tests</a></p>
            <p><a href="index.php" class='btn'>🏠 Volver al Dashboard</a></p>
        </div>
    </div>
</body>
</html> 