// =================== TOAST NOTIFICATIONS SYSTEM ===================

// Create toast container if it doesn't exist
function createToastContainer() {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    return container;
}

// Show toast notification
function showToast(message, type = 'info', duration = 5000, title = null) {
    const container = createToastContainer();
    
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    // Define icons for different types
    const icons = {
        success: '✓',
        error: '✕',
        warning: '⚠',
        info: 'ℹ'
    };
    
    // Define default titles
    const defaultTitles = {
        success: 'Correcto',
        error: 'Error',
        warning: 'Advertencia',
        info: 'Información'
    };
    
    const toastTitle = title || defaultTitles[type];
    
    toast.innerHTML = `
        <div class="toast-icon">${icons[type]}</div>
        <div class="toast-content">
            <div class="toast-title">${toastTitle}</div>
            <div class="toast-message">${message}</div>
        </div>
        <button class="toast-close" onclick="closeToast(this.parentElement)">×</button>
        <div class="toast-progress" style="width: 100%"></div>
    `;
    
    // Add to container
    container.appendChild(toast);
    
    // Trigger show animation
    setTimeout(() => {
        toast.classList.add('show');
    }, 100);
    
    // Auto-close functionality
    if (duration > 0) {
        const progressBar = toast.querySelector('.toast-progress');
        if (progressBar) {
            progressBar.style.transition = `width ${duration}ms linear`;
            setTimeout(() => {
                progressBar.style.width = '0%';
            }, 100);
        }
        
        setTimeout(() => {
            closeToast(toast);
        }, duration);
    }
    
    return toast;
}

// Close toast
function closeToast(toast) {
    toast.classList.add('hide');
    toast.classList.remove('show');
    
    setTimeout(() => {
        if (toast.parentElement) {
            toast.parentElement.removeChild(toast);
        }
    }, 400);
}

// Helper functions for specific toast types
window.showSuccessToast = function(message, duration = 4000) {
    return showToast(message, 'success', duration);
};

window.showErrorToast = function(message, duration = 6000) {
    return showToast(message, 'error', duration);
};

window.showWarningToast = function(message, duration = 5000) {
    return showToast(message, 'warning', duration);
};

window.showInfoToast = function(message, duration = 4000) {
    return showToast(message, 'info', duration);
};

// Handle JSON responses from AJAX calls
window.handleAjaxResponse = function(response) {
    if (typeof response === 'string') {
        try {
            response = JSON.parse(response);
        } catch (e) {
            console.error('Error parsing JSON response:', e);
            return;
        }
    }
    
    if (response.success) {
        showSuccessToast(response.message || 'Operación completada correctamente');
    } else {
        showErrorToast(response.message || 'Ha ocurrido un error');
    }
};

// Handle PHP session messages (for pages that reload)
window.handleSessionMessage = function(type, message) {
    if (!message) return;
    
    const typeMap = {
        'success': 'success',
        'error': 'error',
        'warning': 'warning',
        'info': 'info'
    };
    
    const toastType = typeMap[type] || 'info';
    showToast(message, toastType);
};

