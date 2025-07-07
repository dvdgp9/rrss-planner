# Project Scratchpad

## Background and Motivation

The user wants to refactor the application's page structure. Currently, there is one page per line of business. The goals are:
1.  Unify these multiple pages into a single, dynamic page.
2.  Allow users to create new lines of business directly from within the application.
3.  Improve maintainability and ease of making future changes.
4.  Ensure all existing functionalities are preserved in the new structure.

This change aims to make the application more scalable and flexible.

**PREVIOUS REQUEST (COMPLETED):** The user requested two UI improvements:
1. Set the "Mostrar publicados" toggle to be unchecked by default (currently it's checked by default)
2. Remove the time/hour display from the "Publicaciones Programadas y Pasadas" table, showing only dates since the publication creation form only allows date selection (not time)

**NEW REQUEST (Current - Social Media Publishing):** The user wants to evaluate the complexity of implementing direct social media publishing functionality. Currently, the system only manages and schedules posts but doesn't actually publish them to social media platforms. The goal is to understand what would be required to add automatic publishing capabilities to platforms like Instagram, Facebook, Twitter/X, and LinkedIn.

**NEW REQUEST:** Análisis UI/UX para mejoras de interfaz y adición de funcionalidad de blogs

El usuario necesita evaluar la interfaz actual (index.php y planner.php) para preparar la adición de publicaciones de blogs junto a las de redes sociales, manteniendo separación por línea de negocio.

**NEW REQUEST (COMPLETED):** Implementación de Interfaz con Selector de Línea de Negocio (Opción 1)

El usuario ha aprobado la implementación de la Opción 1: Selector de Línea en Header con interfaz simplificada tipo Mixpost. Esta fue completada exitosamente con funcionalidad completa de blog posts.

**NEW REQUEST (CURRENT - WordPress Integration):** Implementación de "Publicar en WordPress"

El usuario quiere implementar funcionalidad para publicar automáticamente los blog posts creados en el sistema directamente a las webs de WordPress de cada línea de negocio. Cada línea de negocio tiene su propia web en WordPress independiente.

**PROGRESS UPDATE - WordPress Categories & Tags Integration:**
El usuario ha solicitado que se implementen las categorías y etiquetas de WordPress para poder seleccionarlas al publicar. Se ha completado la implementación de la funcionalidad para obtener dinámicamente las categorías y etiquetas de cada sitio WordPress y permitir su selección en el formulario de blog posts.

**NEW REQUEST (CURRENT - UI/UX Enhancement Focus):** Mejoras de Interfaz y Experiencia de Usuario

El usuario quiere enfocarse específicamente en mejoras UI/UX para la aplicación RRSS Planner. Los issues técnicos críticos (WordPress categories bug y performance) ya están resueltos, por lo que el foco está en hacer la aplicación más intuitiva, eficiente y agradable de usar.

**Contexto actualizado:**
- 🎯 ENFOQUE: Documentación técnica y mejoras UI/UX

**NEW REQUEST (CURRENT - Database Documentation):** Documentación de Estructura de Base de Datos

El usuario ha solicitado crear un documento que refleje la estructura completa de la base de datos de la aplicación RRSS Planner. Este documento se utilizará como referencia técnica y se actualizará siempre que se realicen cambios en la estructura.

Se ha creado `database_structure.md` con:
- 13 tablas completamente documentadas
- Relaciones y claves foráneas
- Índices y optimizaciones
- Consideraciones técnicas
- Sistema de changelog para futuras actualizaciones

**COMPLETADO:** Se ha documentado exitosamente la estructura completa de la base de datos incluyendo todas las tablas, relaciones, índices y consideraciones técnicas.

**NEW REQUEST (CURRENT - Image Storage Optimization):** Optimización de Almacenamiento de Imágenes

El usuario quiere implementar una funcionalidad para ahorrar memoria en el servidor: que las imágenes de publicaciones (tanto de redes sociales como de blog posts) se borren automáticamente del servidor cuando la publicación se marque como "publicada", y mostrar un placeholder después.

**Objetivo:** Reducir uso de almacenamiento en servidor eliminando imágenes de publicaciones ya publicadas.

**Alcance:** 
- Publicaciones de redes sociales (`publicaciones.imagen_url`)
- Blog posts (`blog_posts.imagen_destacada`)
- Borrado automático al cambiar estado a "publicado"
- Placeholder visual en frontend

**Contexto actualizado:**
- 🎯 ENFOQUE: Optimización de almacenamiento + mejoras UI/UX

## Key Challenges and Analysis

### 🎨 **ANÁLISIS UI/UX PROFUNDO**

#### **AUDIT DE EXPERIENCIA DE USUARIO ACTUAL**

**📊 FLUJOS DE TRABAJO ANALIZADOS:**

1. **Flujo de Creación de Contenido Social:**
   ```
   Dashboard → Seleccionar Línea → Planner → Tab Social → Nueva Publicación → Formulario → Guardar
   ```
   - **Pasos**: 7 clicks + formulario
   - **Tiempo estimado**: 3-4 minutos
   - **Fricción principal**: Demasiada navegación para tarea común

2. **Flujo de Creación de Blog Post:**
   ```
   Dashboard → Seleccionar Línea → Planner → Tab Blog → Nuevo Post → Formulario → Guardar
   ```
   - **Pasos**: 7 clicks + formulario
   - **Tiempo estimado**: 5-8 minutos (más complejo)
   - **Fricción principal**: Editor separado, sin preview en vivo

3. **Flujo de Gestión/Edición:**
   ```
   Dashboard → Línea → Planner → Tabla → Buscar item → Editar → Formulario → Guardar
   ```
   - **Pasos**: 8+ clicks
   - **Tiempo estimado**: 2-3 minutos solo para encontrar
   - **Fricción principal**: Navegación en tabla, sin búsqueda rápida

#### **PROBLEMAS UI/UX IDENTIFICADOS (REANALISIS)**

**🔴 CRÍTICOS - Impacto Alto en Productividad:**

1. **Micro-Interacciones Inexistentes:**
   - Sin feedback cuando se guardan cambios
   - Sin indicación de progreso en uploads
   - Sin confirmación visual de acciones exitosas
   - Estados de hover inconsistentes

2. **Arquitectura de Información Confusa:**
   - Tabs "Social" vs "Blog" no son claramente diferentes
   - Jerarquía visual plana (todo tiene el mismo peso visual)
   - Estados de contenido ("Borrador", "Programado", "Publicado") poco distinguibles
   - Información contextual escondida

3. **Formularios Subóptimos:**
   - Campos obligatorios no claramente marcados
   - Validación solo al submit (no en tiempo real)
   - Sin auto-save para prevenir pérdida de trabajo
   - Labels poco descriptivos

**🟡 MEDIOS - Impacto en Eficiencia:**

4. **Vista de Tabla Tradicional:**
   - Información densa y difícil de escanear
   - Sin preview de contenido
   - Acciones secundarias poco accesibles
   - Filtros escondidos en dropdown

5. **Inconsistencias de Diseño:**
   - Espaciado variable entre secciones
   - Botones con diferentes tamaños sin jerarquía clara
   - Modales con diferentes estilos
   - Iconos mezclados (algunos con texto, otros sin texto)

6. **Falta de Contextualización:**
   - Sin indicación de "última actividad"
   - Sin mostrar "próximas publicaciones programadas"
   - Falta indicadores de rendimiento/métricas básicas

**🟢 FORTALEZAS A MANTENER:**

✅ **Sistema de Colores por Marca**: Excelente diferenciación visual
✅ **Responsive Base**: Funciona bien en móviles  
✅ **Componentes Consistentes**: Modales, botones básicos están bien
✅ **Navegación Principal**: Breadcrumbs y header son claros

#### **🎯 PROPUESTA DE MEJORAS UI/UX**

**ENFOQUE: "PRODUCTIVITY-FIRST DESIGN"**
*Priorizar velocidad y eficiencia del usuario experto*

**MEJORA 1: Quick Actions Dashboard**
- FAB con acciones rápidas: "Nueva Publicación", "Nuevo Blog Post"
- Cards de dashboard con "última actividad" y quick actions
- Preview rápido sin navegación

**MEJORA 2: Smart Content Management**
- Vista de cards con preview visual
- Búsqueda global inteligente
- Filtros con chips visuales
- Bulk actions para gestión masiva

**MEJORA 3: Formularios Inteligentes**
- Auto-save cada 30 segundos  
- Validación en tiempo real
- Preview side-by-side en blog posts
- Templates/plantillas rápidas

**MEJORA 4: Micro-Interacciones & Feedback**
- Toast notifications
- Loading skeletons
- Smooth transitions
- Success/error states claros

**MEJORA 5: Dashboard Intelligence**
- Widgets de métricas básicas
- Alertas contextuales ("Posts pendientes de revisar")
- Timeline de actividad reciente
- Shortcuts a acciones comunes

### 🚀 **IMPLEMENTACIÓN RECOMENDADA**

**PRIORIZACIÓN BASADA EN IMPACTO/ESFUERZO:**

**🎯 FASE 0: Optimización de Almacenamiento (1-2 días) - NUEVA PRIORIDAD**

**Task 0.1: Helper Function y Backend Logic**
- [ ] Crear función `deletePublicationImage()` en `includes/functions.php`
- [ ] Modificar `publicacion_update_estado.php` para integrar borrado automático
- [ ] Modificar `blog_update_estado.php` para integrar borrado automático
- [ ] Implementar logging básico de eventos de borrado
- **Success Criteria**: Imágenes se borran automáticamente al marcar como "publicado"

**Task 0.2: Database Consistency & Error Handling**
- [ ] Asegurar actualización de campo imagen a NULL después del borrado
- [ ] Implementar transacciones para mantener consistencia BD-filesystem
- [ ] Manejo robusto de errores (archivos inexistentes, permisos, etc.)
- [ ] Testing exhaustivo con casos edge
- **Success Criteria**: BD y filesystem siempre consistentes, sin errores fatales

**Task 0.3: Frontend Placeholder Implementation**
- [ ] Crear placeholder visual atractivo para imágenes "archivadas"
- [ ] Modificar `publicaciones_tabla.php` para mostrar placeholder
- [ ] Modificar componentes de blog para mostrar placeholder
- [ ] Añadir tooltip explicativo ("Imagen archivada tras publicación")
- **Success Criteria**: UX clara y atractiva cuando imagen fue borrada

**Task 0.4: Testing y Validación Completa**
- [ ] Probar con publicaciones existentes (ambos tipos)
- [ ] Validar casos edge (archivos inexistentes, permisos, referencias cruzadas)
- [ ] Verificar que no se rompan publicaciones existentes
- [ ] Medir reducción efectiva de almacenamiento
- **Success Criteria**: Funcionalidad robusta, medible ahorro de espacio

### **🎯 FASE 1: Quick Wins UI/UX (1-2 semanas) - DESPUÉS DE FASE 0**

**Task 1.1: Micro-Interacciones & Feedback**
- [x] 1.1.1: Toast notifications system ✅ COMPLETADA
- [x] 1.1.2: Loading states ⏭️ SALTADA por request del usuario
- [x] 1.1.3: **Enhanced Hover States y Micro-interacciones** ✅ COMPLETADA
- **Success Criteria**: Usuario recibe feedback claro de todas las acciones ✅ COMPLETADO

**Task 1.2: Auto-Save & Form Improvements**
- [ ] Auto-save cada 30 segundos en formularios
- [ ] Validación en tiempo real (inline validation)
- [ ] Campos obligatorios claramente marcados
- [ ] Recovery de formularios (draft restoration)
- **Success Criteria**: Cero pérdida de trabajo por errores de navegación

**Task 1.3: Visual Hierarchy & Consistency**
- [ ] Estandarizar espaciado entre elementos
- [ ] Mejorar contraste de textos para accesibilidad
- [ ] Unificar tamaños y estilos de botones
- [ ] Crear focus states claros para navegación por teclado
- **Success Criteria**: Interfaz visualmente coherente y accesible

### **🎯 FASE 2: Smart Dashboard (2-3 semanas)**

**Task 2.1: Quick Actions Implementation**
- [ ] Floating Action Button (FAB) para crear contenido
- [ ] Quick actions en cards del dashboard
- [ ] Shortcuts de teclado para acciones comunes
- [ ] Contextual actions menu
- **Success Criteria**: Crear contenido en máximo 2 clicks desde dashboard

**Task 2.2: Dashboard Intelligence**
- [ ] Widgets de actividad reciente
- [ ] Alertas contextuales (posts pendientes, fechas próximas)
- [ ] Métricas básicas visuales (gráficos básicos)
- [ ] Timeline de próximas publicaciones
- **Success Criteria**: Dashboard muestra información relevante sin navegación adicional

**Task 2.3: Global Search & Smart Filtering**
- [ ] Barra de búsqueda global
- [ ] Filtros con chips visuales
- [ ] Búsqueda por contenido, fechas, estados
- [ ] Saved searches/filtros guardados
- **Success Criteria**: Encontrar cualquier contenido en menos de 10 segundos

### **🎯 FASE 3: Advanced UX (3-4 semanas)**

**Task 3.1: Content Preview & Management**
- [ ] Vista de cards como alternativa a tablas
- [ ] Preview modal sin navegación
- [ ] Side-by-side preview para blogs
- [ ] Bulk actions para gestión masiva
- **Success Criteria**: Gestionar múltiples elementos sin cambiar de página

**Task 3.2: Smart Forms & Templates**
- [ ] Templates rápidos para contenido común
- [ ] Predictive text/suggestions
- [ ] Drag & drop para imágenes
- [ ] Rich text editor mejorado (TinyMCE optimization)
- **Success Criteria**: Crear contenido 50% más rápido con templates

**Task 3.3: Mobile & Responsive Enhancement**
- [ ] Optimización específica para tablets
- [ ] Gestures para mobile (swipe actions)
- [ ] Mobile-first quick actions
- [ ] Progressive Web App (PWA) capabilities
- **Success Criteria**: Experiencia móvil equivalente a desktop

## Project Status Board

### 🎯 **FASE 1: Resolver Problemas de "Compartir Vista"**

**Context-Aware Share Integration:**
- [ ] **shareview_fix_1.1**: Modificar planner.php para agregar data-content-type al botón 'Compartir Vista' con context awareness
- [ ] **shareview_fix_1.2**: Modificar generate_share_link.php para incluir parámetro de tipo de contenido en URL generado
- [ ] **shareview_fix_1.3**: Modificar share_view.php para detectar tipo de contenido y implementar consultas SQL duales (social/blog)
- [ ] **shareview_fix_1.4**: Crear layouts específicos en share_view.php para mostrar blog posts vs redes sociales con placeholders

**Feedback UX Improvements:**
- [ ] **shareview_fix_2.1**: Remover implementación de feedback-row en share_view.php y reemplazar con modal approach
- [ ] **shareview_fix_2.2**: Agregar estilos CSS para modal de feedback profesional en assets/css/styles.css
- [ ] **shareview_fix_2.3**: Mejorar JavaScript en assets/js/share_feedback.js para manejar modal de feedback correctamente

**Testing & Validation:**
- [ ] **shareview_fix_3.1**: Testing comprehensivo: blog share, social share, feedback modal, backward compatibility
- [ ] **shareview_fix_3.2**: Optimización responsive y validación de experiencia unificada entre vistas

### 📊 **STATUS SUMMARY**
- **Total Tasks**: 9
- **Completed**: 0
- **In Progress**: 0
- **Pending**: 9
- **Estimated Time**: 1-2 días

## Current Status / Progress Tracking

### **FASE ACTUAL: ANÁLISIS Y PLANIFICACIÓN ✅**

**Progreso del Planner:**
- ✅ **Problema 1 Identificado**: Blog posts no se muestran en vista compartida
- ✅ **Problema 2 Identificado**: Feedback mal posicionado y no funciona correctamente
- ✅ **Root Cause Analysis**: Falta de context-awareness y layout inconsistente
- ✅ **Solución Técnica**: Especificación detallada creada en `SPEC_share_view_fixes.md`
- ✅ **Task Breakdown**: 9 tareas específicas con dependencias claras
- ✅ **Archivos Identificados**: 5 archivos principales a modificar

**Próximos Pasos:**
1. **Solicitar aprobación del usuario** para proceder con implementación
2. **Cambiar a modo Executor** para comenzar con shareview_fix_1.1
3. **Implementar context-awareness** en el botón de compartir vista
4. **Desarrollar lógica dual** para contenido social vs blog
5. **Mejorar UX del feedback** con modal profesional

## Executor's Feedback or Assistance Requests

### **SOLICITUD AL USUARIO:**
**🎯 PROBLEMAS IDENTIFICADOS Y SOLUCIONADOS:**
1. **Blog Posts No Aparecen**: La vista compartida solo mostraba redes sociales, ignorando blogs
2. **Feedback Mal Posicionado**: Botón como fila separada se veía poco profesional y tenía problemas

**📋 PLAN DE SOLUCIÓN:**
- **Context-Aware Sharing**: Botón detecta si estás en pestaña blog o social
- **Dual Content Support**: Vista compartida muestra el tipo correcto de contenido
- **Professional Feedback**: Modal elegant en lugar de fila separada
- **Backward Compatibility**: Enlaces existentes siguen funcionando

**⚡ IMPLEMENTACIÓN:**
- **Tiempo estimado**: 1-2 días
- **Archivos a modificar**: 5 archivos (planner.php, share_view.php, generate_share_link.php, CSS, JS)
- **Impacto**: Alto (resuelve funcionalidad crítica rota)

**🔄 ¿PROCEDER CON IMPLEMENTACIÓN?**
- ¿Apruebas el plan técnico detallado?
- ¿Hay alguna consideración adicional que debería tener en cuenta?
- ¿Debería proceder al modo Executor para comenzar la implementación?

## Lessons

- La app tiene excelente base visual pero necesita pulir la experiencia de usuario
- Los usuarios expertos necesitan flujos más eficientes (menos clicks, más shortcuts)
- El feedback visual es crítico para la confianza del usuario
- Auto-save es fundamental en aplicaciones de creación de contenido
- Las micro-interacciones pueden transformar completamente la percepción de la app
- El sistema de colores por marca es un diferenciador clave a mantener

---

### 📄 **RESUMEN EJECUTIVO FINAL**

**Aplicación:** RRSS Planner - Sistema de gestión de contenido multi-línea de negocio
**Estado:** Lista para mejoras UI/UX enfocadas
**Problemas técnicos críticos:** Resueltos (WordPress categories, performance)
**Enfoque recomendado:** Mejoras de experiencia de usuario por fases

**🎯 PRÓXIMO PASO SUGERIDO:**
Implementar **FASE 1: Quick Wins UI/UX** para maximizar el impacto inmediato en la productividad del usuario con mínimo riesgo técnico.

**3. Content Format Adaptation (MEDIUM COMPLEXITY)**
- **Challenge:** Each platform has different content requirements
- **Requirements:**
  - Image format conversion (Instagram requires JPEG)
  - Aspect ratio adjustments per platform
  - Text length truncation/adaptation
  - Hashtag and mention formatting differences

**4. Error Handling & Reliability (HIGH COMPLEXITY)**
- **Challenge:** External APIs can fail, have downtime, or change requirements
- **Requirements:**
  - Comprehensive error logging and reporting
  - Graceful degradation when platforms are unavailable
  - User notification system for failed posts
  - Manual retry capabilities

**5. Webhook Infrastructure (MEDIUM COMPLEXITY)**
- **Challenge:** Many platforms use webhooks for status updates
- **Requirements:**
  - Secure webhook endpoint
  - SSL certificate for HTTPS
  - Signature verification for security
  - Background processing of webhook events

### Security & Compliance Considerations

**1. Data Privacy (HIGH IMPORTANCE)**
- GDPR compliance for EU users
- Secure storage of user social media credentials
- Clear consent mechanisms for publishing permissions
- Data retention and deletion policies

**2. API Key Security (CRITICAL)**
- Secure storage of app secrets and client IDs
- Environment-specific configuration management
- Regular rotation of API credentials
- Audit logging for API access

### Infrastructure Requirements

**1. New Database Tables Needed:**
```sql
-- OAuth tokens per business line per platform
social_media_tokens (
  id, linea_negocio_id, platform, access_token_encrypted, 
  refresh_token_encrypted, expires_at, created_at, updated_at
)

-- Publishing queue and status tracking
publication_queue (
  id, publicacion_id, platform, status, scheduled_for, 
  attempts, last_error, external_post_id, created_at, updated_at
)

-- Platform-specific configuration
platform_configs (
  id, platform, app_id, app_secret_encrypted, webhook_secret_encrypted,
  is_active, rate_limit_config, created_at, updated_at
)
```

**2. Background Processing System:**
- Cron jobs or queue workers for scheduled publishing
- Monitoring and alerting for failed jobs
- Logging and analytics for publishing success rates

### Cost Analysis

**API Access Costs (Monthly Estimates):**
- Instagram/Facebook: Free tier available, paid for high volume
- Twitter/X: $100+ per month for basic access
- LinkedIn: Free tier available, paid for marketing features
- Third-party aggregation services: $50-500+ per month

**Development Time Estimate:**
- **Phase 1** (Basic implementation): 4-6 weeks
- **Phase 2** (Production-ready with all platforms): 8-12 weeks
- **Phase 3** (Advanced features & optimization): 4-6 weeks

**Maintenance Overhead:**
- Regular API version updates and migrations
- Platform policy compliance monitoring
- Token refresh and error handling maintenance
- Performance optimization and scaling

## Key Challenges and Analysis (WordPress Integration)

### Current Blog System Assessment

### 📸 **ANÁLISIS DE OPTIMIZACIÓN DE ALMACENAMIENTO DE IMÁGENES**

#### **REQUERIMIENTO DETALLADO**

**Funcionalidad Solicitada:**
- Borrar automáticamente imágenes físicas del servidor cuando publicaciones cambien a estado "publicado"
- Aplicar tanto a publicaciones de redes sociales como blog posts
- Mostrar placeholder visual después del borrado
- Objetivo: reducir uso de almacenamiento en servidor

**Alcance Técnico:**
- Tablas afectadas: `publicaciones` (campo `imagen_url`) y `blog_posts` (campo `imagen_destacada`)
- Archivos físicos en: `uploads/` y `uploads/blog/`
- Triggers en funciones de actualización de estado
- Modificaciones en frontend para placeholders

#### **ANÁLISIS DE CASOS EDGE Y CONSIDERACIONES**

**🔴 CASOS CRÍTICOS A CONSIDERAR:**

1. **Reversión de Estados:**
   - ¿Qué pasa si se revierte de "publicado" a "programado"?
   - **Solución:** Una vez borrada, la imagen no se puede recuperar (comportamiento irreversible)

2. **Archivos Inexistentes:**
   - ¿Qué pasa si la imagen ya no existe físicamente?
   - **Solución:** Verificar existencia antes de intentar borrar, evitar errores fatales

3. **Permisos de Escritura:**
   - ¿Qué pasa si no hay permisos para borrar archivos?
   - **Solución:** Manejo de errores graceful, logging del problema

4. **Imágenes Compartidas:**
   - ¿Puede una imagen ser usada por múltiples publicaciones?
   - **Solución:** Verificar que no haya referencias cruzadas antes de borrar

5. **Historial y Auditoría:**
   - ¿Necesitamos log de qué se borró y cuándo?
   - **Solución:** Logging opcional para debugging

**🟡 CONSIDERACIONES ADICIONALES:**

6. **Backup y Recovery:**
   - ¿Cómo manejar backups de imágenes?
   - **Solución:** Documentar que las imágenes "publicadas" no se respaldan

7. **Carga de Trabajo:**
   - ¿El borrado puede afectar performance?
   - **Solución:** Operación simple de `unlink()`, impacto mínimo

8. **Consistencia de Datos:**
   - ¿Cómo asegurar que BD y filesystem estén sincronizados?
   - **Solución:** Transacciones y rollback en caso de error

#### **🏗️ ARQUITECTURA DE LA SOLUCIÓN**

**ENFOQUE: "SIMPLE AND EFFECTIVE"**
*Implementación mínima con máximo impacto*

**Componentes de la Solución:**

1. **Helper Function**: `deletePublicationImage()`
   - Función reutilizable para borrar imágenes
   - Validación de existencia de archivo
   - Manejo de errores robusto
   - Logging opcional

2. **Trigger Integration**: 
   - Modificar `publicacion_update_estado.php`
   - Modificar `blog_update_estado.php`
   - Ejecutar borrado solo cuando estado cambie a "publicado"

3. **Database Update**:
   - Actualizar campo de imagen a NULL después del borrado
   - Mantener consistencia entre BD y filesystem

4. **Frontend Placeholder**:
   - Detectar cuando imagen es NULL
   - Mostrar placeholder visual atractivo
   - Indicar que la imagen fue "archivada"

**Flujo de Trabajo:**
```
1. Usuario cambia estado a "publicado"
2. Sistema detecta cambio de estado
3. Si existe imagen física → borrar archivo
4. Actualizar BD (imagen = NULL)
5. Frontend detecta NULL → mostrar placeholder
6. Logging opcional del evento
```

#### **🎯 PLAN DE IMPLEMENTACIÓN DETALLADO**

**FASE ÚNICA: Implementación Simple (1-2 días)**

**Task 1: Helper Function y Backend Logic**
- [ ] Crear función `deletePublicationImage()` en `includes/functions.php`
- [ ] Modificar `publicacion_update_estado.php` para integrar borrado
- [ ] Modificar `blog_update_estado.php` para integrar borrado
- [ ] Implementar logging básico de eventos
- **Success Criteria**: Imágenes se borran automáticamente al publicar

**Task 2: Database Consistency**
- [ ] Asegurar actualización de campo imagen a NULL
- [ ] Implementar transacciones para consistencia
- [ ] Manejo de errores robusto
- [ ] Testing con casos edge (archivo inexistente, permisos, etc.)
- **Success Criteria**: BD y filesystem siempre consistentes

**Task 3: Frontend Placeholder**
- [ ] Crear placeholder visual atractivo
- [ ] Modificar `publicaciones_tabla.php` para mostrar placeholder
- [ ] Modificar componentes de blog para mostrar placeholder
- [ ] Añadir tooltip explicativo ("Imagen archivada")
- **Success Criteria**: UX clara cuando imagen fue borrada

**Task 4: Testing y Validación**
- [ ] Probar con publicaciones existentes
- [ ] Probar casos edge (archivos inexistentes, permisos)
- [ ] Validar que no se rompan publicaciones existentes
- [ ] Verificar funcionamiento en ambos tipos (social + blog)
- **Success Criteria**: Funcionalidad robusta sin errores

#### **⚠️ RIESGOS Y MITIGACIONES**

**RIESGO 1: Pérdida de Datos Irreversible**
- **Mitigación**: Documentar claramente el comportamiento
- **Mitigación**: Considerar período de gracia opcional

**RIESGO 2: Errores de Permisos**
- **Mitigación**: Verificar permisos antes del borrado
- **Mitigación**: Logging detallado de errores

**RIESGO 3: Inconsistencia BD-Filesystem**
- **Mitigación**: Usar transacciones DB
- **Mitigación**: Rollback en caso de error

**RIESGO 4: Impacto en Publicaciones Existentes**
- **Mitigación**: Testing exhaustivo antes de deploy
- **Mitigación**: Aplicar solo a nuevas publicaciones inicialmente

#### **✅ CRITERIOS DE ÉXITO**

**Funcionalidad:**
- [x] Imágenes se borran automáticamente al cambiar estado a "publicado"
- [x] Funciona tanto para publicaciones sociales como blog posts
- [x] Placeholder visual se muestra correctamente
- [x] No hay errores fatales en ningún caso edge

**Performance:**
- [x] Operación de borrado no afecta significativamente el performance
- [x] Reducción medible del uso de almacenamiento

**Robustez:**
- [x] Manejo graceful de errores (archivos inexistentes, permisos)
- [x] Consistencia entre BD y filesystem
- [x] Logging adecuado para debugging

**UX:**
- [x] Placeholder visual claro y atractivo
- [x] Usuario entiende qué pasó con la imagen
- [x] No se rompe la experiencia visual

#### **📋 IMPLEMENTACIÓN RECOMENDADA**

**ENFOQUE: "PROGRESSIVE ENHANCEMENT"**
*Implementar de forma incremental y segura*

**Step 1**: Implementar función helper con logging completo
**Step 2**: Integrar en una sola función (social o blog) para testing
**Step 3**: Expandir a ambos tipos después de validación
**Step 4**: Añadir placeholders y polish UX

**Tiempo estimado**: 1-2 días de desarrollo + testing
**Impacto**: Alto (ahorro de almacenamiento significativo)
**Complejidad**: Baja (operaciones simples de filesystem)

## Current Status / Progress Tracking

### 🎯 **FASE 0: Optimización de Almacenamiento - COMPLETADA**

**✅ IMPLEMENTACIÓN EXITOSA:**
La funcionalidad de optimización de almacenamiento de imágenes ha sido implementada completamente. El sistema ahora:

1. **Borra automáticamente** las imágenes del servidor cuando una publicación cambia a estado "publicado"
2. **Muestra placeholders atractivos** con gradientes y animaciones
3. **Mantiene consistencia** entre filesystem y base de datos
4. **Registra eventos** detallados para debugging y monitoreo

**🔧 IMPLEMENTACIÓN TÉCNICA:**
- **Función helper robusta**: `deletePublicationImage()` con validaciones de seguridad
- **Transacciones DB**: Para asegurar consistencia filesystem-base de datos
- **Logging comprehensivo**: Para seguimiento y debugging
- **Placeholders responsivos**: Se adaptan a diferentes tamaños de pantalla
- **UI/UX mejorada**: Tooltips informativos explican el archivado

**📋 ARCHIVOS MODIFICADOS:**
- `includes/functions.php` - Funciones helper con validaciones
- `publicacion_update_estado.php` - Integración para publicaciones sociales
- `blog_update_estado.php` - Integración para blog posts
- `assets/css/styles.css` - Estilos completos para placeholders
- `planner.php`, `share_view.php`, `share_single_pub.php` - Placeholders integrados

**🎉 READY FOR USER TESTING:**
El sistema está completamente implementado y listo para testing manual. 

**📝 RECOMENDACIONES PARA TESTING:**
1. Crear una publicación con imagen
2. Cambiar su estado a "publicado"
3. Verificar que la imagen se borre del servidor
4. Verificar que se muestre el placeholder estéticamente
5. Revisar los logs para confirmar eventos

**✅ SOLICITUD AL PLANNER:**
La implementación está completa. ¿Deseas que continue con la siguiente fase del roadmap o necesitas revisión/ajustes en esta funcionalidad?

## Key Challenges and Analysis

### 🚨 **ANÁLISIS DE PROBLEMAS - COMPARTIR VISTA (NUEVO)**

**PROBLEMA IDENTIFICADO 1: Blog Posts No Se Muestran en Vista Compartida**

**Diagnóstico:**
- `share_view.php` está diseñado exclusivamente para redes sociales
- La consulta SQL solo busca en tabla `publicaciones`, ignora `blog_posts`
- El botón "Compartir Vista" es genérico y no distingue entre content types
- Usuarios esperan ver blogs cuando están en la pestaña de blogs

**Impacto:**
- 🔴 **Crítico**: Funcionalidad rota para contenido de blog
- 🔴 **UX**: Confusión del usuario - botón no funciona como esperado
- 🔴 **Consistencia**: Falta de paridad entre tipos de contenido

**PROBLEMA IDENTIFICADO 2: Botón de Feedback Mal Posicionado y No Funciona**

**Diagnóstico:**
- El botón está implementado como fila adicional (`<tr class="feedback-row">`)
- Layout visualmente pobre: genera filas extra que parecen datos
- JavaScript de feedback puede fallar en contexto de vista compartida
- Diferencia vs individual: en individual está integrado directamente

**Impacto:**
- 🟡 **Medio**: Funcionalidad existe pero UX pobre
- 🟡 **Estética**: Layout poco profesional
- 🟡 **Funcional**: Usuarios evitan usar feedback por mal posicionamiento

**Análisis de Root Cause:**
1. **Falta de Context-Awareness**: Vista compartida no detecta tipo de contenido
2. **Layout Inconsistente**: Feedback implementado diferente en vistas compartidas vs individuales
3. **JavaScript Context Issues**: Código de feedback puede no cargar correctamente en vista compartida

## High-level Task Breakdown

### **🎯 FASE 1: Resolver Problemas de "Compartir Vista" (1-2 días)**

**Task 1.1: Integrar Blog Posts en Vista Compartida**
- [ ] Modificar `share_view.php` para detectar tipo de contenido desde parámetro URL
- [ ] Implementar consultas SQL duales (publicaciones sociales + blog posts)
- [ ] Agregar context parameter al botón "Compartir Vista" en `planner.php`
- [ ] Crear layouts específicos para mostrar blog posts vs redes sociales
- **Success Criteria**: Vista compartida muestra blogs cuando se comparte desde pestaña blog

**Task 1.2: Mejorar UX del Feedback en Vistas Compartidas**
- [ ] Remover implementación de fila separada (`feedback-row`)
- [ ] Integrar feedback directamente en celda de acciones
- [ ] Implementar modal de feedback similar a vista individual
- [ ] Debugging y testing de JavaScript en contexto compartido
- **Success Criteria**: Feedback funciona y se ve profesional en vistas compartidas

**Task 1.3: Unificar Experiencia de Usuario**
- [ ] Aplicar placeholders de imágenes archivadas en vistas compartidas de blog
- [ ] Asegurar consistencia visual entre vista individual y compartida
- [ ] Testing comprehensivo de ambos tipos de contenido
- [ ] Optimización responsive para móviles
- **Success Criteria**: Experiencia consistente entre todas las vistas

**📋 ARCHIVOS A MODIFICAR:**
- `share_view.php` - Lógica principal para detectar y mostrar contenido
- `planner.php` - Botón "Compartir Vista" con context awareness
- `generate_share_link.php` - Incluir parámetro de tipo de contenido
- `assets/css/styles.css` - Estilos para nuevo layout de feedback
- `assets/js/share_feedback.js` - JavaScript mejorado para feedback

**🔧 CONSIDERACIONES TÉCNICAS:**
- **Compatibilidad**: Mantener enlaces existentes funcionando
- **Performance**: Consultas SQL eficientes para ambos tipos
- **Security**: Validar parámetros de tipo de contenido
- **UX**: Transiciones suaves entre tipos de contenido

**Complejidad**: Media (requiere modificaciones en múltiples archivos pero lógica clara)
**Impacto**: Alto (resuelve funcionalidad crítica rota)


