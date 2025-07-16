# 🔐 Sistema de Administradores - Guía de Usuario

## 📋 Información General

El sistema RRSS Planner ahora cuenta con un sistema de administradores mejorado que permite:
- ✅ **Múltiples usuarios** con credenciales individuales
- ✅ **Roles diferenciados** (admin, superadmin)
- ✅ **Seguridad mejorada** con contraseñas individuales
- ✅ **Auditabilidad** para saber quién hace qué

---

## 🚀 Cómo Acceder al Sistema

### 1. **Nuevo Sistema (Recomendado)**
- **URL**: `https://tu-dominio.com/login.php`
- **Credenciales**: Email + Contraseña individual

### 2. **Sistema Anterior (Temporal)**
- **URL**: `https://tu-dominio.com/login.php`
- **Credenciales**: Solo contraseña (dejar email vacío)

---

## 👥 Tipos de Usuarios

### **Administrador (admin)**
- ✅ Acceso completo a todas las líneas de negocio
- ✅ Crear, editar y eliminar publicaciones
- ✅ Gestionar blog posts y publicaciones sociales
- ✅ Configurar WordPress
- ❌ No puede gestionar otros usuarios

### **Superadministrador (superadmin)**
- ✅ Todas las funciones de administrador
- ✅ Gestionar otros usuarios (crear, modificar, eliminar)
- ✅ Acceso al panel de migración
- ✅ Configuración avanzada del sistema

---

## 🔑 Credenciales Iniciales

### **Superadministrador por Defecto**
```
Email: admin@ebone.es
Contraseña: admin123!
```

> ⚠️ **IMPORTANTE**: Cambiar esta contraseña inmediatamente después del primer login

---

## 📝 Primeros Pasos

### 1. **Primer Login**
1. Ve a `/login.php`
2. Ingresa tu email y contraseña
3. Haz clic en "Acceder"
4. Serás redirigido al dashboard

### 2. **Cambiar Contraseña (Próximamente)**
*Esta funcionalidad se implementará en la Fase 2*

### 3. **Explorar el Sistema**
- **Dashboard**: Vista general de todas las líneas de negocio
- **Planner**: Gestión de publicaciones por línea de negocio
- **WordPress**: Configuración de integración con WordPress

---

## 🛠️ Funcionalidades Principales

### **Gestión de Publicaciones**
- ✅ Crear publicaciones para redes sociales
- ✅ Programar publicaciones futuras
- ✅ Gestionar estados (borrador, programado, publicado)
- ✅ Subir imágenes y contenido multimedia

### **Gestión de Blog Posts**
- ✅ Crear artículos de blog
- ✅ Gestionar categorías y etiquetas
- ✅ Publicar directamente a WordPress
- ✅ Programar publicaciones futuras

### **Configuración WordPress**
- ✅ Conectar con sitios WordPress individuales
- ✅ Configurar credenciales de API
- ✅ Gestionar categorías y etiquetas automáticamente

---

## 🔄 Migración del Sistema Anterior

### **Para Superadministradores**

1. **Acceder al Panel de Migración**
   - URL: `/admin_migration_helper.php`
   - Solo accessible por superadmins

2. **Crear Nuevos Administradores**
   - Usar el formulario en el panel de migración
   - Asignar roles apropiados
   - Comunicar credenciales temporales

3. **Verificar Migración**
   - Cada admin debe hacer login exitosamente
   - Cambiar contraseñas temporales
   - Confirmar acceso a todas las funcionalidades

4. **Desactivar Sistema Anterior**
   - Solo cuando todos hayan migrado
   - Seguir instrucciones del panel de migración

---

## 🚨 Solución de Problemas

### **No Puedo Acceder**
1. **Verificar credenciales**: Email y contraseña correctos
2. **Probar sistema anterior**: Dejar email vacío, usar solo contraseña
3. **Contactar superadmin**: Si el problema persiste

### **Error de Permisos**
1. **Verificar rol**: Confirmar que tienes el rol adecuado
2. **Contactar superadmin**: Para ajustes de permisos

### **Problemas con WordPress**
1. **Verificar configuración**: En `/wordpress_config.php`
2. **Validar credenciales**: App Password de WordPress
3. **Revisar conexión**: Test de conexión en la configuración

---

## 📞 Soporte y Contacto

### **Soporte Técnico**
- **Email**: `admin@ebone.es`
- **Contacto**: Superadministrador del sistema

### **Reportar Problemas**
- **Descripción**: Detalla el problema específico
- **Pasos**: Cómo reproducir el problema
- **Contexto**: Navegador, sistema operativo, etc.

---

## 🔮 Funcionalidades Futuras (Fase 2)

### **Gestión de Usuarios**
- ✅ Interfaz completa para gestión de usuarios
- ✅ Permisos granulares por línea de negocio
- ✅ Logs de actividad y auditoría

### **Seguridad Avanzada**
- ✅ Recuperación de contraseña por email
- ✅ Políticas de contraseña robustas
- ✅ Bloqueo de cuentas por intentos fallidos

### **Funcionalidades Colaborativas**
- ✅ Comentarios y aprobaciones
- ✅ Workflow de publicación
- ✅ Notificaciones por email

---

## 📄 Changelog

### **2025-01-23 - v1.0 (Fase 1)**
- ✅ Sistema de administradores implementado
- ✅ Login por email/contraseña
- ✅ Compatibilidad con sistema anterior
- ✅ Roles básicos (admin, superadmin)
- ✅ Panel de migración para superadmins

### **Próximas Actualizaciones**
- 🔄 Funcionalidades de Fase 2
- 🔄 Mejoras de seguridad
- 🔄 Interfaz de gestión de usuarios

---

## 🔒 Notas de Seguridad

### **Mejores Prácticas**
1. **Contraseñas seguras**: Mínimo 8 caracteres, mezcla de letras, números y símbolos
2. **Logout regular**: Cerrar sesión al terminar
3. **No compartir credenciales**: Cada usuario debe tener sus propias credenciales
4. **Reportar problemas**: Comunicar cualquier actividad sospechosa

### **Responsabilidades del Usuario**
- 🔑 Mantener credenciales seguras
- 🔄 Cambiar contraseñas temporales
- 📊 Usar el sistema de manera responsable
- 📞 Reportar problemas de seguridad

---

*Documentación actualizada: 2025-01-23*
*Versión del sistema: 1.0 (Fase 1)* 