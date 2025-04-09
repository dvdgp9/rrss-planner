<?php
// TODO para mañana: Añadir sistema de feedback en esta vista compartida.
// - Idea inicial: Añadir un formulario simple (textarea + botón enviar) debajo de la tabla.
// - El feedback se guardaría en una nueva tabla (ej. 'shared_feedback') asociado al 'share_token'.
// - Mostraría un mensaje de éxito al enviar.

require_once 'includes/functions.php';
// NO incluir require_authentication() aquí, es una página pública

$token = $_GET['token'] ?? null;
$lineaId = null;
$lineaNombre = 'Vista Compartida';
$lineaLogo = 'assets/images/logos/isotipo-ebone.png'; // Logo por defecto
$lineaColor = '#6c757d'; // Color gris por defecto
$lineaBodyClass = 'linea-shared'; // Clase genérica
$publicaciones = [];
$error = '';

if (!$token) {
    $error = 'Token no proporcionado.';
} else {
    $lineaId = get_linea_id_from_token($token);
    if (!$lineaId) {
        $error = 'Enlace inválido o caducado.';
    } else {
        // Token válido, obtener datos de la línea y publicaciones
        try {
            $db = getDbConnection();
            // Obtener info de la línea (nombre, etc.)
            $stmt = $db->prepare("SELECT * FROM lineas_negocio WHERE id = ?");
            $stmt->execute([$lineaId]);
            $lineaInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($lineaInfo) {
                $lineaNombre = $lineaInfo['nombre'];
                // Asignar logo y color/gradiente según ID
                $headerBgStyle = ''; // Variable para el estilo de fondo
                switch($lineaInfo['id']) {
                    case 1: 
                        $lineaLogo = 'assets/images/logos/logo-ebone.png'; 
                        $lineaColor = '#23AAC5';
                        $lineaColorDark = '#1a8da5';
                        $headerBgStyle = 'background: linear-gradient(90deg, ' . $lineaColor . ' 0%, ' . $lineaColorDark . ' 100%);';
                        $lineaBodyClass = 'linea-ebone';
                        break;
                    case 2: 
                        $lineaLogo = 'assets/images/logos/logo-cubofit.png';
                        $lineaColor = '#E23633';
                        $lineaColorDark = '#c12f2c';
                        $headerBgStyle = 'background: linear-gradient(90deg, ' . $lineaColor . ' 0%, ' . $lineaColorDark . ' 100%);';
                        $lineaBodyClass = 'linea-cubofit';
                        break;
                    case 3: 
                        $lineaLogo = 'assets/images/logos/logo-uniges.png';
                        $lineaColor = '#9B6FCE';
                        $lineaColorDark = '#032551';
                        $headerBgStyle = 'background: linear-gradient(90deg, ' . $lineaColor . ' 0%, ' . $lineaColorDark . ' 100%);';
                        $lineaBodyClass = 'linea-uniges';
                        break;
                    case 4: 
                        $lineaLogo = 'assets/images/logos/logo-teia.jpg';
                        $lineaColor = '#009970';
                        $lineaColorDark = '#007a5a';
                        $headerBgStyle = 'background: linear-gradient(90deg, ' . $lineaColor . ' 0%, ' . $lineaColorDark . ' 100%);';
                        $lineaBodyClass = 'linea-teia';
                        break;
                    default: // Caso por defecto, si algo falla
                        $lineaColor = '#6c757d';
                        $lineaColorDark = '#5a6268';
                        $headerBgStyle = 'background: linear-gradient(90deg, ' . $lineaColor . ' 0%, ' . $lineaColorDark . ' 100%);';
                        break;
                }
            }
            
            // Obtener publicaciones y contar feedback
            $stmt = $db->prepare("\n                SELECT \n                    p.*, \n                    GROUP_CONCAT(rs.nombre SEPARATOR '|') as nombres_redes, \n                    COUNT(DISTINCT pf.id) as feedback_count \n                FROM publicaciones p\n                LEFT JOIN publicacion_red_social prs ON p.id = prs.publicacion_id\n                LEFT JOIN redes_sociales rs ON prs.red_social_id = rs.id\n                LEFT JOIN publication_feedback pf ON p.id = pf.publicacion_id -- Unir para contar feedback
                WHERE p.linea_negocio_id = ?\n                GROUP BY p.id\n                ORDER BY p.fecha_programada ASC, p.id ASC\n            ");
            $stmt->execute([$lineaId]);
            $publicaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            $error = 'Error al cargar los datos.';
            error_log("Error en share_view.php: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vista Compartida: <?php echo htmlspecialchars($lineaNombre); ?> - Planificador RRSS</title>
    <link rel="icon" type="image/png" href="assets/images/logos/isotipo-ebone.png">
    <!-- Google Fonts & Font Awesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Estilos propios -->
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        /* Estilos adicionales para la vista compartida */
        body.share-view {
            background-color: #f8f9fa;
        }
        .share-header {
            padding: 15px 30px;
            background-color: <?php echo $lineaColor; ?>;
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
        .share-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 15px;
        }
        .error-container {
             background-color: #f8d7da;
             color: #721c24;
             padding: 20px;
             margin: 50px auto;
             border-radius: 8px;
             border: 1px solid #f5c6cb;
             text-align: center;
             max-width: 600px;
        }
        .share-table th, .share-table td {
             padding: 12px 15px; /* Menos padding que la tabla normal */
        }
        /* Ocultar columnas no relevantes en vista compartida */
        .share-table .col-actions { 
            display: none; 
        }
    </style>
</head>
<body class="share-view <?php echo $lineaBodyClass; ?>">

    <div class="share-header" style="<?php echo $headerBgStyle; ?> color: white;">
        <?php if($lineaLogo): ?>
            <img src="<?php echo htmlspecialchars($lineaLogo); ?>" alt="Logo <?php echo htmlspecialchars($lineaNombre); ?>">
        <?php endif; ?>
        <h1><?php echo htmlspecialchars($lineaNombre); ?> - Planificación Redes Sociales</h1>
    </div>

    <div class="share-container">
        <?php if ($error): ?>
            <div class="error-container">
                <h2>Error</h2>
                <p><?php echo htmlspecialchars($error); ?></p>
                <p><a href="login.php">Volver al inicio</a></p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="share-table"> 
                    <thead>
                        <tr>
                            <th>Fecha Programada</th>
                            <th>Contenido</th>
                            <th>Imagen</th>
                            <th>Estado</th>
                            <th>Redes</th>
                            <th class="col-actions" style="display: none;">Acciones</th> <!-- Ocultamos acciones -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($publicaciones)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 30px;">No hay publicaciones para mostrar.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($publicaciones as $pub): ?>
                                <tr>
                                    <td><?php echo formatFecha($pub['fecha_programada']); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars($pub['contenido'])); ?></td>
                                    <td>
                                        <?php if (!empty($pub['imagen_url'])): ?>
                                            <img src="<?php echo htmlspecialchars($pub['imagen_url']); ?>" alt="Miniatura" class="thumbnail">
                                        <?php else: ?>
                                            <div class="no-image"><i class="fas fa-image"></i></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php 
                                            echo $pub['estado'] === 'borrador' ? 'badge-draft' : 
                                                 ($pub['estado'] === 'programado' ? 'badge-scheduled' : 'badge-published'); 
                                        ?>">
                                            <?php echo ucfirst(htmlspecialchars($pub['estado'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="redes-iconos">
                                            <?php 
                                            $nombres_redes = !empty($pub['nombres_redes']) ? explode('|', $pub['nombres_redes']) : [];
                                            foreach (array_unique($nombres_redes) as $nombre_red): // Usar nombre en lugar de icono
                                                if (!empty($nombre_red)): 
                                                    $iconClass = '';
                                                    $iconTag = 'fas'; // Default tag
                                                    switch (strtolower($nombre_red)) {
                                                        case 'instagram': $iconClass = 'fa-instagram'; $iconTag='fab'; break;
                                                        case 'facebook': $iconClass = 'fa-facebook-f'; $iconTag='fab'; break;
                                                        case 'twitter':
                                                        case 'twitter (x)': $iconClass = 'fa-twitter'; $iconTag='fab'; break;
                                                        case 'linkedin': $iconClass = 'fa-linkedin-in'; $iconTag='fab'; break;
                                                        default: $iconClass = 'fa-share-alt'; $iconTag='fas'; break;
                                                    }
                                                ?>
                                                    <i class="<?php echo $iconTag . ' ' . $iconClass; ?>"></i>
                                                <?php endif; 
                                            endforeach; 
                                            ?>
                                        </div>
                                    </td>
                                    <td class="col-actions" style="display: none;"></td> <!-- Ocultamos acciones -->
                                </tr>
                                <!-- Nueva fila para Feedback -->
                                <tr class="feedback-row">
                                    <td colspan="6"> <!-- Ocupa todas las columnas visibles -->
                                        <button class="btn btn-sm btn-outline-secondary btn-toggle-feedback" data-publicacion-id="<?php echo $pub['id']; ?>">
                                            <i class="fas fa-comments"></i> Ver/Añadir Feedback 
                                            <span class="feedback-count">(<?php echo $pub['feedback_count']; ?>)</span> <!-- Mostrar conteo inicial -->
                                        </button>
                                        <div class="feedback-area" id="feedback-area-<?php echo $pub['id']; ?>" style="display: none;">
                                            <div class="feedback-list">Cargando feedback...</div>
                                            <div class="feedback-form">
                                                <textarea placeholder="Escribe tu feedback aquí..." rows="3"></textarea>
                                                <button class="btn btn-sm btn-primary btn-submit-feedback">Enviar Feedback</button>
                                                <p class="feedback-message" style="display: none;"></p>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal para Imagen -->
    <div id="imageModal" class="modal-image">
        <span class="close-image-modal">&times;</span>
        <img class="modal-image-content" id="modalImageSrc">
    </div>

    <script src="assets/js/main.js"></script> 
    <!-- Script para Feedback -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const shareToken = new URLSearchParams(window.location.search).get('token'); // Necesitamos el token actual

        document.querySelectorAll('.btn-toggle-feedback').forEach(button => {
            button.addEventListener('click', function() {
                const pubId = this.dataset.publicacionId;
                const feedbackArea = document.getElementById(`feedback-area-${pubId}`);
                const feedbackListDiv = feedbackArea.querySelector('.feedback-list');
                const feedbackCountSpan = this.querySelector('.feedback-count');

                if (feedbackArea.style.display === 'none') {
                    feedbackArea.style.display = 'block';
                    feedbackListDiv.innerHTML = 'Cargando feedback...'; 
                    feedbackCountSpan.textContent = '(...)';
                    
                    // Cargar feedback existente
                    fetch(`get_feedback.php?publicacion_id=${pubId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                displayFeedback(feedbackListDiv, data.feedback);
                                feedbackCountSpan.textContent = `(${data.feedback.length})`;
                            } else {
                                feedbackListDiv.innerHTML = '<p style="color: red;">Error al cargar feedback.</p>';
                                feedbackCountSpan.textContent = '(Error)';
                            }
                        })
                        .catch(error => {
                             console.error('Error fetching feedback:', error);
                             feedbackListDiv.innerHTML = '<p style="color: red;">Error de conexión al cargar feedback.</p>';
                             feedbackCountSpan.textContent = '(Error)';
                        });
                } else {
                    feedbackArea.style.display = 'none';
                }
            });
        });
        
        document.querySelectorAll('.btn-submit-feedback').forEach(button => {
            button.addEventListener('click', function() {
                const feedbackArea = this.closest('.feedback-area');
                const pubId = feedbackArea.id.split('-').pop(); // Obtener ID de pub del ID del área
                const textarea = feedbackArea.querySelector('textarea');
                const feedbackText = textarea.value.trim();
                const feedbackListDiv = feedbackArea.querySelector('.feedback-list');
                const messageP = feedbackArea.querySelector('.feedback-message');
                const toggleButton = document.querySelector(`.btn-toggle-feedback[data-publicacion-id="${pubId}"]`);
                const feedbackCountSpan = toggleButton ? toggleButton.querySelector('.feedback-count') : null;

                if (!feedbackText || !shareToken) {
                    showMessage(messageP, 'Por favor, escribe tu feedback.', 'red');
                    return;
                }

                this.disabled = true; // Deshabilitar botón mientras se envía
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
                        textarea.value = ''; // Limpiar textarea
                        showMessage(messageP, 'Feedback enviado. ¡Gracias!', 'green');
                        // Añadir nuevo feedback a la lista visible
                        appendFeedbackItem(feedbackListDiv, data.feedback);
                        // Actualizar contador (si el span existe)
                        if(feedbackCountSpan) {
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
                    this.disabled = false; // Rehabilitar botón
                    this.textContent = 'Enviar Feedback';
                });
            });
        });

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
            // Si era el primer comentario, quitar el mensaje "No hay feedback"
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
            setTimeout(() => { element.style.display = 'none'; }, 3000); // Ocultar después de 3s
        }
        
        // Función auxiliar para formatear fecha (JS)
        function formatDate(dateString) {
           // Simple formato dd/mm/yyyy HH:MM, asumiendo que viene como YYYY-MM-DD HH:MM:SS
           try {
               const date = new Date(dateString.replace(' ', 'T')); // ISO 8601 ish
               const day = String(date.getDate()).padStart(2, '0');
               const month = String(date.getMonth() + 1).padStart(2, '0');
               const year = date.getFullYear();
               const hours = String(date.getHours()).padStart(2, '0');
               const minutes = String(date.getMinutes()).padStart(2, '0');
               return `${day}/${month}/${year} ${hours}:${minutes}`;
           } catch (e) {
               return dateString; // Fallback a la cadena original si falla
           }
        }

    });
    </script>

</body>
</html> 