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
$estado = filter_input(INPUT_POST, 'estado', FILTER_SANITIZE_STRING);
$linea = filter_input(INPUT_POST, 'linea', FILTER_VALIDATE_INT);
$type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);

if (!$id || !$estado || !$linea || $type !== 'blog') {
    echo json_encode(['success' => false, 'message' => 'Datos de entrada inválidos']);
    exit;
}

// Validar estados permitidos para blog posts
$estados_permitidos = ['draft', 'scheduled', 'publish'];
if (!in_array($estado, $estados_permitidos)) {
    echo json_encode(['success' => false, 'message' => 'Estado no válido']);
    exit;
}

try {
    $db = getDbConnection();
    
    // Verificar que el blog post existe y pertenece a la línea de negocio
    $stmt_check = $db->prepare("SELECT id FROM blog_posts WHERE id = ? AND linea_negocio_id = ?");
    $stmt_check->execute([$id, $linea]);
    
    if (!$stmt_check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Blog post no encontrado o sin permisos']);
        exit;
    }
    
    // Actualizar el estado
    $stmt_update = $db->prepare("UPDATE blog_posts SET estado = ? WHERE id = ?");
    $result = $stmt_update->execute([$estado, $id]);
    
    if ($result) {
        // Mapear estados para mostrar al usuario
        $estados_display = [
            'draft' => 'Borrador',
            'scheduled' => 'Programado',
            'publish' => 'Publicado'
        ];
        
        echo json_encode([
            'success' => true,
            'message' => 'Estado actualizado correctamente',
            'estadoCapitalizado' => $estados_display[$estado] ?? $estado
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar el estado']);
    }
    
} catch (PDOException $e) {
    error_log("Error en blog_update_estado.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error de base de datos']);
}
?> 