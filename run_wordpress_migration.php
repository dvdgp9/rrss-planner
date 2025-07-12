<?php
require_once 'config/db.php';

try {
    $db = getDbConnection();
    
    echo "Iniciando migración WordPress...\n";
    
    // Leer y ejecutar el archivo SQL
    $sql = file_get_contents('database_migration_wordpress.sql');
    
    // Dividir por punto y coma para ejecutar cada statement por separado
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue; // Saltar comentarios y líneas vacías
        }
        
        echo "Ejecutando: " . substr($statement, 0, 50) . "...\n";
        $db->exec($statement);
    }
    
    echo "✅ Migración WordPress completada exitosamente!\n";
    
    // Mostrar estado actual de las líneas de negocio
    echo "\n--- Estado de líneas de negocio ---\n";
    $stmt = $db->query("SELECT nombre, slug, wordpress_url, wordpress_enabled FROM lineas_negocio ORDER BY nombre");
    $lineas = $stmt->fetchAll();
    
    foreach ($lineas as $linea) {
        $status = $linea['wordpress_enabled'] ? '✅ Habilitado' : '❌ Deshabilitado';
        $url = $linea['wordpress_url'] ?: 'No configurado';
        echo "- {$linea['nombre']} ({$linea['slug']}): {$status} - {$url}\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Error en la migración: " . $e->getMessage() . "\n";
    exit(1);
}
?> 