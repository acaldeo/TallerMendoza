# Refactorización: mendoza → tallerFront

## Resumen
Se renombró la carpeta `mendoza` a `tallerFront` para usar un nombre más descriptivo y genérico del frontend del sistema.

## Cambios Realizados

### 1. Estructura de Carpetas
```
ANTES:
/var/www/html/taller/
├── mendoza/
│   ├── css/
│   ├── js/
│   ├── index.html
│   └── admin.html

DESPUÉS:
/var/www/html/taller/
├── tallerFront/
│   ├── css/
│   ├── js/
│   ├── index.html
│   └── admin.html
```

### 2. Archivos Actualizados

**index.html** (página principal):
- `mendoza/index.html` → `tallerFront/index.html`
- `mendoza/admin.html` → `tallerFront/admin.html`

**setup.html** (configuración super usuario):
- `mendoza/css/styles.css` → `tallerFront/css/styles.css`
- `mendoza/js/api.js` → `tallerFront/js/api.js`

### 3. Compatibilidad con URLs Antiguas

Se creó `.htaccess` en `/var/www/html/taller/` con redirección 301:
```apache
RewriteRule ^mendoza/(.*)$ tallerFront/$1 [R=301,L]
```

Esto significa que:
- `http://localhost/taller/mendoza/index.html` → redirige a `tallerFront/index.html`
- `http://localhost/taller/mendoza/admin.html` → redirige a `tallerFront/admin.html`
- Enlaces antiguos siguen funcionando automáticamente

## URLs Actualizadas

### Antes
- Vista Cliente: `http://localhost/taller/mendoza/index.html`
- Panel Admin: `http://localhost/taller/mendoza/admin.html`

### Ahora
- Vista Cliente: `http://localhost/taller/tallerFront/index.html`
- Panel Admin: `http://localhost/taller/tallerFront/admin.html`

## Verificación

Todos los archivos cargan correctamente:
- ✅ `tallerFront/index.html` (HTTP 200)
- ✅ `tallerFront/admin.html` (HTTP 200)
- ✅ `tallerFront/js/api.js` (HTTP 200)
- ✅ `tallerFront/css/styles.css` (HTTP 200)

## Impacto

- ✅ Sin pérdida de funcionalidad
- ✅ URLs antiguas redirigen automáticamente
- ✅ Nombre más descriptivo y profesional
- ✅ Mejor organización del proyecto

## Fecha
Refactorización completada: 2025
