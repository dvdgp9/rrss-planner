/**
 * Share Feedback JavaScript
 * Maneja la funcionalidad de feedback en vistas compartidas
 */

document.addEventListener('DOMContentLoaded', function() {
    // Variables globales
    const feedbackModal = document.getElementById('feedbackModal');
    const feedbackTextarea = document.getElementById('feedbackTextarea');
    const submitFeedbackBtn = document.getElementById('submitFeedbackBtn');
    const feedbackMessage = document.getElementById('feedbackMessage');
    const feedbackDisplayList = document.querySelector('.feedback-display-list');
    
    let currentPublicationId = null;
    let shareToken = null;
    
    // Obtener share token desde URL
    const urlParams = new URLSearchParams(window.location.search);
    shareToken = urlParams.get('token');
    
    // Inicializar modal listeners
    initModalListeners();
    
    // Manejar clicks en botones de feedback
    document.body.addEventListener('click', function(event) {
        const feedbackBtn = event.target.closest('.btn-feedback-modal');
        if (feedbackBtn) {
            event.preventDefault();
            const pubId = feedbackBtn.dataset.publicacionId;
            openFeedbackModal(pubId, feedbackBtn);
        }
    });
    
    /**
     * Inicializar event listeners del modal
     */
    function initModalListeners() {
        if (!feedbackModal) return;
        
        // Cerrar modal con X
        const closeButtons = feedbackModal.querySelectorAll('.close-share-modal');
        closeButtons.forEach(btn => {
            btn.addEventListener('click', closeFeedbackModal);
        });
        
        // Cerrar modal clickeando fuera
        feedbackModal.addEventListener('click', function(event) {
            if (event.target === feedbackModal) {
                closeFeedbackModal();
            }
        });
        
        // Cerrar modal con Escape
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && feedbackModal.style.display === 'flex') {
                closeFeedbackModal();
            }
        });
        
        // Enviar feedback
        if (submitFeedbackBtn) {
            submitFeedbackBtn.addEventListener('click', submitFeedback);
        }
        
        // Submit con Enter en textarea
        if (feedbackTextarea) {
            feedbackTextarea.addEventListener('keydown', function(event) {
                if (event.key === 'Enter' && (event.ctrlKey || event.metaKey)) {
                    event.preventDefault();
                    submitFeedback();
                }
            });
        }
    }
    
    /**
     * Abrir modal de feedback
     */
    function openFeedbackModal(publicationId, buttonElement) {
        if (!feedbackModal || !publicationId) return;
        
        currentPublicationId = publicationId;
        
        // Mostrar modal
        feedbackModal.style.display = 'flex';
        feedbackModal.classList.add('show');
        
        // Limpiar estado anterior
        if (feedbackTextarea) {
            feedbackTextarea.value = '';
        }
        hideMessage();
        
        // Cargar feedback existente
        loadExistingFeedback(publicationId);
        
        // Focus en textarea
        setTimeout(() => {
            if (feedbackTextarea) {
                feedbackTextarea.focus();
            }
        }, 300);
    }
    
    /**
     * Cerrar modal de feedback
     */
    function closeFeedbackModal() {
        if (!feedbackModal) return;
        
        feedbackModal.classList.remove('show');
        setTimeout(() => {
            feedbackModal.style.display = 'none';
            currentPublicationId = null;
        }, 300);
    }
    
    /**
     * Cargar feedback existente
     */
    function loadExistingFeedback(publicationId) {
        if (!feedbackDisplayList) return;
        
        feedbackDisplayList.innerHTML = '<p style="text-align: center; color: #6c757d;"><i class="fas fa-spinner fa-spin"></i> Cargando feedback...</p>';
        
        fetch(`get_feedback.php?publicacion_id=${publicationId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayFeedbackList(data.feedback);
                } else {
                    feedbackDisplayList.innerHTML = '<p style="color: red;">Error al cargar feedback.</p>';
                }
            })
            .catch(error => {
                console.error('Error loading feedback:', error);
                feedbackDisplayList.innerHTML = '<p style="color: red;">Error de conexión al cargar feedback.</p>';
            });
    }
    
    /**
     * Mostrar lista de feedback
     */
    function displayFeedbackList(feedbackItems) {
        if (!feedbackDisplayList) return;
        
        if (feedbackItems.length === 0) {
            feedbackDisplayList.innerHTML = '<p style="text-align: center; color: #6c757d; font-style: italic;">No hay feedback para esta publicación aún.</p>';
            return;
        }
        
        let html = '<ul class="feedback-items-list">';
        feedbackItems.forEach(item => {
            html += `<li><strong>${item.created_at}:</strong> ${item.feedback_text}</li>`;
        });
        html += '</ul>';
        
        feedbackDisplayList.innerHTML = html;
    }
    
    /**
     * Enviar nuevo feedback
     */
    function submitFeedback() {
        if (!currentPublicationId || !shareToken || !feedbackTextarea) return;
        
        const feedbackText = feedbackTextarea.value.trim();
        if (!feedbackText) {
            showMessage('Por favor, escribe tu feedback.', 'error');
            return;
        }
        
        // Deshabilitar botón y mostrar loading
        const originalText = submitFeedbackBtn.innerHTML;
        submitFeedbackBtn.disabled = true;
        submitFeedbackBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
        
        const formData = new FormData();
        formData.append('publicacion_id', currentPublicationId);
        formData.append('share_token', shareToken);
        formData.append('feedback_text', feedbackText);
        
        fetch('submit_feedback.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                feedbackTextarea.value = '';
                showMessage('Feedback enviado. ¡Gracias!', 'success');
                
                // Agregar nuevo feedback a la lista
                if (data.feedback) {
                    appendFeedbackItem(data.feedback);
                }
                
                // Actualizar contador en el botón
                updateFeedbackCounter(currentPublicationId);
                
            } else {
                showMessage(data.message || 'Error al enviar feedback.', 'error');
            }
        })
        .catch(error => {
            console.error('Error submitting feedback:', error);
            showMessage('Error de conexión al enviar feedback.', 'error');
        })
        .finally(() => {
            // Rehabilitar botón
            submitFeedbackBtn.disabled = false;
            submitFeedbackBtn.innerHTML = originalText;
        });
    }
    
    /**
     * Agregar nuevo item de feedback a la lista
     */
    function appendFeedbackItem(feedbackItem) {
        if (!feedbackDisplayList) return;
        
        // Si no hay feedback previo, crear lista
        let list = feedbackDisplayList.querySelector('.feedback-items-list');
        if (!list) {
            feedbackDisplayList.innerHTML = '<ul class="feedback-items-list"></ul>';
            list = feedbackDisplayList.querySelector('.feedback-items-list');
        }
        
        if (list) {
            const newItem = document.createElement('li');
            newItem.innerHTML = `<strong>${formatDate(feedbackItem.created_at)}:</strong> ${feedbackItem.feedback_text}`;
            list.appendChild(newItem);
            
            // Scroll al nuevo item
            newItem.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }
    
    /**
     * Actualizar contador de feedback en el botón
     */
    function updateFeedbackCounter(publicationId) {
        const button = document.querySelector(`.btn-feedback-modal[data-publicacion-id="${publicationId}"]`);
        if (!button) return;
        
        let badge = button.querySelector('.badge');
        if (!badge) {
            // Crear badge si no existe
            badge = document.createElement('span');
            badge.className = 'badge badge-secondary';
            button.appendChild(badge);
        }
        
        const currentCount = parseInt(badge.textContent) || 0;
        badge.textContent = currentCount + 1;
        
        // Mostrar badge si estaba oculto
        badge.style.display = 'inline';
    }
    
    /**
     * Mostrar mensaje de feedback
     */
    function showMessage(text, type) {
        if (!feedbackMessage) return;
        
        feedbackMessage.textContent = text;
        feedbackMessage.className = type;
        feedbackMessage.style.display = 'block';
        
        // Auto-hide después de 3 segundos para mensajes de éxito
        if (type === 'success') {
            setTimeout(() => {
                hideMessage();
            }, 3000);
        }
    }
    
    /**
     * Ocultar mensaje de feedback
     */
    function hideMessage() {
        if (feedbackMessage) {
            feedbackMessage.style.display = 'none';
        }
    }
    
    /**
     * Formatear fecha para display
     */
    function formatDate(dateString) {
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('es-ES', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch (error) {
            return dateString; // Fallback al string original
        }
    }
    
    /**
     * Inicializar toggle para mostrar publicados (si existe)
     */
    const togglePublished = document.getElementById('toggle-published');
    if (togglePublished) {
        togglePublished.addEventListener('change', function() {
            const showPublished = this.checked;
            const rows = document.querySelectorAll('tbody tr[data-estado]');
            
            rows.forEach(row => {
                const estado = row.dataset.estado;
                const isPublished = estado === 'publicado' || estado === 'publish';
                
                if (isPublished && !showPublished) {
                    row.style.display = 'none';
                } else {
                    row.style.display = '';
                }
            });
        });
        
        // Ejecutar filtro inicial
        togglePublished.dispatchEvent(new Event('change'));
    }
}); 