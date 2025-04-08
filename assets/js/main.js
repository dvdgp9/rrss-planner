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
}); 

// --- Image Modal Logic ---
document.addEventListener('DOMContentLoaded', function() {
    const imageModal = document.getElementById('imageModal');
    const modalImage = document.getElementById('modalImageSrc');
    const closeImageBtn = document.querySelector('.close-image-modal'); // Puede haber solo uno

    if (imageModal && modalImage && closeImageBtn) {
        // Abrir modal al hacer clic en una miniatura
        document.body.addEventListener('click', function(event) {
            if (event.target.classList.contains('thumbnail')) {
                const imageSrc = event.target.src; // Asumimos que la URL completa está en src
                if(imageSrc) {
                    modalImage.src = imageSrc;
                    imageModal.classList.add('show');
                    // Opcional: prevenir scroll del body mientras el modal está abierto
                    // document.body.style.overflow = 'hidden';
                }
            }
        });

        // Función para cerrar el modal
        function closeModal() {
            imageModal.classList.remove('show');
            // Opcional: restaurar scroll del body
            // document.body.style.overflow = 'auto';
            // Esperar a que termine la animación de opacidad antes de limpiar src (opcional)
            setTimeout(() => { 
                if (!imageModal.classList.contains('show')) { // Doble check por si se reabre rápido
                    modalImage.src = ''; 
                }
            }, 300); // 300ms coincide con la transición de opacidad
        }

        // Cerrar con el botón X
        closeImageBtn.addEventListener('click', closeModal);

        // Cerrar al hacer clic fuera de la imagen
        imageModal.addEventListener('click', function(event) {
            if (event.target === imageModal) { // Si el clic fue en el fondo del modal
                closeModal();
            }
        });
        
        // Cerrar con la tecla Escape
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && imageModal.classList.contains('show')) {
                closeModal();
            }
        });
    }
}); 