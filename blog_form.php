<?php
require_once 'includes/functions.php';
require_authentication();

// Verificar que tengamos el parámetro de línea de negocio
$lineaId = null;
$lineaSlug = null;

if (isset($_GET['linea_id'])) {
    $lineaId = intval($_GET['linea_id']);
    if (isset($_GET['linea_slug'])) {
        $lineaSlug = trim($_GET['linea_slug']);
    }
} elseif (isset($_GET['linea_slug'])) {
    $lineaSlug = trim($_GET['linea_slug']);
} else {
    $_SESSION['feedback_message'] = ['tipo' => 'error', 'mensaje' => 'Falta el identificador de la línea de negocio.'];
    header("Location: index.php");
    exit;
}

$db = getDbConnection();

// Resolver linea_id y linea_slug si falta uno
if ($lineaId && !$lineaSlug) {
    $stmt = $db->prepare("SELECT slug FROM lineas_negocio WHERE id = ?");
    $stmt->execute([$lineaId]);
    $linea_data = $stmt->fetch();
    if ($linea_data) {
        $lineaSlug = $linea_data['slug'];
    } else {
        $_SESSION['feedback_message'] = ['tipo' => 'error', 'mensaje' => 'Línea de negocio no válida.'];
        header("Location: index.php");
        exit;
    }
} elseif ($lineaSlug && !$lineaId) {
    $stmt = $db->prepare("SELECT id FROM lineas_negocio WHERE slug = ?");
    $stmt->execute([$lineaSlug]);
    $linea_data = $stmt->fetch();
    if ($linea_data) {
        $lineaId = $linea_data['id'];
    } else {
        $_SESSION['feedback_message'] = ['tipo' => 'error', 'mensaje' => 'Línea de negocio no válida.'];
        header("Location: index.php");
        exit;
    }
}

// Obtener el nombre y logo de la línea de negocio
$stmt = $db->prepare("SELECT nombre, logo_filename FROM lineas_negocio WHERE id = ?");
$stmt->execute([$lineaId]);
$linea_data = $stmt->fetch();
if (!$linea_data) {
    $_SESSION['feedback_message'] = ['tipo' => 'error', 'mensaje' => 'Error al obtener la línea de negocio.'];
    header("Location: index.php");
    exit;
}
$lineaNombre = $linea_data['nombre'];
$lineaLogo = $linea_data['logo_filename'] ?: 'default.png'; // Usar default.png si no hay logo

// Página de retorno
$paginaRetorno = "planner.php?slug=" . urlencode($lineaSlug) . "&type=blog";

// Obtener categorías y tags disponibles
$categorias_disponibles = [];
$tags_disponibles = [];

try {
    // Obtener categorías
    $stmt_cat = $db->query("SELECT * FROM blog_categorias ORDER BY nombre ASC");
    $categorias_disponibles = $stmt_cat->fetchAll();
    
    // Obtener tags
    $stmt_tags = $db->query("SELECT * FROM blog_tags ORDER BY nombre ASC");
    $tags_disponibles = $stmt_tags->fetchAll();
} catch (PDOException $e) {
    error_log("Error obteniendo categorías/tags: " . $e->getMessage());
}

// Variables para el formulario
$modo = 'crear';
$blogPost = [
    'id' => '',
    'titulo' => '',
    'contenido' => '',
    'excerpt' => '',
    'slug' => '',
    'imagen_destacada' => '',
    'fecha_publicacion' => date('Y-m-d'),
    'estado' => 'draft',
    'linea_negocio_id' => $lineaId
];
$categorias_seleccionadas = [];
$tags_seleccionados = [];
$errores = [];

