# Migración: Campo Ciudad en Talleres

## Descripción
Se agregó el campo `ciudad` a la entidad `Taller` para permitir distinguir talleres con el mismo nombre en diferentes ubicaciones.

## Cambios Realizados

### Backend
1. **Entidad Taller** (`src/Entities/Taller.php`):
   - Agregado campo `ciudad` (VARCHAR 100, NOT NULL)
   - Agregados métodos `getCiudad()` y `setCiudad()`

2. **Controladores**:
   - `TallerController::listarTalleres()` - Incluye ciudad en respuesta
   - `AdminController::crearTaller()` - Requiere ciudad al crear taller

3. **Script de Inicialización** (`init_data.php`):
   - Crea 2 talleres de ejemplo: "Taller Mecánico López - Mendoza" y "Taller Mecánico López - San Juan"
   - Crea usuarios admin para cada taller

### Frontend
1. **Vista Cliente** (`mendoza/index.html`):
   - Selector de talleres muestra "Nombre - Ciudad"

2. **Panel Admin** (`mendoza/js/admin.js`):
   - Formulario de creación de taller incluye campo ciudad
   - Variable reactiva `tallerForm.ciudad`

## Migración de Datos Existentes

### Opción 1: Recrear Base de Datos (Recomendado para desarrollo)
```bash
cd /var/www/html/taller/tallerApi
mysql -u root -p -e "DROP DATABASE IF EXISTS taller_turnos; CREATE DATABASE taller_turnos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php init_data.php
```

### Opción 2: Migración Sin Pérdida de Datos (Producción)
```bash
cd /var/www/html/taller/tallerApi
php migrate_add_ciudad.php
```

Este script:
- Verifica si la columna `ciudad` ya existe
- Agrega la columna con valor por defecto "Sin especificar"
- Mantiene todos los datos existentes (turnos, usuarios, configuraciones)

Después de ejecutar la migración, actualiza manualmente la ciudad de cada taller desde el panel admin o directamente en la base de datos:

```sql
UPDATE talleres SET ciudad = 'Mendoza' WHERE id = 1;
UPDATE talleres SET ciudad = 'San Juan' WHERE id = 2;
```

## Estructura de Datos

### Tabla `talleres`
```sql
CREATE TABLE talleres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    ciudad VARCHAR(100) NOT NULL,
    capacidad INT NOT NULL DEFAULT 3,
    logo VARCHAR(255) NULL
);
```

### Respuesta API `/api/v1/talleres`
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "nombre": "Taller Mecánico López",
            "ciudad": "Mendoza",
            "capacidad": 3,
            "logo": null,
            "logoUrl": null
        },
        {
            "id": 2,
            "nombre": "Taller Mecánico López",
            "ciudad": "San Juan",
            "capacidad": 3,
            "logo": null,
            "logoUrl": null
        }
    ]
}
```

## Validación

### Crear Taller (Admin)
```json
POST /api/v1/admin/talleres
{
    "nombre": "Taller López",
    "ciudad": "Córdoba",
    "capacidad": 5,
    "usuario": "admin_cordoba",
    "password": "123456"
}
```

### Verificar Cambios
1. Acceder a http://localhost/taller/mendoza/
2. Verificar que el selector de talleres muestre "Nombre - Ciudad"
3. Crear un turno y verificar que funciona correctamente

## Notas
- El campo `ciudad` es obligatorio al crear nuevos talleres
- Los talleres existentes tendrán "Sin especificar" hasta que se actualice manualmente
- La ciudad se muestra en todas las vistas del frontend para mejor identificación