// =================== END TOAST NOTIFICATIONS SYSTEM ===================

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
    
    // Confirmación para eliminación (solo para publicaciones RRSS, no blog posts)
    const deleteButtons = document.querySelectorAll('.action-btn.delete');
    if (deleteButtons.length) {
        deleteButtons.forEach(button => {
            // Excluir botones de blog posts que ya tienen su propia confirmación
            if (!button.onclick || !button.onclick.toString().includes('deleteBlogPost')) {
                button.addEventListener('click', function(e) {
                    if (!confirm('¿Estás seguro que deseas eliminar esta publicación?')) {
                        e.preventDefault();
                    }
                });
            }
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

    // Initialize modern status selectors
    initStatusSelectors();

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
    
    // --- JavaScript for Nueva Línea de Negocio Modal (Consolidado) ---
    const modalNuevaLinea = document.getElementById('modalNuevaLinea');
    const btnOpenModalNuevaLinea = document.getElementById('btnNuevaLinea');
    const btnCloseModalNuevaLinea = document.getElementById('closeNuevaLineaModal');
    const formNuevaLinea = document.getElementById('formNuevaLinea');
    const messageAreaNuevaLinea = document.getElementById('modalNuevaLineaMessage');

    if (btnOpenModalNuevaLinea) {
        console.log('Boton "Nueva Línea" encontrado:', btnOpenModalNuevaLinea);
        btnOpenModalNuevaLinea.onclick = function() {
            console.log('Boton "Nueva Línea" CLICADO!');
            if (modalNuevaLinea) {
                console.log('Modal encontrado, aplicando .show:', modalNuevaLinea);
                modalNuevaLinea.classList.add('show');
            } else {
                console.error('Modal "modalNuevaLinea" NO encontrado!');
            }
            if (messageAreaNuevaLinea) messageAreaNuevaLinea.textContent = '';
            if (formNuevaLinea) formNuevaLinea.reset();
        }
    } else {
        console.error('Boton "btnNuevaLinea" NO encontrado!');
    }

    if (btnCloseModalNuevaLinea) {
        btnCloseModalNuevaLinea.onclick = function() {
            if (modalNuevaLinea) {
                modalNuevaLinea.classList.remove('show');
            }
        }
    }

    window.addEventListener('click', function(event) {
        if (event.target == modalNuevaLinea) {
            if (modalNuevaLinea) {
                modalNuevaLinea.classList.remove('show');
            }
        }
    });
    
    if (formNuevaLinea) {
        formNuevaLinea.addEventListener('submit', function(e) {
            e.preventDefault();
            if (messageAreaNuevaLinea) messageAreaNuevaLinea.textContent = '';

            const formData = new FormData(formNuevaLinea);
            const slugInput = document.getElementById('slugLinea');

            if (slugInput && slugInput.value && !/^[a-z0-9-]+$/.test(slugInput.value)) {
                if (messageAreaNuevaLinea) messageAreaNuevaLinea.innerHTML = '<span style=\"color: red;\">El slug solo puede contener minúsculas, números y guiones.</span>';
                return;
            }

            fetch('crear_linea_negocio.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (messageAreaNuevaLinea) messageAreaNuevaLinea.innerHTML = '<span style=\"color: green;\">' + data.message + '</span>';
                    if (formNuevaLinea) formNuevaLinea.reset();
                    setTimeout(() => {
                        if (modalNuevaLinea) {
                            modalNuevaLinea.classList.remove('show');
                        }
                    }, 2000);
                } else {
                    if (messageAreaNuevaLinea) messageAreaNuevaLinea.innerHTML = '<span style=\"color: red;\">Error: ' + (data.message || 'No se pudo crear la línea de negocio.') + '</span>';
                }
            })
            .catch(error => {
                console.error('Error en fetch para crear línea:', error);
                if (messageAreaNuevaLinea) messageAreaNuevaLinea.innerHTML = '<span style=\"color: red;\">Ocurrió un error de conexión. Inténtalo de nuevo.</span>';
            });
        });
    }
    // --- End JavaScript for Nueva Línea de Negocio Modal (Consolidado) ---
    
    // --- Enhanced Header: Business Line Dropdown Functionality ---
    const businessLineDropdown = document.getElementById('businessLineDropdown');
    const businessLineDropdownMenu = document.getElementById('businessLineDropdownMenu');
    
    if (businessLineDropdown && businessLineDropdownMenu) {
        // Toggle dropdown on click
        businessLineDropdown.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const isOpen = businessLineDropdownMenu.classList.contains('show');
            
            // Close all other dropdowns first (if any)
            document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                menu.classList.remove('show');
            });
            document.querySelectorAll('.dropdown-toggle.active').forEach(toggle => {
                toggle.classList.remove('active');
            });
            
            // Toggle current dropdown
            if (!isOpen) {
                businessLineDropdownMenu.classList.add('show');
                businessLineDropdown.classList.add('active');
            }
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!businessLineDropdown.contains(e.target) && !businessLineDropdownMenu.contains(e.target)) {
                businessLineDropdownMenu.classList.remove('show');
                businessLineDropdown.classList.remove('active');
            }
        });
        
        // Close dropdown on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                businessLineDropdownMenu.classList.remove('show');
                businessLineDropdown.classList.remove('active');
            }
        });
        
        // Handle keyboard navigation in dropdown
        businessLineDropdown.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                businessLineDropdown.click();
            } else if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (!businessLineDropdownMenu.classList.contains('show')) {
                    businessLineDropdown.click();
                } else {
                    const firstItem = businessLineDropdownMenu.querySelector('.dropdown-item');
                    if (firstItem) firstItem.focus();
                }
            }
        });
        
        // Handle keyboard navigation within dropdown items
        businessLineDropdownMenu.addEventListener('keydown', function(e) {
            const items = businessLineDropdownMenu.querySelectorAll('.dropdown-item');
            const currentIndex = Array.from(items).findIndex(item => item === document.activeElement);
            
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                const nextIndex = (currentIndex + 1) % items.length;
                items[nextIndex].focus();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                const prevIndex = currentIndex > 0 ? currentIndex - 1 : items.length - 1;
                items[prevIndex].focus();
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (document.activeElement && document.activeElement.classList.contains('dropdown-item')) {
                    document.activeElement.click();
                }
            }
        });
    }
    
    // --- End Enhanced Header Functionality ---
    
    // OLD STATUS SELECTOR CODE REMOVED - Now using modern status selector component
    // The estado-selector-directo elements are now handled by initStatusSelectors() function
}); // Cierre del DOMContentLoaded principal 


