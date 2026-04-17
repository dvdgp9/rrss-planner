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
$nombre = trim($_POST['nombre'] ?? '');
$slug = trim($_POST['slug'] ?? '');
$redes = isset($_POST['redes_sociales']) && is_array($_POST['redes_sociales'])
    ? array_values(array_unique(array_map('intval', $_POST['redes_sociales'])))
    : [];

if ($id <= 0) {
    $response['message'] = 'ID de línea no válido.';
    echo json_encode($response);
    exit;
}
if (empty($nombre)) {
    $response['message'] = 'El nombre de la línea de negocio es obligatorio.';
    echo json_encode($response);
    exit;
}
if (empty($slug)) {
    $response['message'] = 'El slug es obligatorio.';
    echo json_encode($response);
    exit;
}
if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
    $response['message'] = 'El slug solo puede contener minúsculas, números y guiones.';
    echo json_encode($response);
    exit;
}
if (empty($redes)) {
    $response['message'] = 'Debes seleccionar al menos una red social.';
    echo json_encode($response);
    exit;
}

// Logo es opcional en edición
$newLogoFilename = null;
$newLogoDestino = null;
$hasNewLogo = isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] !== UPLOAD_ERR_NO_FILE;
if ($hasNewLogo) {
    if ($_FILES['logo_file']['error'] !== UPLOAD_ERR_OK) {
        $response['message'] = 'Error al subir el logo (código ' . (int)$_FILES['logo_file']['error'] . ').';
        echo json_encode($response);
        exit;
    }
    $allowedExt = ['png', 'jpg', 'jpeg', 'webp', 'svg'];
    $origName = $_FILES['logo_file']['name'];
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        $response['message'] = 'Formato de logo no permitido. Usa PNG, JPG, WEBP o SVG.';
        echo json_encode($response);
        exit;
    }
    if ($_FILES['logo_file']['size'] > 5 * 1024 * 1024) {
        $response['message'] = 'El logo supera el tamaño máximo permitido (5 MB).';
        echo json_encode($response);
        exit;
    }
    $logos_dir = __DIR__ . '/assets/images/logos/';
    if (!is_dir($logos_dir)) {
        if (!mkdir($logos_dir, 0775, true) && !is_dir($logos_dir)) {
            $response['message'] = 'No se pudo crear el directorio de logos.';
            echo json_encode($response);
            exit;
        }
    }
    $newLogoFilename = $slug . '-' . time() . '.' . $ext;
    $newLogoDestino = $logos_dir . $newLogoFilename;
}

try {
    $db = getDbConnection();

    // Verificar que la línea existe
    $stmt = $db->prepare("SELECT * FROM lineas_negocio WHERE id = ?");
    $stmt->execute([$id]);
    $lineaActual = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$lineaActual) {
        $response['message'] = 'La línea de negocio no existe.';
        echo json_encode($response);
        exit;
    }

    // Comprobar slug único (excluyendo la propia línea)
    $stmt = $db->prepare("SELECT id FROM lineas_negocio WHERE slug = ? AND id <> ?");
    $stmt->execute([$slug, $id]);
    if ($stmt->fetch()) {
        $response['message'] = 'El slug proporcionado ya existe en otra línea. Elige uno diferente.';
        echo json_encode($response);
        exit;
    }

    // Validar redes
    $placeholders = implode(',', array_fill(0, count($redes), '?'));
    $stmt = $db->prepare("SELECT id FROM redes_sociales WHERE id IN ($placeholders)");
    $stmt->execute($redes);
    $redesValidas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (count($redesValidas) !== count($redes)) {
        $response['message'] = 'Alguna de las redes sociales seleccionadas no es válida.';
        echo json_encode($response);
        exit;
    }

    // Mover logo si hay nuevo
    if ($hasNewLogo) {
        if (!move_uploaded_file($_FILES['logo_file']['tmp_name'], $newLogoDestino)) {
            $response['message'] = 'No se pudo guardar el nuevo logo.';
            echo json_encode($response);
            exit;
        }
    }

    $db->beginTransaction();
    try {
        if ($hasNewLogo) {
            $stmt = $db->prepare("UPDATE lineas_negocio SET nombre = ?, slug = ?, logo_filename = ? WHERE id = ?");
            $stmt->execute([$nombre, $slug, $newLogoFilename, $id]);
        } else {
            $stmt = $db->prepare("UPDATE lineas_negocio SET nombre = ?, slug = ? WHERE id = ?");
            $stmt->execute([$nombre, $slug, $id]);
        }

        // Sincronizar redes asociadas
        $stmt = $db->prepare("SELECT red_social_id FROM linea_negocio_red_social WHERE linea_negocio_id = ?");
        $stmt->execute([$id]);
        $redesActuales = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

        $aAgregar = array_diff($redes, $redesActuales);
        $aQuitar = array_diff($redesActuales, $redes);

        if (!empty($aAgregar)) {
            $stmt = $db->prepare("INSERT INTO linea_negocio_red_social (linea_negocio_id, red_social_id) VALUES (?, ?)");
            foreach ($aAgregar as $rid) {
                $stmt->execute([$id, $rid]);
            }
        }
        if (!empty($aQuitar)) {
            $phs = implode(',', array_fill(0, count($aQuitar), '?'));
            $stmt = $db->prepare("DELETE FROM linea_negocio_red_social WHERE linea_negocio_id = ? AND red_social_id IN ($phs)");
            $stmt->execute(array_merge([$id], array_values($aQuitar)));
        }

        $db->commit();

        // Borrar logo antiguo solo tras commit exitoso
        if ($hasNewLogo && !empty($lineaActual['logo_filename'])) {
            $oldPath = __DIR__ . '/assets/images/logos/' . $lineaActual['logo_filename'];
            if (is_file($oldPath) && $lineaActual['logo_filename'] !== $newLogoFilename) {
                @unlink($oldPath);
            }
        }

        $response['success'] = true;
        $response['message'] = 'Línea de negocio actualizada correctamente.';
    } catch (PDOException $e) {
        $db->rollBack();
        if ($hasNewLogo && $newLogoDestino && file_exists($newLogoDestino)) {
            @unlink($newLogoDestino);
        }
        throw $e;
    }
} catch (PDOException $e) {
    $response['message'] = 'Error de base de datos: ' . $e->getMessage();
}

echo json_encode($response);
