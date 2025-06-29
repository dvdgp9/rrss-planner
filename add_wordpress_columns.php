<?php
// Script temporal para añadir columnas de WordPress
// ELIMINAR DESPUÉS DE USAR POR SEGURIDAD

require_once 'config/db.php';

try {
    $db = getDbConnection();
    
    echo "<h2>Añadiendo columnas de WordPress a blog_posts...</h2>";
    
    // Verificar si las columnas ya existen
    $stmt = $db->query("DESCRIBE blog_posts");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $columnsToAdd = [
        'wp_post_id' => 'INT NULL',
        'wp_sync_status' => "ENUM('pending', 'synced', 'error') DEFAULT 'pending'",
        'wp_sync_error' => 'TEXT NULL',
        'wp_last_sync' => 'TIMESTAMP NULL'
    ];
    
    foreach ($columnsToAdd as $columnName => $columnDefinition) {
        if (!in_array($columnName, $columns)) {
            echo "<p>Añadiendo columna: <strong>$columnName</strong></p>";
            $sql = "ALTER TABLE blog_posts ADD COLUMN $columnName $columnDefinition";
            $db->exec($sql);
            echo "<p style='color: green;'>✅ Columna $columnName añadida exitosamente</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Columna $columnName ya existe</p>";
        }
    }
    
    echo "<h3 style='color: green;'>✅ Migración completada!</h3>";
    echo "<p><strong>IMPORTANTE:</strong> Elimina este archivo (add_wordpress_columns.php) por seguridad.</p>";
    
    // Mostrar estructura actual
    echo "<h3>Estructura actual de blog_posts:</h3>";
    $stmt = $db->query("DESCRIBE blog_posts");
    $structure = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($structure as $field) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($field['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($field['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($field['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($field['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($field['Default']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { width: 100%; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style> 