// --- Logic for Share Single Publication Modal ---
document.addEventListener('DOMContentLoaded', function() { // New DOMContentLoaded for this specific logic, or could be merged
    const sharePublicationModal = document.getElementById('sharePublicationModal');
    const sharePublicationLinkInput = document.getElementById('sharePublicationLinkInput');
    const copySharePublicationLinkBtn = document.getElementById('copySharePublicationLinkBtn');
    const copyPublicationMessage = document.getElementById('copyPublicationMessage');
    const sharePublicationError = document.getElementById('sharePublicationError');

    document.body.addEventListener('click', function(event) {
        const sharePubButton = event.target.closest('.share-publication');
        if (sharePubButton) {
            const publicacionId = sharePubButton.dataset.publicacionId;
            if (!publicacionId) {
                console.error('No publicacion_id found on share button.');
                if(sharePublicationError) sharePublicationError.textContent = 'Error: No se encontró ID de publicación.';
                if(sharePublicationModal) sharePublicationModal.classList.add('show'); // Show modal to display error
                return;
            }

            if(sharePublicationLinkInput) sharePublicationLinkInput.value = 'Generando enlace...';
            if(copyPublicationMessage) copyPublicationMessage.style.display = 'none';
            if(sharePublicationError) sharePublicationError.style.display = 'none';
            if(sharePublicationModal) sharePublicationModal.classList.add('show');

            const formData = new FormData();
            formData.append('publicacion_id', publicacionId);

            fetch('generate_single_pub_link.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.share_url) {
                    if(sharePublicationLinkInput) sharePublicationLinkInput.value = data.share_url;
                } else {
                    if(sharePublicationLinkInput) sharePublicationLinkInput.value = '';
                    if(sharePublicationError) {
                        sharePublicationError.textContent = data.message || 'Error al generar el enlace.';
                        sharePublicationError.style.display = 'block';
                    }
                }
            })
            .catch(error => {
                console.error('Error fetching single publication share link:', error);
                if(sharePublicationLinkInput) sharePublicationLinkInput.value = '';
                if(sharePublicationError) {
                    sharePublicationError.textContent = 'Error de conexión al generar el enlace.';
                    sharePublicationError.style.display = 'block';
                }
            });
        }
    });

    if (copySharePublicationLinkBtn && sharePublicationLinkInput) {
        copySharePublicationLinkBtn.addEventListener('click', function() {
            sharePublicationLinkInput.select();
            sharePublicationLinkInput.setSelectionRange(0, 99999); // For mobile devices
            
            try {
                document.execCommand('copy');
                if(copyPublicationMessage) {
                    copyPublicationMessage.textContent = '¡Enlace copiado!';
                    copyPublicationMessage.style.display = 'block';
                }
                if(sharePublicationError) sharePublicationError.style.display = 'none';
                
                setTimeout(() => {
                    if(copyPublicationMessage) copyPublicationMessage.style.display = 'none';
                }, 2000);
            } catch (err) {
                console.error('Error al copiar el enlace:', err);
                if(sharePublicationError) {
                    sharePublicationError.textContent = 'No se pudo copiar el enlace. Inténtalo manualmente.';
                    sharePublicationError.style.display = 'block';
                }
                if(copyPublicationMessage) copyPublicationMessage.style.display = 'none';
            }
        });
    }

    // Generic close for share modals (could be refactored if more modals are added)
    // This assumes all share modals have a span with class 'close-share-modal' and data-modal-id
    document.querySelectorAll('.close-share-modal').forEach(closeBtn => {
        closeBtn.addEventListener('click', function() {
            const modalId = this.dataset.modalId;
            if (modalId) {
                const modalToClose = document.getElementById(modalId);
                if (modalToClose) {
                    modalToClose.classList.remove('show');
                }
            } else if (this.closest('.modal-share')) { // Fallback for older modals without data-modal-id
                 this.closest('.modal-share').classList.remove('show');
            }
        });
    });

    // Close modal on clicking outside of it
    window.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal-share')) {
            event.target.classList.remove('show');
        }
    });
}); 

