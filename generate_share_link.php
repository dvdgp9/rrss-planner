<?php
require_once 'includes/functions.php';
require_authentication(); // Solo usuarios logueados pueden generar enlaces

header('Content-Type: application/json');

// Verificar que se reciba el ID de la línea de negocio
if (!isset($_POST['linea_id']) || !filter_var($_POST['linea_id'], FILTER_VALIDATE_INT)) {
    echo json_encode(['success' => false, 'message' => 'ID de línea de negocio inválido.']);
    exit;
}

$lineaId = (int)$_POST['linea_id'];
$contentType = $_POST['content_type'] ?? 'social';
// Validar tipo de contenido
if (!in_array($contentType, ['social', 'blog'])) {
    $contentType = 'social'; // Fallback seguro
}
$token = generate_share_token();

// Guardar el token en la base de datos
try {
    $db = getDbConnection();
    $stmt = $db->prepare("INSERT INTO share_tokens (token, linea_negocio_id) VALUES (?, ?)");
    $stmt->execute([$token, $lineaId]);
    
    // Construir la URL completa
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $baseUrl = $protocol . "://" . $host;
    // Obtener la ruta base del script actual para construir la URL correctamente
    $path = rtrim(dirname($_SERVER['PHP_SELF']), '/'); 
    $shareUrl = $baseUrl . $path . "/share_view.php?token=" . $token . "&type=" . urlencode($contentType);

    echo json_encode(['success' => true, 'share_url' => $shareUrl]);

} catch (PDOException $e) {
    error_log("Error saving share token: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al generar el enlace para compartir. Inténtalo de nuevo.']);
}

exit;
?> 