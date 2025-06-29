<?php
require_once 'includes/functions.php';
require_authentication(); // Solo usuarios logueados pueden generar enlaces

header('Content-Type: application/json');

// Verificar que se reciba el ID de la publicación
if (!isset($_POST['publicacion_id']) || !filter_var($_POST['publicacion_id'], FILTER_VALIDATE_INT)) {
    echo json_encode(['success' => false, 'message' => 'ID de publicación inválido.']);
    exit;
}

$publicacionId = (int)$_POST['publicacion_id'];
$token = generate_share_token(); // Reutilizamos la función de generación

// Guardar el token en la nueva tabla
try {
    $db = getDbConnection();
    $stmt = $db->prepare("INSERT INTO publication_share_tokens (token, publicacion_id) VALUES (?, ?)");
    $stmt->execute([$token, $publicacionId]);
    
    // Construir la URL completa para la vista individual
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $baseUrl = $protocol . "://" . $host;
    $path = rtrim(dirname($_SERVER['PHP_SELF']), '/'); 
    $shareUrl = $baseUrl . $path . "/share_single_pub.php?token=" . $token; // Apunta a la nueva página

    echo json_encode(['success' => true, 'share_url' => $shareUrl]);

} catch (PDOException $e) {
    error_log("Error saving single publication share token: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al generar el enlace para compartir. Inténtalo de nuevo.']);
}

exit;
?> 