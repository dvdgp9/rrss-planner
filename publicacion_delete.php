<?php
require_once 'includes/functions.php';
require_authentication();

// Verificar si tenemos los parámetros necesarios
if (!isset($_GET['id']) || !isset($_GET['linea'])) {
    header("Location: index.php");
    exit;
}

$publicacionId = $_GET['id'];
$lineaId = $_GET['linea'];

// Determinar la página de retorno
$paginaRetorno = '';
switch($lineaId) {
    case 1: $paginaRetorno = 'ebone.php'; break;
    case 2: $paginaRetorno = 'cubofit.php'; break;
    case 3: $paginaRetorno = 'uniges.php'; break;
    case 4: $paginaRetorno = 'teia.php'; break;
    default: $paginaRetorno = 'index.php'; break;
}

// Verificar que la publicación existe y pertenece a esta línea
$db = getDbConnection();
$stmt = $db->prepare("SELECT * FROM publicaciones WHERE id = ? AND linea_negocio_id = ?");
$stmt->execute([$publicacionId, $lineaId]);
$publicacion = $stmt->fetch();

if (!$publicacion) {
    header("Location: " . $paginaRetorno);
    exit;
}

try {
    $db->beginTransaction();
    
    // Eliminar relaciones con redes sociales primero
    $stmt = $db->prepare("DELETE FROM publicacion_red_social WHERE publicacion_id = ?");
    $stmt->execute([$publicacionId]);
    
    // Eliminar la publicación
    $stmt = $db->prepare("DELETE FROM publicaciones WHERE id = ?");
    $stmt->execute([$publicacionId]);
    
    $db->commit();
    
    // Eliminar la imagen del servidor si existe
    if (!empty($publicacion['imagen_url']) && file_exists($publicacion['imagen_url'])) {
        unlink($publicacion['imagen_url']);
    }
} catch (Exception $e) {
    $db->rollBack();
}

// Redirigir de vuelta al listado de la línea correspondiente
header("Location: " . $paginaRetorno);
exit; 