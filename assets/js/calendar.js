/**
 * Editorial Calendar - FullCalendar Integration
 * Calendario interactivo con drag & drop para reprogramar publicaciones
 */

class EditorialCalendar {
    constructor(containerEl, options = {}) {
        this.container = typeof containerEl === 'string' 
            ? document.querySelector(containerEl) 
            : containerEl;
        
        this.options = {
            lineaId: options.lineaId || null,
            lineaSlug: options.lineaSlug || '',
            contentType: options.contentType || 'all',
            onEventClick: options.onEventClick || null,
            onDateChange: options.onDateChange || null,
            ...options
        };
        
        this.calendar = null;
        this.init();
    }
    
    init() {
        if (!this.container) {
            console.error('Calendar container not found');
            return;
        }
        
        this.calendar = new FullCalendar.Calendar(this.container, {
            initialView: 'dayGridMonth',
            locale: 'es',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,listWeek'
            },
            buttonText: {
                today: 'Hoy',
                month: 'Mes',
                week: 'Semana',
                list: 'Lista'
            },
            firstDay: 1, // Lunes
            height: 'auto',
            editable: true,
            droppable: true,
            eventStartEditable: true,
            eventDurationEditable: false,
            
            // Horario restringido para vistas de tiempo
            slotMinTime: '08:00:00',
            slotMaxTime: '20:00:00',
            
            // Estilos personalizados
            dayMaxEvents: 3,
            moreLinkText: 'más',
            
            // Fuente de eventos
            events: (info, successCallback, failureCallback) => {
                this.fetchEvents(info.start, info.end)
                    .then(events => successCallback(events))
                    .catch(err => {
                        console.error('Error fetching events:', err);
                        failureCallback(err);
                    });
            },
            
            // Click en evento
            eventClick: (info) => {
                this.handleEventClick(info);
            },
            
            // Drag & Drop
            eventDrop: (info) => {
                this.handleEventDrop(info);
            },
            
            // Click en día vacío (DESHABILITADO)
            /*
            dateClick: (info) => {
                this.handleDateClick(info);
            },
            */
            
            // Render personalizado de eventos
            eventDidMount: (info) => {
                this.customizeEventRender(info);
            },
            
            // Loading state
            loading: (isLoading) => {
                this.container.classList.toggle('calendar-loading', isLoading);
            }
        });
        
