<?php
require_once 'includes/functions.php';
require_authentication();

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!is_superadmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado: solo superadmins.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método de solicitud no válido. Se esperaba POST.';
    echo json_encode($response);
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$confirmSlug = trim($_POST['confirm_slug'] ?? '');

if ($id <= 0) {
    $response['message'] = 'ID de línea no válido.';
    echo json_encode($response);
    exit;
}

try {
    $db = getDbConnection();

    $stmt = $db->prepare("SELECT * FROM lineas_negocio WHERE id = ?");
    $stmt->execute([$id]);
    $linea = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$linea) {
        $response['message'] = 'La línea de negocio no existe.';
        echo json_encode($response);
        exit;
    }

    // Confirmación: el usuario debe escribir el slug exacto
    if ($confirmSlug === '' || $confirmSlug !== $linea['slug']) {
        $response['message'] = 'Debes escribir el slug exacto ("' . htmlspecialchars($linea['slug']) . '") para confirmar la eliminación.';
        echo json_encode($response);
        exit;
    }

    // Eliminar la línea (FKs con ON DELETE CASCADE se encargan de publicaciones, blog_posts, tokens, etc.)
    $stmt = $db->prepare("DELETE FROM lineas_negocio WHERE id = ?");
    $stmt->execute([$id]);

    // Borrar el archivo del logo del disco
    if (!empty($linea['logo_filename'])) {
        $logoPath = __DIR__ . '/assets/images/logos/' . $linea['logo_filename'];
        if (is_file($logoPath)) {
            @unlink($logoPath);
        }
    }

    $response['success'] = true;
    $response['message'] = 'Línea de negocio eliminada correctamente.';
} catch (PDOException $e) {
    $response['message'] = 'Error de base de datos: ' . $e->getMessage();
}

echo json_encode($response);
