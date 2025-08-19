<?php
require_once 'includes/functions.php';
// NO requiere autenticación

$token = $_GET['token'] ?? null;
$publicacion = null;
$publicacionId = null;
$lineaNombre = 'Vista Publicación'; // Título genérico inicial
$lineaLogo = 'assets/images/logos/isotipo-ebone.png'; // Logo por defecto
$lineaColor = '#6c757d'; // Color gris por defecto
$lineaBodyClass = 'linea-shared-single'; // Clase específica
$headerBgStyle = 'background: linear-gradient(90deg, #6c757d 0%, #5a6268 100%);'; // Gradiente por defecto
$error = '';

// Función para obtener publicacion_id del nuevo token
function get_pub_id_from_single_token($token) {
    if (empty($token)) return null;
    $db = getDbConnection();
    try {
        $stmt = $db->prepare("SELECT publicacion_id FROM publication_share_tokens WHERE token = ? AND is_active = TRUE");
        $stmt->execute([$token]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['publicacion_id'] : null;
    } catch (PDOException $e) {
        error_log("Error validating single pub share token: " . $e->getMessage());
        return null;
    }
}

if (!$token) {
    $error = 'Token no proporcionado.';
} else {
    $publicacionId = get_pub_id_from_single_token($token);
    if (!$publicacionId) {
        $error = 'Enlace inválido o caducado.';
    } else {
        // Token válido, obtener datos de la publicación y su línea
        try {
            $db = getDbConnection();
            // Obtener publicación y datos de su línea
            $stmt = $db->prepare("
                SELECT 
                    p.*, 
                    ln.nombre as linea_nombre, 
                    ln.id as linea_id,
                    GROUP_CONCAT(rs.nombre SEPARATOR '|') as nombres_redes,
                    COUNT(DISTINCT pf.id) as feedback_count
                FROM publicaciones p
                JOIN lineas_negocio ln ON p.linea_negocio_id = ln.id
                LEFT JOIN publicacion_red_social prs ON p.id = prs.publicacion_id
                LEFT JOIN redes_sociales rs ON prs.red_social_id = rs.id
                LEFT JOIN publication_feedback pf ON p.id = pf.publicacion_id
                WHERE p.id = ?
                GROUP BY p.id
            ");
            $stmt->execute([$publicacionId]);
            $publicacion = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$publicacion) {
                 $error = 'Publicación no encontrada.';
            } else {
                // Asignar logo, color y clase según la línea de la publicación
                $lineaNombre = $publicacion['linea_nombre'];
                switch($publicacion['linea_id']) {
                     case 1: 
                        $lineaLogo = 'assets/images/logos/logo-ebone.png'; 
                        $lineaColor = '#23AAC5'; $lineaColorDark = '#1a8da5';
                        $headerBgStyle = 'background: linear-gradient(90deg, ' . $lineaColor . ' 0%, ' . $lineaColorDark . ' 100%);';
                        $lineaBodyClass = 'linea-ebone'; break;
                    case 2: 
                        $lineaLogo = 'assets/images/logos/logo-cubofit.png';
                        $lineaColor = '#E23633'; $lineaColorDark = '#c12f2c';
                        $headerBgStyle = 'background: linear-gradient(90deg, ' . $lineaColor . ' 0%, ' . $lineaColorDark . ' 100%);';
                        $lineaBodyClass = 'linea-cubofit'; break;
                    case 3: 
                        $lineaLogo = 'assets/images/logos/logo-uniges.png';
                        $lineaColor = '#9B6FCE'; $lineaColorDark = '#032551';
                        $headerBgStyle = 'background: linear-gradient(90deg, ' . $lineaColor . ' 0%, ' . $lineaColorDark . ' 100%);';
                        $lineaBodyClass = 'linea-uniges'; break;
                    case 4: 
                        $lineaLogo = 'assets/images/logos/logo-teia.jpg';
                        $lineaColor = '#009970'; $lineaColorDark = '#007a5a';
                        $headerBgStyle = 'background: linear-gradient(90deg, ' . $lineaColor . ' 0%, ' . $lineaColorDark . ' 100%);';
                        $lineaBodyClass = 'linea-teia'; break;
                }
            }
        } catch (PDOException $e) {
            $error = 'Error al cargar los datos de la publicación.';
            error_log("Error en share_single_pub.php: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vista Publicación: <?php echo $publicacion ? '#'.$publicacion['id'] : 'Error'; ?> - Planificador RRSS</title>
    <link rel="icon" type="image/png" href="assets/images/logos/isotipo-ebone.png">
    <!-- CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        /* Estilos adicionales para la vista individual */
        body.share-view-single {
            background-color: #f8f9fa;
        }
        .share-header {
            padding: 15px 30px;
            color: white;
            display: flex;
            align-items: center;
            gap: 15px;
            border-bottom: 3px solid rgba(0,0,0,0.1);
        }
        .share-header img {
            height: 35px;
            width: auto;
            max-width: 120px;
            object-fit: contain;
        }
        .share-header h1 {
            font-size: 1.4rem;
            font-weight: 600;
            margin: 0;
        }
        .single-pub-container {
            max-width: 900px;
            margin: 30px auto;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            border: 1px solid #eaeaea;
        }
        .pub-header {
            padding: 15px 20px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .pub-header span { font-size: 0.9rem; color: #555; }
        .pub-header .badge { font-size: 0.9rem; }
        .pub-content-area {
            padding: 25px;
        }
        .pub-image {
            text-align: center;
            margin-bottom: 20px;
        }
        .pub-image img {
            max-width: 100%;
            max-height: 500px;
            border-radius: 6px;
            border: 1px solid #eee;
        }
        .pub-text {
            white-space: pre-wrap; /* Respetar saltos de línea */
            line-height: 1.6;
            color: #333;
            margin-bottom: 25px;
        }
        .pub-redes-icons {
             font-size: 1.3rem;
             color: #888;
             display: flex;
             gap: 15px;
             justify-content: flex-start;
        }
        .feedback-section {
             padding: 20px 25px;
             border-top: 1px solid #eee;
             background-color: #fdfdfd;
        }
        .feedback-section h3 {
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 1.1rem;
        }
        /* Reutilizar estilos de .feedback-area, .feedback-list, .feedback-form */
    </style>
</head>
<body class="share-view-single <?php echo $lineaBodyClass; ?>">

    <div class="share-header" style="<?php echo $headerBgStyle; ?> color: white;">
        <?php if($lineaLogo): ?><img src="<?php echo htmlspecialchars($lineaLogo); ?>" alt="Logo <?php echo htmlspecialchars($lineaNombre); ?>"><?php endif; ?>
        <h1>Vista Publicación - <?php echo htmlspecialchars($lineaNombre); ?></h1>
    </div>

    <div class="main-container">
        <?php if ($error): ?>
            <div class="error-container" style="margin-top: 30px;">
                <h2>Error</h2>
                <p><?php echo htmlspecialchars($error); ?></p>
                <p><a href="login.php">Volver al inicio</a></p>
            </div>
        <?php elseif ($publicacion): ?>
            <div class="single-pub-container">
                <div class="pub-header">
                    <span><i class="far fa-calendar-alt"></i> Programada: <?php echo formatFecha($publicacion['fecha_programada']); ?></span>
                    <span class="badge <?php 
                        echo $publicacion['estado'] === 'borrador' ? 'badge-draft' : 
                             ($publicacion['estado'] === 'programado' ? 'badge-scheduled' : 'badge-published'); 
                    ?>">
                        <?php echo ucfirst(htmlspecialchars($publicacion['estado'])); ?>
                    </span>
                </div>
                <div class="pub-content-area">
                    <?php if (!empty($publicacion['imagen_url'])): ?>
                        <div class="pub-image">
                             <!-- Podríamos hacerla clickable para modal si fuera necesario -->
                            <img src="<?php echo htmlspecialchars($publicacion['imagen_url']); ?>" alt="Imagen de publicación">
                        </div>
                    <?php elseif ($publicacion['estado'] === 'publicado'): ?>
                        <div class="pub-image">
                            <div class="image-placeholder archived size-large fade-in" data-tooltip="Imagen archivada para optimizar almacenamiento del servidor">
                                <i class="fas fa-archive"></i>
                                <span>Imagen Archivada</span>
                                <small>Esta imagen fue archivada automáticamente<br>para optimizar el almacenamiento del servidor</small>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="pub-text">
                        <p class="pub-text-content"><?php echo nl2br(htmlspecialchars($publicacion['contenido'])); ?></p>
                    </div>
                     <div class="pub-redes-icons">
                        <?php 
                        $nombres_redes = !empty($publicacion['nombres_redes']) ? explode('|', $publicacion['nombres_redes']) : [];
                        foreach (array_unique($nombres_redes) as $nombre_red):
                            if (!empty($nombre_red)): 
                                $iconClass = ''; $iconTag = 'fas';
                                switch (strtolower($nombre_red)) {
                                    case 'instagram': $iconClass = 'fa-instagram'; $iconTag='fab'; break;
                                    case 'facebook': $iconClass = 'fa-facebook-f'; $iconTag='fab'; break;
                                    case 'twitter': case 'twitter (x)': $iconClass = 'fa-twitter'; $iconTag='fab'; break;
                                    case 'linkedin': $iconClass = 'fa-linkedin-in'; $iconTag='fab'; break;
                                    default: $iconClass = 'fa-share-alt'; break;
                                }
                        ?><i class="<?php echo $iconTag . ' ' . $iconClass; ?>" title="<?php echo htmlspecialchars($nombre_red);?>"></i><?php 
                            endif; 
                        endforeach; 
                        ?>
                    </div>
                </div>
                <div class="feedback-section">
                     <h3>Feedback <span class="feedback-count">(<?php echo $publicacion['feedback_count']; ?>)</span></h3>
                     <!-- Área de Feedback similar a share_view.php -->
                     <div class="feedback-area" id="feedback-area-<?php echo $publicacion['id']; ?>">
                         <div class="feedback-list">Cargando feedback...</div>
                         <div class="feedback-form">
                             <textarea placeholder="Escribe tu feedback aquí..." rows="3"></textarea>
                             <button class="btn btn-sm btn-primary btn-submit-feedback">Enviar Feedback</button>
                             <p class="feedback-message" style="display: none;"></p>
                         </div>
                     </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- JS -->
    <script>
    // Lógica JS para Feedback (muy similar a share_view.php, adaptada)
    document.addEventListener('DOMContentLoaded', function() {
        const shareToken = new URLSearchParams(window.location.search).get('token');
        const pubId = <?php echo $publicacionId ?? 'null'; ?>; // Obtenemos ID de la publicación del PHP
        const feedbackArea = document.getElementById(`feedback-area-${pubId}`);
        
        if (feedbackArea && pubId && shareToken) {
            const feedbackListDiv = feedbackArea.querySelector('.feedback-list');
            const feedbackForm = feedbackArea.querySelector('.feedback-form');
            const submitButton = feedbackForm.querySelector('.btn-submit-feedback');
            const textarea = feedbackForm.querySelector('textarea');
            const messageP = feedbackForm.querySelector('.feedback-message');
            const feedbackCountSpan = document.querySelector('.feedback-section .feedback-count'); // Span en H3

            // Cargar feedback inicial
            fetch(`get_feedback.php?publicacion_id=${pubId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayFeedback(feedbackListDiv, data.feedback);
                        // El contador inicial ya se muestra con PHP, no necesitamos actualizarlo aquí
                    } else {
                        feedbackListDiv.innerHTML = '<p style="color: red;">Error al cargar feedback.</p>';
                    }
                })
                .catch(error => {
                     console.error('Error fetching feedback:', error);
                     feedbackListDiv.innerHTML = '<p style="color: red;">Error de conexión al cargar feedback.</p>';
                });

            // Enviar nuevo feedback
            submitButton.addEventListener('click', function() {
                const feedbackText = textarea.value.trim();
                if (!feedbackText) {
                    showMessage(messageP, 'Por favor, escribe tu feedback.', 'red');
                    return;
                }

                this.disabled = true;
                this.textContent = 'Enviando...';
                const formData = new FormData();
                formData.append('publicacion_id', pubId);
                formData.append('share_token', shareToken);
                formData.append('feedback_text', feedbackText);

                fetch('submit_feedback.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        textarea.value = '';
                        showMessage(messageP, 'Feedback enviado. ¡Gracias!', 'green');
                        appendFeedbackItem(feedbackListDiv, data.feedback);
                        if (feedbackCountSpan) {
                            const currentCount = parseInt(feedbackCountSpan.textContent.match(/\d+/)?.[0] || 0);
                            feedbackCountSpan.textContent = `(${currentCount + 1})`;
                        }
                    } else {
                        showMessage(messageP, data.message || 'Error al enviar feedback.', 'red');
                    }
                })
                .catch(error => {
                    console.error('Error submitting feedback:', error);
                    showMessage(messageP, 'Error de conexión al enviar feedback.', 'red');
                })
                .finally(() => {
                    this.disabled = false;
                    this.textContent = 'Enviar Feedback';
                });
            });
            
            // --- Funciones auxiliares (copiadas de share_view.php) ---
             function displayFeedback(container, feedbackItems) {
                if (feedbackItems.length === 0) {
                    container.innerHTML = '<p><i>No hay feedback para esta publicación aún.</i></p>';
                    return;
                }
                let html = '<ul class="feedback-items-list">';
                feedbackItems.forEach(item => {
                    html += `<li><strong>${item.created_at}:</strong> ${item.feedback_text}</li>`;
                });
                html += '</ul>';
                container.innerHTML = html;
            }
            
            function appendFeedbackItem(container, feedbackItem) {
                const noFeedbackMsg = container.querySelector('p');
                if (noFeedbackMsg && noFeedbackMsg.textContent.includes('No hay feedback')) {
                    container.innerHTML = '<ul class="feedback-items-list"></ul>';
                }
                const list = container.querySelector('.feedback-items-list');
                if (list) {
                    const newItem = document.createElement('li');
                    newItem.innerHTML = `<strong>${formatDate(feedbackItem.created_at)}:</strong> ${feedbackItem.feedback_text}`;
                    list.appendChild(newItem);
                }
            }

            function showMessage(element, text, color) {
                element.textContent = text;
                element.style.color = color;
                element.style.display = 'block';
                setTimeout(() => { element.style.display = 'none'; }, 3000);
            }
            
            function formatDate(dateString) {
               try {
                   const date = new Date(dateString.replace(' ', 'T'));
                   const day = String(date.getDate()).padStart(2, '0');
                   const month = String(date.getMonth() + 1).padStart(2, '0');
                   const year = date.getFullYear();
                   const hours = String(date.getHours()).padStart(2, '0');
                   const minutes = String(date.getMinutes()).padStart(2, '0');
                   return `${day}/${month}/${year} ${hours}:${minutes}`;
               } catch (e) {
                   return dateString;
               }
            }
            // --- Fin Funciones auxiliares ---
        }
    });
    </script>

</body>
</html> 