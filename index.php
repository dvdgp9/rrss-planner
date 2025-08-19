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
        
        // Obtener la publicación más reciente de esta línea
        $stmt = $db->prepare("
            SELECT p.*, COUNT(prs.red_social_id) as redes_count
            FROM publicaciones p
            LEFT JOIN publicacion_red_social prs ON p.id = prs.publicacion_id
            WHERE p.linea_negocio_id = ?
            GROUP BY p.id
            ORDER BY p.fecha_programada DESC, p.id DESC
            LIMIT 1
        ");
        $stmt->execute([$linea['id']]);
        $linea['ultima_publicacion'] = $stmt->fetch();
        
        // Obtener próxima publicación programada (que no esté publicada aún)
        $stmt = $db->prepare("
            SELECT p.*, COUNT(prs.red_social_id) as redes_count
            FROM publicaciones p
            LEFT JOIN publicacion_red_social prs ON p.id = prs.publicacion_id
            WHERE p.linea_negocio_id = ? AND p.estado = 'programado' AND p.fecha_programada >= CURRENT_DATE()
            GROUP BY p.id
            ORDER BY p.fecha_programada ASC, p.id ASC
            LIMIT 1
        ");
        $stmt->execute([$linea['id']]);
        $linea['proxima_publicacion'] = $stmt->fetch();
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
    <!-- Google Fonts - Open Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
                // Determinar URLs, logos y colores según el ID (y slug)
                $paginaUrl = 'planner.php?slug=' . urlencode($linea['slug'] ?? 'error'); // Default URL structure
                $logoUrl = 'assets/images/logos/' . ($linea['logo_filename'] ?? 'default.png');
                $colorPrincipal = '';
                $colorSecundario = '';
                $bgColorStyle = ''; // Para manejar el gradiente

                // Colores específicos (puedes ajustar o quitar el switch si el slug es suficiente)
                switch($linea['id']) {
                    case 1: // Ebone
                        $colorPrincipal = '#23AAC5';
                        $colorSecundario = '#1a8da5'; 
                        $bgColorStyle = 'background: linear-gradient(90deg, ' . $colorPrincipal . ' 0%, ' . $colorSecundario . ' 100%);';
                        break;
                    case 2: // Cubofit
                        $colorPrincipal = '#E23633';
                        $colorSecundario = '#c12f2c'; 
                        $bgColorStyle = 'background: linear-gradient(90deg, ' . $colorPrincipal . ' 0%, ' . $colorSecundario . ' 100%);';
                        break;
                    case 3: // Uniges
                        $unigesColor1 = '#9B6FCE';
                        $unigesColor2 = '#032551';
                        $colorPrincipal = $unigesColor1; 
                        $bgColorStyle = 'background: linear-gradient(90deg, ' . $unigesColor1 . ' 0%, ' . $unigesColor2 . ' 100%);';
                        break;
                    case 4: // Teia
                        $colorPrincipal = '#009970';
                        $colorSecundario = '#007a5a'; 
                        $bgColorStyle = 'background: linear-gradient(90deg, ' . $colorPrincipal . ' 0%, ' . $colorSecundario . ' 100%);';
                        break;
                    default: // Otros (usarán slug para URL pero colores por defecto)
                        $logoUrl = 'assets/images/logos/' . ($linea['logo_filename'] ?? 'default.png'); 
                        $colorPrincipal = '#6c757d'; 
                        $colorSecundario = '#5a6268'; 
                        $bgColorStyle = 'background: linear-gradient(90deg, ' . $colorPrincipal . ' 0%, ' . $colorSecundario . ' 100%);';
                        break;
                }

                // Fallback por si el logo no existe
                if (!file_exists($logoUrl)) {
                    $logoUrl = 'assets/images/logos/default.png'; // Un logo genérico
                }
            ?>
            <div class="dashboard-card">
                <div class="card-header" style="<?php echo $bgColorStyle; ?> color: white;">
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
                    
                    <!-- Última publicación -->
                    <?php if (isset($linea['ultima_publicacion']) && $linea['ultima_publicacion']): ?>
                    <div class="pub-preview">
                        <div class="pub-preview-title">Última publicación</div>
                        <div class="pub-content"><?php echo truncateText($linea['ultima_publicacion']['contenido'], 100); ?></div>
                        <div class="pub-meta">
                            <span>
                                <i class="far fa-calendar"></i> 
                                <?php echo date('d/m/y', strtotime($linea['ultima_publicacion']['fecha_programada'])); ?>
                            </span>
                            <span class="badge <?php 
                                echo $linea['ultima_publicacion']['estado'] === 'borrador' ? 'badge-draft' : 
                                     ($linea['ultima_publicacion']['estado'] === 'programado' ? 'badge-scheduled' : 'badge-published'); 
                            ?>">
                                <?php echo ucfirst($linea['ultima_publicacion']['estado']); ?>
                            </span>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="no-pub">No hay publicaciones disponibles</div>
                    <?php endif; ?>
                    
                    <!-- Próxima publicación programada -->
                    <?php if (isset($linea['proxima_publicacion']) && $linea['proxima_publicacion']): ?>
                    <div class="pub-preview" style="margin-top: 15px;">
                        <div class="pub-preview-title">Próxima programada</div>
                        <div class="pub-content"><?php echo truncateText($linea['proxima_publicacion']['contenido'], 100); ?></div>
                        <div class="pub-meta">
                            <span>
                                <i class="far fa-calendar"></i> 
                                <?php echo date('d/m/y', strtotime($linea['proxima_publicacion']['fecha_programada'])); ?>
                            </span>
                            <span class="badge badge-scheduled">Programada</span>
                        </div>
                    </div>
                    <?php endif; ?>
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