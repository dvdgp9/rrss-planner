<?php
/**
 * API Endpoint: Calendar Events
 * Devuelve publicaciones en formato compatible con FullCalendar
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT');
header('Access-Control-Allow-Headers: Content-Type');

require_once dirname(__DIR__) . '/includes/functions.php';

// Verificar autenticación
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$db = getDbConnection();

// Obtener parámetros
$linea_id = isset($_GET['linea_id']) ? intval($_GET['linea_id']) : 0;
$start = $_GET['start'] ?? null;
$end = $_GET['end'] ?? null;
$type = $_GET['type'] ?? 'social'; // 'social' o 'blog'

if (!$linea_id) {
    http_response_code(400);
    echo json_encode(['error' => 'linea_id requerido']);
    exit;
}

try {
    $events = [];
    
    if ($type === 'social' || $type === 'all') {
        // Obtener publicaciones sociales
        $sql = "
            SELECT 
                p.id, p.contenido, p.imagen_url, p.thumbnail_url,
                p.fecha_programada, p.estado,
                GROUP_CONCAT(DISTINCT rs.nombre SEPARATOR ', ') as redes
            FROM publicaciones p
            LEFT JOIN publicacion_red_social prs ON p.id = prs.publicacion_id
            LEFT JOIN redes_sociales rs ON prs.red_social_id = rs.id
            WHERE p.linea_negocio_id = ?
        ";
        $params = [$linea_id];
        
        if ($start) {
            $sql .= " AND p.fecha_programada >= ?";
            $params[] = $start;
        }
        if ($end) {
            $sql .= " AND p.fecha_programada <= ?";
            $params[] = $end;
        }
        
        $sql .= " GROUP BY p.id ORDER BY p.fecha_programada ASC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $publicaciones = $stmt->fetchAll();
        
        foreach ($publicaciones as $pub) {
            $color = '#6b7280'; // gray default
            switch ($pub['estado']) {
                case 'borrador': $color = '#9ca3af'; break;
                case 'programado': $color = '#f59e0b'; break;
                case 'publicado': $color = '#10b981'; break;
            }
            
            $events[] = [
                'id' => 'social_' . $pub['id'],
                'title' => truncateText($pub['contenido'], 50),
                'start' => $pub['fecha_programada'],
                'allDay' => true,
                'backgroundColor' => $color,
                'borderColor' => $color,
                'extendedProps' => [
                    'type' => 'social',
                    'realId' => $pub['id'],
                    'contenido' => $pub['contenido'],
                    'estado' => $pub['estado'],
                    'redes' => $pub['redes'],
                    'imagen' => $pub['thumbnail_url'] ?: $pub['imagen_url']
                ]
            ];
        }
    }
    
    if ($type === 'blog' || $type === 'all') {
        // Obtener blog posts
        $sql = "
            SELECT 
                bp.id, bp.titulo, bp.excerpt, bp.imagen_destacada, bp.thumbnail_url,
                bp.fecha_publicacion, bp.estado
            FROM blog_posts bp
            WHERE bp.linea_negocio_id = ?
        ";
        $params = [$linea_id];
        
        if ($start) {
            $sql .= " AND bp.fecha_publicacion >= ?";
            $params[] = $start;
        }
        if ($end) {
            $sql .= " AND bp.fecha_publicacion <= ?";
            $params[] = $end;
        }
        
        $sql .= " ORDER BY bp.fecha_publicacion ASC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $blogs = $stmt->fetchAll();
        
        foreach ($blogs as $blog) {
            $color = '#6b7280';
            switch ($blog['estado']) {
                case 'draft': $color = '#9ca3af'; break;
                case 'scheduled': $color = '#f59e0b'; break;
                case 'publish': $color = '#10b981'; break;
            }
            
            $events[] = [
                'id' => 'blog_' . $blog['id'],
                'title' => '📝 ' . truncateText($blog['titulo'], 40),
                'start' => $blog['fecha_publicacion'],
                'allDay' => true,
                'backgroundColor' => $color,
                'borderColor' => $color,
                'classNames' => ['blog-event'],
                'extendedProps' => [
                    'type' => 'blog',
                    'realId' => $blog['id'],
                    'titulo' => $blog['titulo'],
                    'excerpt' => $blog['excerpt'],
                    'estado' => $blog['estado'],
                    'imagen' => $blog['thumbnail_url'] ?: $blog['imagen_destacada']
                ]
            ];
        }
    }
    
    echo json_encode($events);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
}
