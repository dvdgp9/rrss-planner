<?php
/**
 * SCRIPT DE TESTING: Sistema de Administradores
 * 
 * Valida que todas las funcionalidades del sistema de administradores
 * funcionen correctamente antes del despliegue final.
 * 
 * ELIMINAR DESPUÉS DE VALIDAR EL SISTEMA
 */

require_once 'includes/functions.php';

// Verificar autenticación
require_authentication();

// Solo superadmins pueden ejecutar tests
if (!is_superadmin()) {
    die('❌ Este script solo puede ser ejecutado por superadministradores.');
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Testing Sistema de Administradores</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .test-section { margin: 20px 0; padding: 15px; border-radius: 5px; }
        .test-pass { background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .test-fail { background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .test-warning { background-color: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }
        .test-info { background-color: #d1ecf1; border: 1px solid #b3d4fc; color: #0c5460; }
        .test-item { margin: 10px 0; padding: 8px; border-left: 4px solid #007cba; background-color: #f8f9fa; }
        .test-item.pass { border-left-color: #28a745; }
        .test-item.fail { border-left-color: #dc3545; }
        .test-item.warning { border-left-color: #ffc107; }
        h2 { color: #333; margin-top: 30px; }
        .summary { font-size: 1.2em; font-weight: bold; text-align: center; margin: 20px 0; }
        .code { background-color: #f8f9fa; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 0.9em; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Testing Sistema de Administradores</h1>
        <p>Validación completa del sistema antes del despliegue final</p>

        <?php
        $tests_passed = 0;
        $tests_failed = 0;
        $tests_warnings = 0;

        // Función helper para mostrar resultados de tests
        function show_test($title, $condition, $success_msg, $error_msg, $warning = false) {
            global $tests_passed, $tests_failed, $tests_warnings;
            
            if ($condition) {
                if ($warning) {
                    $tests_warnings++;
                    echo "<div class='test-item warning'>⚠️ $title: $success_msg</div>";
                } else {
                    $tests_passed++;
                    echo "<div class='test-item pass'>✅ $title: $success_msg</div>";
                }
            } else {
                $tests_failed++;
                echo "<div class='test-item fail'>❌ $title: $error_msg</div>";
            }
        }

        // TEST 1: Verificar estructura de base de datos
        echo "<h2>🗂️ Test 1: Estructura de Base de Datos</h2>";
        
        try {
            $db = getDbConnection();
            
            // Verificar tabla admins
            $stmt = $db->query("SHOW TABLES LIKE 'admins'");
            show_test("Tabla admins", $stmt->rowCount() > 0, "Tabla existe", "Tabla no encontrada");
            
            // Verificar tabla admin_linea_negocio
            $stmt = $db->query("SHOW TABLES LIKE 'admin_linea_negocio'");
            show_test("Tabla admin_linea_negocio", $stmt->rowCount() > 0, "Tabla existe", "Tabla no encontrada");
            
            // Verificar campos de tabla admins
            $stmt = $db->query("DESCRIBE admins");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            show_test("Campo email", in_array('email', $columns), "Campo existe", "Campo faltante");
            show_test("Campo password_hash", in_array('password_hash', $columns), "Campo existe", "Campo faltante");
            show_test("Campo rol", in_array('rol', $columns), "Campo existe", "Campo faltante");
            
            // Verificar foreign keys
            $stmt = $db->query("SELECT COUNT(*) as count FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'admin_linea_negocio' AND REFERENCED_TABLE_NAME IS NOT NULL");
            show_test("Foreign keys", $stmt->fetch()['count'] >= 2, "Foreign keys configuradas", "Foreign keys faltantes");
            
        } catch (Exception $e) {
            show_test("Conexión BD", false, "", "Error: " . $e->getMessage());
        }

        // TEST 2: Verificar funciones de autenticación
        echo "<h2>🔐 Test 2: Funciones de Autenticación</h2>";
        
        show_test("Función authenticate_user", function_exists('authenticate_user'), "Función existe", "Función no encontrada");
        show_test("Función authenticate_master_password", function_exists('authenticate_master_password'), "Función existe", "Función no encontrada");
        show_test("Función get_current_admin_user", function_exists('get_current_admin_user'), "Función existe", "Función no encontrada");
        show_test("Función is_superadmin", function_exists('is_superadmin'), "Función existe", "Función no encontrada");
        show_test("Función user_can_access_linea", function_exists('user_can_access_linea'), "Función existe", "Función no encontrada");
        show_test("Función logout_user", function_exists('logout_user'), "Función existe", "Función no encontrada");

        // TEST 3: Verificar usuario actual
        echo "<h2>👤 Test 3: Usuario Actual</h2>";
        
        $current_user = get_current_admin_user();
        show_test("Usuario autenticado", $current_user !== null, "Usuario válido", "No hay usuario autenticado");
        show_test("Método de auth", isset($current_user['auth_method']), "Método detectado: " . ($current_user['auth_method'] ?? 'desconocido'), "Método no detectado");
        
        if ($current_user && $current_user['auth_method'] === 'user') {
            show_test("Email del usuario", !empty($current_user['email']), "Email: " . $current_user['email'], "Email vacío");
            show_test("Rol del usuario", !empty($current_user['rol']), "Rol: " . $current_user['rol'], "Rol vacío");
        }

        // TEST 4: Verificar datos en base de datos
        echo "<h2>📊 Test 4: Datos del Sistema</h2>";
        
        try {
            // Contar administradores
            $stmt = $db->query("SELECT COUNT(*) as count FROM admins");
            $admin_count = $stmt->fetch()['count'];
            show_test("Administradores creados", $admin_count > 0, "Total: $admin_count", "No hay administradores");
            
            // Verificar superadmin
            $stmt = $db->query("SELECT COUNT(*) as count FROM admins WHERE rol = 'superadmin'");
            $superadmin_count = $stmt->fetch()['count'];
            show_test("Superadministradores", $superadmin_count > 0, "Total: $superadmin_count", "No hay superadministradores");
            
            // Verificar permisos
            $stmt = $db->query("SELECT COUNT(*) as count FROM admin_linea_negocio");
            $permisos_count = $stmt->fetch()['count'];
            show_test("Permisos asignados", $permisos_count > 0, "Total: $permisos_count", "No hay permisos asignados");
            
        } catch (Exception $e) {
            show_test("Consulta datos", false, "", "Error: " . $e->getMessage());
        }

        // TEST 5: Verificar archivos del sistema
        echo "<h2>📁 Test 5: Archivos del Sistema</h2>";
        
        show_test("functions.php", file_exists('includes/functions.php'), "Archivo existe", "Archivo no encontrado");
        show_test("login.php", file_exists('login.php'), "Archivo existe", "Archivo no encontrado");
        show_test("logout.php", file_exists('logout.php'), "Archivo existe", "Archivo no encontrado");
        show_test("admin_migration_helper.php", file_exists('admin_migration_helper.php'), "Archivo existe", "Archivo no encontrado");
        show_test("Documentación", file_exists('DOCUMENTACION_NUEVOS_ADMINS.md'), "Archivo existe", "Archivo no encontrado");

        // TEST 6: Verificar compatibilidad con sistema anterior
        echo "<h2>🔄 Test 6: Compatibilidad</h2>";
        
        show_test("Constante MASTER_PASSWORD_HASH", defined('MASTER_PASSWORD_HASH'), "Constante existe (compatibilidad)", "Constante no encontrada");
        show_test("Función is_authenticated", function_exists('is_authenticated'), "Función original preservada", "Función no encontrada");
        show_test("Función require_authentication", function_exists('require_authentication'), "Función original preservada", "Función no encontrada");

        // TEST 7: Validar funcionalidades críticas
        echo "<h2>🎯 Test 7: Funcionalidades Críticas</h2>";
        
        try {
            // Test de acceso a líneas de negocio
            $stmt = $db->query("SELECT id FROM lineas_negocio LIMIT 1");
            $linea = $stmt->fetch();
            if ($linea) {
                $can_access = user_can_access_linea($linea['id']);
                show_test("Acceso a líneas de negocio", $can_access, "Acceso permitido", "Acceso denegado");
            }
            
            // Test de verificación de superadmin
            $is_super = is_superadmin();
            show_test("Verificación superadmin", $is_super, "Superadmin confirmado", "No es superadmin", true);
            
        } catch (Exception $e) {
            show_test("Funcionalidades críticas", false, "", "Error: " . $e->getMessage());
        }

        // RESUMEN FINAL
        echo "<div class='summary'>";
        echo "<h2>📋 Resumen de Testing</h2>";
        
        $total_tests = $tests_passed + $tests_failed + $tests_warnings;
        
        if ($tests_failed == 0) {
            echo "<div class='test-section test-pass'>";
            echo "<h3>🎉 ¡SISTEMA VALIDADO EXITOSAMENTE!</h3>";
            echo "<p>✅ Tests pasados: $tests_passed</p>";
            echo "<p>⚠️ Warnings: $tests_warnings</p>";
            echo "<p>❌ Tests fallidos: $tests_failed</p>";
            echo "<p><strong>Total de tests: $total_tests</strong></p>";
            echo "</div>";
        } else {
            echo "<div class='test-section test-fail'>";
            echo "<h3>❌ SISTEMA REQUIERE CORRECCIONES</h3>";
            echo "<p>✅ Tests pasados: $tests_passed</p>";
            echo "<p>⚠️ Warnings: $tests_warnings</p>";
            echo "<p>❌ Tests fallidos: $tests_failed</p>";
            echo "<p><strong>Total de tests: $total_tests</strong></p>";
            echo "</div>";
        }
        echo "</div>";

        // PRÓXIMOS PASOS
        echo "<div class='test-section test-info'>";
        echo "<h3>🚀 Próximos Pasos</h3>";
        if ($tests_failed == 0) {
            echo "<ol>";
            echo "<li>✅ Sistema validado y listo para uso</li>";
            echo "<li>🔄 Migrar usuarios del sistema anterior</li>";
            echo "<li>📚 Distribuir documentación a administradores</li>";
            echo "<li>🗑️ Eliminar archivos de testing y migración</li>";
            echo "<li>🎯 Planificar implementación de Fase 2</li>";
            echo "</ol>";
        } else {
            echo "<ol>";
            echo "<li>❌ Corregir errores encontrados</li>";
            echo "<li>🔄 Ejecutar tests nuevamente</li>";
            echo "<li>📞 Contactar soporte técnico si es necesario</li>";
            echo "</ol>";
        }
        echo "</div>";

        // INFORMACIÓN ADICIONAL
        echo "<div class='test-section test-warning'>";
        echo "<h3>⚠️ Información Importante</h3>";
        echo "<ul>";
        echo "<li><strong>Eliminar archivos:</strong> Después de validar, eliminar este archivo y admin_migration_helper.php</li>";
        echo "<li><strong>Cambiar contraseñas:</strong> Todos los usuarios deben cambiar sus contraseñas temporales</li>";
        echo "<li><strong>Monitorear:</strong> Revisar logs de acceso y actividad unusual</li>";
        echo "<li><strong>Backup:</strong> Crear backup de la base de datos antes de cambios mayores</li>";
        echo "</ul>";
        echo "</div>";
        ?>

        <div style="text-align: center; margin-top: 30px;">
            <a href="admin_migration_helper.php" class="btn">🔧 Panel de Migración</a>
            <a href="index.php" class="btn">🏠 Dashboard</a>
            <a href="logout.php" class="btn btn-danger">🚪 Logout</a>
        </div>
    </div>

    <style>
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 5px;
            background-color: #007cba;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.2s;
        }
        .btn:hover { background-color: #005a8a; }
        .btn-danger { background-color: #dc3545; }
        .btn-danger:hover { background-color: #c82333; }
    </style>
</body>
</html> 