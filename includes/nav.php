<?php
// includes/nav.php
// Ensure functions.php is available for getDbConnection()
// Adjust path if nav.php is moved deeper or if functions.php is elsewhere.
if (file_exists(dirname(__FILE__) . '/functions.php')) {
    require_once dirname(__FILE__) . '/functions.php';
} elseif (file_exists(dirname(__FILE__) . '/../includes/functions.php')) { // If nav.php is in a subdir of includes
    require_once dirname(__FILE__) . '/../includes/functions.php';
} elseif (file_exists('includes/functions.php')) { // Common case from root
    require_once 'includes/functions.php';
} else {
    // Fallback or error if functions.php is not found
    // This might happen if nav.php is included from a script in a subdirectory
    // For now, we assume one of the above paths will work.
    // Consider a more robust way to find functions.php if structure is complex.
    if (file_exists('../includes/functions.php')) { // e.g. if current file is in a subdir like /admin
         require_once '../includes/functions.php';
    } else {
        die("Error: functions.php not found from nav.php. Base path: " . dirname(__FILE__));
    }
}


$db = getDbConnection();
$nav_lineas_negocio = [];
if ($db) {
    try {
        $stmt_nav = $db->query("SELECT nombre, slug FROM lineas_negocio ORDER BY nombre ASC");
        $nav_lineas_negocio = $stmt_nav->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Log error or handle gracefully
        error_log("Error fetching lineas_negocio for navigation: " . $e->getMessage());
    }
}

// Determine the current page/slug to set the 'active' class
// This needs to be robust. A common way is to get it from the script name or a query param.
$current_page_uri = $_SERVER['REQUEST_URI']; // Example: /planner.php?slug=cubofit or /index.php
$current_script_name = basename($_SERVER['SCRIPT_NAME']); // Example: planner.php or index.php
$current_slug_param = isset($_GET['slug']) ? $_GET['slug'] : null;

$logo_path = 'assets/images/logos/loop-logo.png'; // Corrected path and filename
// Ensure the logo path is correct relative to the root
// If nav.php is included from different depths, this might need adjustment or be an absolute path from web root
// For simplicity, assuming 'assets/' is accessible from the root.

// Fallback if logo is not found - good practice
if (!file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($logo_path, '/'))) {
    // If the direct path check fails, try relative to the script including nav.php
    // This is a bit tricky because DOCUMENT_ROOT might not be set or correct in all CLI/web server configs
    // A more robust solution might involve a global config for base_url or base_path
    $potential_logo_path_from_root = ltrim($logo_path, '/');
    if (file_exists($potential_logo_path_from_root)) {
         $logo_path_final = $potential_logo_path_from_root;
    } else {
        // Try one level up if included from a subdir like /includes/
        if (file_exists('../' . $potential_logo_path_from_root)) {
            $logo_path_final = '../' . $potential_logo_path_from_root;
        } else {
            $logo_path_final = '#'; // Fallback if logo not found
            error_log("Lööp navigation logo not found at expected path: " . $logo_path);
        }
    }
} else {
    $logo_path_final = '/' . ltrim($logo_path, '/'); // Prepend / to make it root-relative
}


