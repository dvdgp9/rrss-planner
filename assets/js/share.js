document.addEventListener('DOMContentLoaded', function() {
    const shareModal = document.getElementById('shareModal');
    const closeBtn = document.querySelector('.close-share-modal');
    const shareLinkInput = document.getElementById('shareLinkInput');
    const copyBtn = document.getElementById('copyShareLinkBtn');
    const copyMessage = document.getElementById('copyMessage');
    const shareError = document.getElementById('shareError');

    // Abrir modal al hacer clic en "Compartir"
    document.querySelectorAll('.btn-share').forEach(button => {
        button.addEventListener('click', function() {
            const lineaId = this.dataset.lineaId;
            shareModal.style.display = 'flex';
            shareLinkInput.value = 'Generando enlace...'; // Placeholder
            copyMessage.style.display = 'none';
            shareError.style.display = 'none';
            copyBtn.disabled = true;

            // Llamada AJAX para generar el enlace
            fetch('generate_share_link.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest' // Identificar como AJAX
                },
                body: 'linea_id=' + encodeURIComponent(lineaId)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    shareLinkInput.value = data.share_url;
                    copyBtn.disabled = false;
                } else {
                    shareLinkInput.value = '';
                    shareError.textContent = data.message || 'Error desconocido al generar enlace.';
                    shareError.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error en fetch:', error);
                shareLinkInput.value = '';
                shareError.textContent = 'Error de conexión al generar el enlace.';
                shareError.style.display = 'block';
            });
        });
    });

    // Cerrar modal
    closeBtn.addEventListener('click', function() {
        shareModal.style.display = 'none';
    });

    // Cerrar modal al hacer clic fuera
    window.addEventListener('click', function(event) {
        if (event.target == shareModal) {
            shareModal.style.display = 'none';
        }
    });

    // Copiar enlace al portapapeles
    copyBtn.addEventListener('click', function() {
        shareLinkInput.select();
        shareLinkInput.setSelectionRange(0, 99999); // Para móviles
        
        navigator.clipboard.writeText(shareLinkInput.value).then(() => {
            copyMessage.style.display = 'block';
            setTimeout(() => { copyMessage.style.display = 'none'; }, 2000); // Ocultar mensaje después de 2s
        }).catch(err => {
            console.error('Error al copiar:', err);
            // Fallback por si navigator.clipboard no funciona (http o navegadores viejos)
            try {
                document.execCommand('copy');
                copyMessage.style.display = 'block';
                setTimeout(() => { copyMessage.style.display = 'none'; }, 2000);
            } catch (execErr) {
                console.error('Fallback copy failed:', execErr);
                alert('No se pudo copiar el enlace. Por favor, cópialo manualmente.');
            }
        });
    });
}); 