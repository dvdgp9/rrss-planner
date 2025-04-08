<?php
require_once '../includes/functions.php';
require_authentication();

header('Content-Type: application/json');

// Verificar que sea una solicitud AJAX con POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Verificar que tengamos los parámetros necesarios
if (!isset($_POST['id']) || !isset($_POST['estado']) || !isset($_POST['linea'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Faltan parámetros requeridos']);
    exit;
}

$publicacionId = $_POST['id'];
$nuevoEstado = $_POST['estado'];
$lineaId = $_POST['linea'];

// Validar que el estado sea uno de los valores permitidos
$estadosPermitidos = ['borrador', 'programado', 'publicado'];
if (!in_array($nuevoEstado, $estadosPermitidos)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Estado no válido']);
    exit;
}

try {
    $db = getDbConnection();
    
    // Verificar que la publicación existe y pertenece a esta línea
    $stmt = $db->prepare("SELECT id FROM publicaciones WHERE id = ? AND linea_negocio_id = ?");
    $stmt->execute([$publicacionId, $lineaId]);
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Publicación no encontrada']);
        exit;
    }
    
    // Actualizar el estado
    $stmt = $db->prepare("UPDATE publicaciones SET estado = ? WHERE id = ?");
    $stmt->execute([$nuevoEstado, $publicacionId]);
    
    // Determinar la clase CSS para el estado
    $badgeClass = '';
    switch ($nuevoEstado) {
        case 'borrador':
            $badgeClass = 'badge-draft';
            break;
        case 'programado':
            $badgeClass = 'badge-scheduled';
            break;
        case 'publicado':
            $badgeClass = 'badge-published';
            break;
    }
    
    // Devolver respuesta exitosa con información para actualizar la UI
    echo json_encode([
        'success' => true, 
        'message' => 'Estado actualizado correctamente',
        'estado' => $nuevoEstado,
        'estadoCapitalizado' => ucfirst($nuevoEstado),
        'badgeClass' => $badgeClass
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
} 