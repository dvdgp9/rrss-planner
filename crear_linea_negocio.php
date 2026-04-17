<?php
require_once 'includes/functions.php';
require_authentication(); // Asegura que el usuario esté autenticado

header('Content-Type: application/json'); // Establece el tipo de contenido de la respuesta

$response = ['success' => false, 'message' => ''];

// Solo superadmins pueden crear nuevas líneas de negocio
if (!is_superadmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado: solo superadmins.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $redes = isset($_POST['redes_sociales']) && is_array($_POST['redes_sociales'])
        ? array_values(array_unique(array_map('intval', $_POST['redes_sociales'])))
        : [];

    // Validación básica
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
    if (!isset($_FILES['logo_file']) || $_FILES['logo_file']['error'] === UPLOAD_ERR_NO_FILE) {
        $response['message'] = 'El logo es obligatorio.';
        echo json_encode($response);
        exit;
    }
    if ($_FILES['logo_file']['error'] !== UPLOAD_ERR_OK) {
        $response['message'] = 'Error al subir el logo (código ' . (int)$_FILES['logo_file']['error'] . ').';
        echo json_encode($response);
        exit;
    }
    if (empty($redes)) {
        $response['message'] = 'Debes seleccionar al menos una red social.';
        echo json_encode($response);
        exit;
    }

    // Validar formato del logo
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

    // Preparar destino del logo
    $logos_dir = __DIR__ . '/assets/images/logos/';
    if (!is_dir($logos_dir)) {
        if (!mkdir($logos_dir, 0775, true) && !is_dir($logos_dir)) {
            $response['message'] = 'No se pudo crear el directorio de logos.';
            echo json_encode($response);
            exit;
        }
    }

    $logo_filename = $slug . '-' . time() . '.' . $ext;
    $destino = $logos_dir . $logo_filename;

    try {
        $db = getDbConnection();

        // 1. Verificar si el slug ya existe
        $stmt_check_slug = $db->prepare("SELECT id FROM lineas_negocio WHERE slug = ?");
        $stmt_check_slug->execute([$slug]);
        if ($stmt_check_slug->fetch()) {
            $response['message'] = 'El slug proporcionado ya existe. Por favor, elige uno diferente.';
            echo json_encode($response);
            exit;
        }

        // 2. Validar que las redes existen
        if (!empty($redes)) {
            $placeholders = implode(',', array_fill(0, count($redes), '?'));
            $stmt_redes = $db->prepare("SELECT id FROM redes_sociales WHERE id IN ($placeholders)");
            $stmt_redes->execute($redes);
            $redesValidas = $stmt_redes->fetchAll(PDO::FETCH_COLUMN);
            if (count($redesValidas) !== count($redes)) {
                $response['message'] = 'Alguna de las redes sociales seleccionadas no es válida.';
                echo json_encode($response);
                exit;
            }
        }

        // 3. Mover el archivo del logo
        if (!move_uploaded_file($_FILES['logo_file']['tmp_name'], $destino)) {
            $response['message'] = 'No se pudo guardar el archivo del logo.';
            echo json_encode($response);
            exit;
        }

        // 4. Insertar línea + asociaciones en una transacción
        $db->beginTransaction();
        try {
            $stmt_insert = $db->prepare("INSERT INTO lineas_negocio (nombre, logo_filename, slug) VALUES (?, ?, ?)");
            $stmt_insert->execute([$nombre, $logo_filename, $slug]);
            $newLineaId = (int)$db->lastInsertId();

            if (!empty($redes)) {
                $stmt_link = $db->prepare("INSERT INTO linea_negocio_red_social (linea_negocio_id, red_social_id) VALUES (?, ?)");
                foreach ($redes as $redId) {
                    $stmt_link->execute([$newLineaId, $redId]);
                }
            }

            $db->commit();
            $response['success'] = true;
            $response['message'] = 'Línea de negocio creada exitosamente.';
            $response['new_linea_id'] = $newLineaId;
        } catch (PDOException $e) {
            $db->rollBack();
            // Limpiar logo subido si falló el insert
            if (file_exists($destino)) {
                @unlink($destino);
            }
            throw $e;
        }
    } catch (PDOException $e) {
        // error_log("Error en crear_linea_negocio.php: " . $e->getMessage());
        $response['message'] = 'Error de base de datos: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Método de solicitud no válido. Se esperaba POST.';
}

echo json_encode($response);
?> 