# TallerApi - API Backend

API REST en PHP puro con Doctrine ORM para gestión multi-taller de turnos.

## Instalación

1. **Instalar dependencias:**
```bash
composer install
```

2. **Configurar base de datos:**
- Crear base de datos MySQL: `taller_turnos`
- Ajustar credenciales en `config/bootstrap.php`

3. **Inicializar datos:**
```bash
php init_data.php
```

## Endpoints

### Cliente (Público)

**Crear turno:**
```
POST /api/v1/taller/{taller_id}/turnos
Content-Type: application/json

{
    "nombreCliente": "Juan Pérez",
    "telefono": "1234567890",
    "modeloVehiculo": "Toyota Corolla 2020",
    "descripcionProblema": "Ruido en el motor"
}
```

**Ver estado del taller:**
```
GET /api/v1/taller/{taller_id}/estado
```

### Admin (Autenticado)

**Login:**
```
POST /api/v1/admin/login
Content-Type: application/json

{
    "usuario": "admin",
    "password": "123456"
}
```

**Listar turnos:**
```
GET /api/v1/admin/taller/{taller_id}/turnos
```

**Finalizar turno:**
```
POST /api/v1/admin/turno/{id}/finalizar
```

## Reglas de Negocio

- **Multi-taller:** Cada taller maneja sus turnos independientemente
- **Capacidad:** Configurable por taller (default: 3)
- **Estados:** EN_ESPERA → EN_TALLER → FINALIZADO
- **Promoción automática:** Al finalizar un turno, el siguiente en espera pasa a EN_TALLER
- **Numeración continua:** Los números de turno no se reinician

## Arquitectura

```
src/
├── Entities/          # Entidades Doctrine
├── Services/          # Lógica de negocio
├── Controllers/       # Controladores HTTP
├── Middleware/        # Middleware de autenticación
└── Repositories/      # Repositorios personalizados

config/
└── bootstrap.php      # Configuración Doctrine

index.php             # Front Controller
```

## Características Técnicas

- **ORM:** Doctrine con PHP Attributes
- **Transacciones:** Bloqueo pesimista para numeración
- **Seguridad:** Sesiones PHP + middleware
- **Validación:** Input sanitization
- **Auto-schema:** Creación automática de tablas