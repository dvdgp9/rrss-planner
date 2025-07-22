<?php
// TODO para mañana: Añadir sistema de feedback en esta vista compartida.
// - Idea inicial: Añadir un formulario simple (textarea + botón enviar) debajo de la tabla.
// - El feedback se guardaría en una nueva tabla (ej. 'shared_feedback') asociado al 'share_token'.
// - Mostraría un mensaje de éxito al enviar.

require_once 'includes/functions.php';
// NO incluir require_authentication() aquí, es una página pública

$token = $_GET['token'] ?? null;
$contentType = $_GET['type'] ?? 'social';
// Validar tipo de contenido
if (!in_array($contentType, ['social', 'blog'])) {
    $contentType = 'social'; // Fallback seguro
}
$lineaId = null;
$lineaNombre = 'Vista Compartida';
$lineaLogo = 'assets/images/logos/isotipo-ebone.png'; // Logo por defecto
$lineaColor = '#6c757d'; // Color gris por defecto
$lineaBodyClass = 'linea-shared'; // Clase genérica
$publicaciones = [];
$contentItems = []; // Nueva variable más genérica para contenido
$error = '';

if (!$token) {
    $error = 'Token no proporcionado.';
} else {
    $lineaId = get_linea_id_from_token($token);
    if (!$lineaId) {
        $error = 'Enlace inválido o caducado.';
    } else {
        // Token válido, obtener datos de la línea y publicaciones
        try {
            $db = getDbConnection();
            // Obtener info de la línea (nombre, etc.)
            $stmt = $db->prepare("SELECT * FROM lineas_negocio WHERE id = ?");
            $stmt->execute([$lineaId]);
            $lineaInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($lineaInfo) {
                $lineaNombre = $lineaInfo['nombre'];
                // Asignar logo y color/gradiente según ID
                $headerBgStyle = ''; // Variable para el estilo de fondo
                switch($lineaInfo['id']) {
                    case 1: 
                        $lineaLogo = 'assets/images/logos/logo-ebone.png'; 
                        $lineaColor = '#23AAC5';
                        $lineaColorDark = '#1a8da5';
                        $headerBgStyle = 'background: linear-gradient(90deg, ' . $lineaColor . ' 0%, ' . $lineaColorDark . ' 100%);';
                        $lineaBodyClass = 'linea-ebone';
                        break;
                    case 2: 
                        $lineaLogo = 'assets/images/logos/logo-cubofit.png';
                        $lineaColor = '#E23633';
                        $lineaColorDark = '#c12f2c';
                        $headerBgStyle = 'background: linear-gradient(90deg, ' . $lineaColor . ' 0%, ' . $lineaColorDark . ' 100%);';
                        $lineaBodyClass = 'linea-cubofit';
                        break;
                    case 3: 
                        $lineaLogo = 'assets/images/logos/logo-uniges.png';
                        $lineaColor = '#9B6FCE';
                        $lineaColorDark = '#032551';
                        $headerBgStyle = 'background: linear-gradient(90deg, ' . $lineaColor . ' 0%, ' . $lineaColorDark . ' 100%);';
                        $lineaBodyClass = 'linea-uniges';
                        break;
                    case 4: 
                        $lineaLogo = 'assets/images/logos/logo-teia.jpg';
                        $lineaColor = '#009970';
                        $lineaColorDark = '#007a5a';
                        $headerBgStyle = 'background: linear-gradient(90deg, ' . $lineaColor . ' 0%, ' . $lineaColorDark . ' 100%);';
                        $lineaBodyClass = 'linea-teia';
                        break;
                    default: // Caso por defecto, si algo falla
                        $lineaColor = '#6c757d';
                        $lineaColorDark = '#5a6268';
                        $headerBgStyle = 'background: linear-gradient(90deg, ' . $lineaColor . ' 0%, ' . $lineaColorDark . ' 100%);';
                        break;
                }
            }
            
            // Obtener contenido según tipo
            if ($contentType === 'blog') {
                // Consulta para blog posts
                $stmt = $db->prepare("
                    SELECT 
                        bp.*,
                        'blog' as content_type,
                        0 as feedback_count,
                        '' as nombres_redes
                    FROM blog_posts bp
                    WHERE bp.linea_negocio_id = ?
                    ORDER BY bp.fecha_publicacion DESC, bp.id DESC
                ");
                $stmt->execute([$lineaId]);
                $contentItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                // Consulta para publicaciones sociales (original)
                $stmt = $db->prepare("
                    SELECT 
                        p.*, 
                        'social' as content_type,
                        GROUP_CONCAT(rs.nombre SEPARATOR '|') as nombres_redes, 
                        COUNT(DISTINCT pf.id) as feedback_count 
                    FROM publicaciones p
                    LEFT JOIN publicacion_red_social prs ON p.id = prs.publicacion_id
                    LEFT JOIN redes_sociales rs ON prs.red_social_id = rs.id
                    LEFT JOIN publication_feedback pf ON p.id = pf.publicacion_id
                    WHERE p.linea_negocio_id = ?
                    GROUP BY p.id
                    ORDER BY p.fecha_programada ASC, p.id ASC
                ");
                $stmt->execute([$lineaId]);
                $contentItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            // Mantener $publicaciones por compatibilidad temporal
            $publicaciones = $contentItems;

        } catch (PDOException $e) {
            $error = 'Error al cargar los datos.';
            error_log("Error en share_view.php: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vista Compartida: <?php echo htmlspecialchars($lineaNombre); ?> - Planificador RRSS</title>
    <link rel="icon" type="image/png" href="assets/images/logos/isotipo-ebone.png">
    <!-- Google Fonts & Font Awesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Estilos propios -->
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        /* Estilos adicionales para la vista compartida */
        body.share-view {
            background-color: #f8f9fa;
        }
        .share-header {
            padding: 15px 30px;
            background-color: <?php echo $lineaColor; ?>;
            color: white;
            display: flex;
            align-items: center;
            gap: 15px;
            border-bottom: 3px solid rgba(0,0,0,0.1);
        }
        .share-header img {
            height: 35px;
            width: auto;
            max-width: 120px;
            object-fit: contain;
        }
        .share-header h1 {
            font-size: 1.4rem;
            font-weight: 600;
            margin: 0;
        }
        .share-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 15px;
        }
        .error-container {
             background-color: #f8d7da;
             color: #721c24;
             padding: 20px;
             margin: 50px auto;
             border-radius: 8px;
             border: 1px solid #f5c6cb;
             text-align: center;
             max-width: 600px;
        }
        .share-table th, .share-table td {
             padding: 12px 15px; /* Menos padding que la tabla normal */
        }
        /* Ocultar columnas no relevantes en vista compartida */
        .share-table .col-actions { 
            display: none; 
        }
        
        /* Estilos para contenido expandible */
        .expandable-content {
            position: relative;
        }
        
        .btn-expand {
            background: none;
            border: none;
            color: <?php echo $lineaColor; ?>;
            font-weight: 500;
            cursor: pointer;
            padding: 4px 8px;
            margin-top: 5px;
            border-radius: 4px;
            transition: all 0.3s ease;
            font-size: 0.85rem;
        }
        
        .btn-expand:hover {
            background-color: <?php echo $lineaColor; ?>;
            color: white;
            transform: translateY(-1px);
        }
        
        .content-full {
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</head>
<body class="share-view <?php echo $lineaBodyClass; ?>">

    <div class="share-header" style="<?php echo $headerBgStyle; ?> color: white;">
        <?php if($lineaLogo): ?>
            <img src="<?php echo htmlspecialchars($lineaLogo); ?>" alt="Logo <?php echo htmlspecialchars($lineaNombre); ?>">
        <?php endif; ?>
        <h1><?php echo htmlspecialchars($lineaNombre); ?> - <?php echo $contentType === 'blog' ? 'Blog Posts' : 'Redes Sociales'; ?></h1>
    </div>

    <div class="share-container">
        <?php if ($error): ?>
            <div class="error-container">
                <h2>Error</h2>
                <p><?php echo htmlspecialchars($error); ?></p>
                <p><a href="login.php">Volver al inicio</a></p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <!-- Toggle Switch HTML -->
                <div class="table-actions" style="padding: 10px 15px; border-bottom: 1px solid #eee; background-color: #f9f9f9;"> 
                    <div class="toggle-switch-container">
                        <label for="toggle-published" class="toggle-switch-label">Mostrar Publicados</label>
                        <label class="switch">
                            <input type="checkbox" id="toggle-published">
                            <span class="slider round"></span>
                        </label>
                    </div>
                </div>

                <?php if ($contentType === 'blog'): ?>
                <!-- Tabla específica para Blog Posts -->
                <table class="share-table"> 
                    <thead>
                        <tr>
                            <th>Fecha Publicación</th>
                            <th>Imagen</th>
                            <th>Título</th>
                            <th>Excerpt</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($contentItems)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 30px;">No hay blog posts para mostrar.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($contentItems as $post): ?>
                                <tr data-estado="<?php echo htmlspecialchars($post['estado']); ?>">
                                    <td><?php echo date("d/m/Y", strtotime($post['fecha_publicacion'])); ?></td>
                                    <td>
                                        <?php if (!empty($post['imagen_destacada'])): ?>
                                            <?php 
                                            // Usar thumbnail optimizado para mostrar, original para modal
                                            $thumbnailUrl = getBestThumbnailUrl($post['imagen_destacada'], $post['thumbnail_url'] ?? null);
                                            $originalUrl = $post['imagen_destacada'];
                                            ?>
                                            <img src="<?php echo htmlspecialchars($thumbnailUrl); ?>" 
                                                 data-original="<?php echo htmlspecialchars($originalUrl); ?>" 
                                                 alt="Imagen destacada" 
                                                 class="thumbnail">
                                        <?php elseif ($post['estado'] === 'publish'): ?>
                                            <div class="image-placeholder archived size-small fade-in" data-tooltip="Imagen archivada para optimizar almacenamiento">
                                                <i class="fas fa-archive"></i>
                                                <span>Archivada</span>
                                                <small>Para optimizar<br>almacenamiento</small>
                                            </div>
                                        <?php else: ?>
                                            <div class="no-image"><i class="fas fa-image"></i></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="blog-post-title">
                                            <strong><?php echo htmlspecialchars($post['titulo']); ?></strong>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="blog-post-excerpt">
                                            <?php 
                                            if (!empty($post['excerpt'])) {
                                                $fullExcerpt = nl2br(htmlspecialchars($post['excerpt']));
                                                $truncatedExcerpt = nl2br(htmlspecialchars(truncateText($post['excerpt'], 150)));
                                                $needsExpansion = strlen($post['excerpt']) > 150;
                                                
                                                if ($needsExpansion) {
                                                    echo '<div class="expandable-content">';
                                                    echo '<div class="content-truncated">' . $truncatedExcerpt . '</div>';
                                                    echo '<div class="content-full" style="display: none;">' . $fullExcerpt . '</div>';
                                                    echo '<button class="btn-expand" onclick="toggleContent(this)">Ver más</button>';
                                                    echo '</div>';
                                                } else {
                                                    echo $fullExcerpt;
                                                }
                                            } else {
                                                echo '<em style="color: #999;">Sin excerpt</em>';
                                            }
                                            ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge <?php 
                                            echo $post['estado'] === 'draft' ? 'badge-draft' : 
                                                 ($post['estado'] === 'scheduled' ? 'badge-scheduled' : 'badge-published'); 
                                        ?>">
                                            <?php 
                                            $estadoDisplay = $post['estado'];
                                            if ($estadoDisplay === 'draft') $estadoDisplay = 'Borrador';
                                            elseif ($estadoDisplay === 'scheduled') $estadoDisplay = 'Programado';
                                            elseif ($estadoDisplay === 'publish') $estadoDisplay = 'Publicado';
                                            echo ucfirst(htmlspecialchars($estadoDisplay)); 
                                            ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <?php else: ?>
                <!-- Tabla para Redes Sociales (mejorada) -->
                <table class="share-table"> 
                    <thead>
                        <tr>
                            <th>Fecha Programada</th>
                            <th>Contenido</th>
                            <th>Imagen</th>
                            <th>Estado</th>
                            <th>Redes</th>
                            <th>Feedback</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($contentItems)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 30px;">No hay publicaciones para mostrar.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($contentItems as $pub): ?>
                                <tr data-estado="<?php echo htmlspecialchars($pub['estado']); ?>">
                                    <td><?php echo formatFecha($pub['fecha_programada']); ?></td>
                                    <td>
                                        <?php 
                                        $fullContent = nl2br(htmlspecialchars($pub['contenido']));
                                        $truncatedContent = nl2br(htmlspecialchars(truncateText($pub['contenido'], 200)));
                                        $needsExpansion = strlen($pub['contenido']) > 200;
                                        
                                        if ($needsExpansion) {
                                            echo '<div class="expandable-content">';
                                            echo '<div class="content-truncated">' . $truncatedContent . '</div>';
                                            echo '<div class="content-full" style="display: none;">' . $fullContent . '</div>';
                                            echo '<button class="btn-expand" onclick="toggleContent(this)">Ver más</button>';
                                            echo '</div>';
                                        } else {
                                            echo $fullContent;
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($pub['imagen_url'])): ?>
                                            <?php 
                                            // Usar thumbnail optimizado para mostrar, original para modal
                                            $thumbnailUrl = getBestThumbnailUrl($pub['imagen_url'], $pub['thumbnail_url'] ?? null);
                                            $originalUrl = $pub['imagen_url'];
                                            ?>
                                            <img src="<?php echo htmlspecialchars($thumbnailUrl); ?>" 
                                                 data-original="<?php echo htmlspecialchars($originalUrl); ?>" 
                                                 alt="Miniatura" 
                                                 class="thumbnail">
                                        <?php elseif ($pub['estado'] === 'publicado'): ?>
                                            <div class="image-placeholder archived size-small fade-in" data-tooltip="Imagen archivada para optimizar almacenamiento">
                                                <i class="fas fa-archive"></i>
                                                <span>Archivada</span>
                                                <small>Para optimizar<br>almacenamiento</small>
                                            </div>
                                        <?php else: ?>
                                            <div class="no-image"><i class="fas fa-image"></i></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php 
                                            echo $pub['estado'] === 'borrador' ? 'badge-draft' : 
                                                 ($pub['estado'] === 'programado' ? 'badge-scheduled' : 'badge-published'); 
                                        ?>">
                                            <?php echo ucfirst(htmlspecialchars($pub['estado'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="redes-iconos">
                                            <?php 
                                            $nombres_redes = !empty($pub['nombres_redes']) ? explode('|', $pub['nombres_redes']) : [];
                                            foreach (array_unique($nombres_redes) as $nombre_red):
                                                if (!empty($nombre_red)): 
                                                    $iconClass = '';
                                                    $iconTag = 'fas';
                                                    switch (strtolower($nombre_red)) {
                                                        case 'instagram': $iconClass = 'fa-instagram'; $iconTag='fab'; break;
                                                        case 'facebook': $iconClass = 'fa-facebook-f'; $iconTag='fab'; break;
                                                        case 'twitter':
                                                        case 'twitter (x)': $iconClass = 'fa-twitter'; $iconTag='fab'; break;
                                                        case 'linkedin': $iconClass = 'fa-linkedin-in'; $iconTag='fab'; break;
                                                        default: $iconClass = 'fa-share-alt'; $iconTag='fas'; break;
                                                    }
                                                ?>
                                                    <i class="<?php echo $iconTag . ' ' . $iconClass; ?>"></i>
                                                <?php endif; 
                                            endforeach; 
                                            ?>
                                        </div>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-secondary btn-feedback-modal" 
                                                data-publicacion-id="<?php echo $pub['id']; ?>"
                                                title="Ver/Añadir Feedback">
                                            <i class="fas fa-comments"></i>
                                            <?php if ($pub['feedback_count'] > 0): ?>
                                                <span class="badge badge-secondary"><?php echo $pub['feedback_count']; ?></span>
                                            <?php endif; ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal para Imagen -->
    <div id="imageModal" class="modal-image">
        <span class="close-image-modal">&times;</span>
        <img class="modal-image-content" id="modalImageSrc">
    </div>

    <!-- Modal para Feedback -->
    <div id="feedbackModal" class="modal modal-share" style="display: none;">
        <div class="modal-share-content">
            <span class="close-share-modal" data-modal-id="feedbackModal">&times;</span>
            <h2>Feedback de Publicación</h2>
            <div id="feedbackContent">
                <div class="feedback-list">
                    <div class="feedback-display-list">Cargando feedback...</div>
                </div>
                <div class="feedback-form">
                    <textarea id="feedbackTextarea" placeholder="Escribe tu feedback aquí..." rows="4"></textarea>
                    <button id="submitFeedbackBtn" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Enviar Feedback
                    </button>
                    <div id="feedbackMessage" style="display: none; margin-top: 10px;"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    <script src="assets/js/share_feedback.js"></script>
    
    <script>
        // Función para alternar contenido expandible
        function toggleContent(button) {
            const expandableContent = button.parentElement;
            const truncatedContent = expandableContent.querySelector('.content-truncated');
            const fullContent = expandableContent.querySelector('.content-full');
            
            if (fullContent.style.display === 'none') {
                // Expandir
                truncatedContent.style.display = 'none';
                fullContent.style.display = 'block';
                button.textContent = 'Ver menos';
            } else {
                // Contraer
                truncatedContent.style.display = 'block';
                fullContent.style.display = 'none';
                button.textContent = 'Ver más';
            }
        }
    </script>

</body>
</html> 