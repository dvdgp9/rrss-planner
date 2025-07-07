# Especificación Técnica: Optimización de Almacenamiento de Imágenes

## 📋 Información General
- **Fecha**: 2025-01-23
- **Versión**: 1.0
- **Autor**: AI Assistant (Planner Mode)
- **Estado**: Planificado - Pendiente de implementación

## 🎯 Objetivo
Implementar borrado automático de imágenes cuando las publicaciones cambien a estado "publicado" para reducir el uso de almacenamiento del servidor.

## 🔍 Alcance

### Tablas Afectadas
- `publicaciones` (campo `imagen_url`)
- `blog_posts` (campo `imagen_destacada`)

### Archivos Afectados
- `includes/functions.php` (nueva función helper)
- `publicacion_update_estado.php` (modificación)
- `blog_update_estado.php` (modificación)
- `publicaciones_tabla.php` (placeholder)
- Componentes de blog (placeholder)

### Directorios de Imágenes
- `/uploads/` (publicaciones sociales)
- `/uploads/blog/` (blog posts)

## 🏗️ Arquitectura Técnica

### 1. Helper Function
```php
function deletePublicationImage($imagePath, $logContext = '') {
    // Validar que la imagen existe
    if (!file_exists($imagePath)) {
        error_log("Image not found for deletion: {$imagePath} - {$logContext}");
        return true; // No es error crítico
    }
    
    // Verificar permisos
    if (!is_writable($imagePath)) {
        error_log("Permission denied for image deletion: {$imagePath} - {$logContext}");
        return false;
    }
    
    // Intentar borrar
    if (unlink($imagePath)) {
        error_log("Image deleted successfully: {$imagePath} - {$logContext}");
        return true;
    } else {
        error_log("Failed to delete image: {$imagePath} - {$logContext}");
        return false;
    }
}
```

### 2. Trigger Logic
```php
// En publicacion_update_estado.php y blog_update_estado.php
if ($newStatus === 'publicado' && $oldStatus !== 'publicado') {
    // Obtener ruta de imagen actual
    $imagePath = /* obtener de BD */;
    
    if ($imagePath) {
        // Intentar borrar archivo físico
        if (deletePublicationImage($imagePath, "Publication ID: {$id}")) {
            // Actualizar BD para poner imagen = NULL
            $stmt = $db->prepare("UPDATE tabla SET imagen_field = NULL WHERE id = ?");
            $stmt->execute([$id]);
        }
    }
}
```

### 3. Frontend Placeholder
```php
// En templates de visualización
if (empty($publicacion['imagen_url'])) {
    echo '<div class="image-placeholder archived">
        <i class="fas fa-archive"></i>
        <span>Imagen archivada</span>
        <small>Se eliminó tras la publicación</small>
    </div>';
} else {
    echo '<img src="' . htmlspecialchars($publicacion['imagen_url']) . '" alt="...">';
}
```

## 🔄 Flujo de Trabajo

### Diagrama de Flujo
```
Usuario actualiza estado → "publicado"
    ↓
Sistema detecta cambio de estado
    ↓
¿Hay imagen asociada?
    ↓ (SÍ)
¿Existe archivo físico?
    ↓ (SÍ)
¿Permisos OK?
    ↓ (SÍ)
Borrar archivo físico
    ↓
¿Borrado exitoso?
    ↓ (SÍ)
Actualizar BD (imagen = NULL)
    ↓
Log evento
    ↓
Frontend muestra placeholder
```

## 📋 Casos de Uso y Testing

### Casos de Uso Principales
1. **Publicación nueva**: Crear → Programar → Publicar (con imagen)
2. **Publicación existente**: Cambiar de borrador/programado → publicado
3. **Publicación sin imagen**: Cambiar estado (sin crash)
4. **Archivo inexistente**: Intentar borrar archivo que no existe

### Casos Edge
1. **Permisos insuficientes**: Archivo sin permisos de escritura
2. **Disco lleno**: Error de filesystem
3. **Concurrent access**: Múltiples usuarios actualizando
4. **Rollback necesario**: Error durante actualización de BD

### Test Cases
```php
// Test 1: Borrado exitoso
testImageDeletionSuccess() {
    // Crear publicación con imagen
    // Cambiar estado a "publicado"  
    // Verificar que archivo físico no existe
    // Verificar que BD tiene imagen = NULL
    // Verificar log de evento
}

// Test 2: Archivo inexistente
testImageDeletionFileNotFound() {
    // Crear publicación con imagen_url en BD
    // Borrar archivo físico manualmente
    // Cambiar estado a "publicado"
    // Verificar que no hay error fatal
    // Verificar que BD se actualiza correctamente
}

// Test 3: Permisos insuficientes
testImageDeletionPermissionDenied() {
    // Crear publicación con imagen
    // Cambiar permisos de archivo (chmod 444)
    // Cambiar estado a "publicado"
    // Verificar manejo graceful del error
    // Verificar logging del problema
}
```

## 🎨 Diseño Visual

### Placeholder CSS
```css
.image-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    padding: 20px;
    color: #6c757d;
    min-height: 120px;
}

.image-placeholder.archived {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-color: #adb5bd;
}

.image-placeholder i {
    font-size: 2rem;
    margin-bottom: 8px;
    opacity: 0.7;
}

.image-placeholder span {
    font-weight: 500;
    margin-bottom: 4px;
}

.image-placeholder small {
    font-size: 0.75rem;
    opacity: 0.8;
}
```

## 🔒 Seguridad y Validación

### Validaciones Requeridas
- Verificar que el archivo está dentro de directorio permitido
- Validar permisos antes de intentar borrado
- Sanitizar paths para evitar directory traversal
- Verificar ownership de archivos

### Logging de Seguridad
```php
// Log todos los eventos de borrado
error_log("IMAGE_DELETE: {$imagePath} - User: {$userId} - Publication: {$pubId} - Status: {$result}");
```

## 📊 Métricas y Monitoreo

### Métricas a Trackear
- Número de imágenes borradas por día
- Espacio liberado en MB/GB
- Errores de borrado por tipo
- Tiempo promedio de operación

### Alertas Recomendadas
- Más de 10 errores de borrado por hora
- Espacio de disco crítico
- Permisos de directorio modificados

## 🚀 Plan de Deployment

### Pre-deployment
1. Backup completo de `/uploads/` y `/uploads/blog/`
2. Verificar permisos de directorios
3. Testing en ambiente de desarrollo
4. Documentación para rollback

### Deployment
1. Implementar función helper
2. Activar en publicaciones sociales primero
3. Monitorear por 24h
4. Activar en blog posts
5. Monitorear por 48h

### Post-deployment
1. Verificar métricas de espacio liberado
2. Revisar logs de errores
3. Feedback de usuarios
4. Optimizaciones si es necesario

## 📝 Documentación de Usuario

### Mensaje para Usuarios
"Las imágenes de publicaciones se eliminan automáticamente del servidor después de ser publicadas para optimizar el almacenamiento. Esta acción es irreversible y no afecta la funcionalidad del sistema."

### FAQ
**Q: ¿Puedo recuperar una imagen borrada?**
A: No, las imágenes se eliminan permanentemente para ahorrar espacio. Asegúrate de tener copias locales si las necesitas.

**Q: ¿Afecta esto la visualización de publicaciones?**
A: No, se muestra un placeholder indicando que la imagen fue archivada.

**Q: ¿Puedo deshabilitar esta funcionalidad?**
A: Sí, contacta al administrador técnico para configurar esta opción.

---

*Especificación técnica v1.0 - Optimización de Almacenamiento de Imágenes*
*Preparado para implementación en RRSS Planner* 