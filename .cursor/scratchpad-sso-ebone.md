# Sistema SSO + Integración de Correo Electrónico - Ecosistema Ebone

## Background and Motivation
La empresa Ebone maneja múltiples aplicaciones en desarrollo, cada una con su propia base de datos y tabla de usuarios. Se requiere implementar un sistema de Single Sign-On (SSO) unificado que utilice las credenciales del servidor de correo corporativo como fuente de autenticación principal.

**Objetivo:** Crear un sistema donde una sola contraseña (la del correo) permita acceso a todas las aplicaciones del ecosistema Ebone.

**Contexto técnico:**
- Servidor: Plesk
- Base de datos: MySQL
- Múltiples aplicaciones en desarrollo
- Servidor de correo corporativo existente
- Necesidad de administración centralizada

## Key Challenges and Analysis

### 1. Arquitectura de Integración
**Desafíos:**
- Identificar el protocolo de autenticación del servidor de correo en Plesk
- Decidir entre autenticación directa vs. sincronización de credenciales
- Manejar usuarios que existen en aplicaciones pero no tienen correo
- Implementar fallback seguro en caso de fallo del servidor de correo

**Opciones técnicas disponibles:**
- **IMAP/POP3 Authentication:** Validar credenciales directamente contra el servidor de correo
- **LDAP/Active Directory:** Si Plesk tiene integración LDAP configurada
- **API de Plesk:** Utilizar API nativa para validación de usuarios de correo
- **Sincronización híbrida:** Base de datos central + validación contra correo

### 2. Consideraciones de Seguridad
**Fortalezas del enfoque:**
- Una sola fuente de verdad para credenciales
- Políticas de contraseña centralizadas
- Auditoría unificada de accesos
- Menor superficie de ataque

**Riesgos a mitigar:**
- Dependencia crítica del servidor de correo
- Exposición de credenciales de correo a aplicaciones
- Manejo seguro de tokens de sesión
- Prevención de ataques de fuerza bruta

### 3. Experiencia del Usuario
**Beneficios:**
- Una sola contraseña para recordar
- Inicio de sesión único (SSO)
- Gestión centralizada de cambios de contraseña
- Consistencia en toda la plataforma

**Consideraciones:**
- Qué hacer con usuarios sin correo corporativo
- Manejo de cambios de contraseña de correo
- Experiencia durante mantenimiento del servidor de correo

### 4. Sistema de Autorización Granular
**Requerimiento crítico:** Distinguir entre aplicaciones públicas y privadas:

**Aplicaciones públicas** (acceso para toda la empresa):
- App de creación de firmas
- Verificador de IP
- Herramientas generales de productividad
- Recursos corporativos

**Aplicaciones privadas** (acceso controlado por roles):
- RRSS Planner (solo usuarios autorizados)
- Sistemas de gestión específicos
- Aplicaciones con información sensible
- Herramientas de administración

**Desafíos de implementación:**
- Mantener sistema de roles existente en cada aplicación
- Crear capa de autorización centralizada
- Manejar permisos granulares por aplicación
- Permitir administración delegada de accesos

## High-level Task Breakdown

### **Fase 1: Investigación y Análisis Técnico**
- [ ] **1.1 Análisis del servidor de correo en Plesk**
  - Identificar configuración actual (Postfix, Dovecot, etc.)
  - Determinar protocolos disponibles (IMAP, POP3, LDAP)
  - Evaluar API disponible de Plesk para usuarios de correo
  - Documentar estructura actual de usuarios de correo

- [ ] **1.2 Inventario de aplicaciones existentes**
  - Catalogar todas las aplicaciones del ecosistema
  - Analizar sistemas de autenticación actuales
  - Identificar tablas de usuarios existentes
  - Evaluar compatibilidad con SSO

- [ ] **1.3 Investigación de opciones técnicas**
  - Investigar mejores prácticas de autenticación IMAP en PHP
  - Evaluar bibliotecas de autenticación disponibles
  - Analizar sistemas de tokens JWT para SSO
  - Investigar soluciones de fallback y alta disponibilidad

### **Fase 2: Diseño de Arquitectura**
- [ ] **2.1 Diseño de base de datos central**
  - Crear esquema de base de datos SSO
  - Definir tabla de usuarios unificada
  - Diseñar tabla de aplicaciones (públicas vs privadas)
  - Crear tabla de permisos granulares por aplicación
  - Diseñar tabla de roles y asignaciones
  - Planificar tabla de sesiones activas con contexto de permisos

- [ ] **2.2 Diseño de flujo de autenticación**
  - Definir flujo de login con validación de correo
  - Diseñar sistema de tokens JWT
  - Planificar manejo de sesiones entre aplicaciones
  - Crear flujo de logout global

