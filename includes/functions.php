<?php
require_once __DIR__ . '/../config/db.php';

// Función auxiliar para depuración
function debug($data) {
    echo '<pre style="background-color: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 4px; overflow: auto;">';
    print_r($data);
    echo '</pre>';
}

// Obtener todas las líneas de negocio
function getAllLineasNegocio() {
    $db = getDbConnection();
    try {
        $stmt = $db->query("SELECT * FROM lineas_negocio ORDER BY nombre");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        echo '<div style="background-color: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 4px;">';
        echo '<strong>Error al obtener líneas de negocio:</strong><br>';
        echo $e->getMessage();
        echo '</div>';
        return [];
    }
}

// Obtener redes sociales asociadas a una línea de negocio
function getRedesByLinea($lineaId) {
    $db = getDbConnection();
    try {
        $stmt = $db->prepare("
            SELECT r.* 
            FROM redes_sociales r
            JOIN linea_negocio_red_social lnrs ON r.id = lnrs.red_social_id
            WHERE lnrs.linea_negocio_id = ?
            ORDER BY r.nombre
        ");
        $stmt->execute([$lineaId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        echo '<div style="background-color: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 4px;">';
        echo '<strong>Error al obtener redes sociales:</strong><br>';
        echo $e->getMessage();
        echo '</div>';
        return [];
    }
}

// Obtener publicaciones por línea de negocio y estado (opcional)
function getPublicaciones($lineaId, $estado = null) {
    $db = getDbConnection();
    $params = [$lineaId];
    $sql = "
        SELECT p.*, ln.nombre as linea_nombre 
        FROM publicaciones p
        JOIN lineas_negocio ln ON p.linea_negocio_id = ln.id
        WHERE p.linea_negocio_id = ?
    ";
    
    if ($estado) {
        $sql .= " AND p.estado = ?";
        $params[] = $estado;
    }
    
    $sql .= " ORDER BY p.fecha_programada DESC, p.id DESC";
    
    // Mostrar consulta para depuración
    echo '<div style="background-color: #e8f4f8; color: #0c5460; padding: 15px; margin: 10px 0; border-radius: 4px; font-family: monospace;">';
    echo '<strong>SQL Debug:</strong><br>';
    echo $sql . '<br>';
    echo 'Params: ';
    print_r($params);
    echo '</div>';
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetchAll();
        
        // Si no hay resultados, verificar si la línea existe realmente
        if (empty($result)) {
            $checkStmt = $db->prepare("SELECT * FROM lineas_negocio WHERE id = ?");
            $checkStmt->execute([$lineaId]);
            $lineaExists = $checkStmt->fetch();
            
            if (!$lineaExists) {
                echo '<div style="background-color: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 4px;">';
                echo '<strong>Advertencia:</strong> La línea de negocio con ID ' . $lineaId . ' no existe.';
                echo '</div>';
            } else {
                echo '<div style="background-color: #fff3cd; color: #856404; padding: 15px; margin: 10px 0; border-radius: 4px;">';
                echo '<strong>Nota:</strong> No hay publicaciones para esta línea de negocio' . ($estado ? ' con estado "' . $estado . '"' : '') . '.';
                echo '</div>';
            }
        }
        
        return $result;
    } catch (PDOException $e) {
        echo '<div style="background-color: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 4px;">';
        echo '<strong>Error al obtener publicaciones:</strong><br>';
        echo $e->getMessage();
        echo '</div>';
        return [];
    }
}

// Obtener redes sociales seleccionadas para una publicación
function getRedesPublicacion($publicacionId) {
    $db = getDbConnection();
    try {
        $stmt = $db->prepare("
            SELECT r.id
            FROM redes_sociales r
            JOIN publicacion_red_social prs ON r.id = prs.red_social_id
            WHERE prs.publicacion_id = ?
        ");
        $stmt->execute([$publicacionId]);
        $result = $stmt->fetchAll();
        
        // Extraer solo IDs para simplificar el uso
        $redesIds = [];
        foreach ($result as $row) {
            $redesIds[] = $row['id'];
        }
        
        return $redesIds;
    } catch (PDOException $e) {
        echo '<div style="background-color: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 4px;">';
        echo '<strong>Error al obtener redes de publicación:</strong><br>';
        echo $e->getMessage();
        echo '</div>';
        return [];
    }
}

// Sanitizar entradas para prevenir inyección SQL y XSS
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Formatear fecha para mostrar
function formatFecha($fechaDb) {
    if (!$fechaDb) return '';
    $fecha = new DateTime($fechaDb);
    return $fecha->format('d/m/Y H:i');
}

// Truncar texto para previsualizaciones
function truncateText($text, $length = 100) {
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . '...';
} 