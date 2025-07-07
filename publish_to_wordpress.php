<?php
require_once 'includes/functions.php';
require_once 'includes/WordPressAPI.php';
require_authentication();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    $db = getDbConnection();
    
    // Get post ID from request
    $blog_post_id = filter_input(INPUT_POST, 'blog_post_id', FILTER_VALIDATE_INT);
    
    if (!$blog_post_id) {
        throw new Exception('ID de blog post no válido');
    }
    
    // Get blog post data
    $stmt = $db->prepare("
        SELECT bp.*, ln.wordpress_url, ln.wordpress_username, ln.wordpress_app_password, ln.wordpress_enabled, ln.nombre as linea_nombre
        FROM blog_posts bp
        JOIN lineas_negocio ln ON bp.linea_negocio_id = ln.id
        WHERE bp.id = ?
    ");
    $stmt->execute([$blog_post_id]);
    $blog_post = $stmt->fetch();
    
    // Get WordPress categories and tags from form if provided
    $wp_categories = isset($_POST['wp_categories']) ? $_POST['wp_categories'] : [];
    $wp_tags = isset($_POST['wp_tags']) ? $_POST['wp_tags'] : [];
    
    if (!$blog_post) {
        throw new Exception('Blog post no encontrado');
    }
    
    // If no categories/tags from form, get saved WordPress categories/tags from database
    if (empty($wp_categories) && !empty($blog_post['wp_categories_selected'])) {
        $wp_categories = json_decode($blog_post['wp_categories_selected'], true) ?: [];
    }
    
    if (empty($wp_tags) && !empty($blog_post['wp_tags_selected'])) {
        $wp_tags = json_decode($blog_post['wp_tags_selected'], true) ?: [];
    }
    

    
    if (!$blog_post['wordpress_enabled']) {
        throw new Exception('WordPress no está habilitado para la línea de negocio: ' . $blog_post['linea_nombre']);
    }
    
    if (empty($blog_post['wordpress_url']) || empty($blog_post['wordpress_username']) || empty($blog_post['wordpress_app_password'])) {
        throw new Exception('Configuración de WordPress incompleta para: ' . $blog_post['linea_nombre']);
    }
    
    // Get categories for this post
    $stmt_cat = $db->prepare("
        SELECT bc.nombre
        FROM blog_categorias bc
        JOIN blog_post_categoria bpc ON bc.id = bpc.categoria_id
        WHERE bpc.blog_post_id = ?
    ");
    $stmt_cat->execute([$blog_post_id]);
    $categories = $stmt_cat->fetchAll(PDO::FETCH_COLUMN);
    
    // Get tags for this post
    $stmt_tag = $db->prepare("
        SELECT bt.nombre
        FROM blog_tags bt
        JOIN blog_post_tag bpt ON bt.id = bpt.tag_id
        WHERE bpt.blog_post_id = ?
    ");
    $stmt_tag->execute([$blog_post_id]);
    $tags = $stmt_tag->fetchAll(PDO::FETCH_COLUMN);
    
    // Prepare post data for WordPress
    $post_data = [
        'titulo' => $blog_post['titulo'],
        'contenido' => $blog_post['contenido'],
        'excerpt' => $blog_post['excerpt'],
        'slug' => $blog_post['slug'],
        'estado' => $blog_post['estado'],
        'fecha_publicacion' => $blog_post['fecha_publicacion'],
        'imagen_destacada' => $blog_post['imagen_destacada'],
        'categories' => $categories,
        'tags' => $tags,
        'wp_categories' => $wp_categories, // WordPress categories by ID
        'wp_tags' => $wp_tags // WordPress tags by ID
    ];
    
    // Initialize WordPress API
    $wp_api = new WordPressAPI(
        $blog_post['wordpress_url'],
        $blog_post['wordpress_username'],
        $blog_post['wordpress_app_password']
    );
    
    // Test connection first
    $connection_test = $wp_api->testConnection();
    if (!$connection_test['success']) {
        throw new Exception('Error de conexión con WordPress: ' . $connection_test['error']);
    }
    
    // Change status to publish before sending to WordPress
    $update_status_stmt = $db->prepare("UPDATE blog_posts SET estado = 'publish' WHERE id = ?");
    $update_status_stmt->execute([$blog_post_id]);
    
    // Update post data with publish status
    $post_data['estado'] = 'publish';
    
    // Publish post
    $existing_wp_post_id = $blog_post['wp_post_id'] ?? null;
    $result = $wp_api->publishPost($post_data, $existing_wp_post_id);
    
    if ($result['success']) {
        // Update blog post with WordPress data
        $update_stmt = $db->prepare("
            UPDATE blog_posts 
            SET 
                wp_post_id = ?,
                wp_sync_status = 'synced',
                wp_sync_error = NULL,
                wp_last_sync = NOW(),
                estado = 'publish'
            WHERE id = ?
        ");
        $update_stmt->execute([$result['wp_post_id'], $blog_post_id]);
        
        // Update business line sync timestamp
        $sync_stmt = $db->prepare("UPDATE lineas_negocio SET wordpress_last_sync = NOW() WHERE id = ?");
        $sync_stmt->execute([$blog_post['linea_negocio_id']]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Post publicado exitosamente en WordPress',
            'wp_post_id' => $result['wp_post_id'],
            'wp_url' => $result['wp_url']
        ]);
    } else {
        // Update error status
        $error_stmt = $db->prepare("
            UPDATE blog_posts 
            SET 
                wp_sync_status = 'error',
                wp_sync_error = ?,
                wp_last_sync = NOW()
            WHERE id = ?
        ");
        $error_stmt->execute([$result['error'], $blog_post_id]);
        
        throw new Exception($result['message'] . ': ' . $result['error']);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage()
    ]);
}
?> 