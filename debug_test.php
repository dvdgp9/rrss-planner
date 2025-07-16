<?php
// Debug test file to check database connectivity and table existence
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/db.php';

echo "<h1>Debug Test - Database Check</h1>";

try {
    $db = getDbConnection();
    echo "<p>✓ Database connection successful</p>";
    
    // Check if admins table exists
    $stmt = $db->query("SHOW TABLES LIKE 'admins'");
    $result = $stmt->fetch();
    
    if ($result) {
        echo "<p>✓ 'admins' table exists</p>";
        
        // Check table structure
        $stmt = $db->query("DESCRIBE admins");
        $columns = $stmt->fetchAll();
        echo "<p>✓ Table structure:</p>";
        echo "<ul>";
        foreach ($columns as $column) {
            echo "<li>{$column['Field']} - {$column['Type']}</li>";
        }
        echo "</ul>";
        
        // Check if there are any records
        $stmt = $db->query("SELECT COUNT(*) as count FROM admins");
        $count = $stmt->fetch()['count'];
        echo "<p>✓ Number of admin records: {$count}</p>";
        
    } else {
        echo "<p>❌ 'admins' table does NOT exist</p>";
        echo "<p>You need to run the database migration first.</p>";
    }
    
    // Check existing tables
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll();
    echo "<p>✓ Available tables:</p>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>" . array_values($table)[0] . "</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<p><a href='index.php'>Back to Index</a></p>";
?> 