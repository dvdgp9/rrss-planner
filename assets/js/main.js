// Funcionalidad para los filtros de publicaciones
document.addEventListener('DOMContentLoaded', function() {
    // Gestión de filtros
    const filterButtons = document.querySelectorAll('.filter-btn');
    if (filterButtons.length) {
        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Actualiza clase activa
                filterButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                
                // Redirige o filtra según sea necesario
                const lineaId = new URLSearchParams(window.location.search).get('linea');
                const estado = this.dataset.filter;
                
                if (estado === 'all') {
                    window.location.href = `index.php?linea=${lineaId}`;
                } else {
                    window.location.href = `index.php?linea=${lineaId}&estado=${estado}`;
                }
            });
        });
    }
    
    // Inicializa el botón activo según el estado de URL
    const currentEstado = new URLSearchParams(window.location.search).get('estado');
    if (currentEstado && filterButtons.length) {
        filterButtons.forEach(btn => btn.classList.remove('active'));
        const activeButton = document.querySelector(`.filter-btn[data-filter="${currentEstado}"]`);
        if (activeButton) {
            activeButton.classList.add('active');
        }
    } else if (filterButtons.length) {
        // Por defecto, selecciona "Todos"
        const allButton = document.querySelector('.filter-btn[data-filter="all"]');
        if (allButton) {
            allButton.classList.add('active');
        }
    }
    
    // Confirmación para eliminación
    const deleteButtons = document.querySelectorAll('.action-btn.delete');
    if (deleteButtons.length) {
        deleteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                if (!confirm('¿Estás seguro que deseas eliminar esta publicación?')) {
                    e.preventDefault();
                }
            });
        });
    }
    
    // Preview de imagen al subir
    const imageInput = document.getElementById('imagen');
    const imagePreview = document.getElementById('imagen-preview');
    
    if (imageInput && imagePreview) {
        imageInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                };
                
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
    
    // --- Image Modal Logic (movido aquí dentro) ---
    const imageModal = document.getElementById('imageModal');
    const modalImage = document.getElementById('modalImageSrc');
    const closeImageBtn = imageModal ? imageModal.querySelector('.close-image-modal') : null;

    if (imageModal && modalImage && closeImageBtn) {
        // Abrir modal al hacer clic en una miniatura
        document.body.addEventListener('click', function(event) {
            if (event.target.classList.contains('thumbnail')) {
                const imageSrc = event.target.src;
                if(imageSrc) {
                    modalImage.src = imageSrc;
                    imageModal.classList.add('show');
                }
            }
        });

        function closeImageModal() { // Renombrada para claridad
            imageModal.classList.remove('show');
            setTimeout(() => { 
                if (!imageModal.classList.contains('show')) {
                    modalImage.src = ''; 
                }
            }, 300);
        }
        closeImageBtn.addEventListener('click', closeImageModal);
        imageModal.addEventListener('click', function(event) {
            if (event.target === imageModal) {
                closeImageModal();
            }
        });
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && imageModal.classList.contains('show')) {
                closeImageModal();
            }
        });
    } // Fin Image Modal Logic
    
    // --- Feedback Display Modal Logic (Internal - movido aquí dentro) ---
    const feedbackModal = document.getElementById('feedbackDisplayModal');
    const feedbackModalContent = feedbackModal ? feedbackModal.querySelector('.feedback-display-list') : null;
    const closeFeedbackBtn = feedbackModal ? feedbackModal.querySelector('.close-feedback-modal') : null;

    if (feedbackModal && feedbackModalContent && closeFeedbackBtn) {
        // Abrir modal al hacer clic en el indicador
        document.body.addEventListener('click', function(event) {
            const indicator = event.target.closest('.feedback-indicator');
            if (indicator) {
                event.preventDefault(); 
                const pubId = indicator.dataset.publicacionId;
                feedbackModalContent.innerHTML = 'Cargando feedback...';
                feedbackModal.classList.add('show');

                fetch(`get_feedback.php?publicacion_id=${pubId}`) 
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            displayInternalFeedback(feedbackModalContent, data.feedback);
                        } else {
                            feedbackModalContent.innerHTML = '<p style="color: red;">Error al cargar feedback.</p>';
                        }
                    })
                    .catch(error => {
                         console.error('Error fetching internal feedback:', error);
                         feedbackModalContent.innerHTML = '<p style="color: red;">Error de conexión al cargar feedback.</p>';
                    });
            }
        });

        function closeFeedbackDisplayModal() {
            feedbackModal.classList.remove('show');
            setTimeout(() => { 
                if (!feedbackModal.classList.contains('show')) {
                   feedbackModalContent.innerHTML = 'Cargando feedback...'; 
                }
             }, 300);
        }
        closeFeedbackBtn.addEventListener('click', closeFeedbackDisplayModal);
        feedbackModal.addEventListener('click', function(event) {
            if (event.target === feedbackModal) {
                closeFeedbackDisplayModal();
            }
        });
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && feedbackModal.classList.contains('show')) {
                closeFeedbackDisplayModal();
            }
        });
    } // Fin Feedback Display Modal Logic

    // Función auxiliar para mostrar feedback (usada por el modal interno)
    function displayInternalFeedback(container, feedbackItems) {
        if (feedbackItems.length === 0) {
            container.innerHTML = '<p><i>No se ha recibido feedback para esta publicación.</i></p>';
            return;
        }
        let html = '<ul class="feedback-items-list">'; // Reusar clase de CSS si es apropiado
        feedbackItems.forEach(item => {
            html += `<li><strong>${item.created_at}:</strong> ${item.feedback_text}</li>`;
        });
        html += '</ul>';
        container.innerHTML = html;
    }
    
    // --- Toggle Published Posts Logic ---
    const toggleSwitch = document.getElementById('toggle-published');
    const publicacionesTableBody = document.querySelector('.table-container table tbody'); // Renamed for clarity

    function filterPublishedPosts() {
        if (!publicacionesTableBody) return; // Exit if table body not found

        const showPublished = toggleSwitch.checked;
        // Select all main publication rows (including those that might be published)
        const allMainRows = publicacionesTableBody.querySelectorAll('tr:not(.feedback-row)'); 

        allMainRows.forEach(row => {
            const isPublished = row.dataset.estado === 'publicado';
            let displayStyle = ''; // Default: show

            if (isPublished && !showPublished) {
                displayStyle = 'none'; // Hide if it's published and toggle is off
            }
            
            row.style.display = displayStyle;

            // Find the next sibling, which might be the feedback row
            const nextRow = row.nextElementSibling;
            if (nextRow && nextRow.classList.contains('feedback-row')) {
                // Apply the same display style to the feedback row
                nextRow.style.display = displayStyle;
            }
        });
    }

    if (toggleSwitch && publicacionesTableBody) { // Check for table body existence too
        // Add event listener
        toggleSwitch.addEventListener('change', filterPublishedPosts);

        // Initial filter on page load
        filterPublishedPosts(); 
    }
    // --- End Toggle Published Posts Logic ---
    
}); // Cierre del DOMContentLoaded principal 