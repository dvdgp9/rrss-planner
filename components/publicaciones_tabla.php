<?php
// Componente para mostrar la tabla de publicaciones de una línea de negocio
// Se espera que $lineaId esté definido antes de incluir este componente
if (!isset($lineaId)) {
    echo '<div class="alert">Error: No se ha seleccionado una línea de negocio.</div>';
    return;
}

// Mostrar ID de línea para depuración
echo '<div style="background: #f5f5f5; padding: 10px; margin-bottom: 15px; border-radius: 4px;">';
echo '<strong>Debug:</strong> LineaID = ' . $lineaId;
echo '</div>';

// Obtener estado desde la URL (si existe)
$estado = isset($_GET['estado']) ? $_GET['estado'] : null;

// Obtener redes sociales de esta línea para mostrar las columnas adecuadas
$redesLinea = getRedesByLinea($lineaId);
// Debug redes disponibles
echo '<div style="background: #f5f5f5; padding: 10px; margin-bottom: 15px; border-radius: 4px;">';
echo '<strong>Redes disponibles:</strong> ';
echo '<pre>';
print_r($redesLinea);
echo '</pre>';
echo '</div>';

// Obtener publicaciones filtradas por línea y opcionalmente por estado
$publicaciones = getPublicaciones($lineaId, $estado);
// Debug publicaciones
echo '<div style="background: #f5f5f5; padding: 10px; margin-bottom: 15px; border-radius: 4px;">';
echo '<strong>Publicaciones encontradas:</strong> ' . count($publicaciones);
echo '<pre>';
print_r($publicaciones);
echo '</pre>';
echo '</div>';
?>

<div class="table-container">
    <div class="table-header">
        <div class="table-title">Publicaciones</div>
        <div class="table-actions">
            <div class="table-filters">
                <button class="filter-btn" data-filter="all">Todas</button>
                <button class="filter-btn" data-filter="borrador">Borradores</button>
                <button class="filter-btn" data-filter="programado">Programadas</button>
                <button class="filter-btn" data-filter="publicado">Publicadas</button>
            </div>
            <a href="publicacion_form.php?linea=<?php echo $lineaId; ?>" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nueva Publicación
            </a>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th style="width: 40px">#</th>
                <th style="width: 100px">Fecha</th>
                <th>Contenido</th>
                <th style="width: 120px">Estado</th>
                <th style="width: 120px">Redes</th>
                <th style="width: 100px">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($publicaciones)): ?>
            <tr>
                <td colspan="6" style="text-align: center; padding: 30px;">
                    No hay publicaciones disponibles.
                </td>
            </tr>
            <?php else: ?>
                <?php foreach ($publicaciones as $publicacion): 
                    // Obtener redes sociales seleccionadas para esta publicación
                    $redesPublicacion = getRedesPublicacion($publicacion['id']);
                    
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
                <tr>
                    <td><?php echo $publicacion['id']; ?></td>
                    <td><?php echo formatFecha($publicacion['fecha_programada']); ?></td>
                    <td><?php echo truncateText($publicacion['contenido'], 80); ?></td>
                    <td><span class="badge <?php echo $badgeClass; ?>"><?php echo ucfirst($publicacion['estado']); ?></span></td>
                    <td>
                        <?php foreach ($redesLinea as $red): 
                            if (in_array($red['id'], $redesPublicacion)): 
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
                                    case 'x':
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
                        <?php endif; endforeach; ?>
                    </td>
                    <td>
                        <div class="row-actions">
                            <a href="publicacion_form.php?id=<?php echo $publicacion['id']; ?>&linea=<?php echo $lineaId; ?>" class="action-btn edit" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="publicacion_delete.php?id=<?php echo $publicacion['id']; ?>&linea=<?php echo $lineaId; ?>" class="action-btn delete" title="Eliminar">
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