        this.calendar.render();
    }
    
    async fetchEvents(start, end) {
        const params = new URLSearchParams({
            linea_id: this.options.lineaId,
            start: start.toISOString().split('T')[0],
            end: end.toISOString().split('T')[0],
            type: this.options.contentType
        });
        
        const response = await fetch(`/api/calendar_events.php?${params}`);
        if (!response.ok) throw new Error('Failed to fetch events');
        return await response.json();
    }
    
    handleEventClick(info) {
        const props = info.event.extendedProps;
        
        // Mostrar popup con detalles
        this.showEventPopup(info.event, info.el);
        
        // Callback personalizado
        if (this.options.onEventClick) {
            this.options.onEventClick(info.event, props);
        }
    }
    
    async handleEventDrop(info) {
        const event = info.event;
        const props = event.extendedProps;
        const newDate = event.start.toISOString().split('T')[0];
        
        // Obtener el ID numérico real (quitar el prefijo social_ o blog_)
        const realId = event.id.replace(/^(social_|blog_)/, '');
        
        console.log('Dragging event:', {
            id: event.id,
            realId: realId,
            type: props.type,
            newDate: newDate
        });
        
        // Mostrar loading
        event.setProp('backgroundColor', '#9ca3af');
        
        try {
            const response = await fetch('/api/update_event_date.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    event_id: realId,
                    new_date: newDate,
                    type: props.type
                })
            });
            
            const result = await response.json();
            console.log('Update result:', result);
            
            if (result.success) {
                this.showToast(result.message || 'Fecha actualizada', 'success');
                // Restaurar color según estado
                this.updateEventColor(event, props.estado);
                
                if (this.options.onDateChange) {
                    this.options.onDateChange(event, newDate);
                }
            } else {
                console.error('Update failed:', result.error);
                info.revert();
                this.showToast(result.error || 'Error al actualizar', 'error');
            }
        } catch (err) {
            console.error('Error updating date:', err);
            info.revert();
            this.showToast('Error de conexión', 'error');
        }
    }
    
    handleDateClick(info) {
        // Permitir crear nuevo evento en esa fecha
        const dateStr = info.dateStr;
        const type = this.options.contentType === 'blog' ? 'blog' : 'social';
        
        if (type === 'social') {
            window.location.href = `/publicacion_form.php?linea_id=${this.options.lineaId}&linea_slug=${this.options.lineaSlug}&fecha=${dateStr}`;
        } else {
            window.location.href = `/blog_form.php?linea_id=${this.options.lineaId}&linea_slug=${this.options.lineaSlug}&fecha=${dateStr}`;
        }
    }
    
    customizeEventRender(info) {
        const props = info.event.extendedProps;
        const el = info.el;
        
        // Añadir tooltip
        el.setAttribute('title', this.getEventTooltip(props));
        
        // Añadir icono según tipo
        if (props.type === 'blog') {
            el.classList.add('calendar-event-blog');
        } else {
            el.classList.add('calendar-event-social');
        }
        
        // Añadir indicador de imagen
        if (props.imagen) {
            el.classList.add('has-image');
        }
    }
    
    getEventTooltip(props) {
        if (props.type === 'blog') {
            return `📝 ${props.titulo}\n${props.estado}`;
        }
        return `${props.contenido?.substring(0, 100)}...\nRedes: ${props.redes || 'N/A'}\nEstado: ${props.estado}`;
    }
    
    showEventPopup(event, targetEl) {
        const props = event.extendedProps;
        
        // Remover popup anterior si existe
        const existing = document.querySelector('.calendar-event-popup');
        if (existing) existing.remove();
        
        const popup = document.createElement('div');
        popup.className = 'calendar-event-popup';
        
        const isBlog = props.type === 'blog';
        const editUrl = isBlog 
            ? `/blog_form.php?id=${props.realId}&linea_slug=${this.options.lineaSlug}`
            : `/publicacion_form.php?id=${props.realId}&linea_slug=${this.options.lineaSlug}&linea_id=${this.options.lineaId}`;
        
        popup.innerHTML = `
            <div class="popup-header">
                <span class="popup-type-badge ${props.type}">${isBlog ? '📝 Blog' : '📱 Social'}</span>
                <button class="popup-close" onclick="this.closest('.calendar-event-popup').remove()">×</button>
            </div>
            <div class="popup-content">
                ${props.imagen ? `<img src="${props.imagen}" alt="" class="popup-image">` : ''}
                <div class="popup-text">
                    ${isBlog 
                        ? `<h4>${props.titulo}</h4><p>${props.excerpt || ''}</p>`
                        : `<p>${props.contenido?.substring(0, 150)}${props.contenido?.length > 150 ? '...' : ''}</p>`
                    }
                </div>
                <div class="popup-meta">
                    <span class="popup-status ${props.estado}">${this.getStatusLabel(props.estado)}</span>
                    ${!isBlog && props.redes ? `<span class="popup-redes">${props.redes}</span>` : ''}
                </div>
            </div>
            <div class="popup-actions">
                <a href="${editUrl}" class="btn btn-sm btn-primary">
                    <i class="fas fa-edit"></i> Editar
                </a>
                <button class="btn btn-sm btn-secondary popup-preview-btn" data-type="${props.type}" data-id="${props.realId}">
                    <i class="fas fa-eye"></i> Preview
                </button>
            </div>
        `;
        
        document.body.appendChild(popup);
        
        // Posicionar popup
        const rect = targetEl.getBoundingClientRect();
        popup.style.top = `${rect.bottom + window.scrollY + 8}px`;
        popup.style.left = `${rect.left + window.scrollX}px`;
        
        // Ajustar si sale de pantalla
        const popupRect = popup.getBoundingClientRect();
        if (popupRect.right > window.innerWidth) {
            popup.style.left = `${window.innerWidth - popupRect.width - 16}px`;
        }
        
        // Event listener para preview
        popup.querySelector('.popup-preview-btn')?.addEventListener('click', (e) => {
            const btn = e.currentTarget;
            if (btn.dataset.type === 'social' && window.FeedSimulator) {
                window.FeedSimulator.previewPost(props);
            }
            popup.remove();
        });
        
        // Cerrar al hacer click fuera
        setTimeout(() => {
            document.addEventListener('click', function closePopup(e) {
                if (!popup.contains(e.target) && !targetEl.contains(e.target)) {
                    popup.remove();
                    document.removeEventListener('click', closePopup);
                }
            });
        }, 100);
    }
    
    getStatusLabel(estado) {
        const labels = {
            'borrador': 'Borrador',
            'programado': 'Programado',
            'publicado': 'Publicado',
            'draft': 'Borrador',
            'scheduled': 'Programado',
            'publish': 'Publicado'
        };
        return labels[estado] || estado;
    }
    
    updateEventColor(event, estado) {
        const colors = {
            'borrador': '#9ca3af',
            'programado': '#f59e0b',
            'publicado': '#10b981',
            'draft': '#9ca3af',
            'scheduled': '#f59e0b',
            'publish': '#10b981'
        };
        const color = colors[estado] || '#6b7280';
        event.setProp('backgroundColor', color);
        event.setProp('borderColor', color);
    }
    
    showToast(message, type = 'info') {
        if (window.showToast) {
            window.showToast(message, type);
        } else {
            alert(message);
        }
    }
    
    // Métodos públicos
    refresh() {
        this.calendar.refetchEvents();
    }
    
    goToDate(date) {
        this.calendar.gotoDate(date);
    }
    
    changeView(view) {
        this.calendar.changeView(view);
    }
    
    setContentType(type) {
        this.options.contentType = type;
        this.refresh();
    }
    
    destroy() {
        if (this.calendar) {
            this.calendar.destroy();
        }
    }
}

// Exportar globalmente
window.EditorialCalendar = EditorialCalendar;
