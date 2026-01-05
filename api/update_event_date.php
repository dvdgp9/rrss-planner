<?php
/**
 * API Endpoint: Update Event Date
 * Actualiza la fecha de una publicación (drag & drop del calendario)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, PUT');
header('Access-Control-Allow-Headers: Content-Type');

require_once dirname(__DIR__) . '/includes/functions.php';

// Verificar autenticación
if (!is_authenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// Leer datos JSON
$input = json_decode(file_get_contents('php://input'), true);

$event_id = $input['event_id'] ?? null;
$new_date = $input['new_date'] ?? null;
$type = $input['type'] ?? 'social';

if (!$event_id || !$new_date) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'event_id y new_date son requeridos']);
    exit;
}

// Validar formato de fecha
$date = DateTime::createFromFormat('Y-m-d', $new_date);
if (!$date) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Formato de fecha inválido']);
    exit;
}

try {
    $db = getDbConnection();
    
    if ($type === 'social') {
        $stmt = $db->prepare("UPDATE publicaciones SET fecha_programada = ? WHERE id = ?");
        $stmt->execute([$new_date, $event_id]);
    } elseif ($type === 'blog') {
        $stmt = $db->prepare("UPDATE blog_posts SET fecha_publicacion = ? WHERE id = ?");
        $stmt->execute([$new_date, $event_id]);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Tipo no válido']);
        exit;
    }
    
    // Si la ejecución no lanzó excepción, consideramos éxito
    // rowCount() puede ser 0 si se arrastró a la misma fecha (no hay cambios)
    echo json_encode([
        'success' => true, 
        'message' => 'Fecha procesada correctamente',
        'new_date' => $new_date
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error de base de datos: ' . $e->getMessage()]);
}
