<?php
require_once 'includes/functions.php';
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
    // También obtener el estado actual y las imágenes para procesamiento posterior
    $stmt = $db->prepare("SELECT id, estado, imagen_url, imagenes_json FROM publicaciones WHERE id = ? AND linea_negocio_id = ?");
    $stmt->execute([$publicacionId, $lineaId]);
    
    $publicacion = $stmt->fetch();
    if (!$publicacion) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Publicación no encontrada']);
        exit;
    }
    
    $estadoAnterior = $publicacion['estado'];
    $imagenActual = $publicacion['imagen_url'];
    $imagenesActuales = parsePublicationImages($publicacion['imagenes_json'] ?? null, $imagenActual);
    
    // Actualizar el estado
    $stmt = $db->prepare("UPDATE publicaciones SET estado = ? WHERE id = ?");
    $stmt->execute([$nuevoEstado, $publicacionId]);
    
    $imagesArchived = false;

    // OPTIMIZACIÓN DE ALMACENAMIENTO: Borrar imágenes si cambia a "publicado"
    if ($nuevoEstado === 'publicado' && $estadoAnterior !== 'publicado' && !empty($imagenesActuales)) {
        $logContext = "Social Publication ID: {$publicacionId}, Linea: {$lineaId}";
        $allDeleted = true;

        foreach ($imagenesActuales as $imagePath) {
            if (!deletePublicationImage($imagePath, $logContext)) {
                $allDeleted = false;
            }
        }

        if ($allDeleted) {
            $stmtCleanup = $db->prepare("UPDATE publicaciones SET imagen_url = NULL, imagenes_json = NULL, thumbnail_url = NULL WHERE id = ?");
            $stmtCleanup->execute([$publicacionId]);
            $imagesArchived = true;
        } else {
            error_log("IMAGE_DELETION_WARNING: Could not delete all images for published social post - {$logContext}");
        }
    }
    
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
    $response = [
        'success' => true, 
        'message' => 'Estado actualizado correctamente',
        'estado' => $nuevoEstado,
        'estadoCapitalizado' => ucfirst($nuevoEstado),
        'badgeClass' => $badgeClass
    ];
    
    // Añadir información sobre borrado de imagen si ocurrió
    if ($imagesArchived) {
        $response['imageArchived'] = true;
        $response['message'] = 'Estado actualizado e imágenes archivadas para optimizar almacenamiento';
    }
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
} 
