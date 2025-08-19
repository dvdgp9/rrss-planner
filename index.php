<?php
require_once 'includes/functions.php';
require_authentication();

try {
    $db = getDbConnection();
    
    // Obtener estadísticas para el dashboard
    $stats = [];
    
    // Obtener todas las líneas de negocio
    $stmt = $db->query("SELECT * FROM lineas_negocio ORDER BY id");
    $lineasNegocio = $stmt->fetchAll();
    
    // Para cada línea, contar publicaciones por estado
    foreach ($lineasNegocio as &$linea) {
        $linea['stats'] = [
            'borrador' => 0,
            'programado' => 0,
            'publicado' => 0,
            'total' => 0
        ];
        
        $stmt = $db->prepare("
            SELECT estado, COUNT(*) as total
            FROM publicaciones 
            WHERE linea_negocio_id = ?
            GROUP BY estado
        ");
        $stmt->execute([$linea['id']]);
        $results = $stmt->fetchAll();
        
        foreach ($results as $result) {
            $linea['stats'][$result['estado']] = $result['total'];
            $linea['stats']['total'] += $result['total'];
        }
        
        // Eliminado: previews de última y próxima publicación para simplificar el dashboard
    }
    unset($linea);
    
    // Calcular estadísticas generales
    $totalPublicaciones = 0;
    $totalPorEstado = [
        'borrador' => 0,
        'programado' => 0,
        'publicado' => 0
    ];
    
    foreach ($lineasNegocio as $linea) {
        $totalPublicaciones += $linea['stats']['total'];
        $totalPorEstado['borrador'] += $linea['stats']['borrador'];
        $totalPorEstado['programado'] += $linea['stats']['programado'];
        $totalPorEstado['publicado'] += $linea['stats']['publicado'];
    }
    
    // Obtener todas las redes sociales
    $stmt = $db->query("SELECT * FROM redes_sociales");
    $redesSociales = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lööp - Dashboard</title>
    <link rel="icon" type="image/png" href="assets/images/logos/Loop-favicon.png">
    <!-- Google Fonts - Geist -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@100..900&display=swap" rel="stylesheet">
    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Estilos propios -->
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="app-simple">
        <?php require 'includes/nav.php'; ?>
        
        <?php if (isset($error)): ?>
            <div style="background-color: #f8d7da; color: #721c24; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
                <strong>Error:</strong> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <!-- Resumen general -->
        <div class="summary-section">
            <h2 class="summary-title">Resumen General</h2>
            <div class="summary-stats">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $totalPublicaciones; ?></div>
                    <div class="stat-label">Total de Publicaciones</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $totalPorEstado['borrador']; ?></div>
                    <div class="stat-label">Borradores</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $totalPorEstado['programado']; ?></div>
                    <div class="stat-label">Programadas</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $totalPorEstado['publicado']; ?></div>
                    <div class="stat-label">Publicadas</div>
                </div>
            </div>
        </div>

        <!-- Barra de acciones profesional -->
        <div class="dashboard-actions">
            <div class="actions-left">
                <h3>Líneas de Negocio</h3>
                <p>Gestiona todas tus líneas de negocio desde aquí</p>
            </div>
            <?php if (is_superadmin()): ?>
            <div class="actions-right">
                <button id="btnNuevaLinea" class="btn btn-primary btn-action">
                    <i class="fas fa-plus"></i> Nueva Línea de Negocio
                </button>
            </div>
            <?php endif; ?>
        </div>

        <?php if (is_superadmin()): ?>
        <!-- Modal para Nueva Línea de Negocio -->
        <div id="modalNuevaLinea" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Crear Nueva Línea de Negocio</h2>
                    <span class="close-button" id="closeNuevaLineaModal">&times;</span>
                </div>
                <div class="modal-body">
                    <form id="formNuevaLinea">
                        <div class="form-group">
                            <label for="nombreLinea">Nombre de la línea:</label>
                            <input type="text" id="nombreLinea" name="nombre" required>
                        </div>
                        <div class="form-group">
                            <label for="logoFilenameLinea">Archivo del logo (ej: logo.png):</label>
                            <input type="text" id="logoFilenameLinea" name="logo_filename" required>
                            <small>Asegúrate de que el archivo exista en `assets/images/logos/`</small>
                        </div>
                        <div class="form-group">
                            <label for="slugLinea">Slug (ej: nombre-linea):</label>
                            <input type="text" id="slugLinea" name="slug" required>
                            <small>Usar minúsculas, números y guiones. Debe ser único.</small>
                        </div>
                        <button type="submit" class="btn btn-primary">Guardar Línea de Negocio</button>
                    </form>
                    <div id="modalNuevaLineaMessage" style="margin-top: 15px;"></div>
                </div>
            </div>
        </div>
        <!-- Fin del Modal -->
        <?php endif; ?>

        <!-- Cards de líneas de negocio -->
        <div class="dashboard-cards">
            <?php foreach ($lineasNegocio as $index => $linea): 
                // URL por línea de negocio (logo/colores dinámicos eliminados)
                $paginaUrl = 'planner.php?slug=' . urlencode($linea['slug'] ?? 'error');
            ?>
            <div class="dashboard-card">
                <div class="card-header">
                    <h2><?php echo htmlspecialchars($linea['nombre']); ?></h2>
                </div>
                <div class="card-body">
                    <div class="stat-item">
                        <span>Borradores</span>
                        <span class="badge badge-draft"><?php echo $linea['stats']['borrador']; ?></span>
                    </div>
                    <div class="stat-item">
                        <span>Programadas</span>
                        <span class="badge badge-scheduled"><?php echo $linea['stats']['programado']; ?></span>
                    </div>
                    <div class="stat-item">
                        <span>Publicadas</span>
                        <span class="badge badge-published"><?php echo $linea['stats']['publicado']; ?></span>
                    </div>
                    <div class="stat-item">
                        <span>Total</span>
                        <span><?php echo $linea['stats']['total']; ?></span>
                    </div>
                    
                    <!-- Previews eliminadas para un dashboard más limpio -->
                </div>
                <div class="card-footer">
                    <a href="<?php echo htmlspecialchars($paginaUrl); ?>" class="btn btn-sm">
                        <i class="fas fa-list"></i> Ver todas las publicaciones
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="assets/js/main.js" defer></script>
</body>
</html>