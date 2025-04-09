<?php
require_once 'includes/functions.php';
// No requiere autenticación (se llama desde share_view.php)

header('Content-Type: application/json');

// Validar entrada
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

$publicacionId = $_POST['publicacion_id'] ?? null;
$shareToken = $_POST['share_token'] ?? null;
$feedbackText = trim($_POST['feedback_text'] ?? '');

if (!filter_var($publicacionId, FILTER_VALIDATE_INT) || empty($shareToken) || empty($feedbackText)) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos o inválidos.']);
    exit;
}

// Podríamos añadir una validación extra para asegurar que el token existe
// $lineaIdFromToken = get_linea_id_from_token($shareToken); 
// if (!$lineaIdFromToken) { ... error ... }
// Y quizás incluso verificar que la publicacionId pertenece a esa lineaId...
// Pero por simplicidad inicial, omitimos estas validaciones extra.

// Guardar en la base de datos
try {
    $db = getDbConnection();
    $stmt = $db->prepare("
        INSERT INTO publication_feedback (publicacion_id, share_token, feedback_text) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$publicacionId, $shareToken, $feedbackText]);

    // Devolver el comentario guardado (útil para actualizar la UI)
    $newFeedbackId = $db->lastInsertId();
    echo json_encode([
        'success' => true, 
        'message' => 'Feedback enviado.', 
        'feedback' => [
            'id' => $newFeedbackId,
            'feedback_text' => htmlspecialchars($feedbackText), // Devolver texto saneado
            'created_at' => date('Y-m-d H:i:s') // Fecha actual aprox.
        ]
    ]);

} catch (PDOException $e) {
    error_log("Error saving feedback: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al guardar el feedback. Inténtalo de nuevo.']);
}

exit;
?> 