- [ ] **2.3 Diseño de API centralizada**
  - Especificar endpoints de autenticación
  - Definir API para validación de tokens
  - Planificar endpoints de gestión de usuarios
  - Diseñar sistema de logs de auditoría

### **Fase 3: Implementación del Sistema Central**
- [ ] **3.1 Desarrollo de sistema de autenticación**
  - Implementar validación contra servidor de correo
  - Crear sistema de tokens JWT
  - Desarrollar API de autenticación
  - Implementar manejo de sesiones

- [ ] **3.2 Desarrollo de panel de administración**
  - Crear interfaz de gestión de usuarios
  - Implementar dashboard de sesiones activas
  - Desarrollar sistema de logs y auditoría
  - Crear herramientas de diagnóstico

- [ ] **3.3 Desarrollo de bibliotecas cliente**
  - Crear biblioteca PHP para integración
  - Implementar middleware de autenticación
  - Desarrollar componentes de UI comunes
  - Crear documentación de integración

### **Fase 4: Migración y Testing**
- [ ] **4.1 Migración de aplicaciones existentes**
  - Migrar aplicación piloto (ej: RRSS Planner)
  - Migrar usuarios existentes
  - Actualizar sistemas de permisos
  - Validar funcionalidad completa

- [ ] **4.2 Testing integral**
  - Testing de carga del sistema de autenticación
  - Testing de seguridad y penetración
  - Testing de experiencia de usuario
  - Testing de recuperación ante fallos

- [ ] **4.3 Documentación y capacitación**
  - Documentar procedimientos de administración
  - Crear guías de integración para nuevas aplicaciones
  - Capacitar equipo de desarrollo
  - Documentar procedimientos de emergencia

## Sistema de Autorización y Roles - Diseño Técnico

### Estructura de Base de Datos Propuesta

```sql
-- Tabla de aplicaciones
CREATE TABLE sso_applications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(50) UNIQUE NOT NULL,
    type ENUM('public', 'private') NOT NULL,
    description TEXT,
    url VARCHAR(255),
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de usuarios (sincronizada con servidor de correo)
CREATE TABLE sso_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(100),
    department VARCHAR(100),
    active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de roles por aplicación
CREATE TABLE sso_roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    application_id INT NOT NULL,
    role_name VARCHAR(50) NOT NULL,
    description TEXT,
    permissions JSON, -- Para permisos específicos
    FOREIGN KEY (application_id) REFERENCES sso_applications(id),
    UNIQUE KEY unique_role_per_app (application_id, role_name)
);

-- Tabla de asignaciones usuario-aplicación-rol
CREATE TABLE sso_user_application_roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    application_id INT NOT NULL,
    role_id INT,
    granted_by INT, -- Usuario que otorgó el permiso
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES sso_users(id),
    FOREIGN KEY (application_id) REFERENCES sso_applications(id),
    FOREIGN KEY (role_id) REFERENCES sso_roles(id),
    FOREIGN KEY (granted_by) REFERENCES sso_users(id),
    UNIQUE KEY unique_user_app (user_id, application_id)
);
```

### Flujo de Autorización

1. **Autenticación (común para todos):**
   - Usuario ingresa email + contraseña
   - Sistema valida contra servidor de correo
   - Se genera JWT con información básica del usuario

2. **Autorización por aplicación:**
   - **Aplicaciones públicas:** Acceso automático para cualquier usuario autenticado
   - **Aplicaciones privadas:** Se verifica en `sso_user_application_roles`

3. **Verificación de permisos:**
   - Aplicación consulta roles del usuario
   - Sistema devuelve permisos específicos
   - Aplicación maneja funcionalidades según rol

### Ejemplos de Implementación

**Para aplicaciones públicas:**
```php
// Solo verificar autenticación
if (SSOAuth::isAuthenticated()) {
    // Acceso permitido
    $user = SSOAuth::getCurrentUser();
}
```

**Para aplicaciones privadas:**
```php
// Verificar autorización específica
if (SSOAuth::hasAccessToApp('rrss-planner')) {
    $userRole = SSOAuth::getUserRole('rrss-planner');
    
    if ($userRole === 'superadmin') {
        // Acceso completo
    } elseif ($userRole === 'admin') {
        // Acceso limitado
    } else {
        // Acceso denegado
    }
}
```

### Panel de Administración de Accesos

**Funcionalidades requeridas:**
- Gestión de usuarios por aplicación
- Asignación/revocación de roles
- Auditoría de permisos
- Permisos delegados (admins de cada app)

## Project Status Board

