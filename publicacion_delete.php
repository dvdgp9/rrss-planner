<?php
require_once 'includes/functions.php';
require_authentication();

// Verificar si tenemos los parámetros necesarios
if (!isset($_GET['id']) || (!isset($_GET['slug_redirect']) && !isset($_GET['linea'])) ) { // linea is for old compatibility if needed
    $_SESSION['feedback_message'] = ['tipo' => 'error', 'mensaje' => 'Parámetros insuficientes para eliminar.'];
    header("Location: index.php");
    exit;
}

$publicacionId = intval($_GET['id']);
$lineaIdParaVerificar = isset($_GET['linea_id']) ? intval($_GET['linea_id']) : null; // From planner.php
$slugRedirect = trim($_GET['slug_redirect'] ?? '');

// Determinar la página de retorno
$paginaRetorno = "index.php"; // Default fallback
if (!empty($slugRedirect)) {
    $paginaRetorno = "planner.php?slug=" . urlencode($slugRedirect);
} elseif (isset($_GET['linea'])) { // Fallback to old system if slug_redirect is not present but old 'linea' is
    $oldLineaId = intval($_GET['linea']);
    // This part is for backward compatibility, ideally it will be removed later
    switch($oldLineaId) {
    case 1: $paginaRetorno = 'ebone.php'; break;
    case 2: $paginaRetorno = 'cubofit.php'; break;
    case 3: $paginaRetorno = 'uniges.php'; break;
    case 4: $paginaRetorno = 'teia.php'; break;
    }
}

$db = getDbConnection();

// Verificar que la publicación existe.
// Si se proporciona lineaIdParaVerificar, también se comprueba que la publicación pertenezca a esa línea.
$sql_verify = "SELECT * FROM publicaciones WHERE id = ?";
$params_verify = [$publicacionId];

if ($lineaIdParaVerificar !== null) {
    $sql_verify .= " AND linea_negocio_id = ?";
    $params_verify[] = $lineaIdParaVerificar;
}

$stmt_verify = $db->prepare($sql_verify);
$stmt_verify->execute($params_verify);
$publicacion = $stmt_verify->fetch();

if (!$publicacion) {
    $_SESSION['feedback_message'] = ['tipo' => 'error', 'mensaje' => 'Publicación no encontrada o no pertenece a la línea especificada.'];
    header("Location: " . $paginaRetorno); // Use resolved $paginaRetorno
    exit;
}

// Si no se pasó lineaIdParaVerificar, pero se quiere obtener el slug para el redirect (en caso de que slug_redirect no viniera)
if (empty($slugRedirect) && $lineaIdParaVerificar === null) {
    $stmt_slug_fetch = $db->prepare("SELECT slug FROM lineas_negocio WHERE id = ?");
    $stmt_slug_fetch->execute([$publicacion['linea_negocio_id']]);
    $ln_data = $stmt_slug_fetch->fetch();
    if ($ln_data && !empty($ln_data['slug'])) {
        $paginaRetorno = "planner.php?slug=" . urlencode($ln_data['slug']);
    }
}


try {
    $db->beginTransaction();
    
    // Eliminar relaciones con redes sociales primero
    $stmt_del_prs = $db->prepare("DELETE FROM publicacion_red_social WHERE publicacion_id = ?");
    $stmt_del_prs->execute([$publicacionId]);

    // Eliminar feedback asociado
    $stmt_del_pf = $db->prepare("DELETE FROM publication_feedback WHERE publicacion_id = ?");
    $stmt_del_pf->execute([$publicacionId]);

    // Eliminar share tokens asociados
    $stmt_del_pst = $db->prepare("DELETE FROM publication_share_tokens WHERE publicacion_id = ?");
    $stmt_del_pst->execute([$publicacionId]);
    
    // Eliminar la publicación
    $stmt_del_pub = $db->prepare("DELETE FROM publicaciones WHERE id = ?");
    $stmt_del_pub->execute([$publicacionId]);
    
    $db->commit();
    $_SESSION['feedback_message'] = ['tipo' => 'success', 'mensaje' => 'Publicación eliminada correctamente.'];
    
    // Eliminar todas las imágenes del servidor si existen (portada + carrusel)
    $imagesToDelete = parsePublicationImages($publicacion['imagenes_json'] ?? null, $publicacion['imagen_url'] ?? null);
    foreach ($imagesToDelete as $imagePath) {
        deletePublicationImage($imagePath, "Delete publication {$publicacionId}");
    }
} catch (Exception $e) {
    $db->rollBack();
    $_SESSION['feedback_message'] = ['tipo' => 'error', 'mensaje' => 'Error al eliminar la publicación: ' . $e->getMessage()];
}

// Redirigir de vuelta
header("Location: " . $paginaRetorno);
exit; 
