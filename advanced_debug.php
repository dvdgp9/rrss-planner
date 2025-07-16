<?php
// Advanced debug script to identify the real cause of site failure
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Advanced Debug - Site Failure Analysis</h1>";

// Test 1: Database Connection
echo "<h2>1. Database Connection Test</h2>";
try {
    require_once 'config/db.php';
    $db = getDbConnection();
    echo "<p>✅ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Test 2: Admins Table Check
echo "<h2>2. Admins Table Verification</h2>";
try {
    $stmt = $db->query("SELECT COUNT(*) as count FROM admins");
    $count = $stmt->fetch()['count'];
    echo "<p>✅ Admins table exists with {$count} records</p>";
    
    // Show admin records
    $stmt = $db->query("SELECT id, nombre, email, rol, activo FROM admins");
    $admins = $stmt->fetchAll();
    echo "<ul>";
    foreach ($admins as $admin) {
        echo "<li>ID: {$admin['id']}, Name: {$admin['nombre']}, Email: {$admin['email']}, Role: {$admin['rol']}, Active: {$admin['activo']}</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p>❌ Admins table error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 3: Functions.php Loading
echo "<h2>3. Functions.php Loading Test</h2>";
try {
    require_once 'includes/functions.php';
    echo "<p>✅ Functions.php loaded successfully</p>";
} catch (Exception $e) {
    echo "<p>❌ Functions.php loading failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Test 4: Authentication Function Test
echo "<h2>4. Authentication Function Test</h2>";
try {
    if (function_exists('authenticate_user')) {
        echo "<p>✅ authenticate_user() function exists</p>";
        
        // Test authentication with known credentials
        $result = authenticate_user('admin@ebone.es', 'admin123!');
        if ($result) {
            echo "<p>✅ Authentication successful with admin@ebone.es</p>";
        } else {
            echo "<p>❌ Authentication failed with admin@ebone.es</p>";
        }
        
    } else {
        echo "<p>❌ authenticate_user() function does NOT exist</p>";
    }
    
    // Test get_current_admin_user function
    if (function_exists('get_current_admin_user')) {
        echo "<p>✅ get_current_admin_user() function exists</p>";
    } else {
        echo "<p>❌ get_current_admin_user() function does NOT exist</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Authentication test error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 5: Session Test
echo "<h2>5. Session Test</h2>";
try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    echo "<p>✅ Session started successfully</p>";
    echo "<p>Session ID: " . session_id() . "</p>";
} catch (Exception $e) {
    echo "<p>❌ Session error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 6: Basic Authentication Flow
echo "<h2>6. Basic Authentication Flow Test</h2>";
try {
    // Clear any existing session
    session_unset();
    
    // Test is_authenticated function
    if (function_exists('is_authenticated')) {
        $auth_status = is_authenticated();
        echo "<p>✅ is_authenticated() function works, result: " . ($auth_status ? 'true' : 'false') . "</p>";
    } else {
        echo "<p>❌ is_authenticated() function does NOT exist</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Authentication flow error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 7: Check for PHP errors in key files
echo "<h2>7. PHP Syntax Check</h2>";
$files_to_check = [
    'includes/functions.php',
    'login.php',
    'index.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        $output = shell_exec("php -l $file 2>&1");
        if (strpos($output, 'No syntax errors') !== false) {
            echo "<p>✅ $file - No syntax errors</p>";
        } else {
            echo "<p>❌ $file - Syntax errors found:</p>";
            echo "<pre>" . htmlspecialchars($output) . "</pre>";
        }
    } else {
        echo "<p>❌ $file - File not found</p>";
    }
}

// Test 8: Memory and Error Logs
echo "<h2>8. System Status</h2>";
echo "<p>Memory Usage: " . memory_get_usage(true) . " bytes</p>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Error Log Location: " . ini_get('error_log') . "</p>";

echo "<h2>9. Next Steps</h2>";
echo "<p>If all tests pass, the issue might be:</p>";
echo "<ul>";
echo "<li>Apache/Nginx configuration</li>";
echo "<li>File permissions</li>";
echo "<li>htaccess issues</li>";
echo "<li>Server-level error (check server logs)</li>";
echo "</ul>";

echo "<p><a href='index.php'>Try accessing index.php directly</a></p>";
echo "<p><a href='login.php'>Try accessing login.php directly</a></p>";
?> 