// --- Blog Posts Functionality ---
document.addEventListener('DOMContentLoaded', function() {
    // Toggle Published Blog Posts Logic
    const toggleSwitchBlog = document.getElementById('toggle-published-blog');
    const blogTableBody = document.querySelector('.table-container table tbody');

    function filterPublishedBlogPosts() {
        if (!blogTableBody || !toggleSwitchBlog) return;

        const showPublished = toggleSwitchBlog.checked;
        const allBlogRows = blogTableBody.querySelectorAll('tr[data-estado]');

        allBlogRows.forEach(row => {
            const isPublished = row.dataset.estado === 'published';
            let displayStyle = '';

            if (isPublished && !showPublished) {
                displayStyle = 'none';
            }
            
            row.style.display = displayStyle;
        });
    }

    if (toggleSwitchBlog && blogTableBody) {
        toggleSwitchBlog.addEventListener('change', filterPublishedBlogPosts);
        filterPublishedBlogPosts(); // Initial filter on page load
    }

    // OLD BLOG STATUS SELECTOR CODE REMOVED - Now using modern status selector component
    // Blog post status updates are now handled by initStatusSelectors() function
});

// Global function for blog post deletion
function deleteBlogPost(blogPostId, lineaSlug) {
    if (confirm('¿Estás seguro de que quieres eliminar este blog post? Esta acción no se puede deshacer.')) {
        // Show loading toast
        showInfoToast('Eliminando blog post...', 0);
        
        const formData = new FormData();
        formData.append('id', blogPostId);
        formData.append('slug_redirect', lineaSlug);

        fetch('blog_delete.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSuccessToast('Blog post eliminado exitosamente');
                // Reload the page to reflect changes
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showErrorToast('Error al eliminar el blog post: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error al eliminar blog post:', error);
            showErrorToast('Error de conexión al eliminar el blog post');
        });
    }
}

