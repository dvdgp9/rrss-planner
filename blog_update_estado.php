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
    // También obtener el estado actual y la imagen para procesamiento posterior
    $stmt_check = $db->prepare("SELECT id, estado, imagen_destacada FROM blog_posts WHERE id = ? AND linea_negocio_id = ?");
    $stmt_check->execute([$id, $linea]);
    
    $blogPost = $stmt_check->fetch();
    if (!$blogPost) {
        echo json_encode(['success' => false, 'message' => 'Blog post no encontrado o sin permisos']);
        exit;
    }
    
    $estadoAnterior = $blogPost['estado'];
    $imagenActual = $blogPost['imagen_destacada'];
    
    // Actualizar el estado
    $stmt_update = $db->prepare("UPDATE blog_posts SET estado = ? WHERE id = ?");
    $result = $stmt_update->execute([$estado, $id]);
    
    if ($result) {
        // OPTIMIZACIÓN DE ALMACENAMIENTO: Borrar imagen si cambia a "publish"
        if ($estado === 'publish' && $estadoAnterior !== 'publish' && !empty($imagenActual)) {
            $logContext = "Blog Post ID: {$id}, Linea: {$linea}";
            $imageDeleted = processImageDeletionOnPublish(
                $db, 
                'blog_posts', 
                'imagen_destacada', 
                $id, 
                $imagenActual, 
                $logContext
            );
            
            // Log el resultado pero no fallar la operación si no se pudo borrar la imagen
            if (!$imageDeleted) {
                error_log("IMAGE_DELETION_WARNING: Could not delete image for published blog post - {$logContext}");
            }
        }
        
        // Mapear estados para mostrar al usuario
        $estados_display = [
            'draft' => 'Borrador',
            'scheduled' => 'Programado',
            'publish' => 'Publicado'
        ];
        
        $response = [
            'success' => true,
            'message' => 'Estado actualizado correctamente',
            'estadoCapitalizado' => $estados_display[$estado] ?? $estado
        ];
        
        // Añadir información sobre borrado de imagen si ocurrió
        if ($estado === 'publish' && $estadoAnterior !== 'publish' && !empty($imagenActual)) {
            $response['imageArchived'] = true;
            $response['message'] = 'Estado actualizado e imagen archivada para optimizar almacenamiento';
        }
        
        echo json_encode($response);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar el estado']);
    }
    
} catch (PDOException $e) {
    error_log("Error en blog_update_estado.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error de base de datos']);
}
?> 