?>
<div class="nav-simple">
    <a href="<?php echo ($current_script_name === 'index.php' && empty($current_slug_param)) ? '#' : '/index.php'; ?>" class="nav-logo-link">
        <img src="<?php echo htmlspecialchars($logo_path_final); ?>" alt="Lööp Logo" class="nav-loop-logo">
    </a>
    <a href="/index.php" class="nav-item <?php echo ($current_script_name === 'index.php' && empty($current_slug_param)) ? 'active' : ''; ?>">Dashboard</a>
    
    <?php foreach ($nav_lineas_negocio as $linea): ?>
        <a href="/planner.php?slug=<?php echo htmlspecialchars($linea['slug']); ?>" 
           class="nav-item <?php echo ($current_script_name === 'planner.php' && $current_slug_param === $linea['slug']) ? 'active' : ''; ?>">
            <?php echo htmlspecialchars($linea['nombre']); ?>
        </a>
    <?php endforeach; ?>
    
    <?php if (is_superadmin()): ?>
        <a href="/configuracion.php" class="nav-item <?php echo ($current_script_name === 'configuracion.php') ? 'active' : ''; ?>">
            <i class="fas fa-cog"></i> Configuración
        </a>
    <?php endif; ?>
    
    <a href="/mi_cuenta.php" class="nav-item <?php echo ($current_script_name === 'mi_cuenta.php') ? 'active' : ''; ?>">
        <i class="fas fa-user"></i> Mi cuenta
    </a>
    
    <a href="/logout.php" class="nav-item nav-logout">
        <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
    </a>
</div>
<script>
// Small script to adjust logo path if it was set to a relative one from a deeper inclusion point
// This is a fallback if the PHP path resolution isn't perfect for all server setups / inclusion depths.
document.addEventListener('DOMContentLoaded', function() {
    const logoImg = document.querySelector('.nav-loop-logo');
    if (logoImg && logoImg.src.includes('../')) { // If it's a relative path trying to go up
        // Check if the image is actually loading. If not, try a root-relative path.
        const testImage = new Image();
        testImage.onload = function() { /* Image loaded, path is fine */ };
        testImage.onerror = function() {
            // Image failed to load, try a root-relative path assuming assets/ is at root
            const originalSrc = logoImg.getAttribute('src');
            const parts = originalSrc.split('/');
            const filename = parts.pop();
            // A common structure is assets/images/brand/filename.png
            // This is a heuristic. A defined base URL in PHP would be more robust.
            if (originalSrc.includes('assets/images/brand/')) {
                 const brandPath = 'assets/images/brand/' + filename;
                 // Check if this path works from root
                 const rootRelativePath = '/' + brandPath;
                 // Test this new path
                 const testRootImage = new Image();
                 testRootImage.onload = function() { logoImg.src = rootRelativePath; };
                 testRootImage.onerror = function() { /* still not found, leave as is or hide */ };
                 testRootImage.src = rootRelativePath;
            }
        };
        testImage.src = logoImg.src;
    }
});
</script>
<style>
/* Basic styling for the nav, can be expanded or moved to styles.css */
.nav-simple {
    display: flex;
    align-items: center;
    padding: 10px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-bottom: 1px solid #dee2e6;
    flex-wrap: wrap;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.nav-logo-link {
    margin-right: 20px;
    display: inline-block;
}

.nav-loop-logo {
    height: 40px;
    width: auto;
    vertical-align: middle;
    filter: brightness(0) invert(1); /* Makes logo white */
}

.nav-item {
    padding: 10px 15px;
    text-decoration: none;
    color: rgba(255, 255, 255, 0.9);
    border-radius: 8px;
    margin-right: 8px;
    transition: all 0.3s ease;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 6px;
}

.nav-item:hover {
    background-color: rgba(255, 255, 255, 0.15);
    color: white;
    transform: translateY(-1px);
}

.nav-item.active {
    background-color: rgba(255, 255, 255, 0.2);
    color: white;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

.nav-logout {
    margin-left: auto;
    background-color: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: rgba(255, 255, 255, 0.9);
}

.nav-logout:hover {
    background-color: rgba(255, 255, 255, 0.2);
    color: white;
    border-color: rgba(255, 255, 255, 0.3);
}

.nav-logout i {
    font-size: 14px;
}

/* Responsive design */
@media (max-width: 768px) {
    .nav-simple {
        flex-direction: column;
        gap: 10px;
        padding: 15px;
    }
    
    .nav-logout {
        margin-left: 0;
        order: 99;
    }
    
    .nav-logo-link {
        margin-right: 0;
        margin-bottom: 10px;
    }
}
</style> 