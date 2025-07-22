<?php
/**
 * Script de migración: Generar thumbnails para imágenes existentes
 * Procesa todas las imágenes de publicaciones y blog posts que no tienen thumbnail
 * Ejecutar: php generate_missing_thumbnails.php
 */

require_once 'includes/functions.php';
require_once 'config/db.php';

// Configuración
$batchSize = 20; // Procesar de 20 en 20 para evitar timeout
$processedCount = 0;
$errorCount = 0;
$skippedCount = 0;

echo "=== GENERADOR DE THUMBNAILS FALTANTES ===\n";
echo "Inicio: " . date('Y-m-d H:i:s') . "\n\n";

try {
    $db = getDbConnection();
    
    // 1. Procesar publicaciones sociales
    echo "🔄 Procesando publicaciones sociales...\n";
    
    $stmt = $db->prepare("
        SELECT id, imagen_url, thumbnail_url 
        FROM publicaciones 
        WHERE imagen_url IS NOT NULL 
        AND imagen_url != ''
        ORDER BY id DESC
    ");
    $stmt->execute();
    $publicaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Encontradas " . count($publicaciones) . " publicaciones con imágenes\n";
    
    foreach ($publicaciones as $i => $pub) {
        $needsProcessing = false;
        $imagePath = $pub['imagen_url'];
        
        // Verificar si la imagen existe
        if (!file_exists($imagePath)) {
            echo "⚠️  Imagen no encontrada: {$imagePath} (ID: {$pub['id']})\n";
            $skippedCount++;
            continue;
        }
        
        // Verificar si ya tiene thumbnail y si existe
        if (!empty($pub['thumbnail_url'])) {
            $thumbnailPath = $_SERVER['DOCUMENT_ROOT'] . $pub['thumbnail_url'];
            if (file_exists($thumbnailPath)) {
                echo "✓ Ya tiene thumbnail: ID {$pub['id']}\n";
                $skippedCount++;
                continue;
            } else {
                echo "🔧 Thumbnail registrado pero no existe: ID {$pub['id']}\n";
                $needsProcessing = true;
            }
        } else {
            echo "🆕 Sin thumbnail: ID {$pub['id']}\n";
            $needsProcessing = true;
        }
        
        if ($needsProcessing) {
            // Generar thumbnail
            $result = generateThumbnail($imagePath);
            
            if ($result) {
                // Actualizar base de datos con la nueva URL del thumbnail
                $thumbnailUrl = null;
                if (isset($result['webp_url'])) {
                    $thumbnailUrl = $result['webp_url'];
                } elseif (isset($result['jpeg_url'])) {
                    $thumbnailUrl = $result['jpeg_url'];
                }
                
                if ($thumbnailUrl) {
                    $updateStmt = $db->prepare("UPDATE publicaciones SET thumbnail_url = ? WHERE id = ?");
                    $updateStmt->execute([$thumbnailUrl, $pub['id']]);
                    echo "✅ Thumbnail generado: ID {$pub['id']} -> {$thumbnailUrl}\n";
                    $processedCount++;
                } else {
                    echo "❌ Error generando thumbnail: ID {$pub['id']}\n";
                    $errorCount++;
                }
            } else {
                echo "❌ Error procesando imagen: ID {$pub['id']}\n";
                $errorCount++;
            }
        }
        
        // Pausa cada batch para no sobrecargar el servidor
        if (($i + 1) % $batchSize === 0) {
            echo "⏸️  Pausa (procesados " . ($i + 1) . "/" . count($publicaciones) . ")\n";
            sleep(2);
        }
    }
    
    echo "\n";
    
    // 2. Procesar blog posts
    echo "🔄 Procesando blog posts...\n";
    
    $stmt = $db->prepare("
        SELECT id, imagen_destacada, thumbnail_url 
        FROM blog_posts 
        WHERE imagen_destacada IS NOT NULL 
        AND imagen_destacada != ''
        ORDER BY id DESC
    ");
    $stmt->execute();
    $blogPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Encontrados " . count($blogPosts) . " blog posts con imágenes\n";
    
    foreach ($blogPosts as $i => $post) {
        $needsProcessing = false;
        $imagePath = $post['imagen_destacada'];
        
        // Verificar si la imagen existe
        if (!file_exists($imagePath)) {
            echo "⚠️  Imagen no encontrada: {$imagePath} (ID: {$post['id']})\n";
            $skippedCount++;
            continue;
        }
        
        // Verificar si ya tiene thumbnail y si existe
        if (!empty($post['thumbnail_url'])) {
            $thumbnailPath = $_SERVER['DOCUMENT_ROOT'] . $post['thumbnail_url'];
            if (file_exists($thumbnailPath)) {
                echo "✓ Ya tiene thumbnail: ID {$post['id']}\n";
                $skippedCount++;
                continue;
            } else {
                echo "🔧 Thumbnail registrado pero no existe: ID {$post['id']}\n";
                $needsProcessing = true;
            }
        } else {
            echo "🆕 Sin thumbnail: ID {$post['id']}\n";
            $needsProcessing = true;
        }
        
        if ($needsProcessing) {
            // Generar thumbnail
            $result = generateThumbnail($imagePath);
            
            if ($result) {
                // Actualizar base de datos con la nueva URL del thumbnail
                $thumbnailUrl = null;
                if (isset($result['webp_url'])) {
                    $thumbnailUrl = $result['webp_url'];
                } elseif (isset($result['jpeg_url'])) {
                    $thumbnailUrl = $result['jpeg_url'];
                }
                
                if ($thumbnailUrl) {
                    $updateStmt = $db->prepare("UPDATE blog_posts SET thumbnail_url = ? WHERE id = ?");
                    $updateStmt->execute([$thumbnailUrl, $post['id']]);
                    echo "✅ Thumbnail generado: ID {$post['id']} -> {$thumbnailUrl}\n";
                    $processedCount++;
                } else {
                    echo "❌ Error generando thumbnail: ID {$post['id']}\n";
                    $errorCount++;
                }
            } else {
                echo "❌ Error procesando imagen: ID {$post['id']}\n";
                $errorCount++;
            }
        }
        
        // Pausa cada batch para no sobrecargar el servidor
        if (($i + 1) % $batchSize === 0) {
            echo "⏸️  Pausa (procesados " . ($i + 1) . "/" . count($blogPosts) . ")\n";
            sleep(2);
        }
    }
    
    // 3. Limpiar thumbnails huérfanos
    echo "\n🧹 Limpiando thumbnails huérfanos...\n";
    $cleanedCount = cleanOrphanThumbnails();
    echo "Thumbnails huérfanos eliminados: {$cleanedCount}\n";
    
} catch (Exception $e) {
    echo "❌ ERROR CRÍTICO: " . $e->getMessage() . "\n";
    exit(1);
}

// Resumen final
echo "\n=== RESUMEN DE MIGRACIÓN ===\n";
echo "Fecha/Hora: " . date('Y-m-d H:i:s') . "\n";
echo "Thumbnails generados: {$processedCount}\n";
echo "Imágenes omitidas (ya tenían thumbnail): {$skippedCount}\n";
echo "Errores: {$errorCount}\n";
echo "Thumbnails huérfanos limpiados: {$cleanedCount}\n";

if ($errorCount > 0) {
    echo "\n⚠️  Revisar errores en el log del servidor\n";
    exit(1);
} else {
    echo "\n✅ Migración completada exitosamente\n";
    exit(0);
}
?> 