// Global function for publishing blog posts to WordPress
function publishToWordPressFromTable(blogPostId) {
    if (!blogPostId) {
        showErrorToast('ID de blog post no válido');
        return;
    }
    
    if (!confirm('¿Publicar este blog post en WordPress?')) {
        return;
    }
    
    // Show loading toast
    showInfoToast('Publicando en WordPress...', 0);
    
    // Find the WordPress button for this specific blog post
    const button = document.querySelector(`button[onclick="publishToWordPressFromTable(${blogPostId})"]`);
    
    if (button) {
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    }
    
    // Create FormData for the POST request
    const formData = new FormData();
    formData.append('blog_post_id', blogPostId);
    
    fetch('publish_to_wordpress.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessToast(data.message + (data.wp_url ? `\nURL: ${data.wp_url}` : ''), 8000);
            // Reload the page to show updated status
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            showErrorToast(data.message || 'Error al publicar en WordPress');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorToast('Error de conexión: ' + error.message);
    })
    .finally(() => {
        if (button) {
            button.disabled = false;
            button.innerHTML = '<i class="fab fa-wordpress"></i>';
        }
    });
}

// =============== MODERN STATUS SELECTOR FUNCTIONALITY ===============

// Initialize modern status selectors
function initStatusSelectors() {
    const selectors = document.querySelectorAll('.status-selector');
    
    selectors.forEach(selector => {
        const trigger = selector.querySelector('.status-selector-trigger');
        const dropdown = selector.querySelector('.status-selector-dropdown');
        const options = selector.querySelectorAll('.status-selector-option');
        
        if (!trigger || !dropdown) return;
        
        // Toggle dropdown
        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            
            // Close other open selectors FIRST
            document.querySelectorAll('.status-selector.open').forEach(otherSelector => {
                if (otherSelector !== selector) {
                    otherSelector.classList.remove('open');
                    // Reset any z-index issues
                    const parentRow = otherSelector.closest('tr');
                    if (parentRow) {
                        parentRow.style.zIndex = '';
                        parentRow.style.position = '';
                    }
                }
            });
            
            // Toggle current selector
            const isOpening = !selector.classList.contains('open');
            
            if (isOpening) {
                selector.classList.add('open');
                // Ensure parent row has proper z-index
                const parentRow = selector.closest('tr');
                if (parentRow) {
                    parentRow.style.zIndex = '9998';
                    parentRow.style.position = 'relative';
                }
            } else {
                selector.classList.remove('open');
                // Reset parent row z-index
                const parentRow = selector.closest('tr');
                if (parentRow) {
                    parentRow.style.zIndex = '';
                    parentRow.style.position = '';
                }
            }
        });
        
        // Handle option selection
        options.forEach(option => {
            option.addEventListener('click', (e) => {
                e.stopPropagation();
                
                const newStatus = option.dataset.status;
                const publicationId = selector.dataset.id;
                const lineaId = selector.dataset.lineaId;
                const isBlogs = selector.dataset.type === 'blog';
                
                // Close dropdown and reset z-index
                selector.classList.remove('open');
                const parentRow = selector.closest('tr');
                if (parentRow) {
                    parentRow.style.zIndex = '';
                    parentRow.style.position = '';
                }
                
                // Update status
                updateStatusModern(selector, publicationId, newStatus, lineaId, isBlogs);
            });
        });
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', () => {
        document.querySelectorAll('.status-selector.open').forEach(selector => {
            selector.classList.remove('open');
            // Reset parent row z-index
            const parentRow = selector.closest('tr');
            if (parentRow) {
                parentRow.style.zIndex = '';
                parentRow.style.position = '';
            }
        });
    });
    
    // Close dropdowns on scroll to prevent positioning issues
    document.addEventListener('scroll', () => {
        document.querySelectorAll('.status-selector.open').forEach(selector => {
            selector.classList.remove('open');
            const parentRow = selector.closest('tr');
            if (parentRow) {
                parentRow.style.zIndex = '';
                parentRow.style.position = '';
            }
        });
    });
}

