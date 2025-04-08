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
    <title>Planificador RRSS - Inicio</title>
    <link rel="icon" type="image/png" href="assets/images/logos/isotipo-ebone.png">
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
        <div class="header-simple">
            <h1>Dashboard - Planificador RRSS</h1>
        </div>
        
        <div class="nav-simple">
            <a href="index.php" class="active">Dashboard</a>
            <a href="ebone.php">Ebone Servicios</a>
            <a href="cubofit.php">CUBOFIT</a>
            <a href="uniges.php">Uniges-3</a>
            <a href="teia.php">Teiá</a>
            <a href="logout.php" style="margin-left: auto; background-color: #dc3545; color: white;"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
        </div>
        
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

        <!-- Cards de líneas de negocio -->
        <div class="dashboard-cards">
            <?php foreach ($lineasNegocio as $index => $linea): 
                // Determinar URLs, logos y colores según el ID
                $paginaUrl = '';
                $logoUrl = '';
                $colorPrincipal = '';
                $bgColorStyle = ''; // Para manejar el gradiente

                switch($linea['id']) {
                    case 1: 
                        $paginaUrl = 'ebone.php'; 
                        $logoUrl = 'assets/images/logos/logo-ebone.png'; 
                        $colorPrincipal = '#23AAC5';
                        $bgColorStyle = 'background-color: ' . $colorPrincipal . ';';
                        break;
                    case 2: 
                        $paginaUrl = 'cubofit.php'; 
                        $logoUrl = 'assets/images/logos/logo-cubofit.png';
                        $colorPrincipal = '#E23633';
                        $bgColorStyle = 'background-color: ' . $colorPrincipal . ';';
                        break;
                    case 3: 
                        $paginaUrl = 'uniges.php'; 
                        $logoUrl = 'assets/images/logos/logo-uniges.png';
                        // Colores para el gradiente
                        $unigesColor1 = '#9B6FCE';
                        $unigesColor2 = '#032551';
                        $colorPrincipal = $unigesColor1; // Usamos el primero como referencia si es necesario
                        $bgColorStyle = 'background: linear-gradient(90deg, ' . $unigesColor1 . ' 0%, ' . $unigesColor2 . ' 100%);';
                        break;
                    case 4: 
                        $paginaUrl = 'teia.php'; 
                        $logoUrl = 'assets/images/logos/logo-teia.jpg';
                        $colorPrincipal = '#009970';
                        $bgColorStyle = 'background-color: ' . $colorPrincipal . ';';
                        break;
                    default: 
                        $paginaUrl = 'index.php'; 
                        $logoUrl = ''; // Sin logo específico
                        $colorPrincipal = '#6c757d'; // Un gris por defecto
                        $bgColorStyle = 'background-color: ' . $colorPrincipal . ';';
                        break;
                }
            ?>
            <div class="dashboard-card">
                <div class="card-header" style="<?php echo $bgColorStyle; ?>">
                    <h2><?php echo $linea['nombre']; ?></h2>
                    <?php if (!empty($logoUrl)): ?>
                        <div class="logo-icon">
                            <img src="<?php echo $logoUrl; ?>" alt="Logo <?php echo $linea['nombre']; ?>">
                        </div>
                    <?php else: ?>
                        <div class="icon"><i class="fas fa-building"></i></div> <!-- Icono genérico si no hay logo -->
                    <?php endif; ?>
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
                    <a href="<?php echo $paginaUrl; ?>" class="btn">
                        Ver todas las publicaciones
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>