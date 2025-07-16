<?php
/**
 * HELPER DE MIGRACIÓN: Sistema de Administradores
 * 
 * Este script ayuda con la migración del sistema de contraseña maestra
 * al nuevo sistema de administradores con email/contraseña.
 * 
 * IMPORTANTE: Eliminar este archivo después de completar la migración
 * por razones de seguridad.
 */

require_once 'includes/functions.php';

// Verificar autenticación
require_authentication();

// Solo superadmins pueden usar este script
if (!is_superadmin()) {
    die('❌ Este script solo puede ser usado por superadministradores.');
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migración de Administradores - RRSS Planner</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; }
        .section { margin: 20px 0; padding: 15px; border-left: 4px solid #007cba; background-color: #f8f9fa; }
        .success { border-left-color: #28a745; background-color: #d4edda; }
        .warning { border-left-color: #ffc107; background-color: #fff3cd; }
        .danger { border-left-color: #dc3545; background-color: #f8d7da; }
        .info { border-left-color: #17a2b8; background-color: #d1ecf1; }
        .btn { padding: 10px 20px; margin: 5px; background-color: #007cba; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .btn:hover { background-color: #005a8a; }
        .btn-danger { background-color: #dc3545; }
        .btn-danger:hover { background-color: #c82333; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; }
        .status-active { color: #28a745; font-weight: bold; }
        .status-inactive { color: #dc3545; font-weight: bold; }
        .code { background-color: #f8f9fa; padding: 10px; border-radius: 4px; font-family: monospace; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Migración de Administradores</h1>
            <p>Sistema de transición de contraseña maestra a administradores individuales</p>
        </div>

        <?php
        // Mostrar información del usuario actual
        $current_user = get_current_admin_user();
        echo "<div class='section success'>";
        echo "<h3>👤 Usuario Actual</h3>";
        echo "<p><strong>Nombre:</strong> " . htmlspecialchars($current_user['nombre']) . "</p>";
        echo "<p><strong>Email:</strong> " . htmlspecialchars($current_user['email']) . "</p>";
        echo "<p><strong>Rol:</strong> " . htmlspecialchars($current_user['rol']) . "</p>";
        echo "<p><strong>Método de auth:</strong> " . htmlspecialchars($current_user['auth_method']) . "</p>";
        echo "</div>";

        // Verificar estado de las tablas
        try {
            $db = getDbConnection();
            
            // Contar administradores
            $stmt = $db->query("SELECT COUNT(*) as total FROM admins");
            $admin_count = $stmt->fetch()['total'];
            
            // Contar superadmins
            $stmt = $db->query("SELECT COUNT(*) as total FROM admins WHERE rol = 'superadmin'");
            $superadmin_count = $stmt->fetch()['total'];
            
            // Contar administradores activos
            $stmt = $db->query("SELECT COUNT(*) as total FROM admins WHERE activo = 1");
            $active_count = $stmt->fetch()['total'];
            
            echo "<div class='section info'>";
            echo "<h3>📊 Estado del Sistema</h3>";
            echo "<p><strong>Total de administradores:</strong> $admin_count</p>";
            echo "<p><strong>Superadministradores:</strong> $superadmin_count</p>";
            echo "<p><strong>Administradores activos:</strong> $active_count</p>";
            echo "</div>";
            
            // Mostrar lista de administradores
            echo "<div class='section'>";
            echo "<h3>👥 Administradores Registrados</h3>";
            
            $stmt = $db->query("SELECT * FROM admins ORDER BY rol DESC, nombre ASC");
            $admins = $stmt->fetchAll();
            
            if (count($admins) > 0) {
                echo "<table>";
                echo "<tr><th>Nombre</th><th>Email</th><th>Rol</th><th>Estado</th><th>Último Login</th><th>Creado</th></tr>";
                
                foreach ($admins as $admin) {
                    $status_class = $admin['activo'] ? 'status-active' : 'status-inactive';
                    $status_text = $admin['activo'] ? 'Activo' : 'Inactivo';
                    $ultimo_login = $admin['ultimo_login'] ? date('d/m/Y H:i', strtotime($admin['ultimo_login'])) : 'Nunca';
                    
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($admin['nombre']) . "</td>";
                    echo "<td>" . htmlspecialchars($admin['email']) . "</td>";
                    echo "<td>" . htmlspecialchars($admin['rol']) . "</td>";
                    echo "<td class='$status_class'>$status_text</td>";
                    echo "<td>$ultimo_login</td>";
                    echo "<td>" . date('d/m/Y H:i', strtotime($admin['created_at'])) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p>No hay administradores registrados.</p>";
            }
            echo "</div>";
            
        } catch (PDOException $e) {
            echo "<div class='section danger'>";
            echo "<h3>❌ Error de Base de Datos</h3>";
            echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "</div>";
        }
        ?>

        <div class="section warning">
            <h3>⚠️ Instrucciones de Migración</h3>
            <ol>
                <li><strong>Crear más administradores:</strong> Usa el formulario inferior para crear cuentas adicionales</li>
                <li><strong>Comunicar credenciales:</strong> Informa a cada admin sus credenciales temporales</li>
                <li><strong>Verificar acceso:</strong> Cada admin debe hacer login y cambiar su contraseña</li>
                <li><strong>Deshabilitar contraseña maestra:</strong> Una vez todos migren, desactiva el sistema anterior</li>
                <li><strong>Eliminar este archivo:</strong> Por seguridad, elimina este archivo después de la migración</li>
            </ol>
        </div>

        <div class="section">
            <h3>➕ Crear Nuevo Administrador</h3>
            <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                <input type="hidden" name="action" value="create_admin">
                <table>
                    <tr>
                        <td><label for="nombre">Nombre:</label></td>
                        <td><input type="text" id="nombre" name="nombre" required style="width: 100%; padding: 8px;"></td>
                    </tr>
                    <tr>
                        <td><label for="email">Email:</label></td>
                        <td><input type="email" id="email" name="email" required style="width: 100%; padding: 8px;"></td>
                    </tr>
                    <tr>
                        <td><label for="password">Contraseña temporal:</label></td>
                        <td><input type="password" id="password" name="password" required style="width: 100%; padding: 8px;"></td>
                    </tr>
                    <tr>
                        <td><label for="rol">Rol:</label></td>
                        <td>
                            <select id="rol" name="rol" style="width: 100%; padding: 8px;">
                                <option value="admin">Administrador</option>
                                <option value="superadmin">Superadministrador</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" style="text-align: center;">
                            <button type="submit" class="btn">Crear Administrador</button>
                        </td>
                    </tr>
                </table>
            </form>
        </div>

        <?php
        // Procesar formulario de creación
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_admin') {
            $nombre = trim($_POST['nombre'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $rol = $_POST['rol'] ?? 'admin';
            
            if (!empty($nombre) && !empty($email) && !empty($password)) {
                try {
                    $db = getDbConnection();
                    
                    // Verificar si el email ya existe
                    $stmt = $db->prepare("SELECT id FROM admins WHERE email = ?");
                    $stmt->execute([$email]);
                    
                    if ($stmt->fetch()) {
                        echo "<div class='section danger'>";
                        echo "<h3>❌ Error</h3>";
                        echo "<p>Ya existe un administrador con ese email.</p>";
                        echo "</div>";
                    } else {
                        // Crear nuevo admin
                        $password_hash = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $db->prepare("INSERT INTO admins (nombre, email, password_hash, rol, activo) VALUES (?, ?, ?, ?, 1)");
                        $stmt->execute([$nombre, $email, $password_hash, $rol]);
                        
                        // Asignar acceso a todas las líneas de negocio
                        $admin_id = $db->lastInsertId();
                        $stmt = $db->prepare("INSERT INTO admin_linea_negocio (admin_id, linea_negocio_id) SELECT ?, id FROM lineas_negocio");
                        $stmt->execute([$admin_id]);
                        
                        echo "<div class='section success'>";
                        echo "<h3>✅ Administrador Creado</h3>";
                        echo "<p><strong>Nombre:</strong> $nombre</p>";
                        echo "<p><strong>Email:</strong> $email</p>";
                        echo "<p><strong>Rol:</strong> $rol</p>";
                        echo "<p><strong>Contraseña temporal:</strong> $password</p>";
                        echo "<p><em>⚠️ Comunica estas credenciales al nuevo administrador y pídele que cambie la contraseña.</em></p>";
                        echo "</div>";
                        
                        // Refrescar para mostrar la lista actualizada
                        echo "<script>setTimeout(function(){ location.reload(); }, 3000);</script>";
                    }
                } catch (PDOException $e) {
                    echo "<div class='section danger'>";
                    echo "<h3>❌ Error</h3>";
                    echo "<p>Error creando administrador: " . htmlspecialchars($e->getMessage()) . "</p>";
                    echo "</div>";
                }
            } else {
                echo "<div class='section danger'>";
                echo "<h3>❌ Error</h3>";
                echo "<p>Todos los campos son obligatorios.</p>";
                echo "</div>";
            }
        }
        ?>

        <div class="section danger">
            <h3>🚨 Desactivar Sistema Anterior</h3>
            <p>Una vez que todos los administradores hayan migrado al nuevo sistema, puedes desactivar la contraseña maestra:</p>
            <div class="code">
                // En includes/functions.php, comenta o elimina esta línea:
                // define('MASTER_PASSWORD_HASH', '$2y$12$...');
                
                // Y en login.php, elimina la lógica de authenticate_master_password()
            </div>
            <p><strong>⚠️ Solo hazlo cuando estés 100% seguro de que todos pueden acceder con email/contraseña.</strong></p>
        </div>

        <div class="section info">
            <h3>🔧 Pasos Finales</h3>
            <ol>
                <li>Todos los administradores deben cambiar sus contraseñas temporales</li>
                <li>Verificar que todos pueden acceder sin problemas</li>
                <li>Desactivar el sistema de contraseña maestra</li>
                <li>Eliminar este archivo: <code>admin_migration_helper.php</code></li>
                <li>Actualizar la documentación interna</li>
            </ol>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="index.php" class="btn">🏠 Volver al Dashboard</a>
            <a href="logout.php" class="btn btn-danger">🚪 Cerrar Sesión</a>
        </div>
    </div>
</body>
</html> 