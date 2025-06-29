<?php
require_once 'includes/functions.php';
require_authentication();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Validar datos de entrada
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$slug_redirect = filter_input(INPUT_POST, 'slug_redirect', FILTER_SANITIZE_STRING);

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID de blog post inválido']);
    exit;
}

try {
    $db = getDbConnection();
    
    // Obtener información del blog post antes de eliminarlo
    $stmt_get = $db->prepare("SELECT * FROM blog_posts WHERE id = ?");
    $stmt_get->execute([$id]);
    $blog_post = $stmt_get->fetch();
    
    if (!$blog_post) {
        echo json_encode(['success' => false, 'message' => 'Blog post no encontrado']);
        exit;
    }
    
    // Iniciar transacción
    $db->beginTransaction();
    
    try {
        // Eliminar relaciones con categorías
        $stmt_delete_cat = $db->prepare("DELETE FROM blog_post_categoria WHERE blog_post_id = ?");
        $stmt_delete_cat->execute([$id]);
        
        // Eliminar relaciones con tags
        $stmt_delete_tag = $db->prepare("DELETE FROM blog_post_tag WHERE blog_post_id = ?");
        $stmt_delete_tag->execute([$id]);
        
        // Eliminar el blog post
        $stmt_delete = $db->prepare("DELETE FROM blog_posts WHERE id = ?");
        $result = $stmt_delete->execute([$id]);
        
        if ($result) {
            // Eliminar imagen destacada si existe
            if (!empty($blog_post['imagen_destacada']) && file_exists($blog_post['imagen_destacada'])) {
                unlink($blog_post['imagen_destacada']);
            }
            
            $db->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Blog post eliminado correctamente',
                'redirect_url' => $slug_redirect ? "planner.php?slug=" . urlencode($slug_redirect) . "&type=blog" : "index.php"
            ]);
        } else {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Error al eliminar el blog post']);
        }
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    error_log("Error en blog_delete.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error de base de datos']);
}
?> 