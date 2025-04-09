<?php
require_once 'includes/functions.php';
// No requiere autenticación (se llama desde share_view.php)
// Podríamos añadir autenticación si se llama desde el backend interno en el futuro

header('Content-Type: application/json');

$publicacionId = $_GET['publicacion_id'] ?? null;
// $shareToken = $_GET['share_token'] ?? null; // Podríamos necesitarlo si filtramos por token

if (!filter_var($publicacionId, FILTER_VALIDATE_INT)) {
    echo json_encode(['success' => false, 'feedback' => [], 'message' => 'ID de publicación inválido.']);
    exit;
}

// Obtener feedback de la base de datos
try {
    $db = getDbConnection();
    // Ordenamos por fecha para mostrar los más recientes al final (o al principio si cambiamos a DESC)
    $stmt = $db->prepare("
        SELECT id, feedback_text, created_at 
        FROM publication_feedback 
        WHERE publicacion_id = ? 
        ORDER BY created_at ASC 
    ");
    $stmt->execute([$publicacionId]);
    $feedbackList = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Sanitizar salida
    foreach ($feedbackList as &$item) {
        $item['feedback_text'] = nl2br(htmlspecialchars($item['feedback_text']));
        $item['created_at'] = date('d/m/Y H:i', strtotime($item['created_at'])); // Formatear fecha
    }

    echo json_encode(['success' => true, 'feedback' => $feedbackList]);

} catch (PDOException $e) {
    error_log("Error getting feedback: " . $e->getMessage());
    echo json_encode(['success' => false, 'feedback' => [], 'message' => 'Error al obtener el feedback.']);
}

exit;
?> 