📋 **Pendiente:**
- Todas las tareas están en fase de planificación
- Requiere aprobación para proceder con investigación técnica
- Necesita definir prioridades y cronograma

⚠️ **Consideraciones importantes:**
- Impacto en productividad durante migración
- Necesidad de ambiente de testing robusto
- Plan de rollback en caso de problemas
- Coordinación con administrador de sistema/Plesk

## Current Status / Progress Tracking

**Estado actual:** 📋 Planificación inicial completada

**Próximos pasos sugeridos:**
1. Obtener acceso y documentación del servidor de correo en Plesk
2. Realizar pruebas de concepto con autenticación IMAP
3. Definir cronograma de implementación
4. Establecer métricas de éxito

## Executor's Feedback or Assistance Requests

**Información requerida para continuar:**
- Acceso al panel de Plesk para análisis técnico
- Lista completa de aplicaciones del ecosistema Ebone
- Definición de prioridades de migración
- Presupuesto/recursos disponibles para el proyecto
- Ventana de mantenimiento disponible

**Decisiones técnicas pendientes:**
- ¿Autenticación directa contra correo o sincronización?
- ¿Migración gradual o big bang?
- ¿Manejo de usuarios legacy sin correo?
- ¿Integración con sistemas externos (CRM, ERP)?
- ¿Cómo migrar roles existentes del RRSS Planner?
- ¿Quién puede administrar permisos de cada aplicación?
- ¿Roles temporales con fecha de expiración?
- ¿Notificaciones automáticas de cambios de permisos?

## Lessons

**Principios de diseño SSO:**
- Siempre implementar fallback de autenticación
- Separar autenticación de autorización
- Logs detallados para auditoría
- Validación robusta de tokens
- Manejo graceful de errores de red

**Mejores prácticas de seguridad:**
- Nunca almacenar contraseñas de correo
- Usar tokens JWT con expiración corta
- Implementar rate limiting en endpoints
- Validar todas las entradas de usuario
- Mantener logs de acceso detallados

**Consideraciones de UX:**
- Feedback claro durante autenticación
- Manejo elegante de errores de servidor
- Opción de "recordar sesión" segura
- Logout global intuitivo
- Recuperación de contraseña integrada

## Migración desde Sistema Actual (RRSS Planner)

### Estado Actual del RRSS Planner:
- Sistema de autenticación dual (master password + usuario específico)
- Roles: superadmin, admin, usuario
- Tabla `admin_users` con gestión de permisos
- Sistema de restricciones por página (configuracion.php solo para superadmins)

### Plan de Migración:

1. **Fase de preparación:**
   - Mapear usuarios actuales a emails corporativos
   - Identificar usuarios sin correo corporativo
   - Documentar permisos actuales por usuario

2. **Fase de transición:**
   - Mantener ambos sistemas en paralelo
   - Crear script de migración de usuarios
   - Validar equivalencia de permisos

3. **Fase de activación:**
   - Activar SSO en RRSS Planner
   - Mantener fallback al sistema anterior
   - Migrar usuarios gradualmente

### Ejemplo de Migración de Datos:

```sql
-- Migrar usuarios existentes
INSERT INTO sso_users (email, name, active) 
SELECT email, username, activo 
FROM admin_users 
WHERE email IS NOT NULL;

-- Migrar aplicación RRSS Planner
INSERT INTO sso_applications (name, slug, type, description) 
VALUES ('RRSS Planner', 'rrss-planner', 'private', 'Planificador de redes sociales');

-- Migrar roles
INSERT INTO sso_roles (application_id, role_name, description) 
SELECT app.id, 'superadmin', 'Acceso completo al sistema'
FROM sso_applications app WHERE app.slug = 'rrss-planner';

-- Migrar asignaciones de roles
INSERT INTO sso_user_application_roles (user_id, application_id, role_id, granted_by)
SELECT u.id, app.id, r.id, 1
FROM admin_users old_user
JOIN sso_users u ON u.email = old_user.email
JOIN sso_applications app ON app.slug = 'rrss-planner'
JOIN sso_roles r ON r.application_id = app.id AND r.role_name = old_user.tipo_usuario;
```

### Compatibilidad con Sistema Actual:

**Opción 1: Migración completa**
- Reemplazar completamente el sistema de autenticación
- Ventaja: Simplicidad, una sola fuente de verdad
- Desventaja: Riesgo mayor, cambio drástico

**Opción 2: Migración gradual**
- Mantener ambos sistemas temporalmente
- Permitir login con SSO o sistema legacy
- Migrar usuarios uno por uno
- Ventaja: Menor riesgo, rollback fácil
- Desventaja: Más complejo temporalmente 