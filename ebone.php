<?php
require_once 'includes/functions.php';
require_authentication();

$lineaNombre = "Ebone Servicios";
$lineaId = 1; // ID fijo para Ebone Servicios

// --- Gestión de Ordenación ---
$sort_options = ['fecha_programada', 'estado'];
$sort_by = isset($_GET['sort']) && in_array($_GET['sort'], $sort_options) ? $_GET['sort'] : 'fecha_programada';
// Default sort: Fecha Ascendente
$default_sort_dir = ($sort_by === 'fecha_programada') ? 'ASC' : 'DESC'; 
$sort_dir = isset($_GET['dir']) && in_array(strtoupper($_GET['dir']), ['ASC', 'DESC']) ? strtoupper($_GET['dir']) : $default_sort_dir;
$next_sort_dir = ($sort_dir === 'ASC') ? 'DESC' : 'ASC';

// --- Gestión de Filtrado por Redes ---
$redes_filtro = isset($_GET['redes']) && is_array($_GET['redes']) ? $_GET['redes'] : [];
// Sanitizar los IDs de redes
$redes_filtro = array_map('intval', $redes_filtro);
$redes_filtro = array_filter($redes_filtro, function($id) { return $id > 0; });

// Conexión directa a BD para depuración
try {
    $db = getDbConnection();
    
    // 1. Verificar la línea de negocio
    $stmt = $db->prepare("SELECT * FROM lineas_negocio WHERE id = ?");
    $stmt->execute([$lineaId]);
    $linea = $stmt->fetch();
    
    // 2. Construir la consulta principal de publicaciones
    $sql = "\n    SELECT \n        p.*, \n        GROUP_CONCAT(rs.nombre SEPARATOR '|') as nombres_redes, \n        COUNT(DISTINCT pf.id) as feedback_count \n    FROM publicaciones p\n    LEFT JOIN publicacion_red_social prs ON p.id = prs.publicacion_id\n    LEFT JOIN redes_sociales rs ON prs.red_social_id = rs.id\n    LEFT JOIN publication_feedback pf ON p.id = pf.publicacion_id -- Unir con tabla de feedback
    WHERE p.linea_negocio_id = ?\n";
    
    // Añadir JOIN para filtrar por redes si es necesario
    if (!empty($redes_filtro)) {
        $placeholders = implode(',', array_fill(0, count($redes_filtro), '?'));
        $sql .= " JOIN publicacion_red_social prs_filter ON p.id = prs_filter.publicacion_id AND prs_filter.red_social_id IN ($placeholders) ";
    }
    
    $sql .= " GROUP BY p.id ";
    
    // Añadir ordenación
    $sql .= " ORDER BY p." . $sort_by . " " . $sort_dir . ", p.id DESC"; // Fallback sort por ID
    
    $stmt = $db->prepare($sql);
    
    // Combinar parámetros
    $params = [];
    if (!empty($redes_filtro)) {
        $params = $redes_filtro;
    }
    $params[] = $lineaId;
    
    $stmt->execute($params);
    $publicaciones = $stmt->fetchAll();
    
    // 3. Obtener todas las redes sociales disponibles para esta línea (para el filtro)
    $stmt = $db->prepare("
        SELECT r.* 
        FROM redes_sociales r
        JOIN linea_negocio_red_social lnrs ON r.id = lnrs.red_social_id
        WHERE lnrs.linea_negocio_id = ?
        ORDER BY r.nombre ASC
    ");
    $stmt->execute([$lineaId]);
    $redesDisponiblesFiltro = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($lineaNombre) ? $lineaNombre : 'Planificador RRSS'; ?> - Planificador RRSS</title>
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
<body class="linea-ebone">
    <div class="app-simple">
        <div class="header-simple">
            <div class="header-title-logo">
                <img src="assets/images/logos/logo-ebone.png" alt="Logo <?php echo $lineaNombre; ?>" class="header-logo">
                <h1><?php echo $lineaNombre; ?> - Publicaciones</h1>
            </div>
            <!-- Botón Nueva Publicación movido al table-header -->
            <button class="btn btn-secondary btn-share" data-linea-id="<?php echo $lineaId; ?>">
                <i class="fas fa-share-alt"></i> Compartir
            </button>
        </div>
        
        <div class="nav-simple">
            <a href="index.php">Dashboard</a>
            <a href="ebone.php" class="active">Ebone Servicios</a>
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
        
        <!-- Contenedor de Filtros y Ordenación -->
        <div class="filter-sort-container">
            <form action="" method="GET" class="filter-form">
                <div class="filter-group">
                    <label>Filtrar por Redes Sociales:</label>
                    <div class="redes-filter">
                        <?php foreach ($redesDisponiblesFiltro as $red): 
                            $checked = in_array($red['id'], $redes_filtro) ? 'checked' : '';
                        ?>
                        <div class="checkbox-item">
                            <input type="checkbox" id="filtro_red_<?php echo $red['id']; ?>" name="redes[]" value="<?php echo $red['id']; ?>" <?php echo $checked; ?>>
                            <label for="filtro_red_<?php echo $red['id']; ?>">
                                <?php 
                                $icon = '';
                                switch (strtolower($red['nombre'])) {
                                    case 'instagram': $icon = 'fab fa-instagram'; break;
                                    case 'facebook': $icon = 'fab fa-facebook-f'; break;
                                    case 'twitter': case 'twitter (x)': $icon = 'fab fa-twitter'; break;
                                    case 'linkedin': $icon = 'fab fa-linkedin-in'; break;
                                    default: $icon = 'fas fa-share-alt'; break;
                                }
                                ?>
                                <i class="<?php echo $icon; ?>"></i>
                                <?php echo $red['nombre']; ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="ebone.php" class="btn btn-secondary">Limpiar</a>
                <!-- Mantener ordenación actual al filtrar -->
                <?php if ($sort_by !== 'fecha_programada' || $sort_dir !== 'DESC'): ?>
                <input type="hidden" name="sort" value="<?php echo $sort_by; ?>">
                <input type="hidden" name="dir" value="<?php echo $sort_dir; ?>">
                <?php endif; ?>
            </form>
        </div>
        
        <div class="table-container">
            <div class="table-header">
                <h2 class="table-title">Publicaciones</h2>
                <div class="table-actions">
                    <div class="toggle-switch-container">
                        <label for="toggle-published" class="toggle-switch-label">Mostrar Publicados</label>
                        <label class="switch">
                            <input type="checkbox" id="toggle-published" checked>
                            <span class="slider round"></span>
                        </label>
                    </div>
                    <a href="publicacion_form.php?linea=<?php echo $lineaId; ?>" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nueva Publicación
                    </a>
                </div>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <?php 
                        function getSortLink($currentSortBy, $currentSortDir, $nextSortDir, $columnName, $columnLabel) {
                            $link = "ebone.php?sort=$columnName";
                            $icon = "";
                            $class = "sort-header";
                            
                            if ($currentSortBy === $columnName) {
                                // Si es la columna activa, el siguiente link invierte la dirección
                                $link .= "&dir=$nextSortDir";
                                $iconClass = ($currentSortDir === 'ASC') ? 'fa-sort-up' : 'fa-sort-down';
                                $icon = "<i class=\"fas $iconClass sort-icon\"></i>";
                                $class .= " active";
                            } else {
                                // Si no es la columna activa, el link ordena ASC por defecto 
                                // (o DESC si es estado, pero eso se maneja en la otra función)
                                $link .= "&dir=ASC";
                                $icon = "<i class=\"fas fa-sort sort-icon\"></i>";
                            }
                            
                            // Mantener filtros de redes activos al ordenar
                            global $redes_filtro;
                            if (!empty($redes_filtro)) {
                                foreach($redes_filtro as $redId) {
                                    $link .= "&redes[]=$redId";
                                }
                            }
                            
                            return "<th class=\"$class\" style=\"width: 100px\"><a href=\"$link\">$columnLabel $icon</a></th>";
                        }
                        ?>
                        <?php echo getSortLink($sort_by, $sort_dir, $next_sort_dir, 'fecha_programada', 'Fecha'); ?>
                        
                        <th style="width: 70px">Imagen</th>
                        <th>Contenido</th>
                        
                        <?php 
                        function getSortLinkEstado($currentSortBy, $currentSortDir, $nextSortDir, $columnName, $columnLabel) {
                            $link = "ebone.php?sort=$columnName";
                            $icon = "";
                            $class = "sort-header";
                            
                            if ($currentSortBy === $columnName) {
                                // Si es la columna activa, el siguiente link invierte la dirección
                                $link .= "&dir=$nextSortDir";
                                $iconClass = ($currentSortDir === 'ASC') ? 'fa-sort-up' : 'fa-sort-down';
                                $icon = "<i class=\"fas $iconClass sort-icon\"></i>";
                                $class .= " active";
                            } else {
                                // Si no es la columna activa, el link ordena DESC por defecto para el estado
                                $link .= "&dir=DESC"; 
                                $icon = "<i class=\"fas fa-sort sort-icon\"></i>";
                            }
                            
                            // Mantener filtros de redes activos al ordenar
                            global $redes_filtro;
                            if (!empty($redes_filtro)) {
                                foreach($redes_filtro as $redId) {
                                    $link .= "&redes[]=$redId";
                                }
                            }
                            
                            return "<th class=\"$class\" style=\"width: 120px\"><a href=\"$link\">$columnLabel $icon</a></th>";
                        }
                        ?>
                        <?php echo getSortLinkEstado($sort_by, $sort_dir, $next_sort_dir, 'estado', 'Estado'); ?>
                        
                        <th style="width: 120px">Redes</th>
                        <th style="width: 100px">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($publicaciones)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 30px;">
                            No hay publicaciones disponibles para <?php echo $lineaNombre; ?>.
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($publicaciones as $publicacion): 
                            // Obtener redes sociales para esta publicación
                            $stmt = $db->prepare("
                                SELECT r.id, r.nombre
                                FROM redes_sociales r
                                JOIN publicacion_red_social prs ON r.id = prs.red_social_id
                                WHERE prs.publicacion_id = ?
                            ");
                            $stmt->execute([$publicacion['id']]);
                            $redesPublicacion = $stmt->fetchAll();
                            
                            // Determinar clase de badge según estado
                            $badgeClass = '';
                            switch ($publicacion['estado']) {
                                case 'borrador':
                                    $badgeClass = 'badge-draft';
                                    break;
                                case 'programado':
                                    $badgeClass = 'badge-scheduled';
                                    break;
                                case 'publicado':
                                    $badgeClass = 'badge-published';
                                    break;
                            }
                        ?>
                        <tr data-estado="<?php echo $publicacion['estado']; ?>">
                            <td><?php echo date('d/m/y', strtotime($publicacion['fecha_programada'])); ?></td>
                            <td>
                                <?php if (!empty($publicacion['imagen_url'])): ?>
                                    <img src="<?php echo $publicacion['imagen_url']; ?>" alt="Miniatura" class="thumbnail" onclick="openImageModal('<?php echo $publicacion['imagen_url']; ?>')">
                                <?php else: ?>
                                    <div class="no-image"><i class="fas fa-image"></i></div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo truncateText($publicacion['contenido'], 80); ?></td>
                            <td>
                                <div class="status-feedback-wrapper">
                                    <div class="estado-selector" data-id="<?php echo $publicacion['id']; ?>">
                                        <span class="estado-badge badge <?php echo $badgeClass; ?>" onclick="toggleEstadoDropdown(this)">
                                            <?php echo ucfirst($publicacion['estado']); ?>
                                        </span>
                                        <div class="estado-dropdown">
                                            <div class="estado-dropdown-item <?php echo $publicacion['estado'] === 'borrador' ? 'active' : ''; ?>" 
                                                 data-estado="borrador" onclick="cambiarEstado(this)">
                                                Borrador
                                            </div>
                                            <div class="estado-dropdown-item <?php echo $publicacion['estado'] === 'programado' ? 'active' : ''; ?>" 
                                                 data-estado="programado" onclick="cambiarEstado(this)">
                                                Programado
                                            </div>
                                            <div class="estado-dropdown-item <?php echo $publicacion['estado'] === 'publicado' ? 'active' : ''; ?>" 
                                                 data-estado="publicado" onclick="cambiarEstado(this)">
                                                Publicado
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Indicador de Feedback -->
                                    <?php if ($publicacion['feedback_count'] > 0): ?>
                                        <a href="#" class="feedback-indicator" data-publicacion-id="<?php echo $publicacion['id']; ?>" title="<?php echo $publicacion['feedback_count']; ?> comentarios">
                                            <i class="fas fa-comments"></i> 
                                            <span><?php echo $publicacion['feedback_count']; ?></span>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php foreach ($redesPublicacion as $red): 
                                    $iconClass = '';
                                    switch (strtolower($red['nombre'])) {
                                        case 'instagram':
                                            $iconClass = 'instagram';
                                            $icon = 'fa-instagram';
                                            break;
                                        case 'facebook':
                                            $iconClass = 'facebook';
                                            $icon = 'fa-facebook-f';
                                            break;
                                        case 'twitter':
                                        case 'twitter (x)':
                                            $iconClass = 'twitter';
                                            $icon = 'fa-twitter';
                                            break;
                                        case 'linkedin':
                                            $iconClass = 'linkedin';
                                            $icon = 'fa-linkedin-in';
                                            break;
                                        default:
                                            $iconClass = '';
                                            $icon = 'fa-share-alt';
                                    }
                                ?>
                                <span class="social-icon <?php echo $iconClass; ?>">
                                    <i class="fab <?php echo $icon; ?>"></i>
                                </span>
                                <?php endforeach; ?>
                            </td>
                            <td>
                                <div class="row-actions">
                                    <a href="publicacion_form.php?id=<?php echo $publicacion['id']; ?>&linea=<?php echo $lineaId; ?>" class="action-btn edit" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="publicacion_delete.php?id=<?php echo $publicacion['id']; ?>&linea=<?php echo $lineaId; ?>" class="action-btn delete" title="Eliminar" onclick="return confirm('¿Estás seguro de que quieres eliminar esta publicación? Esta acción no se puede deshacer.');">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Modal para mostrar la imagen ampliada -->
    <div id="imageModal" class="modal">
        <span class="close-modal" onclick="closeImageModal()">&times;</span>
        <img class="modal-content" id="modalImage">
    </div>
    
    <!-- Notificación de éxito -->
    <div id="notification" class="notification">
        Estado actualizado correctamente
    </div>
    
    <script>
        // Funciones para el modal de imágenes
        function openImageModal(imgSrc) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            
            modalImg.src = imgSrc;
            modal.classList.add('show');
            
            // Prevenir scroll del cuerpo
            document.body.style.overflow = 'hidden';
        }
        
        function closeImageModal() {
            const modal = document.getElementById('imageModal');
            modal.classList.remove('show');
            
            // Restaurar scroll
            document.body.style.overflow = '';
            
            // Limpiar src después de la transición
            setTimeout(() => {
                document.getElementById('modalImage').src = '';
            }, 300);
        }
        
        // Cerrar modal con ESC o clic fuera de la imagen
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeImageModal();
        });
        
        document.getElementById('imageModal').addEventListener('click', function(e) {
            if (e.target === this) closeImageModal();
        });
        
        // Funciones para el selector de estado
        let estadoDropdownAbierto = null;
        
        function toggleEstadoDropdown(badgeElement) {
            const selector = badgeElement.closest('.estado-selector');
            const dropdown = selector.querySelector('.estado-dropdown');
            
            // Si hay otro dropdown abierto, cerrarlo
            if (estadoDropdownAbierto && estadoDropdownAbierto !== dropdown) {
                estadoDropdownAbierto.classList.remove('show');
            }
            
            // Alternar el dropdown actual
            dropdown.classList.toggle('show');
            
            // Actualizar la referencia global
            estadoDropdownAbierto = dropdown.classList.contains('show') ? dropdown : null;
        }
        
        // Cerrar todos los dropdowns al hacer clic en cualquier parte del documento
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.estado-selector')) {
                const dropdowns = document.querySelectorAll('.estado-dropdown.show');
                dropdowns.forEach(dropdown => dropdown.classList.remove('show'));
                estadoDropdownAbierto = null;
            }
        });
        
        function cambiarEstado(opcionElement) {
            const selector = opcionElement.closest('.estado-selector');
            const publicacionId = selector.dataset.id;
            const nuevoEstado = opcionElement.dataset.estado;
            const badgeElement = selector.querySelector('.estado-badge');
            
            // Añadir spinner de carga
            badgeElement.innerHTML = badgeElement.textContent + ' <span class="loading-spinner"></span>';
            
            // Enviar solicitud AJAX para actualizar el estado
            const formData = new FormData();
            formData.append('id', publicacionId);
            formData.append('estado', nuevoEstado);
            formData.append('linea', <?php echo $lineaId; ?>);
            
            fetch('publicacion_update_estado.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Actualizar la UI
                    badgeElement.innerHTML = data.estadoCapitalizado;
                    badgeElement.className = 'estado-badge badge ' + data.badgeClass;
                    
                    // Actualizar el estado activo en el dropdown
                    const opcionesEstado = selector.querySelectorAll('.estado-dropdown-item');
                    opcionesEstado.forEach(opcion => {
                        opcion.classList.toggle('active', opcion.dataset.estado === nuevoEstado);
                    });
                    
                    // Mostrar notificación de éxito
                    const notification = document.getElementById('notification');
                    notification.classList.add('show');
                    setTimeout(() => {
                        notification.classList.remove('show');
                    }, 3000);
                } else {
                    console.error('Error al actualizar el estado:', data.message);
                    badgeElement.innerHTML = badgeElement.innerHTML.replace('<span class="loading-spinner"></span>', '');
                    alert('Error al actualizar el estado: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error de red:', error);
                badgeElement.innerHTML = badgeElement.innerHTML.replace('<span class="loading-spinner"></span>', '');
                alert('Error de red al actualizar el estado');
            })
            .finally(() => {
                // Cerrar el dropdown
                const dropdown = selector.querySelector('.estado-dropdown');
                dropdown.classList.remove('show');
                estadoDropdownAbierto = null;
            });
        }
    </script>

    <!-- Modal Compartir -->
    <div id="shareModal" class="modal-share">
        <div class="modal-share-content">
            <span class="close-share-modal">&times;</span>
            <h2>Enlace para compartir (Solo Lectura)</h2>
            <p>Copia este enlace para compartir una vista de solo lectura de las publicaciones de esta línea.</p>
            <div class="share-link-container">
                <input type="text" id="shareLinkInput" readonly>
                <button id="copyShareLinkBtn" class="btn btn-primary"><i class="fas fa-copy"></i> Copiar</button>
            </div>
            <p id="copyMessage" style="color: green; margin-top: 10px; display: none;">¡Enlace copiado!</p>
            <p id="shareError" style="color: red; margin-top: 10px; display: none;">Error al generar el enlace.</p>
        </div>
    </div>

    <script src="assets/js/main.js"></script> 
    <script src="assets/js/share.js"></script> <!-- Nuevo JS para compartir -->

    <!-- Modal Genérico para Mostrar Feedback -->
    <div id="feedbackDisplayModal" class="modal-feedback-display">
        <div class="modal-feedback-content">
            <span class="close-feedback-modal">&times;</span>
            <h2>Feedback Recibido</h2>
            <div class="feedback-display-list">
                <!-- El contenido se cargará aquí vía JS -->
                Cargando feedback...
            </div>
        </div>
    </div>

</body>
</html> 