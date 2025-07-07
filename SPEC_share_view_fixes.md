# Especificación Técnica: Fixes para "Compartir Vista"

## 📋 Información General
- **Fecha**: 2025-01-23
- **Versión**: 1.0
- **Autor**: AI Assistant (Planner Mode)
- **Estado**: Planificado - Pendiente de implementación

## 🎯 Objetivos
1. **Integrar blog posts** en vista compartida (`share_view.php`)
2. **Mejorar UX del feedback** en vistas compartidas (reposicionar y arreglar funcionalidad)
3. **Unificar experiencia** entre vista individual y compartida

## 🚨 Problemas Identificados

### Problema 1: Blog Posts No Se Muestran
**Estado Actual**: `share_view.php` solo consulta tabla `publicaciones` (redes sociales)
**Problema**: Usuarios en pestaña blog no ven contenido al compartir vista
**Impacto**: Funcionalidad crítica rota

### Problema 2: Feedback Mal Posicionado
**Estado Actual**: Botón como fila separada (`<tr class="feedback-row">`)
**Problema**: Layout poco profesional y posibles fallos de JavaScript
**Impacto**: UX pobre, usuarios evitan usar feedback

## 🏗️ Solución Técnica

### 1. Context-Aware Share View

#### Modificar `planner.php` - Botón "Compartir Vista"
```javascript
// ANTES (línea ~276):
<button class="btn btn-secondary btn-share" data-linea-id="<?php echo intval($current_linea_id); ?>" data-linea-nombre="<?php echo htmlspecialchars($current_linea_nombre); ?>">

// DESPUÉS:
<button class="btn btn-secondary btn-share" data-linea-id="<?php echo intval($current_linea_id); ?>" data-linea-nombre="<?php echo htmlspecialchars($current_linea_nombre); ?>" data-content-type="<?php echo $content_type; ?>">
```

#### Modificar `generate_share_link.php`
```php
// Agregar parámetro de tipo de contenido al URL
$contentType = $_POST['content_type'] ?? 'social';
$shareUrl = $baseUrl . $path . "/share_view.php?token=" . $token . "&type=" . urlencode($contentType);
```

### 2. Dual-Content Share View

#### Modificar `share_view.php` - Detectar Tipo de Contenido
```php
// Línea ~25, después de validar token:
$contentType = $_GET['type'] ?? 'social';
if (!in_array($contentType, ['social', 'blog'])) {
    $contentType = 'social'; // Fallback seguro
}

// Si es blog, consultar blog_posts en lugar de publicaciones
if ($contentType === 'blog') {
    $stmt = $db->prepare("
        SELECT 
            bp.*,
            'blog' as content_type,
            0 as feedback_count  -- Blog posts no tienen feedback aún
        FROM blog_posts bp
        WHERE bp.linea_negocio_id = ?
        ORDER BY bp.fecha_publicacion DESC, bp.id DESC
    ");
    $stmt->execute([$lineaId]);
    $content_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Consulta actual para publicaciones sociales
    $stmt = $db->prepare("...");
    // mantener consulta existente
}
```

### 3. Layout Específico por Tipo

#### HTML Condicional en `share_view.php`
```php
<?php if ($contentType === 'blog'): ?>
    <!-- Tabla específica para blog posts -->
    <table class="share-table">
        <thead>
            <tr>
                <th>Fecha Publicación</th>
                <th>Imagen</th>
                <th>Título</th>
                <th>Excerpt</th>
                <th>Estado</th>
                <th>WordPress</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($content_items as $post): ?>
            <tr data-estado="<?php echo htmlspecialchars($post['estado']); ?>">
                <!-- Usar placeholders para imágenes archivadas -->
                <td><?php echo date("d/m/Y", strtotime($post['fecha_publicacion'])); ?></td>
                <td>
                    <?php if (!empty($post['imagen_destacada'])): ?>
                        <img src="<?php echo htmlspecialchars($post['imagen_destacada']); ?>" alt="Imagen destacada" class="thumbnail">
                    <?php elseif ($post['estado'] === 'publish'): ?>
                        <div class="image-placeholder archived size-small fade-in">
                            <i class="fas fa-archive"></i>
                            <span>Archivada</span>
                        </div>
                    <?php else: ?>
                        <div class="no-image"><i class="fas fa-image"></i></div>
                    <?php endif; ?>
                </td>
                <!-- Más campos específicos para blog -->
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <!-- Tabla actual para redes sociales -->
    <!-- Mantener estructura existente pero mejorar feedback -->
<?php endif; ?>
```