// Update status with modern component
function updateStatusModern(selector, publicationId, newStatus, lineaId, isBlogs = false) {
    const trigger = selector.querySelector('.status-selector-trigger');
    const options = selector.querySelectorAll('.status-selector-option');
    
    if (!trigger) return;
    
    // Add loading state
    trigger.classList.add('loading');
    showInfoToast('Actualizando estado...', 2000);
    
    // Prepare form data
    const formData = new FormData();
    formData.append('id', publicationId);
    formData.append('estado', newStatus);
    formData.append('linea', lineaId);
    
    const endpoint = isBlogs ? 'blog_update_estado.php' : 'publicacion_update_estado.php';
    
    fetch(endpoint, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            return response.json().catch(() => { 
                throw new Error(response.statusText) 
            }).then(errData => { 
                throw errData; 
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Update UI
            updateStatusSelectorUI(selector, newStatus, data.estadoCapitalizado);
            
            // Update row data attribute
            const row = selector.closest('tr');
            if (row) {
                row.dataset.estado = newStatus;
            }
            
            // Success animation
            trigger.classList.add('success-flash');
            setTimeout(() => {
                trigger.classList.remove('success-flash');
            }, 400);
            
            showSuccessToast(`Estado actualizado a: ${data.estadoCapitalizado}`);
        } else {
            showErrorToast('Error al actualizar estado: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error updating status:', error);
        showErrorToast('Error al actualizar estado: ' + (error.message || 'Error desconocido'));
    })
    .finally(() => {
        // Remove loading state
        trigger.classList.remove('loading');
    });
}

// Update status selector UI
function updateStatusSelectorUI(selector, newStatus, displayText) {
    const trigger = selector.querySelector('.status-selector-trigger');
    const options = selector.querySelectorAll('.status-selector-option');
    
    if (!trigger) return;
    
    // Update trigger appearance
    trigger.className = `status-selector-trigger ${newStatus}`;
    trigger.querySelector('.status-text').textContent = displayText;
    
    // Update active option
    options.forEach(option => {
        if (option.dataset.status === newStatus) {
            option.classList.add('active');
        } else {
            option.classList.remove('active');
        }
    });
}

// Generate modern status selector HTML
function createStatusSelectorHTML(publicationId, currentStatus, lineaId, isBlogs = false) {
    const statusConfig = {
        // For RRSS publications
        'borrador': { label: 'Borrador', icon: '📝' },
        'programado': { label: 'Programado', icon: '📅' },
        'publicado': { label: 'Publicado', icon: '✅' },
        // For blog posts
        'draft': { label: 'Borrador', icon: '📝' },
        'scheduled': { label: 'Programado', icon: '📅' },
        'publish': { label: 'Publicado', icon: '✅' }
    };
    
    const currentConfig = statusConfig[currentStatus];
    if (!currentConfig) return '';
    
    const allStatuses = isBlogs 
        ? ['draft', 'scheduled', 'publish']  
        : ['borrador', 'programado', 'publicado'];
    
    const typeAttr = isBlogs ? 'data-type="blog"' : '';
    
    let html = `
        <div class="status-selector" data-id="${publicationId}" data-linea-id="${lineaId}" ${typeAttr}>
            <button class="status-selector-trigger ${currentStatus}">
                <span class="status-text">${currentConfig.label}</span>
                <span class="status-selector-arrow">▼</span>
            </button>
            <div class="status-selector-dropdown">
    `;
    
    allStatuses.forEach(status => {
        const config = statusConfig[status];
        const isActive = status === currentStatus ? 'active' : '';
        
        html += `
            <button class="status-selector-option ${isActive}" data-status="${status}">
                <span class="status-icon ${status}">${config.icon}</span>
                <span>${config.label}</span>
            </button>
        `;
    });
    
    html += `
            </div>
        </div>
    `;
    
    return html;
}

// =============== END MODERN STATUS SELECTOR FUNCTIONALITY ===============