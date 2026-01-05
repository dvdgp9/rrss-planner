/**
 * Feed Simulator - Social Media Preview
 * Previsualización en tiempo real de posts en Instagram, Facebook y LinkedIn
 */

class FeedSimulator {
    constructor() {
        this.modal = null;
        this.currentPlatform = 'instagram';
        this.postData = {};
        this.init();
    }
    
    init() {
        this.createModal();
        this.bindEvents();
    }
    
    createModal() {
        const modal = document.createElement('div');
        modal.id = 'feed-simulator-modal';
        modal.className = 'feed-simulator-modal';
        modal.innerHTML = `
            <div class="feed-simulator-container">
                <div class="feed-simulator-header">
                    <h2><i class="fas fa-mobile-alt"></i> Vista Previa del Post</h2>
                    <button class="feed-simulator-close" aria-label="Cerrar">×</button>
                </div>
                
                <div class="feed-simulator-body">
                    <!-- Platform Tabs -->
                    <div class="platform-tabs">
                        <button class="platform-tab active" data-platform="instagram">
                            <i class="fab fa-instagram"></i>
                            <span>Instagram</span>
                        </button>
                        <button class="platform-tab" data-platform="facebook">
                            <i class="fab fa-facebook"></i>
                            <span>Facebook</span>
                        </button>
                        <button class="platform-tab" data-platform="linkedin">
                            <i class="fab fa-linkedin"></i>
                            <span>LinkedIn</span>
                        </button>
                    </div>
                    
                    <!-- Character Counter -->
                    <div class="char-counter-bar">
                        <div class="char-counter-fill"></div>
                        <span class="char-counter-text">0 / 2200</span>
                    </div>
                    
                    <!-- Preview Container -->
                    <div class="preview-phone-frame">
                        <div class="phone-notch"></div>
                        <div class="preview-content">
                            <!-- Instagram Preview -->
                            <div class="platform-preview instagram-preview active" data-platform="instagram">
                                <div class="ig-header">
                                    <div class="ig-avatar"></div>
                                    <div class="ig-user-info">
                                        <span class="ig-username">tu_cuenta</span>
                                        <span class="ig-location">Ubicación</span>
                                    </div>
                                    <div class="ig-more">•••</div>
                                </div>
                                <div class="ig-image-container">
                                    <div class="ig-image-placeholder">
                                        <i class="fas fa-image"></i>
                                        <span>Imagen del post</span>
                                    </div>
                                    <img class="ig-image" src="" alt="" style="display: none;">
                                </div>
                                <div class="ig-actions">
                                    <div class="ig-actions-left">
                                        <i class="far fa-heart"></i>
                                        <i class="far fa-comment"></i>
                                        <i class="far fa-paper-plane"></i>
                                    </div>
                                    <i class="far fa-bookmark"></i>
                                </div>
                                <div class="ig-likes">1,234 Me gusta</div>
                                <div class="ig-caption">
                                    <span class="ig-username-caption">tu_cuenta</span>
                                    <span class="ig-caption-text"></span>
                                </div>
                                <div class="ig-hashtags"></div>
                                <div class="ig-time">HACE 2 HORAS</div>
                            </div>
                            
                            <!-- Facebook Preview -->
                            <div class="platform-preview facebook-preview" data-platform="facebook">
                                <div class="fb-header">
                                    <div class="fb-avatar"></div>
                                    <div class="fb-user-info">
                                        <span class="fb-username">Tu Página</span>
                                        <span class="fb-time">Ahora · <i class="fas fa-globe-americas"></i></span>
                                    </div>
                                    <div class="fb-more">•••</div>
                                </div>
                                <div class="fb-content">
                                    <p class="fb-text"></p>
                                </div>
                                <div class="fb-image-container">
                                    <div class="fb-image-placeholder">
                                        <i class="fas fa-image"></i>
                                        <span>Imagen del post</span>
                                    </div>
                                    <img class="fb-image" src="" alt="" style="display: none;">
                                </div>
                                <div class="fb-stats">
                                    <span><i class="fas fa-thumbs-up"></i> 45</span>
                                    <span>12 comentarios · 5 compartidos</span>
                                </div>
                                <div class="fb-actions">
                                    <button><i class="far fa-thumbs-up"></i> Me gusta</button>
                                    <button><i class="far fa-comment"></i> Comentar</button>
                                    <button><i class="far fa-share-square"></i> Compartir</button>
                                </div>
                            </div>
                            
                            <!-- LinkedIn Preview -->
                            <div class="platform-preview linkedin-preview" data-platform="linkedin">
                                <div class="li-header">
                                    <div class="li-avatar"></div>
                                    <div class="li-user-info">
                                        <span class="li-username">Tu Empresa</span>
                                        <span class="li-followers">1,234 seguidores</span>
                                        <span class="li-time">Ahora · <i class="fas fa-globe-americas"></i></span>
                                    </div>
                                    <button class="li-follow">+ Seguir</button>
                                </div>
                                <div class="li-content">
                                    <p class="li-text"></p>
                                </div>
                                <div class="li-image-container">
                                    <div class="li-image-placeholder">
                                        <i class="fas fa-image"></i>
                                        <span>Imagen del post</span>
                                    </div>
                                    <img class="li-image" src="" alt="" style="display: none;">
                                </div>
                                <div class="li-stats">
                                    <span><i class="fas fa-thumbs-up"></i> 89 reacciones</span>
                                    <span>23 comentarios</span>
                                </div>
                                <div class="li-actions">
                                    <button><i class="far fa-thumbs-up"></i> Recomendar</button>
                                    <button><i class="far fa-comment"></i> Comentar</button>
                                    <button><i class="fas fa-share"></i> Compartir</button>
                                    <button><i class="far fa-paper-plane"></i> Enviar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Validation Messages -->
                    <div class="preview-validations">
                        <div class="validation-item" data-type="chars">
                            <i class="fas fa-check-circle"></i>
                            <span>Longitud del texto</span>
                        </div>
                        <div class="validation-item" data-type="hashtags">
                            <i class="fas fa-check-circle"></i>
                            <span>Hashtags</span>
                        </div>
                        <div class="validation-item" data-type="image">
                            <i class="fas fa-check-circle"></i>
                            <span>Imagen</span>
                        </div>
                    </div>
                </div>
                
                <div class="feed-simulator-footer">
                    <button class="btn btn-secondary feed-simulator-close-btn">Cerrar</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        this.modal = modal;
    }
    
    bindEvents() {
        // Close button
        this.modal.querySelectorAll('.feed-simulator-close, .feed-simulator-close-btn').forEach(btn => {
            btn.addEventListener('click', () => this.close());
        });
        
        // Close on backdrop click
        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal) this.close();
        });
        
        // Platform tabs
        this.modal.querySelectorAll('.platform-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                const platform = tab.dataset.platform;
                this.switchPlatform(platform);
            });
        });
        
        // ESC to close
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.modal.classList.contains('show')) {
                this.close();
            }
        });
    }
    
    open(postData = {}) {
        this.postData = postData;
        this.updatePreview();
        this.modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
    
    close() {
        this.modal.classList.remove('show');
        document.body.style.overflow = '';
    }
    
    switchPlatform(platform) {
        this.currentPlatform = platform;
        
        // Update tabs
        this.modal.querySelectorAll('.platform-tab').forEach(tab => {
            tab.classList.toggle('active', tab.dataset.platform === platform);
        });
        
        // Update previews
        this.modal.querySelectorAll('.platform-preview').forEach(preview => {
            preview.classList.toggle('active', preview.dataset.platform === platform);
        });
        
        // Update character counter
        this.updateCharCounter();
        
        // Update validations
        this.updateValidations();
    }
    
    updatePreview() {
        const { contenido, imagen, redes, username } = this.postData;
        const text = contenido || '';
        const imageUrl = imagen || '';
        
        // Extract hashtags
        const hashtags = this.extractHashtags(text);
        const cleanText = this.removeHashtags(text);
        
        // Update all platform previews
        this.updateInstagramPreview(cleanText, hashtags, imageUrl, username);
        this.updateFacebookPreview(text, imageUrl, username);
        this.updateLinkedInPreview(text, imageUrl, username);
        
        // Update counters and validations
        this.updateCharCounter();
        this.updateValidations();
    }
    
    updateInstagramPreview(text, hashtags, imageUrl, username) {
        const preview = this.modal.querySelector('.instagram-preview');
        
        if (username) {
            preview.querySelector('.ig-username').textContent = username;
            preview.querySelector('.ig-username-caption').textContent = username;
        }
        
        preview.querySelector('.ig-caption-text').textContent = text;
        preview.querySelector('.ig-hashtags').textContent = hashtags.join(' ');
        
        this.updateImage(preview, '.ig-image', '.ig-image-placeholder', imageUrl);
    }
    
    updateFacebookPreview(text, imageUrl, username) {
        const preview = this.modal.querySelector('.facebook-preview');
        
        if (username) {
            preview.querySelector('.fb-username').textContent = username;
        }
        
        preview.querySelector('.fb-text').textContent = text;
        
        this.updateImage(preview, '.fb-image', '.fb-image-placeholder', imageUrl);
    }
    
    updateLinkedInPreview(text, imageUrl, username) {
        const preview = this.modal.querySelector('.linkedin-preview');
        
        if (username) {
            preview.querySelector('.li-username').textContent = username;
        }
        
        preview.querySelector('.li-text').textContent = text;
        
        this.updateImage(preview, '.li-image', '.li-image-placeholder', imageUrl);
    }
    
    updateImage(container, imgSelector, placeholderSelector, imageUrl) {
        const img = container.querySelector(imgSelector);
        const placeholder = container.querySelector(placeholderSelector);
        
        if (imageUrl) {
            img.src = imageUrl;
            img.style.display = 'block';
            placeholder.style.display = 'none';
            
            img.onerror = () => {
                img.style.display = 'none';
                placeholder.style.display = 'flex';
            };
        } else {
            img.style.display = 'none';
            placeholder.style.display = 'flex';
        }
    }
    
    updateCharCounter() {
        const text = this.postData.contenido || '';
        const length = text.length;
        
        const limits = {
            instagram: 2200,
            facebook: 63206,
            linkedin: 3000
        };
        
        const limit = limits[this.currentPlatform];
        const percentage = Math.min((length / limit) * 100, 100);
        
        const fill = this.modal.querySelector('.char-counter-fill');
        const counterText = this.modal.querySelector('.char-counter-text');
        
        fill.style.width = `${percentage}%`;
        counterText.textContent = `${length} / ${limit}`;
        
        // Color based on usage
        if (percentage > 90) {
            fill.style.backgroundColor = '#ef4444';
        } else if (percentage > 70) {
            fill.style.backgroundColor = '#f59e0b';
        } else {
            fill.style.backgroundColor = '#10b981';
        }
    }
    
    updateValidations() {
        const text = this.postData.contenido || '';
        const imageUrl = this.postData.imagen || '';
        const hashtags = this.extractHashtags(text);
        
        const validations = {
            chars: this.validateCharacters(text),
            hashtags: this.validateHashtags(hashtags),
            image: this.validateImage(imageUrl)
        };
        
        Object.entries(validations).forEach(([type, result]) => {
            const item = this.modal.querySelector(`.validation-item[data-type="${type}"]`);
            if (item) {
                item.className = `validation-item ${result.status}`;
                item.querySelector('i').className = result.status === 'success' 
                    ? 'fas fa-check-circle' 
                    : result.status === 'warning' 
                        ? 'fas fa-exclamation-triangle'
                        : 'fas fa-times-circle';
                item.querySelector('span').textContent = result.message;
            }
        });
    }
    
    validateCharacters(text) {
        const length = text.length;
        const limits = {
            instagram: { max: 2200, recommended: 125 },
            facebook: { max: 63206, recommended: 80 },
            linkedin: { max: 3000, recommended: 150 }
        };
        
        const { max, recommended } = limits[this.currentPlatform];
        
        if (length === 0) {
            return { status: 'error', message: 'El texto está vacío' };
        }
        if (length > max) {
            return { status: 'error', message: `Excede el límite (${length}/${max})` };
        }
        if (length > recommended * 2) {
            return { status: 'warning', message: `Texto largo (recomendado: ${recommended})` };
        }
        return { status: 'success', message: `Longitud óptima (${length} caracteres)` };
    }
    
    validateHashtags(hashtags) {
        const limits = {
            instagram: { max: 30, recommended: 11 },
            facebook: { max: 30, recommended: 3 },
            linkedin: { max: 30, recommended: 5 }
        };
        
        const { max, recommended } = limits[this.currentPlatform];
        const count = hashtags.length;
        
        if (count === 0 && this.currentPlatform === 'instagram') {
            return { status: 'warning', message: 'Sin hashtags (recomendado usar algunos)' };
        }
        if (count > max) {
            return { status: 'error', message: `Demasiados hashtags (${count}/${max})` };
        }
        if (count > recommended) {
            return { status: 'warning', message: `Muchos hashtags (${count}, recomendado: ${recommended})` };
        }
        return { status: 'success', message: `${count} hashtags (óptimo)` };
    }
    
    validateImage(imageUrl) {
        if (!imageUrl) {
            if (this.currentPlatform === 'instagram') {
                return { status: 'error', message: 'Instagram requiere imagen' };
            }
            return { status: 'warning', message: 'Sin imagen (recomendado incluir)' };
        }
        return { status: 'success', message: 'Imagen incluida' };
    }
    
    extractHashtags(text) {
        const matches = text.match(/#[\w\u00C0-\u024F]+/g);
        return matches || [];
    }
    
    removeHashtags(text) {
        return text.replace(/#[\w\u00C0-\u024F]+/g, '').trim();
    }
    
    // Método estático para previsualizar desde fuera
    static previewPost(postData) {
        if (!window.feedSimulatorInstance) {
            window.feedSimulatorInstance = new FeedSimulator();
        }
        window.feedSimulatorInstance.open(postData);
    }
    
    // Método para vincular con un textarea en tiempo real
    bindToForm(textareaSelector, imageInputSelector) {
        const textarea = document.querySelector(textareaSelector);
        const imageInput = document.querySelector(imageInputSelector);
        
        if (textarea) {
            textarea.addEventListener('input', () => {
                this.postData.contenido = textarea.value;
                this.updatePreview();
            });
        }
        
        if (imageInput) {
            imageInput.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (ev) => {
                        this.postData.imagen = ev.target.result;
                        this.updatePreview();
                    };
                    reader.readAsDataURL(file);
                }
            });
        }
    }
}

// Exportar globalmente
window.FeedSimulator = FeedSimulator;

// Auto-inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.feedSimulatorInstance = new FeedSimulator();
});