// Verificar si estamos editando
$blogPostId = isset($_GET['id']) ? intval($_GET['id']) : null;
if ($blogPostId) {
    $modo = 'editar';
    
    $stmt = $db->prepare("SELECT * FROM blog_posts WHERE id = ? AND linea_negocio_id = ?");
    $stmt->execute([$blogPostId, $lineaId]);
    $blogPostBD = $stmt->fetch();
    
    if ($blogPostBD) {
        $blogPost = $blogPostBD;
        // Formatear fecha para el input date
        if (!empty($blogPost['fecha_publicacion'])) {
            $blogPost['fecha_publicacion'] = date('Y-m-d', strtotime($blogPost['fecha_publicacion']));
        }
        
        // Obtener categorías seleccionadas
        $stmt_cat_sel = $db->prepare("SELECT categoria_id FROM blog_post_categoria WHERE blog_post_id = ?");
        $stmt_cat_sel->execute([$blogPostId]);
        $categorias_seleccionadas = $stmt_cat_sel->fetchAll(PDO::FETCH_COLUMN);
        
        // Obtener tags seleccionados
        $stmt_tag_sel = $db->prepare("SELECT tag_id FROM blog_post_tag WHERE blog_post_id = ?");
        $stmt_tag_sel->execute([$blogPostId]);
        $tags_seleccionados = $stmt_tag_sel->fetchAll(PDO::FETCH_COLUMN);
        
        // Obtener taxonomías de WordPress seleccionadas
        $wp_categorias_seleccionadas = [];
        $wp_tags_seleccionados = [];
        if (!empty($blogPost['wp_categories_selected'])) {
            $wp_categorias_seleccionadas = json_decode($blogPost['wp_categories_selected'], true) ?: [];
        }
        if (!empty($blogPost['wp_tags_selected'])) {
            $wp_tags_seleccionados = json_decode($blogPost['wp_tags_selected'], true) ?: [];
        }
        
    } else {
        header("Location: " . $paginaRetorno);
        exit;
    }
}

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitizar inputs
    $titulo = trim($_POST['titulo']);
    $contenido = $_POST['contenido']; // No filtramos para permitir formato HTML de TinyMCE
    $excerpt = trim($_POST['excerpt']);
    $slug = trim($_POST['slug']);
    $fecha = filter_input(INPUT_POST, 'fecha', FILTER_SANITIZE_STRING);
    $estado = filter_input(INPUT_POST, 'estado', FILTER_SANITIZE_STRING);
    $categorias = isset($_POST['categorias']) ? $_POST['categorias'] : [];
    $tags = isset($_POST['tags']) ? $_POST['tags'] : [];
    $wp_categorias = isset($_POST['wp_categories']) ? $_POST['wp_categories'] : [];
    $wp_tags = isset($_POST['wp_tags']) ? $_POST['wp_tags'] : [];
    
    // Auto-generar slug si está vacío
    if (empty($slug) && !empty($titulo)) {
        $slug = generateSlug($titulo);
    }
    
    // Validar campos requeridos
    if (empty($titulo)) {
        $errores[] = "El título es obligatorio";
    }
    
    if (empty($contenido)) {
        $errores[] = "El contenido es obligatorio";
    }
    
    if (empty($fecha)) {
        $errores[] = "La fecha es obligatoria";
    }
    
    if (!in_array($estado, ['draft', 'scheduled', 'publish'])) {
        $errores[] = "Estado no válido";
    }
    
    if (empty($slug)) {
        $errores[] = "El slug es obligatorio";
    } elseif (!preg_match('/^[a-z0-9-]+$/', $slug)) {
        $errores[] = "El slug solo puede contener letras minúsculas, números y guiones";
    }
    
    // Procesar imagen si se sube una nueva
    $imagen_destacada = $blogPost['imagen_destacada']; // Mantener la actual por defecto
    
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['imagen']['name'];
        $tmp_name = $_FILES['imagen']['tmp_name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            $errores[] = "Formato de imagen no permitido. Use: jpg, jpeg, png, gif o webp.";
        } else {
            // Crear directorio si no existe
            $upload_dir = 'uploads/blog/' . $lineaId . '/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generar nombre único
            $new_filename = uniqid() . '.' . $ext;
            $destino = $upload_dir . $new_filename;
            
            if (move_uploaded_file($tmp_name, $destino)) {
                // Si hay una imagen anterior, la eliminamos
                if (!empty($blogPost['imagen_destacada']) && file_exists($blogPost['imagen_destacada']) && $modo === 'editar') {
                    unlink($blogPost['imagen_destacada']);
                }
                $imagen_destacada = $destino;
            } else {
                $errores[] = "Error al subir la imagen";
            }
        }
    }
    
    // Si no hay errores, guardar en la BD
    if (empty($errores)) {
        try {
            $db->beginTransaction();
            
            // Convertir arrays de WordPress a JSON
            $wp_categorias_json = !empty($wp_categorias) ? json_encode(array_map('intval', $wp_categorias)) : null;
            $wp_tags_json = !empty($wp_tags) ? json_encode(array_map('intval', $wp_tags)) : null;
            
            if ($modo === 'crear') {
                $stmt = $db->prepare("
                    INSERT INTO blog_posts (titulo, contenido, excerpt, slug, imagen_destacada, fecha_publicacion, estado, linea_negocio_id, wp_categories_selected, wp_tags_selected)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$titulo, $contenido, $excerpt, $slug, $imagen_destacada, $fecha, $estado, $lineaId, $wp_categorias_json, $wp_tags_json]);
                $blogPostId = $db->lastInsertId();
            } else {
                $stmt = $db->prepare("
                    UPDATE blog_posts 
                    SET titulo = ?, contenido = ?, excerpt = ?, slug = ?, imagen_destacada = ?, fecha_publicacion = ?, estado = ?, wp_categories_selected = ?, wp_tags_selected = ?
                    WHERE id = ?
                ");
                $stmt->execute([$titulo, $contenido, $excerpt, $slug, $imagen_destacada, $fecha, $estado, $wp_categorias_json, $wp_tags_json, $blogPost['id']]);
                $blogPostId = $blogPost['id'];
                
                // Eliminar relaciones anteriores
                $stmt_del_cat = $db->prepare("DELETE FROM blog_post_categoria WHERE blog_post_id = ?");
                $stmt_del_cat->execute([$blogPostId]);
                
                $stmt_del_tag = $db->prepare("DELETE FROM blog_post_tag WHERE blog_post_id = ?");
                $stmt_del_tag->execute([$blogPostId]);
            }
            
            // Insertar categorías seleccionadas
            if (!empty($categorias)) {
                $stmt_cat = $db->prepare("INSERT INTO blog_post_categoria (blog_post_id, categoria_id) VALUES (?, ?)");
                foreach ($categorias as $categoria_id) {
                    $stmt_cat->execute([$blogPostId, intval($categoria_id)]);
                }
            }
            
            // Insertar tags seleccionados
            if (!empty($tags)) {
                $stmt_tag = $db->prepare("INSERT INTO blog_post_tag (blog_post_id, tag_id) VALUES (?, ?)");
                foreach ($tags as $tag_id) {
                    $stmt_tag->execute([$blogPostId, intval($tag_id)]);
                }
            }
            
            $db->commit();
            
            // Establecer mensaje de éxito
            if ($modo === 'crear') {
                $_SESSION['feedback_message'] = ['tipo' => 'success', 'mensaje' => 'Blog post creado correctamente'];
            } else {
                $_SESSION['feedback_message'] = ['tipo' => 'success', 'mensaje' => 'Blog post actualizado correctamente'];
            }
            
            // Redirigir a la página de blog posts
            header("Location: " . $paginaRetorno);
            exit;
            
        } catch (Exception $e) {
            $db->rollBack();
            $errores[] = "Error en la base de datos: " . $e->getMessage();
        }
    }
    
    // Si hay errores, prepopular el formulario con los valores enviados
    $blogPost['titulo'] = $titulo;
    $blogPost['contenido'] = $contenido;
    $blogPost['excerpt'] = $excerpt;
    $blogPost['slug'] = $slug;
    $blogPost['fecha_publicacion'] = $fecha;
    $blogPost['estado'] = $estado;
    $blogPost['imagen_destacada'] = $imagen_destacada;
    $categorias_seleccionadas = $categorias;
    $tags_seleccionados = $tags;
}