### 4. Mejorar Feedback UX

#### Remover Feedback-Row Implementation
```php
// ELIMINAR de share_view.php (líneas ~242-260):
<!-- Nueva fila para Feedback -->
<tr class="feedback-row">
    <td colspan="6">
        <button class="btn btn-sm btn-outline-secondary btn-toggle-feedback">
        <!-- ... -->
    </td>
</tr>
```

#### Integrar Feedback en Acciones
```php
// AGREGAR nueva columna de acciones:
<th>Acciones</th>

// En tbody:
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
```

### 5. Modal de Feedback Mejorado

#### CSS para Modal
```css
.feedback-modal {
    /* Reutilizar estilos de .modal-share */
    display: none;
    position: fixed;
    z-index: 1000;
    /* ... */
}

.feedback-modal-content {
    max-width: 600px;
    /* ... */
}

.btn-feedback-modal {
    position: relative;
    /* Para posicionar badge de contador */
}

.btn-feedback-modal .badge {
    position: absolute;
    top: -8px;
    right: -8px;
    font-size: 0.7rem;
}
```

#### JavaScript Mejorado
```javascript
// En assets/js/share_feedback.js
document.addEventListener('DOMContentLoaded', function() {
    // Manejar clicks en botones de feedback
    document.body.addEventListener('click', function(event) {
        const feedbackBtn = event.target.closest('.btn-feedback-modal');
        if (feedbackBtn) {
            const pubId = feedbackBtn.dataset.publicacionId;
            openFeedbackModal(pubId);
        }
    });
    
    function openFeedbackModal(pubId) {
        // Crear modal dinámicamente o mostrar modal existente
        // Cargar feedback existente
        // Configurar form para envío
    }
});
```

## 🧪 Testing

### Test Cases
1. **Blog Share**: Compartir vista desde pestaña blog → Debe mostrar blog posts
2. **Social Share**: Compartir vista desde pestaña social → Debe mostrar publicaciones sociales
3. **Feedback Modal**: Click en botón feedback → Modal se abre correctamente
4. **Feedback Submit**: Enviar feedback → Se guarda y actualiza contador
5. **Responsive**: Probar en móvil → Layout se mantiene usable
6. **Backward Compatibility**: Enlaces existentes → Siguen funcionando (default a social)

### Validation Checklist
- [ ] Blog posts se muestran correctamente en vista compartida
- [ ] Placeholders de imágenes archivadas funcionan en blogs compartidos
- [ ] Botón de feedback tiene posición profesional
- [ ] Modal de feedback se abre sin errores JavaScript
- [ ] Contador de feedback se actualiza después de envío
- [ ] Vista responsive funciona en móviles
- [ ] Enlaces de compartir existentes no se rompen

## 📋 Archivos a Modificar

1. **`planner.php`** - Agregar data-content-type al botón
2. **`generate_share_link.php`** - Incluir tipo en URL generado
3. **`share_view.php`** - Lógica dual para social/blog + nuevo feedback
4. **`assets/css/styles.css`** - Estilos para modal de feedback
5. **`assets/js/share_feedback.js`** - JavaScript mejorado para feedback

## 🔄 Backward Compatibility

- Enlaces existentes sin parámetro `type` defaultean a `social`
- Estructura de tokens permanece igual
- APIs de feedback mantienen compatibilidad

## 📊 Métricas de Éxito

- **Funcionalidad**: Blog posts aparecen en vista compartida
- **UX**: Feedback tiene posición profesional y funciona
- **Consistency**: Experiencia unificada entre vistas
- **Performance**: Sin degradación de velocidad de carga 