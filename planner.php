<?php
require_once 'includes/functions.php';
require_authentication();

// Variables para la línea de negocio actual
$current_linea_id = null;
$current_linea_nombre = 'Línea de Negocio Desconocida';
$current_linea_logo_filename = '';
$current_linea_slug = '';
$page_error = null;
$publicaciones = [];
$redesDisponiblesFiltro = [];

// Obtener el slug de la URL
$slug = trim($_GET['slug'] ?? '');

if (empty($slug)) {
    $page_error = "No se ha especificado una línea de negocio (slug faltante).";
} else {
    try {
        $db = getDbConnection();
        
        // 1. Obtener datos de la línea de negocio por slug
        $stmt_linea = $db->prepare("SELECT id, nombre, logo_filename, slug FROM lineas_negocio WHERE slug = ?");
        $stmt_linea->execute([$slug]);
        $linea_negocio_actual = $stmt_linea->fetch();

        if ($linea_negocio_actual) {
            $current_linea_id = $linea_negocio_actual['id'];
            $current_linea_nombre = $linea_negocio_actual['nombre'];
            $current_linea_logo_filename = $linea_negocio_actual['logo_filename'];
            $current_linea_slug = $linea_negocio_actual['slug'];

            // --- Gestión de Ordenación ---
            $sort_options = ['fecha_programada', 'estado'];
            $sort_by = isset($_GET['sort']) && in_array($_GET['sort'], $sort_options) ? $_GET['sort'] : 'fecha_programada';
            $default_sort_dir = ($sort_by === 'fecha_programada') ? 'ASC' : 'DESC'; 
            $sort_dir = isset($_GET['dir']) && in_array(strtoupper($_GET['dir']), ['ASC', 'DESC']) ? strtoupper($_GET['dir']) : $default_sort_dir;
            $next_sort_dir = ($sort_dir === 'ASC') ? 'DESC' : 'ASC';

            // --- Gestión de Filtrado por Redes ---
            $redes_filtro_ids = isset($_GET['redes']) && is_array($_GET['redes']) ? $_GET['redes'] : [];
            $redes_filtro_ids = array_map('intval', $redes_filtro_ids);
            $redes_filtro_ids = array_filter($redes_filtro_ids, function($id) { return $id > 0; });

            // --- Conexión y Obtención de Datos de Publicaciones ---
            $sql_publicaciones = "
                SELECT 
                    p.id, p.contenido, p.imagen_url, p.thumbnail_url, 
                    p.fecha_programada, p.estado, p.linea_negocio_id,
                    GROUP_CONCAT(DISTINCT rs.nombre SEPARATOR '|') as nombres_redes, 
                    COUNT(DISTINCT pf.id) as feedback_count 
                FROM publicaciones p
                LEFT JOIN publicacion_red_social prs ON p.id = prs.publicacion_id
                LEFT JOIN redes_sociales rs ON prs.red_social_id = rs.id
                LEFT JOIN publication_feedback pf ON p.id = pf.publicacion_id
                WHERE p.linea_negocio_id = ?
            ";
            
            // Initialize parameters with the current line of business ID FIRST
            $params_publicaciones = [$current_linea_id]; 

            if (!empty($redes_filtro_ids)) {
                $placeholders = implode(',', array_fill(0, count($redes_filtro_ids), '?'));
                // Subconsulta para asegurar que la publicación esté en TODAS las redes seleccionadas (si se quisiera)
                // O en CUALQUIERA de las redes seleccionadas (más común para filtros)
                // Optamos por "cualquiera" (JOIN estándar)
                $sql_publicaciones .= " AND p.id IN (SELECT DISTINCT prs_filter.publicacion_id FROM publicacion_red_social prs_filter WHERE prs_filter.red_social_id IN ($placeholders)) ";
                // Append social network IDs to the parameters array
                foreach($redes_filtro_ids as $id_red) {
                    $params_publicaciones[] = $id_red; 
                }
            }
            
            $sql_publicaciones .= " GROUP BY p.id ";
            $sql_publicaciones .= " ORDER BY p." . $sort_by . " " . $sort_dir . ", p.id DESC"; 
            
            $stmt_publicaciones = $db->prepare($sql_publicaciones);
            $stmt_publicaciones->execute($params_publicaciones);
            $publicaciones = $stmt_publicaciones->fetchAll();
            
            // 3. Obtener todas las redes sociales disponibles para esta línea (para el filtro)
            $stmt_redes = $db->prepare("
                SELECT r.* 
                FROM redes_sociales r
                JOIN linea_negocio_red_social lnrs ON r.id = lnrs.red_social_id
                WHERE lnrs.linea_negocio_id = ?
                ORDER BY r.nombre ASC
            ");
            $stmt_redes->execute([$current_linea_id]);
            $redesDisponiblesFiltro = $stmt_redes->fetchAll();

        } else {
            $page_error = "La línea de negocio con el slug '{$slug}' no fue encontrada.";
            // Considerar loguear este evento
        }
    } catch (PDOException $e) {
        $page_error = "Error de base de datos: " . $e->getMessage();
        // Considerar loguear $e->getMessage() y mostrar un error más genérico al usuario
    }
}

// Obtener todas las líneas de negocio para el dropdown
$all_lineas_negocio = [];
if (!$page_error) {
    try {
        $stmt_all_lineas = $db->query("SELECT id, nombre, logo_filename, slug FROM lineas_negocio ORDER BY nombre ASC");
        $all_lineas_negocio = $stmt_all_lineas->fetchAll();
    } catch (PDOException $e) {
        // Si no podemos obtener las líneas, no es crítico, solo no mostramos el dropdown
        error_log("Error obteniendo líneas para dropdown: " . $e->getMessage());
    }
}

// Detectar tipo de contenido desde URL
$content_type = trim($_GET['type'] ?? 'social');
if (!in_array($content_type, ['social', 'blog'])) {
    $content_type = 'social'; // Default fallback
}

// Variables para blog posts
$blog_posts = [];

// Si el tipo de contenido es blog, obtener blog posts en lugar de publicaciones sociales
if ($content_type === 'blog' && $current_linea_id && !$page_error) {
    try {
        // --- Gestión de Ordenación para Blog Posts ---
        $blog_sort_options = ['fecha_publicacion', 'titulo', 'estado'];
$blog_sort_by = isset($_GET['sort']) && in_array($_GET['sort'], $blog_sort_options) ? $_GET['sort'] : 'fecha_publicacion';
$blog_default_sort_dir = ($blog_sort_by === 'fecha_publicacion') ? 'DESC' : 'ASC'; 
        $blog_sort_dir = isset($_GET['dir']) && in_array(strtoupper($_GET['dir']), ['ASC', 'DESC']) ? strtoupper($_GET['dir']) : $blog_default_sort_dir;
        
        // Actualizar variables para usar en la función de ordenación
        $sort_by = $blog_sort_by;
        $sort_dir = $blog_sort_dir;
        $next_sort_dir = ($sort_dir === 'ASC') ? 'DESC' : 'ASC';
        
        // --- Obtención de Blog Posts ---
        $sql_blog_posts = "
            SELECT 
                bp.id, bp.titulo, bp.contenido, bp.excerpt, bp.slug,
                bp.imagen_destacada, bp.thumbnail_url, bp.fecha_publicacion, bp.estado,
                bp.linea_negocio_id, bp.wp_categories_selected, bp.wp_tags_selected,
                bp.wp_post_id, bp.wp_sync_status, bp.wp_sync_error, bp.wp_last_sync,
                ln.wordpress_enabled,
                ln.wordpress_url,
                CASE 
                    WHEN bp.estado = 'draft' THEN 'Borrador'
                    WHEN bp.estado = 'scheduled' THEN 'Programado'
                    WHEN bp.estado = 'published' THEN 'Publicado'
                    ELSE bp.estado
                END as estado_display
            FROM blog_posts bp
            JOIN lineas_negocio ln ON bp.linea_negocio_id = ln.id
            WHERE bp.linea_negocio_id = ?
            ORDER BY bp." . $blog_sort_by . " " . $blog_sort_dir . ", bp.id DESC
        ";
        
        $stmt_blog_posts = $db->prepare($sql_blog_posts);
        $stmt_blog_posts->execute([$current_linea_id]);
        $blog_posts = $stmt_blog_posts->fetchAll();
        
    } catch (PDOException $e) {
        error_log("Error obteniendo blog posts: " . $e->getMessage());
        // No establecer page_error para que la página siga funcionando
    }
}

// Funciones para generar enlaces de ordenación (adaptadas)
function getSortLinkPlanner($currentSlug, $currentSortBy, $currentSortDir, $nextSortDir, $columnName, $columnLabel, $currentRedesFiltro, $contentType = 'social') {
    $link = "planner.php?slug=" . urlencode($currentSlug) . "&type=" . urlencode($contentType) . "&sort=" . urlencode($columnName);
    $icon = "";
    $class = "sort-header";
    
    if ($currentSortBy === $columnName) {
        $link .= "&dir=" . urlencode($nextSortDir);
        $iconClass = ($currentSortDir === 'ASC') ? 'fa-sort-up' : 'fa-sort-down';
        $icon = "<i class=\"fas " . $iconClass . " sort-icon\"></i>";
        $class .= " active";
    } else {
        // Para blog posts, el default para fecha es DESC, para título y estado es ASC
        $defaultDir = ($columnName === 'fecha_publicacion' && $contentType === 'blog') ? 'DESC' : 'ASC';
        $link .= "&dir=" . $defaultDir;
        $icon = "<i class=\"fas fa-sort sort-icon\"></i>";
    }
    
    // Solo añadir filtros de redes para contenido social
    if ($contentType === 'social' && !empty($currentRedesFiltro)) {
        foreach($currentRedesFiltro as $redId) {
            $link .= "&redes[]=" . intval($redId);
        }
    }
    return "<th class=\"" . htmlspecialchars($class) . "\" style=\"/* Ancho original si aplica */\"><a href=\"" . htmlspecialchars($link) . "\">" . htmlspecialchars($columnLabel) . " " . $icon . "</a></th>";
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lööp - <?php echo htmlspecialchars($current_linea_nombre); ?></title>
    <link rel="icon" type="image/png" href="assets/images/logos/Loop-favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<?php
// Determinar clase de línea para theming dinámico
$linea_body_class = 'linea-page-dinamica';
if ($current_linea_id) {
    switch($current_linea_id) {
        case 1: $linea_body_class .= ' linea-ebone'; break;
        case 2: $linea_body_class .= ' linea-cubofit'; break;
        case 3: $linea_body_class .= ' linea-uniges'; break;
        case 4: $linea_body_class .= ' linea-teia'; break;
    }
}
?>
<body class="<?php echo $linea_body_class; ?>">

    <div class="app-simple">
        <?php require 'includes/nav.php'; ?>
        
        <?php if ($page_error): ?>
            <div style="background-color: #f8d7da; color: #721c24; padding: 15px; margin-bottom: 20px; border-radius: 4px; text-align: center;">
                <h1>Error</h1>
                <p><?php echo htmlspecialchars($page_error); ?></p>
                <p><a href="index.php" class="btn btn-primary">Volver al Dashboard</a></p>
            </div>
        <?php else: ?>
            <!-- Sección de línea de negocio y pestañas -->
            <div class="planner-header">
                <div class="planner-header-main">
                    <div class="planner-title-section">
                        <div class="planner-logo">
                            <?php if (!empty($current_linea_logo_filename)): ?>
                                <img src="assets/images/logos/<?php echo htmlspecialchars($current_linea_logo_filename); ?>" alt="Logo" class="linea-logo">
                            <?php endif; ?>
                        </div>
                        <div class="planner-title-info">
                            <h1 class="planner-title"><?php echo htmlspecialchars($current_linea_nombre); ?></h1>
                            <div class="planner-subtitle">
                                <select class="linea-selector" onchange="window.location.href = this.value;">
                                    <?php foreach ($all_lineas_negocio as $linea): ?>
                                        <option value="planner.php?slug=<?php echo urlencode($linea['slug']); ?>&type=<?php echo urlencode($content_type); ?>" 
                                                <?php echo ($linea['id'] == $current_linea_id) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($linea['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="planner-actions">
                        <button class="btn btn-secondary btn-share" data-linea-id="<?php echo intval($current_linea_id); ?>" data-linea-nombre="<?php echo htmlspecialchars($current_linea_nombre); ?>" data-content-type="<?php echo $content_type; ?>">
                            <i class="fas fa-share-alt"></i> Compartir Vista
                        </button>
                    </div>
                </div>
                
                <!-- Pestañas de tipo de contenido -->
                <div class="content-type-tabs-container">
                    <div class="content-type-tabs">
                        <a href="planner.php?slug=<?php echo urlencode($current_linea_slug); ?>&type=social" 
                           class="tab-item <?php echo ($content_type === 'social') ? 'active' : ''; ?>">
                            <i class="fas fa-share-alt"></i>
                            <span>Posts Sociales</span>
                        </a>
                        <a href="planner.php?slug=<?php echo urlencode($current_linea_slug); ?>&type=blog" 
                           class="tab-item <?php echo ($content_type === 'blog') ? 'active' : ''; ?>">
                            <i class="fas fa-blog"></i>
                            <span>Blog Posts</span>
                        </a>
                    </div>
                </div>
            </div>
            
            <?php if ($content_type === 'social'): ?>
            <div class="filter-sort-container">
                <form action="planner.php" method="GET" class="filter-form">
                    <input type="hidden" name="slug" value="<?php echo htmlspecialchars($current_linea_slug); ?>">
                    <input type="hidden" name="type" value="<?php echo htmlspecialchars($content_type); ?>">
                     <?php if (isset($_GET['sort'])) { echo '<input type="hidden" name="sort" value="'.htmlspecialchars($_GET['sort']).'">'; } ?>
                     <?php if (isset($_GET['dir'])) { echo '<input type="hidden" name="dir" value="'.htmlspecialchars($_GET['dir']).'">'; } ?>

                    <div class="filter-group">
                        <label>Filtrar por Redes Sociales:</label>
                        <div class="redes-filter">
                            <?php foreach ($redesDisponiblesFiltro as $red): 
                                $checked = in_array($red['id'], $redes_filtro_ids) ? 'checked' : '';
                            ?>
                            <div class="checkbox-item">
                                <input type="checkbox" id="filtro_red_<?php echo $red['id']; ?>" name="redes[]" value="<?php echo $red['id']; ?>" <?php echo $checked; ?>>
                                <label for="filtro_red_<?php echo $red['id']; ?>">
                                    <?php 
                                    $iconClass = 'fas fa-share-alt'; // Icono por defecto
                                    switch (strtolower($red['nombre'])) {
                                        case 'instagram': $iconClass = 'fab fa-instagram'; break;
                                        case 'facebook': $iconClass = 'fab fa-facebook-f'; break;
                                        case 'twitter': case 'twitter (x)': $iconClass = 'fab fa-twitter'; break;
                                        case 'linkedin': $iconClass = 'fab fa-linkedin-in'; break;
                                        // Añadir más casos si es necesario
                                    }
                                    ?>
                                    <i class="<?php echo $iconClass; ?>"></i>&nbsp;<?php echo htmlspecialchars($red['nombre']); ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <a href="planner.php?slug=<?php echo htmlspecialchars($current_linea_slug); ?>&type=<?php echo htmlspecialchars($content_type); ?>" class="btn btn-secondary">Limpiar Filtros</a>
                </form>
            </div>
            <?php endif; ?>
            
            <div class="table-container">
                <?php if ($content_type === 'social'): ?>
                <div class="table-header">
                    <h2 class="table-title">Posts de Redes Sociales</h2>
                    <div class="table-actions">
                        <div class="toggle-switch-container">
                            <label for="toggle-published" class="toggle-switch-label">Mostrar Publicados</label>
                            <label class="switch">
                                <input type="checkbox" id="toggle-published">
                                <span class="slider round"></span>
                            </label>
                        </div>
                        <a href="publicacion_form.php?linea_id=<?php echo intval($current_linea_id); ?>&linea_slug=<?php echo htmlspecialchars($current_linea_slug); ?>" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nuevo Post Social
                        </a>
                    </div>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <?php echo getSortLinkPlanner($current_linea_slug, $sort_by, $sort_dir, $next_sort_dir, 'fecha_programada', 'Fecha', $redes_filtro_ids, $content_type); ?>
                            <th style="width: 70px;">Imagen</th>
                            <th>Contenido</th>
                            <?php echo getSortLinkPlanner($current_linea_slug, $sort_by, $sort_dir, $next_sort_dir, 'estado', 'Estado', $redes_filtro_ids, $content_type); ?>
                            <th style="width: 120px;">Redes</th>
                            <th style="width: 140px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($publicaciones)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 30px;">
                                No hay publicaciones disponibles para <?php echo htmlspecialchars($current_linea_nombre); ?> con los filtros actuales.
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($publicaciones as $publicacion): ?>
                            <tr data-estado="<?php echo htmlspecialchars($publicacion['estado']); ?>">
                                <td data-label="Fecha"><?php echo date("d/m/Y", strtotime($publicacion['fecha_programada'])); ?></td>
                                <td data-label="Imagen">
                                    <?php if (!empty($publicacion['imagen_url'])): ?>
                                        <?php 
                                        // Usar thumbnail optimizado para mostrar, original para modal
                                        $thumbnailUrl = getBestThumbnailUrl($publicacion['imagen_url'], $publicacion['thumbnail_url'] ?? null);
                                        $originalUrl = $publicacion['imagen_url'];
                                        ?>
                                        <img src="<?php echo htmlspecialchars($thumbnailUrl); ?>" 
                                             data-original="<?php echo htmlspecialchars($originalUrl); ?>" 
                                             alt="Miniatura" 
                                             class="thumbnail">
                                    <?php elseif ($publicacion['estado'] === 'publicado'): ?>
                                        <div class="image-placeholder archived size-small fade-in" data-tooltip="Imagen archivada para optimizar almacenamiento">
                                            <i class="fas fa-archive"></i>
                                            <span>Archivada</span>
                                            <small>Para optimizar<br>almacenamiento</small>
                                        </div>
                                    <?php else: ?>
                                        <span class="no-image"><i class="fas fa-image"></i></span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Contenido">
                                    <div class="contenido-publicacion">
                                        <?php echo nl2br(htmlspecialchars(truncateText($publicacion['contenido'], 200))); ?>
                                    </div>
                                </td>
                                <td data-label="Estado">
                                    <div class="status-feedback-wrapper">
                                        <?php
                                        // Modern status selector component
                                        $status_config = [
                                            'borrador' => ['label' => 'Borrador', 'icon' => '📝'],
                                            'programado' => ['label' => 'Programado', 'icon' => '📅'],
                                            'publicado' => ['label' => 'Publicado', 'icon' => '✅']
                                        ];
                                        $current_status = $publicacion['estado'];
                                        $current_config = $status_config[$current_status];
                                        ?>
                                        <div class="status-selector" data-id="<?php echo $publicacion['id']; ?>" data-linea-id="<?php echo intval($current_linea_id); ?>">
                                            <button class="status-selector-trigger <?php echo $current_status; ?>">
                                                <span class="status-text"><?php echo $current_config['label']; ?></span>
                                                <span class="status-selector-arrow">▼</span>
                                            </button>
                                            <div class="status-selector-dropdown">
                                                <?php foreach ($status_config as $status => $config): ?>
                                                    <button class="status-selector-option <?php echo ($status === $current_status) ? 'active' : ''; ?>" data-status="<?php echo $status; ?>">
                                                        <span class="status-icon <?php echo $status; ?>"><?php echo $config['icon']; ?></span>
                                                        <span><?php echo $config['label']; ?></span>
                                                    </button>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <?php if ($publicacion['feedback_count'] > 0): ?>
                                        <a href="#" class="feedback-indicator" data-publicacion-id="<?php echo $publicacion['id']; ?>">
                                            <i class="fas fa-comments"></i> (<?php echo $publicacion['feedback_count']; ?>)
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td data-label="Redes">
                                    <?php 
                                    if (!empty($publicacion['nombres_redes'])) {
                                        $redes_array = explode('|', $publicacion['nombres_redes']);
                                        echo '<div class="redes-sociales-iconos">';
                                        foreach ($redes_array as $nombre_red_raw) {
                                            $nombre_red = strtolower(trim($nombre_red_raw));
                                            $icon_clase_fa = 'fas fa-share-alt'; // Default icon
                                            $span_clase_especifica = 'red-social-icon-generic';

                                            if ($nombre_red === 'instagram') {
                                                $icon_clase_fa = 'fab fa-instagram';
                                                $span_clase_especifica = 'red-social-icon-instagram';
                                            } elseif ($nombre_red === 'facebook') {
                                                $icon_clase_fa = 'fab fa-facebook-f';
                                                $span_clase_especifica = 'red-social-icon-facebook';
                                            } elseif ($nombre_red === 'twitter' || $nombre_red === 'x' || $nombre_red === 'twitter (x)') {
                                                $icon_clase_fa = 'fab fa-twitter'; // Consider using fa-x-twitter if available/desired
                                                $span_clase_especifica = 'red-social-icon-twitter';
                                            } elseif ($nombre_red === 'linkedin') {
                                                $icon_clase_fa = 'fab fa-linkedin-in';
                                                $span_clase_especifica = 'red-social-icon-linkedin';
                                            }
                                            echo '<span class="red-social-icon ' . $span_clase_especifica . '" title="' . htmlspecialchars(ucfirst($nombre_red_raw)) . '"><i class="' . $icon_clase_fa . '"></i></span> ';
                                        }
                                        echo '</div>';
                                    }
                                    ?>
                                </td>
                                <td data-label="Acciones">
                                    <div class="row-actions">
                                        <a href="publicacion_form.php?id=<?php echo $publicacion['id']; ?>&linea_slug=<?php echo htmlspecialchars($current_linea_slug); ?>&linea_id=<?php echo intval($current_linea_id); ?>" class="action-btn edit" title="Editar"><i class="fas fa-edit"></i></a>
                                        <button class="action-btn share-publication" data-publicacion-id="<?php echo $publicacion['id']; ?>" title="Compartir Publicación"><i class="fas fa-share-square"></i></button>
                                        <a href="publicacion_delete.php?id=<?php echo $publicacion['id']; ?>&linea_id=<?php echo intval($current_linea_id); ?>&slug_redirect=<?php echo htmlspecialchars($current_linea_slug); ?>" class="action-btn delete" title="Eliminar" ><i class="fas fa-trash-alt"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                <?php elseif ($content_type === 'blog'): ?>
                <!-- Blog Posts Section -->
                <div class="table-header">
                    <h2 class="table-title">Posts de Blog</h2>
                    <div class="table-actions">
                        <div class="toggle-switch-container">
                            <label for="toggle-published-blog" class="toggle-switch-label">Mostrar Publicados</label>
                            <label class="switch">
                                <input type="checkbox" id="toggle-published-blog">
                                <span class="slider round"></span>
                            </label>
                        </div>
                        <a href="blog_form.php?linea_id=<?php echo intval($current_linea_id); ?>&linea_slug=<?php echo htmlspecialchars($current_linea_slug); ?>" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nuevo Blog Post
                        </a>
                    </div>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <?php echo getSortLinkPlanner($current_linea_slug, $sort_by, $sort_dir, $next_sort_dir, 'fecha_publicacion', 'Fecha', $redes_filtro_ids, $content_type); ?>
                            <th style="width: 70px;">Imagen</th>
                            <?php echo getSortLinkPlanner($current_linea_slug, $sort_by, $sort_dir, $next_sort_dir, 'titulo', 'Título', $redes_filtro_ids, $content_type); ?>
                            <th>Excerpt</th>
                            <?php echo getSortLinkPlanner($current_linea_slug, $sort_by, $sort_dir, $next_sort_dir, 'estado', 'Estado', $redes_filtro_ids, $content_type); ?>
                            <th style="width: 90px;">WordPress</th>
                            <th style="width: 180px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($blog_posts)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 30px;">
                                No hay blog posts disponibles para <?php echo htmlspecialchars($current_linea_nombre); ?>.
                                <br><br>
                                <a href="blog_form.php?linea_id=<?php echo intval($current_linea_id); ?>&linea_slug=<?php echo htmlspecialchars($current_linea_slug); ?>" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Crear tu primer Blog Post
                                </a>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($blog_posts as $blog_post): ?>
                            <tr data-estado="<?php echo htmlspecialchars($blog_post['estado']); ?>">
                                <td data-label="Fecha"><?php echo date("d/m/Y", strtotime($blog_post['fecha_publicacion'])); ?></td>
                                <td data-label="Imagen">
                                    <?php if (!empty($blog_post['imagen_destacada'])): ?>
                                        <?php 
                                        // Usar thumbnail optimizado para mostrar, original para modal
                                        $thumbnailUrl = getBestThumbnailUrl($blog_post['imagen_destacada'], $blog_post['thumbnail_url'] ?? null);
                                        $originalUrl = $blog_post['imagen_destacada'];
                                        ?>
                                        <img src="<?php echo htmlspecialchars($thumbnailUrl); ?>" 
                                             data-original="<?php echo htmlspecialchars($originalUrl); ?>" 
                                             alt="Imagen destacada" 
                                             class="thumbnail">
                                    <?php elseif ($blog_post['estado'] === 'publish'): ?>
                                        <div class="image-placeholder archived size-small fade-in" data-tooltip="Imagen archivada para optimizar almacenamiento">
                                            <i class="fas fa-archive"></i>
                                            <span>Archivada</span>
                                            <small>Para optimizar<br>almacenamiento</small>
                                        </div>
                                    <?php else: ?>
                                        <span class="no-image"><i class="fas fa-image"></i></span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Título">
                                    <div class="blog-post-title">
                                        <strong><?php echo htmlspecialchars($blog_post['titulo']); ?></strong>
                                    </div>
                                </td>
                                <td data-label="Excerpt">
                                    <div class="blog-post-excerpt">
                                        <?php 
                                        if (!empty($blog_post['excerpt'])) {
                                            echo htmlspecialchars(truncateText($blog_post['excerpt'], 150));
                                        } else {
                                            echo '<em style="color: #999;">Sin excerpt</em>';
                                        }
                                        ?>
                                    </div>
                                </td>
                                <td data-label="Estado">
                                    <?php
                                    // Modern status selector component for blog posts
                                    $blog_status_config = [
                                        'draft' => ['label' => 'Borrador', 'icon' => '📝'],
                                        'scheduled' => ['label' => 'Programado', 'icon' => '📅'],
                                        'publish' => ['label' => 'Publicado', 'icon' => '✅']
                                    ];
                                    $current_blog_status = $blog_post['estado'];
                                    $current_blog_config = $blog_status_config[$current_blog_status];
                                    ?>
                                    <div class="status-selector" data-id="<?php echo $blog_post['id']; ?>" data-linea-id="<?php echo intval($current_linea_id); ?>" data-type="blog">
                                        <button class="status-selector-trigger <?php echo $current_blog_status; ?>">
                                            <span class="status-text"><?php echo $current_blog_config['label']; ?></span>
                                            <span class="status-selector-arrow">▼</span>
                                        </button>
                                        <div class="status-selector-dropdown">
                                            <?php foreach ($blog_status_config as $status => $config): ?>
                                                <button class="status-selector-option <?php echo ($status === $current_blog_status) ? 'active' : ''; ?>" data-status="<?php echo $status; ?>">
                                                    <span class="status-icon <?php echo $status; ?>"><?php echo $config['icon']; ?></span>
                                                    <span><?php echo $config['label']; ?></span>
                                                </button>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="WordPress">
                                    <?php if ($blog_post['wordpress_enabled']): ?>
                                        <?php if (!empty($blog_post['wp_post_id'])): ?>
                                            <span class="wp-sync-status <?php echo $blog_post['wp_sync_status'] ?? 'synced'; ?>" title="WordPress Post ID: <?php echo $blog_post['wp_post_id']; ?>">
                                                <?php 
                                                switch($blog_post['wp_sync_status'] ?? 'synced') {
                                                    case 'synced': echo '✅ Sync'; break;
                                                    case 'pending': echo '⏳ Pend'; break;
                                                    case 'error': echo '❌ Error'; break;
                                                    default: echo '✅ Sync'; break;
                                                }
                                                ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="wp-sync-status pending">⏳ No sync</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color: #999; font-size: 12px;">Deshabilitado</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Acciones">
                                    <div class="row-actions">
                                        <a href="blog_form.php?id=<?php echo $blog_post['id']; ?>&linea_slug=<?php echo htmlspecialchars($current_linea_slug); ?>&linea_id=<?php echo intval($current_linea_id); ?>" class="action-btn edit" title="Editar"><i class="fas fa-edit"></i></a>

                                        <?php if ($blog_post['wordpress_enabled']): ?>
                                            <button class="action-btn" style="background: #21759b; color: white;" title="Publicar en WordPress" onclick="publishToWordPressFromTable(<?php echo $blog_post['id']; ?>)">
                                                <i class="fab fa-wordpress"></i>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <button class="action-btn delete" title="Eliminar" onclick="deleteBlogPost(<?php echo $blog_post['id']; ?>, '<?php echo htmlspecialchars($current_linea_slug); ?>')"><i class="fas fa-trash-alt"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div> <!-- Fin table-container -->

            <!-- Modal para previsualización de imagen -->
            <div id="imageModal" class="modal-image">
                <span class="close-image-modal">&times;</span>
                <img class="modal-image-content" id="modalImageSrc">
            </div>
             <!-- Modal para mostrar feedback -->
            <div id="feedbackDisplayModal" class="modal-feedback-display">
                <div class="modal-feedback-content">
                    <span class="close-feedback-modal">&times;</span>
                    <h2>Feedback Recibido</h2>
                    <div class="feedback-display-list">
                        <!-- Contenido cargado por JS -->
                    </div>
                </div>
            </div>

            <!-- Modal para Compartir Vista (ADDED) -->
            <div id="shareModal" class="modal modal-share" style="display: none;">
                <div class="modal-share-content">
                    <span class="close-share-modal" data-modal-id="shareModal">&times;</span>
                    <h2>Compartir Vista de Línea de Negocio</h2>
                    <p>Copia este enlace para compartir una vista de solo lectura de esta línea de negocio:</p>
                    <div class="share-link-container">
                        <input type="text" id="shareLinkInput" readonly>
                        <button id="copyShareLinkBtn" class="btn btn-primary"><i class="fas fa-copy"></i> Copiar</button>
                    </div>
                    <div id="copyMessage" style="display: none; margin-top: 10px; color: green;">¡Enlace copiado!</div>
                    <div id="shareError" style="display: none; margin-top: 10px; color: red;"></div>
                </div>
            </div>

            <!-- Modal para Compartir Publicación Individual -->
            <div id="sharePublicationModal" class="modal modal-share" style="display: none;">
                <div class="modal-share-content">
                    <span class="close-share-modal" data-modal-id="sharePublicationModal">&times;</span>
                    <h2>Compartir Publicación Individual</h2>
                    <p>Copia este enlace para compartir una vista de solo lectura de esta publicación:</p>
                    <div class="share-link-container">
                        <input type="text" id="sharePublicationLinkInput" readonly>
                        <button id="copySharePublicationLinkBtn" class="btn btn-primary"><i class="fas fa-copy"></i> Copiar</button>
                    </div>
                    <div id="copyPublicationMessage" style="display: none; margin-top: 10px; color: green;">¡Enlace copiado!</div>
                    <div id="sharePublicationError" style="display: none; margin-top: 10px; color: red;"></div>
                </div>
            </div>

        <?php endif; // Fin del if(!$page_error) ?>
    </div> <!-- Fin app-simple -->
    
    <!-- Scripts JS -->
    <script src="assets/js/main.js"></script>
    <script src="assets/js/share.js" defer></script> <!-- ADDED share.js -->
    
    <script>
        // Handle PHP session messages with toast notifications
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (isset($_SESSION['feedback_message'])): ?>
                const feedbackType = '<?php echo $_SESSION['feedback_message']['tipo']; ?>';
                const feedbackMessage = <?php echo json_encode($_SESSION['feedback_message']['mensaje']); ?>;
                
                console.log('Session message detected:', feedbackType, feedbackMessage);
                
                // Ensure main.js is loaded and functions are available
                setTimeout(() => {
                    if (typeof handleSessionMessage === 'function') {
                        console.log('Showing toast:', feedbackType, feedbackMessage);
                        handleSessionMessage(feedbackType, feedbackMessage);
                    } else {
                        console.error('handleSessionMessage function not found');
                    }
                }, 300);
                
                <?php unset($_SESSION['feedback_message']); ?>
            <?php else: ?>
                console.log('No session feedback message found');
            <?php endif; ?>
        });
    </script>
</body>
</html> 