# Migración: Campo Ciudad → Localidad con Normalización

## Resumen
Se reemplazó el campo `ciudad` (VARCHAR) por `localidad_id` (INT) en la tabla `talleres`, agregando las tablas `provincias` y `localidades` para normalizar la base de datos.

## Cambios en la Base de Datos

### Nuevas Tablas

**provincias:**
```sql
CREATE TABLE provincias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL
);
```

**localidades:**
```sql
CREATE TABLE localidades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    provincia_id INT NOT NULL,
    FOREIGN KEY (provincia_id) REFERENCES provincias(id)
);
```

**talleres (modificada):**
```sql
CREATE TABLE talleres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    localidad_id INT NOT NULL,  -- Antes era: ciudad VARCHAR(100)
    capacidad INT NOT NULL DEFAULT 3,
    logo VARCHAR(255) NULL,
    FOREIGN KEY (localidad_id) REFERENCES localidades(id)
);
```

## Cambios en el Backend

### Nuevas Entidades

1. **Provincia.php**
   - Propiedades: id, nombre
   - Relación OneToMany con Localidad

2. **Localidad.php**
   - Propiedades: id, nombre, provincia_id
   - Relación ManyToOne con Provincia
   - Relación OneToMany con Taller

3. **Taller.php (modificada)**
   - Eliminado: `getCiudad()`, `setCiudad()`
   - Agregado: `getLocalidad()`, `setLocalidad()`
   - Relación ManyToOne con Localidad

### Controladores Actualizados

**TallerController:**
- `listarTalleres()`: Ahora retorna `localidad`, `provincia` y `localidadId`

**AdminController:**
- `crearTaller()`: Ahora requiere `localidadId` en lugar de `ciudad`
- Valida que la localidad exista antes de crear el taller

### Nuevos Endpoints

```
GET /api/v1/provincias
Response: [{"id": 1, "nombre": "Mendoza"}, ...]

GET /api/v1/provincias/{id}/localidades
Response: [{"id": 1, "nombre": "Mendoza Capital"}, ...]
```

## Cambios en el Frontend

### setup.html
- Campo de texto "Ciudad" reemplazado por selector "Localidad"
- Opciones hardcodeadas temporalmente (se puede mejorar cargando dinámicamente)

### tallerFront/index.html
- Muestra: "Nombre - Localidad, Provincia" en lugar de "Nombre - Ciudad"

## Datos Iniciales

El script `init_data.php` ahora crea:

**Provincias:**
- Mendoza (ID: 1)
- San Juan (ID: 2)
- Córdoba (ID: 3)

**Localidades:**
- Mendoza Capital (ID: 1, Provincia: Mendoza)
- San Juan Capital (ID: 2, Provincia: San Juan)
- Córdoba Capital (ID: 3, Provincia: Córdoba)

**Talleres:**
- Taller Mecánico López - Mendoza Capital
- Taller Mecánico López - San Juan Capital

## Migración de Datos Existentes

### Para Instalación Nueva:
```bash
cd /var/www/html/taller/tallerApi
mysql -u root -p -e "DROP DATABASE IF EXISTS taller_turnos; CREATE DATABASE taller_turnos;"
php init_data.php
```

### Para Migrar Datos Existentes:

**Paso 1: Crear nuevas tablas**
```sql
CREATE TABLE provincias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL
);

CREATE TABLE localidades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    provincia_id INT NOT NULL,
    FOREIGN KEY (provincia_id) REFERENCES provincias(id)
);
```

**Paso 2: Insertar provincias y localidades**
```sql
INSERT INTO provincias (nombre) VALUES ('Mendoza'), ('San Juan'), ('Córdoba');

INSERT INTO localidades (nombre, provincia_id) VALUES
('Mendoza Capital', 1),
('San Juan Capital', 2),
('Córdoba Capital', 3);
```

**Paso 3: Agregar columna temporal y migrar datos**
```sql
ALTER TABLE talleres ADD COLUMN localidad_id INT NULL;

-- Mapear ciudades existentes a localidades
UPDATE talleres SET localidad_id = 1 WHERE ciudad = 'Mendoza';
UPDATE talleres SET localidad_id = 2 WHERE ciudad = 'San Juan';
UPDATE talleres SET localidad_id = 3 WHERE ciudad = 'Córdoba';

-- Verificar que todos los talleres tienen localidad
SELECT * FROM talleres WHERE localidad_id IS NULL;
```

**Paso 4: Hacer localidad_id obligatorio y eliminar ciudad**
```sql
ALTER TABLE talleres MODIFY localidad_id INT NOT NULL;
ALTER TABLE talleres ADD FOREIGN KEY (localidad_id) REFERENCES localidades(id);
ALTER TABLE talleres DROP COLUMN ciudad;
```

## Respuestas API Actualizadas

### GET /api/v1/talleres
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "nombre": "Taller Mecánico López",
      "localidad": "Mendoza Capital",
      "provincia": "Mendoza",
      "localidadId": 1,
      "capacidad": 3,
      "logo": null,
      "logoUrl": null
    }
  ]
}
```

### POST /api/v1/admin/talleres
```json
{
  "nombre": "Taller Nuevo",
  "localidadId": 3,
  "capacidad": 5,
  "usuario": "admin_nuevo",
  "password": "123456"
}
```

## Ventajas de la Normalización

1. **Integridad de Datos**: No se pueden crear talleres con localidades inexistentes
2. **Consistencia**: Nombres de localidades y provincias estandarizados
3. **Escalabilidad**: Fácil agregar nuevas provincias y localidades
4. **Consultas Eficientes**: Joins optimizados en lugar de búsquedas por texto
5. **Mantenimiento**: Cambiar nombre de localidad actualiza todos los talleres automáticamente

## Mejoras Futuras

1. Cargar provincias y localidades dinámicamente en setup.html
2. Agregar endpoint para crear nuevas localidades
3. Agregar más localidades por provincia
4. Implementar búsqueda de talleres por provincia/localidad
