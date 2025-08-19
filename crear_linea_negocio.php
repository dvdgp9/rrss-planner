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
    $logo_filename = trim($_POST['logo_filename'] ?? '');
    $slug = trim($_POST['slug'] ?? '');

    // Validación básica
    if (empty($nombre)) {
        $response['message'] = 'El nombre de la línea de negocio es obligatorio.';
        echo json_encode($response);
        exit;
    }
    if (empty($logo_filename)) {
        $response['message'] = 'El archivo del logo es obligatorio.';
        echo json_encode($response);
        exit;
    }
    if (empty($slug)) {
        $response['message'] = 'El slug es obligatorio.';
        echo json_encode($response);
        exit;
    }

    // TODO: Podríamos añadir una validación más robusta para el formato del slug (ej: solo minúsculas, números y guiones)
    // TODO: Podríamos verificar si el logo_filename existe en assets/images/logos/, aunque esto es más una ayuda que una validación estricta aquí.

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

        // 2. Insertar la nueva línea de negocio
        $stmt_insert = $db->prepare("INSERT INTO lineas_negocio (nombre, logo_filename, slug) VALUES (?, ?, ?)");
        
        if ($stmt_insert->execute([$nombre, $logo_filename, $slug])) {
            $response['success'] = true;
            $response['message'] = 'Línea de negocio creada exitosamente.';
            // Podríamos devolver el ID de la nueva línea si fuera necesario
            // $response['new_linea_id'] = $db->lastInsertId(); 
        } else {
            $response['message'] = 'Error al crear la línea de negocio en la base de datos.';
        }
    } catch (PDOException $e) {
        // Loguear el error real en un archivo de logs en un sistema de producción
        // error_log("Error en crear_linea_negocio.php: " . $e->getMessage());
        $response['message'] = 'Error de base de datos: ' . $e->getMessage(); // O un mensaje más genérico para el usuario
    }
} else {
    $response['message'] = 'Método de solicitud no válido. Se esperaba POST.';
}

echo json_encode($response);
?> 