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

        // Redes sociales asociadas a esta línea (IDs)
        $stmtR = $db->prepare("SELECT red_social_id FROM linea_negocio_red_social WHERE linea_negocio_id = ?");
        $stmtR->execute([$linea['id']]);
        $linea['redes_ids'] = array_map('intval', $stmtR->fetchAll(PDO::FETCH_COLUMN));
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
    <!-- Fuente Geist cargada globalmente desde assets/css/styles.css -->
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
                    <form id="formNuevaLinea" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="nombreLinea">Nombre de la línea:</label>
                            <input type="text" id="nombreLinea" name="nombre" required>
                        </div>
                        <div class="form-group">
                            <label for="logoFileLinea">Logo:</label>
                            <input type="file" id="logoFileLinea" name="logo_file" accept=".png,.jpg,.jpeg,.webp,.svg,image/png,image/jpeg,image/webp,image/svg+xml" required>
                            <small>Formatos permitidos: PNG, JPG, WEBP, SVG. Se guardará en <code>assets/images/logos/</code>.</small>
                        </div>
                        <div class="form-group">
                            <label for="slugLinea">Slug (ej: nombre-linea):</label>
                            <input type="text" id="slugLinea" name="slug" required>
                            <small>Usar minúsculas, números y guiones. Debe ser único.</small>
                        </div>
                        <div class="form-group">
                            <label>Redes sociales asignadas:</label>
                            <div style="display: flex; flex-wrap: wrap; gap: 12px; margin-top: 6px;">
                                <?php foreach ($redesSociales as $red): ?>
                                    <label style="display: inline-flex; align-items: center; gap: 6px;">
                                        <input type="checkbox" name="redes_sociales[]" value="<?php echo (int)$red['id']; ?>">
                                        <?php echo htmlspecialchars($red['nombre']); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <small>Selecciona al menos una red social.</small>
                        </div>
                        <button type="submit" class="btn btn-primary">Guardar Línea de Negocio</button>
                    </form>
                    <div id="modalNuevaLineaMessage" style="margin-top: 15px;"></div>
                </div>
            </div>
        </div>
        <!-- Fin del Modal -->

        <!-- Modal para Editar Línea de Negocio -->
        <div id="modalEditarLinea" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Editar Línea de Negocio</h2>
                    <span class="close-button" id="closeEditarLineaModal">&times;</span>
                </div>
                <div class="modal-body">
                    <form id="formEditarLinea" enctype="multipart/form-data">
                        <input type="hidden" name="id" id="editarLineaId">
                        <div class="form-group">
                            <label for="editarNombreLinea">Nombre de la línea:</label>
                            <input type="text" id="editarNombreLinea" name="nombre" required>
                        </div>
                        <div class="form-group">
                            <label>Logo actual:</label>
                            <div id="editarLogoPreview" style="margin: 6px 0;"></div>
                            <label for="editarLogoFile">Reemplazar logo (opcional):</label>
                            <input type="file" id="editarLogoFile" name="logo_file" accept=".png,.jpg,.jpeg,.webp,.svg,image/png,image/jpeg,image/webp,image/svg+xml">
                            <small>Si no seleccionas archivo, se conservará el logo actual.</small>
                        </div>
                        <div class="form-group">
                            <label for="editarSlugLinea">Slug:</label>
                            <input type="text" id="editarSlugLinea" name="slug" required>
                            <small>Usar minúsculas, números y guiones. Debe ser único.</small>
                        </div>
                        <div class="form-group">
                            <label>Redes sociales asignadas:</label>
                            <div id="editarRedesContainer" style="display: flex; flex-wrap: wrap; gap: 12px; margin-top: 6px;">
                                <?php foreach ($redesSociales as $red): ?>
                                    <label style="display: inline-flex; align-items: center; gap: 6px;">
                                        <input type="checkbox" name="redes_sociales[]" value="<?php echo (int)$red['id']; ?>" class="editar-red-checkbox">
                                        <?php echo htmlspecialchars($red['nombre']); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <small>Selecciona al menos una red social.</small>
                        </div>
                        <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    </form>
                    <div id="modalEditarLineaMessage" style="margin-top: 15px;"></div>
                </div>
            </div>
        </div>
        <!-- Fin del Modal Editar -->

        <!-- Modal para Eliminar Línea de Negocio -->
        <div id="modalEliminarLinea" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Eliminar Línea de Negocio</h2>
                    <span class="close-button" id="closeEliminarLineaModal">&times;</span>
                </div>
                <div class="modal-body">
                    <div style="background:#fff3cd;color:#664d03;padding:12px;border-radius:4px;margin-bottom:12px;">
                        <strong>⚠️ Acción irreversible.</strong> Al eliminar la línea <strong id="eliminarLineaNombre"></strong> se borrarán en cascada:
                        <ul style="margin:8px 0 0 18px;">
                            <li>Todas sus publicaciones de RRSS y sus imágenes asociadas en BD.</li>
                            <li>Todos los blog posts, categorías y tags de esa línea.</li>
                            <li>Todos los enlaces para compartir (share tokens).</li>
                            <li>El archivo de logo del servidor.</li>
                        </ul>
                    </div>
                    <form id="formEliminarLinea">
                        <input type="hidden" name="id" id="eliminarLineaId">
                        <div class="form-group">
                            <label for="eliminarConfirmSlug">Para confirmar, escribe el slug exacto (<code id="eliminarSlugHint"></code>):</label>
                            <input type="text" id="eliminarConfirmSlug" name="confirm_slug" required autocomplete="off">
                        </div>
                        <button type="submit" class="btn btn-primary" style="background:#b00020;border-color:#b00020;">
                            <i class="fas fa-trash"></i> Eliminar definitivamente
                        </button>
                    </form>
                    <div id="modalEliminarLineaMessage" style="margin-top: 15px;"></div>
                </div>
            </div>
        </div>
        <!-- Fin del Modal Eliminar -->
        <?php endif; ?>

        <!-- Cards de líneas de negocio -->
        <div class="dashboard-cards">
            <?php foreach ($lineasNegocio as $index => $linea): 
                // URL por línea de negocio (logo/colores dinámicos eliminados)
                $paginaUrl = 'planner.php?slug=' . urlencode($linea['slug'] ?? 'error');
            ?>
            <div class="dashboard-card"
                 data-linea-id="<?php echo (int)$linea['id']; ?>"
                 data-linea-nombre="<?php echo htmlspecialchars($linea['nombre'], ENT_QUOTES); ?>"
                 data-linea-slug="<?php echo htmlspecialchars($linea['slug'] ?? '', ENT_QUOTES); ?>"
                 data-linea-logo="<?php echo htmlspecialchars($linea['logo_filename'] ?? '', ENT_QUOTES); ?>"
                 data-linea-redes="<?php echo htmlspecialchars(implode(',', $linea['redes_ids']), ENT_QUOTES); ?>">
                <div class="card-header" style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                    <h2 style="margin: 0;"><?php echo htmlspecialchars($linea['nombre']); ?></h2>
                    <?php if (is_superadmin()): ?>
                    <div class="card-admin-actions" style="display: inline-flex; gap: 6px;">
                        <button type="button" class="btn btn-sm btn-edit-linea" title="Editar línea">
                            <i class="fas fa-pen"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-delete-linea" title="Eliminar línea" style="color: #b00020;">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
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