// Función para generar slug URL-friendly
function generateSlug($text) {
    // Convertir a minúsculas
    $text = strtolower($text);
    
    // Reemplazar caracteres especiales españoles
    $text = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'n'], $text);
    
    // Eliminar caracteres que no sean letras, números o espacios
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    
    // Reemplazar espacios y múltiples guiones por un solo guión
    $text = preg_replace('/[\s-]+/', '-', $text);
    
    // Eliminar guiones del inicio y final
    $text = trim($text, '-');
    
    return $text;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ($modo === 'crear' ? 'Nuevo' : 'Editar'); ?> Blog Post - <?php echo $lineaNombre; ?></title>
    <link rel="icon" type="image/png" href="assets/images/logos/Loop-favicon.png">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- TinyMCE -->
    <script src="https://cdn.tiny.cloud/1/nfo9dnfbcv9jmtctmgwb4w6d590ifkd6gxhkuzvx8m820j4g/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <!-- Estilos propios -->
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="app-simple">
        <!-- Enhanced Blog Form Header -->
        <div class="blog-form-header">
            <div class="header-top">
                <div class="header-left">
                    <div class="breadcrumb">
                        <a href="<?php echo $paginaRetorno; ?>" class="breadcrumb-link">
                            <i class="fas fa-blog"></i> Blog Posts
                        </a>
                        <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
                        <span class="breadcrumb-current">
                            <?php echo ($modo === 'crear' ? 'Nuevo Post' : 'Editar Post'); ?>
                        </span>
                    </div>
                </div>
                <div class="header-right">
                    <div class="business-line-info">
                        <div class="business-line-logo">
                            <img src="assets/images/logos/<?php echo $lineaLogo; ?>" alt="<?php echo $lineaNombre; ?>" class="business-logo">
                        </div>
                        <div class="business-line-text">
                            <span class="business-line-label">Línea de Negocio</span>
                            <span class="business-line-name"><?php echo $lineaNombre; ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="header-main">
                <div class="page-title-section">
                    <h1 class="page-title">
                        <i class="fas fa-<?php echo ($modo === 'crear' ? 'plus-circle' : 'edit'); ?> title-icon"></i>
                        <?php if ($modo === 'crear'): ?>
                            <span>Crear Nuevo Blog Post</span>
                        <?php else: ?>
                            <span>Editar Blog Post</span>
                            <?php if (!empty($blogPost['titulo'])): ?>
                                <span class="post-title-preview">"<?php echo htmlspecialchars(truncateText($blogPost['titulo'], 40)); ?>"</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </h1>
                    <p class="page-subtitle">
                        <?php if ($modo === 'crear'): ?>
                            Crea contenido de calidad para el blog de <?php echo $lineaNombre; ?>. Compatible con WordPress.
                        <?php else: ?>
                            Modifica el contenido y configuración de tu blog post.
                        <?php endif; ?>
                    </p>
                </div>
                
                <div class="header-actions">
                    <a href="<?php echo $paginaRetorno; ?>" class="btn btn-secondary btn-header">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                    <?php if ($modo === 'editar'): ?>
                        <div class="post-status-info">
                            <span class="status-label">Estado:</span>
                            <span class="status-badge status-<?php echo $blogPost['estado']; ?>">
                                <?php 
                                $estados_display = ['draft' => 'Borrador', 'scheduled' => 'Programado', 'publish' => 'Publicado'];
                                echo $estados_display[$blogPost['estado']] ?? $blogPost['estado']; 
                                ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- All errors are now displayed via toast notifications -->

        <form method="POST" enctype="multipart/form-data" class="form-section blog-form">
            <!-- Título y Slug -->
            <div class="form-row">
                <div class="form-column">
                    <div class="form-group">
                        <label for="titulo">Título <span class="required">*</span></label>
                        <input type="text" id="titulo" name="titulo" class="form-control" 
                               value="<?php echo htmlspecialchars($blogPost['titulo']); ?>" required>
                    </div>
                </div>
                <div class="form-column">
                    <div class="form-group">
                        <label for="slug">Slug (URL) <span class="required">*</span></label>
                        <input type="text" id="slug" name="slug" class="form-control" 
                               value="<?php echo htmlspecialchars($blogPost['slug']); ?>" 
                               pattern="[a-z0-9\-]+" title="Solo letras minúsculas, números y guiones">
                        <small>Se genera automáticamente desde el título si se deja vacío</small>
                    </div>
                </div>
            </div>

            <!-- Excerpt -->
            <div class="form-row">
                <div class="form-column">
                    <div class="form-group">
                        <label for="excerpt">Resumen/Excerpt</label>
                        <textarea id="excerpt" name="excerpt" class="form-control" rows="3" 
                                  placeholder="Breve descripción del post (opcional para SEO)"><?php echo htmlspecialchars($blogPost['excerpt']); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Contenido con TinyMCE -->
            <div class="form-row">
                <div class="form-column">
                    <div class="form-group">
                        <label for="contenido">Contenido <span class="required">*</span></label>
                        <textarea id="contenido" name="contenido" class="form-control tinymce-editor" 
                                  placeholder="Escribe el contenido del blog post aquí..." required><?php echo htmlspecialchars($blogPost['contenido']); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Categorías y Tags -->
            <div class="form-row">
                <div class="form-column">
                    <div class="form-group">
                        <label for="categorias">Categorías</label>
                        <div id="categorias-container" class="categorias-container">
                            <?php
                            // Verificar si WordPress está habilitado para esta línea de negocio
                            $stmt_wp_check = $db->prepare("SELECT wordpress_enabled, wordpress_url FROM lineas_negocio WHERE id = ?");
                            $stmt_wp_check->execute([$lineaId]);
                            $wp_check = $stmt_wp_check->fetch();
                            ?>
                            
                            <?php if ($wp_check && $wp_check['wordpress_enabled']): ?>
                                <p class="loading-message">
                                    <i class="fas fa-spinner fa-spin"></i> Cargando categorías de WordPress...
                                </p>
                            <?php else: ?>
                                <?php if (!empty($categorias_disponibles)): ?>
                                    <?php foreach ($categorias_disponibles as $categoria): ?>
                                        <div class="checkbox-item">
                                            <input type="checkbox" id="cat_<?php echo $categoria['id']; ?>" 
                                                   name="categorias[]" value="<?php echo $categoria['id']; ?>"
                                                   <?php echo in_array($categoria['id'], $categorias_seleccionadas) ? 'checked' : ''; ?>>
                                            <label for="cat_<?php echo $categoria['id']; ?>">
                                                <?php echo htmlspecialchars($categoria['nombre']); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="no-items">No hay categorías disponibles.</p>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="form-column">
                    <div class="form-group">
                        <label for="tags">Etiquetas/Tags</label>
                        <div id="tags-container" class="tags-container">
                            <?php if ($wp_check && $wp_check['wordpress_enabled']): ?>
                                <p class="loading-message">
                                    <i class="fas fa-spinner fa-spin"></i> Cargando etiquetas de WordPress...
                                </p>
                            <?php else: ?>
                                <?php if (!empty($tags_disponibles)): ?>
                                    <?php foreach ($tags_disponibles as $tag): ?>
                                        <div class="checkbox-item">
                                            <input type="checkbox" id="tag_<?php echo $tag['id']; ?>" 
                                                   name="tags[]" value="<?php echo $tag['id']; ?>"
                                                   <?php echo in_array($tag['id'], $tags_seleccionados) ? 'checked' : ''; ?>>
                                            <label for="tag_<?php echo $tag['id']; ?>">
                                                <?php echo htmlspecialchars($tag['nombre']); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="no-items">No hay etiquetas disponibles.</p>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Imagen Destacada y Configuración -->
            <div class="form-row">
                <div class="form-column">
                    <div class="form-group">
                        <label for="imagen">Imagen Destacada</label>
                        <input type="file" id="imagen" name="imagen" class="form-control" accept="image/*">
                        <?php if (!empty($blogPost['imagen_destacada'])): ?>
                            <div class="preview-container">
                                <p>Imagen actual:</p>
                                <img src="<?php echo htmlspecialchars($blogPost['imagen_destacada']); ?>" alt="Imagen actual">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="form-column">
                    <div class="form-group">
                        <label for="fecha">Fecha de Publicación <span class="required">*</span></label>
                        <input type="date" id="fecha" name="fecha" class="form-control" 
                               value="<?php echo $blogPost['fecha_publicacion']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="estado">Estado <span class="required">*</span></label>
                        <select id="estado" name="estado" class="form-control" required>
                            <option value="draft" <?php echo ($blogPost['estado'] === 'draft') ? 'selected' : ''; ?>>Borrador</option>
                            <option value="scheduled" <?php echo ($blogPost['estado'] === 'scheduled') ? 'selected' : ''; ?>>Programado</option>
                            <option value="publish" <?php echo ($blogPost['estado'] === 'publish') ? 'selected' : ''; ?>>Publicado</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="buttons">
                <a href="<?php echo $paginaRetorno; ?>" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo ($modo === 'crear' ? 'Crear' : 'Actualizar'); ?> Blog Post
                </button>
                
                <?php if ($modo === 'editar' && !empty($blogPost['id'])): ?>
                    <?php
                    // Verificar si WordPress está habilitado para esta línea de negocio
                    $stmt_wp = $db->prepare("SELECT wordpress_enabled, wordpress_url FROM lineas_negocio WHERE id = ?");
                    $stmt_wp->execute([$lineaId]);
                    $wp_config = $stmt_wp->fetch();
                    ?>
                    
                    <?php if ($wp_config && $wp_config['wordpress_enabled']): ?>
                        <button type="button" class="btn btn-wordpress" onclick="publishToWordPress(<?php echo $blogPost['id']; ?>)">
                            <i class="fab fa-wordpress"></i> Publicar en WordPress
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <script>
        // Función JavaScript para generar slug
        function generateSlug(text) {
            if (!text) return '';
            
            return text
                .toLowerCase()
                .replace(/[áàäâ]/g, 'a')
                .replace(/[éèëê]/g, 'e')
                .replace(/[íìïî]/g, 'i')
                .replace(/[óòöô]/g, 'o')
                .replace(/[úùüû]/g, 'u')
                .replace(/ñ/g, 'n')
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/[\s-]+/g, '-')
                .replace(/^-+|-+$/g, '');
        }

        // Esperar a que el DOM esté completamente cargado
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar TinyMCE
            tinymce.init({
                selector: '.tinymce-editor',
                height: 400,
                menubar: true,
                plugins: [
                    'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                    'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                    'insertdatetime', 'media', 'table', 'wordcount', 'emoticons'
                ],
                toolbar: 'undo redo | blocks | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | link image media | table | code preview fullscreen',
                content_style: 'body { font-family: "Open Sans", Arial, sans-serif; font-size: 14px; }',
                language: 'es',
                branding: false,
                promotion: false,
                setup: function (editor) {
                    editor.on('change', function () {
                        editor.save();
                    });
                }
            });

            // Auto-generar slug desde el título
            const tituloField = document.getElementById('titulo');
            const slugField = document.getElementById('slug');
            
            if (tituloField && slugField) {
                let slugWasAutoGenerated = true; // Flag para saber si el slug fue auto-generado
                
                tituloField.addEventListener('input', function() {
                    // Solo generar si el slug fue auto-generado (no editado manualmente)
                    if (slugWasAutoGenerated) {
                        const titulo = this.value;
                        const slug = generateSlug(titulo);
                        console.log('Generated slug:', slug, 'from title:', titulo); // Debug
                        slugField.value = slug;
                    }
                });

                // Marcar como editado manualmente cuando el usuario cambia el slug
                slugField.addEventListener('input', function() {
                    slugWasAutoGenerated = false;
                });

                // También generar al salir del campo de título (blur)
                tituloField.addEventListener('blur', function() {
                    if (slugWasAutoGenerated && this.value.trim() !== '') {
                        const slug = generateSlug(this.value);
                        slugField.value = slug;
                    }
                });
                
                // Si estamos editando un post existente y ya tiene slug, marcar como no auto-generado
                if (slugField.value.trim() !== '') {
                    slugWasAutoGenerated = false;
                }
            }

            // Validar slug en tiempo real
            if (slugField) {
                slugField.addEventListener('input', function() {
                    const slug = this.value;
                    const isValid = /^[a-z0-9-]*$/.test(slug);
                    
                    if (!isValid && slug !== '') {
                        this.style.borderColor = '#e74c3c';
                        this.title = 'Solo se permiten letras minúsculas, números y guiones';
                    } else {
                        this.style.borderColor = '';
                        this.title = '';
                    }
                });
            }

            // Asegurar que TinyMCE se guarde antes del envío del formulario
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function() {
                    tinymce.triggerSave();
                });
            }

            // Cargar taxonomías de WordPress si está habilitado
            <?php if ($wp_check && $wp_check['wordpress_enabled']): ?>
                loadWordPressTaxonomies(<?php echo $lineaId; ?>, <?php echo json_encode($wp_categorias_seleccionadas ?? []); ?>, <?php echo json_encode($wp_tags_seleccionados ?? []); ?>);
            <?php endif; ?>
        });

        // Función para cargar categorías y etiquetas de WordPress
        function loadWordPressTaxonomies(lineaNegocioId, selectedCategories = [], selectedTags = []) {
            fetch(`get_wordpress_taxonomies.php?linea_negocio_id=${lineaNegocioId}`)
                .then(response => response.json())
                .then(data => {
                    const categoriesContainer = document.getElementById('categorias-container');
                    const tagsContainer = document.getElementById('tags-container');
                    
                    if (data.success) {
                        // Renderizar categorías
                        if (data.categories && data.categories.length > 0) {
                            let categoriesHtml = '';
                            data.categories.forEach(category => {
                                const isSelected = selectedCategories.includes(category.id);
                                categoriesHtml += `
                                    <div class="checkbox-item">
                                        <input type="checkbox" id="wp_cat_${category.id}" 
                                               name="wp_categories[]" value="${category.id}" class="wp-category-checkbox"
                                               ${isSelected ? 'checked' : ''}>
                                        <label for="wp_cat_${category.id}">
                                            ${category.name} <span class="count">(${category.count})</span>
                                        </label>
                                    </div>
                                `;
                            });
                            categoriesContainer.innerHTML = categoriesHtml;
                        } else {
                            categoriesContainer.innerHTML = '<p class="no-items">No hay categorías disponibles en WordPress</p>';
                        }
                        
                        // Renderizar etiquetas
                        if (data.tags && data.tags.length > 0) {
                            let tagsHtml = '';
                            data.tags.forEach(tag => {
                                const isSelected = selectedTags.includes(tag.id);
                                tagsHtml += `
                                    <div class="checkbox-item">
                                        <input type="checkbox" id="wp_tag_${tag.id}" 
                                               name="wp_tags[]" value="${tag.id}" class="wp-tag-checkbox"
                                               ${isSelected ? 'checked' : ''}>
                                        <label for="wp_tag_${tag.id}">
                                            ${tag.name} <span class="count">(${tag.count})</span>
                                        </label>
                                    </div>
                                `;
                            });
                            tagsContainer.innerHTML = tagsHtml;
                        } else {
                            tagsContainer.innerHTML = '<p class="no-items">No hay etiquetas disponibles en WordPress</p>';
                        }
                    } else {
                        categoriesContainer.innerHTML = `<p class="error-message">Error: ${data.error || 'No se pudieron cargar las categorías'}</p>`;
                        tagsContainer.innerHTML = `<p class="error-message">Error: ${data.error || 'No se pudieron cargar las etiquetas'}</p>`;
                    }
                })
                .catch(error => {
                    console.error('Error loading WordPress taxonomies:', error);
                    const errorMessage = '<p class="error-message">Error de conexión al cargar taxonomías</p>';
                    document.getElementById('categorias-container').innerHTML = errorMessage;
                    document.getElementById('tags-container').innerHTML = errorMessage;
                });
        }

        // Función para publicar en WordPress
        function publishToWordPress(blogPostId) {
            if (!blogPostId) {
                alert('Error: ID de blog post no válido');
                return;
            }
            
            const button = document.querySelector('.btn-wordpress');
            if (button) {
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Publicando...';
            }
            
            // Crear FormData para enviar el POST
            const formData = new FormData();
            formData.append('blog_post_id', blogPostId);
            
            // Recoger categorías de WordPress seleccionadas
            const wpCategories = [];
            document.querySelectorAll('.wp-category-checkbox:checked').forEach(checkbox => {
                wpCategories.push(checkbox.value);
            });
            
            // Recoger etiquetas de WordPress seleccionadas
            const wpTags = [];
            document.querySelectorAll('.wp-tag-checkbox:checked').forEach(checkbox => {
                wpTags.push(checkbox.value);
            });
            
            // Añadir taxonomías al FormData
            wpCategories.forEach(catId => {
                formData.append('wp_categories[]', catId);
            });
            wpTags.forEach(tagId => {
                formData.append('wp_tags[]', tagId);
            });
            
            fetch('publish_to_wordpress.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ ' + data.message + '\n\nURL: ' + (data.wp_url || 'N/A'));
                    // Recargar la página para mostrar el estado actualizado
                    window.location.reload();
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ Error de conexión: ' + error.message);
            })
            .finally(() => {
                if (button) {
                    button.disabled = false;
                    button.innerHTML = '<i class="fab fa-wordpress"></i> Publicar en WordPress';
                }
            });
        }
    </script>
    
    <!-- Include main JavaScript file with toast notifications -->
    <script src="assets/js/main.js"></script>
    
    <script>
        // Handle PHP session messages and form errors with toast notifications
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (isset($_SESSION['feedback_message'])): ?>
                const feedbackType = '<?php echo $_SESSION['feedback_message']['tipo']; ?>';
                const feedbackMessage = <?php echo json_encode($_SESSION['feedback_message']['mensaje']); ?>;
                handleSessionMessage(feedbackType, feedbackMessage);
                <?php unset($_SESSION['feedback_message']); ?>
            <?php endif; ?>
            
            <?php if (!empty($errores)): ?>
                // Show form validation errors as toasts
                <?php foreach ($errores as $error): ?>
                    showErrorToast(<?php echo json_encode($error); ?>);
                <?php endforeach; ?>
            <?php endif; ?>
        });
        
        // Override the WordPress publish function to use toasts instead of alerts
        function publishToWordPress(blogPostId) {
            if (!blogPostId) {
                showErrorToast('ID de blog post no válido');
                return;
            }
            
            const button = document.querySelector('.btn-wordpress');
            if (button) {
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Publicando...';
            }
            
            // Show loading toast
            showInfoToast('Publicando en WordPress...', 0); // Permanent toast
            
            // Crear FormData para enviar el POST
            const formData = new FormData();
            formData.append('blog_post_id', blogPostId);
            
            // Recoger categorías de WordPress seleccionadas
            const wpCategories = [];
            document.querySelectorAll('.wp-category-checkbox:checked').forEach(checkbox => {
                wpCategories.push(checkbox.value);
            });
            
            // Recoger etiquetas de WordPress seleccionadas
            const wpTags = [];
            document.querySelectorAll('.wp-tag-checkbox:checked').forEach(checkbox => {
                wpTags.push(checkbox.value);
            });
            
            // Añadir taxonomías al FormData
            wpCategories.forEach(catId => {
                formData.append('wp_categories[]', catId);
            });
            wpTags.forEach(tagId => {
                formData.append('wp_tags[]', tagId);
            });
            
            fetch('publish_to_wordpress.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessToast(data.message + (data.wp_url ? `\nURL: ${data.wp_url}` : ''), 8000);
                    // Recargar la página para mostrar el estado actualizado
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    showErrorToast(data.message || 'Error al publicar en WordPress');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorToast('Error de conexión: ' + error.message);
            })
            .finally(() => {
                if (button) {
                    button.disabled = false;
                    button.innerHTML = '<i class="fab fa-wordpress"></i> Publicar en WordPress';
                }
            });
        }
    </script>
</body>
</html> 