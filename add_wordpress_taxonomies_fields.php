<?php
// Script para añadir campos de taxonomías de WordPress seleccionadas
// ELIMINAR DESPUÉS DE USAR POR SEGURIDAD

require_once 'config/db.php';

try {
    $db = getDbConnection();
    
    echo "<h2>Añadiendo campos de taxonomías de WordPress a blog_posts...</h2>";
    
    // Verificar si las columnas ya existen
    $stmt = $db->query("DESCRIBE blog_posts");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $columnsToAdd = [
        'wp_categories_selected' => 'TEXT NULL COMMENT "JSON array of selected WordPress category IDs"',
        'wp_tags_selected' => 'TEXT NULL COMMENT "JSON array of selected WordPress tag IDs"'
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
    echo "<p><strong>IMPORTANTE:</strong> Elimina este archivo después de usarlo por seguridad.</p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
</style> 