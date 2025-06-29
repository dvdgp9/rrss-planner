<?php
require_once 'config/db.php';
require_once 'includes/WordPressAPI.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$linea_negocio_id = $_GET['linea_negocio_id'] ?? null;

if (!$linea_negocio_id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de línea de negocio requerido']);
    exit;
}

try {
    $db = getDbConnection();
    
    // Get WordPress configuration for this business line
    $stmt = $db->prepare("
        SELECT wordpress_url, wordpress_username, wordpress_app_password, wordpress_enabled 
        FROM lineas_negocio 
        WHERE id = ? AND wordpress_enabled = 1
    ");
    $stmt->execute([$linea_negocio_id]);
    $config = $stmt->fetch();
    
    if (!$config) {
        echo json_encode([
            'error' => 'WordPress no configurado o deshabilitado para esta línea de negocio',
            'categories' => [],
            'tags' => []
        ]);
        exit;
    }
    
    // Initialize WordPress API
    $wp_api = new WordPressAPI(
        $config['wordpress_url'],
        $config['wordpress_username'],
        $config['wordpress_app_password']
    );
    
    // Get categories and tags
    $categories_response = $wp_api->getCategories();
    $tags_response = $wp_api->getTags();
    
    echo json_encode([
        'success' => true,
        'categories' => $categories_response['categories'] ?? [],
        'tags' => $tags_response['tags'] ?? [],
        'categories_error' => !$categories_response['success'] ? $categories_response['error'] : null,
        'tags_error' => !$tags_response['success'] ? $tags_response['error'] : null
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error del servidor: ' . $e->getMessage(),
        'categories' => [],
        'tags' => []
    ]);
}
?> 