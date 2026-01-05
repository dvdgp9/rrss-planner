<?php
// includes/nav.php
if (file_exists(dirname(__FILE__) . '/functions.php')) {
    require_once dirname(__FILE__) . '/functions.php';
} elseif (file_exists(dirname(__FILE__) . '/../includes/functions.php')) {
    require_once dirname(__FILE__) . '/../includes/functions.php';
} elseif (file_exists('includes/functions.php')) {
    require_once 'includes/functions.php';
} else {
    if (file_exists('../includes/functions.php')) {
         require_once '../includes/functions.php';
    } else {
        die("Error: functions.php not found from nav.php.");
    }
}

$db = getDbConnection();
$nav_lineas_negocio = [];
if ($db) {
    try {
        $stmt_nav = $db->query("SELECT nombre, slug, logo_filename FROM lineas_negocio ORDER BY nombre ASC");
        $nav_lineas_negocio = $stmt_nav->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching lineas_negocio for navigation: " . $e->getMessage());
    }
}

$current_script_name = basename($_SERVER['SCRIPT_NAME']);
$current_slug_param = isset($_GET['slug']) ? $_GET['slug'] : null;
$logo_path_final = '/assets/images/logos/loop-logo.png';
?>

<nav class="main-nav">
    <div class="nav-container">
        <div class="nav-brand">
            <a href="/index.php" class="nav-logo-link">
                <img src="<?php echo htmlspecialchars($logo_path_final); ?>" alt="Lööp" class="nav-logo">
            </a>
        </div>
        
        <div class="nav-links">
            <a href="/index.php" class="nav-link <?php echo ($current_script_name === 'index.php' && empty($current_slug_param)) ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i>
                <span>Dashboard</span>
            </a>
            
            <div class="nav-divider"></div>
            
            <?php foreach ($nav_lineas_negocio as $linea): ?>
                <a href="/planner.php?slug=<?php echo htmlspecialchars($linea['slug']); ?>" 
                   class="nav-link <?php echo ($current_script_name === 'planner.php' && $current_slug_param === $linea['slug']) ? 'active' : ''; ?>">
                    <span><?php echo htmlspecialchars($linea['nombre']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        
        <div class="nav-actions">
            <?php if (is_superadmin()): ?>
                <a href="/configuracion.php" class="nav-link nav-link-icon <?php echo ($current_script_name === 'configuracion.php') ? 'active' : ''; ?>" title="Configuración">
                    <i class="fas fa-cog"></i>
                </a>
            <?php endif; ?>
            
            <a href="/mi_cuenta.php" class="nav-link nav-link-icon <?php echo ($current_script_name === 'mi_cuenta.php') ? 'active' : ''; ?>" title="Mi cuenta">
                <i class="fas fa-user-circle"></i>
            </a>
            
            <a href="/logout.php" class="nav-link nav-link-logout" title="Cerrar Sesión">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>
</nav> 