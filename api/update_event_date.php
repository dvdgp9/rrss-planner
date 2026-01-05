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
    } elseif ($type === 'blog') {
        $stmt = $db->prepare("UPDATE blog_posts SET fecha_publicacion = ? WHERE id = ?");
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Tipo no válido']);
        exit;
    }
    
    // Solo un execute con los parámetros correctos
    if ($stmt->execute([$new_date, $event_id])) {
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true, 
                'message' => 'Fecha actualizada correctamente',
                'new_date' => $new_date
            ]);
        } else {
            // Verificar si el registro existe para distinguir entre "no encontrado" y "sin cambios"
            if ($type === 'social') {
                $check = $db->prepare("SELECT id FROM publicaciones WHERE id = ?");
            } else {
                $check = $db->prepare("SELECT id FROM blog_posts WHERE id = ?");
            }
            $check->execute([$event_id]);
            
            if ($check->fetch()) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Sin cambios (la fecha ya era la misma)',
                    'new_date' => $new_date
                ]);
            } else {
                echo json_encode([
                    'success' => false, 
                    'error' => "No se encontró el evento con ID $event_id"
                ]);
            }
        }
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error al ejecutar la actualización']);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error de base de datos: ' . $e->getMessage()]);
}
