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

    $blog_post_id = filter_input(INPUT_POST, 'blog_post_id', FILTER_VALIDATE_INT);
    $linea = filter_input(INPUT_POST, 'linea', FILTER_VALIDATE_INT);
    $scheduled_datetime_raw = $_POST['scheduled_datetime'] ?? '';

    if (!$blog_post_id || !$linea || empty($scheduled_datetime_raw)) {
        throw new Exception('Parámetros inválidos');
    }

    // Normalize/validate datetime
    $timestamp = strtotime($scheduled_datetime_raw);
    if ($timestamp === false) {
        throw new Exception('Fecha/hora no válida');
    }
    $scheduled_datetime = date('Y-m-d H:i:s', $timestamp);

    // Load blog post + WP config
    $stmt = $db->prepare("\n        SELECT bp.*, ln.wordpress_url, ln.wordpress_username, ln.wordpress_app_password, ln.wordpress_enabled, ln.nombre as linea_nombre\n        FROM blog_posts bp\n        JOIN lineas_negocio ln ON bp.linea_negocio_id = ln.id\n        WHERE bp.id = ? AND bp.linea_negocio_id = ?\n    ");
    $stmt->execute([$blog_post_id, $linea]);
    $blog_post = $stmt->fetch();

    if (!$blog_post) {
        throw new Exception('Blog post no encontrado o sin permisos');
    }

    if (!$blog_post['wordpress_enabled']) {
        throw new Exception('WordPress no está habilitado para la línea de negocio: ' . $blog_post['linea_nombre']);
    }
    if (empty($blog_post['wordpress_url']) || empty($blog_post['wordpress_username']) || empty($blog_post['wordpress_app_password'])) {
        throw new Exception('Configuración de WordPress incompleta para: ' . $blog_post['linea_nombre']);
    }

    // Load categories/tags for this post (names)
    $stmt_cat = $db->prepare("\n        SELECT bc.nombre\n        FROM blog_categorias bc\n        JOIN blog_post_categoria bpc ON bc.id = bpc.categoria_id\n        WHERE bpc.blog_post_id = ?\n    ");
    $stmt_cat->execute([$blog_post_id]);
    $categories = $stmt_cat->fetchAll(PDO::FETCH_COLUMN);

    $stmt_tag = $db->prepare("\n        SELECT bt.nombre\n        FROM blog_tags bt\n        JOIN blog_post_tag bpt ON bt.id = bpt.tag_id\n        WHERE bpt.blog_post_id = ?\n    ");
    $stmt_tag->execute([$blog_post_id]);
    $tags = $stmt_tag->fetchAll(PDO::FETCH_COLUMN);

    // Decode saved WordPress category/tag IDs if any
    $wp_categories = [];
    $wp_tags = [];
    if (!empty($blog_post['wp_categories_selected'])) {
        $wp_categories = json_decode($blog_post['wp_categories_selected'], true) ?: [];
    }
    if (!empty($blog_post['wp_tags_selected'])) {
        $wp_tags = json_decode($blog_post['wp_tags_selected'], true) ?: [];
    }

    // Prepare post data for WordPress
    $post_data = [
        'titulo' => $blog_post['titulo'],
        'contenido' => $blog_post['contenido'],
        'excerpt' => $blog_post['excerpt'],
        'slug' => $blog_post['slug'],
        'estado' => 'scheduled',
        'fecha_publicacion' => $scheduled_datetime,
        'imagen_destacada' => $blog_post['imagen_destacada'],
        'categories' => $categories,
        'tags' => $tags,
        'wp_categories' => $wp_categories,
        'wp_tags' => $wp_tags
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

    // Update DB: set estado to scheduled and fecha_publicacion
    $update_state_stmt = $db->prepare("UPDATE blog_posts SET estado = 'scheduled', fecha_publicacion = ? WHERE id = ?");
    $update_state_stmt->execute([$scheduled_datetime, $blog_post_id]);

    // Send to WordPress: create or update as scheduled (future)
    $existing_wp_post_id = $blog_post['wp_post_id'] ?? null;
    $result = $wp_api->publishPost($post_data, $existing_wp_post_id);

    if ($result['success']) {
        // Persist WP sync data
        $update_stmt = $db->prepare("\n            UPDATE blog_posts \n            SET \n                wp_post_id = ?,\n                wp_sync_status = 'synced',\n                wp_sync_error = NULL,\n                wp_last_sync = NOW(),\n                estado = 'scheduled',\n                fecha_publicacion = ?\n            WHERE id = ?\n        ");
        $update_stmt->execute([$result['wp_post_id'], $scheduled_datetime, $blog_post_id]);

        // Update business line sync timestamp
        $sync_stmt = $db->prepare("UPDATE lineas_negocio SET wordpress_last_sync = NOW() WHERE id = ?");
        $sync_stmt->execute([$blog_post['linea_negocio_id']]);

        echo json_encode([
            'success' => true,
            'message' => 'Entrada programada en WordPress',
            'wp_post_id' => $result['wp_post_id']
        ]);
    } else {
        // Save error
        $error_stmt = $db->prepare("\n            UPDATE blog_posts \n            SET \n                wp_sync_status = 'error',\n                wp_sync_error = ?,\n                wp_last_sync = NOW(),\n                estado = 'draft'\n            WHERE id = ?\n        ");
        $error_stmt->execute([$result['error'] ?? 'Error desconocido', $blog_post_id]);
        throw new Exception($result['message'] . ': ' . ($result['error'] ?? ''));
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
