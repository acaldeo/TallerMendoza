# Guía de Instalación / Installation Guide

## Taller Turnos API - Sistema de Gestión de Turnos

---

# ESPAÑOL

## 1. Requisitos del Servidor

### Requisitos Mínimos
- **PHP**: Versión 8.1 o superior
- **MySQL**: Versión 5.7 o superior / MariaDB 10.2 o superior
- **Composer**: Gestor de dependencias de PHP
- **Extensiones PHP requeridas**:
  - `pdo_mysql`
  - `mbstring`
  - `tokenizer`
  - `xml`
  - `ctype`
  - `json`
  - `curl`

### Requisitos Recomendados para Producción
- **Servidor Web**: Apache 2.4+ o Nginx 1.18+
- **RAM**: Mínimo 512MB (recomendado 1GB+)
- **Espacio en Disco**: Mínimo 100MB

---

## 2. Preparación del Entorno

### 2.1. Verificar Versión de PHP

```bash
php -v
```

Debería mostrar PHP 8.1 o superior.

### 2.2. Verificar Composer

```bash
composer --version
```

Si no tienes Composer instalado, instálalo desde [getcomposer.org](https://getcomposer.org)

### 2.3. Verificar MySQL

```bash
mysql --version
```

---

## 3. Instalación Paso a Paso

### Paso 1: Clonar o Descargar el Proyecto

```bash
# Opción A: Clonar desde repositorio
git clone <url-del-repositorio> /var/www/html/taller

# Opción B: Descargar y extraer
cd /var/www/html
wget <url-del-archivo>.zip
unzip taller.zip
```

### Paso 2: Instalar Dependencias con Composer

```bash
cd /var/www/html/taller/tallerApi
composer install
```

Este comando instalará:
- **doctrine/orm**: ORM para interacción con base de datos
- **doctrine/dbal**: Capa de abstracción de base de datos
- **doctrine/annotations**: Metadatos para entidades
- **symfony/cache**: Sistema de caché
- **phpmailer/phpmailer**: Envío de correos electrónicos

### Paso 3: Crear la Base de Datos

Conéctate a MySQL y crea la base de datos:

```bash
mysql -u root -p
```

Ejecuta los siguientes comandos en MySQL:

```sql
-- Crear la base de datos
CREATE DATABASE taller_turnos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Crear usuario (recomendado para seguridad)
CREATE USER 'taller_user'@'localhost' IDENTIFIED BY 'tu_contraseña_segura';

-- Otorgar permisos
GRANT ALL PRIVILEGES ON taller_turnos.* TO 'taller_user'@'localhost';

-- Aplicar cambios
FLUSH PRIVILEGES;

-- Salir
EXIT;
```

### Paso 4: Configurar la Conexión a Base de Datos

Edita el archivo `tallerApi/config/bootstrap.php`:

```php
// Parámetros de conexión a la base de datos MySQL
$connectionParams = [
    'dbname' => 'taller_turnos',    // Nombre de la base de datos
    'user' => 'taller_user',        // Usuario de MySQL
    'password' => 'tu_contraseña_segura',  // Contraseña
    'host' => 'localhost',          // Servidor de base de datos
    'driver' => 'pdo_mysql',        // Driver PDO para MySQL
    'charset' => 'utf8mb4',         // Codificación de caracteres
];
```

> ⚠️ **Importante**: Para producción, usa credenciales seguras y considera usar variables de entorno.

### Paso 5: Configurar el Servidor Web

#### Opción A: Apache

Asegúrate de tener el módulo `mod_rewrite` habilitado y crea un VirtualHost:

```apache
<VirtualHost *:80>
    ServerName taller.local
    DocumentRoot /var/www/html/taller
    
    <Directory /var/www/html/taller>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/taller-error.log
    CustomLog ${APACHE_LOG_DIR}/taller-access.log combined
</VirtualHost>
```

Habilita el sitio y reinicia Apache:

```bash
a2ensite taller.conf
a2enmod rewrite
systemctl restart apache2
```

#### Opción B: Nginx

Crea un archivo de configuración:

```nginx
server {
    listen 80;
    server_name taller.local;
    root /var/www/html/taller;
    index index.html index.php;

    location / {
        try_files $uri $uri/ /index.html;
    }

    location /tallerApi/ {
        try_files $uri $uri/ /tallerApi/index.php?$args;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

Habilita el sitio:

```bash
ln -s /etc/nginx/sites-available/taller.conf /etc/nginx/sites-enabled/
nginx -t
systemctl restart nginx
```

### Paso 6: Configurar Permisos de Archivos

```bash
cd /var/www/html/taller
# Permisos para archivos
find . -type f -exec chmod 644 {} \;
# Permisos para directorios
find . -type d -exec chmod 755 {} \;
# Permisos de escritura para uploads si los hay
chmod 775 uploads/
```

---

## 4. Inicialización de la Base de Datos

### Método Automático (Doctrine ORM)

La aplicación está configurada para crear automáticamente las tablas en el primer acceso. Doctrine ORM leerá las entidades en `tallerApi/src/Entities/` y creará el esquema.

### Método Manual (Opcional)

Si prefieres crear las tablas manualmente:

```sql
USE taller_turnos;

-- Tabla de talleres
CREATE TABLE talleres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    direccion VARCHAR(500),
    telefono VARCHAR(50),
    email VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de turnos
CREATE TABLE turnos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    taller_id INT,
    cliente_nombre VARCHAR(255) NOT NULL,
    cliente_telefono VARCHAR(50),
    cliente_email VARCHAR(255),
    vehiculo_patente VARCHAR(50),
    vehiculo_modelo VARCHAR(255),
    descripcion TEXT,
    estado ENUM('pendiente', 'en_proceso', 'finalizado', 'cancelado') DEFAULT 'pendiente',
    fecha_turno DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (taller_id) REFERENCES talleres(id)
);

-- Tabla de usuarios administradores
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    taller_id INT,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'empleado') DEFAULT 'empleado',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (taller_id) REFERENCES talleres(id)
);
```

---

## 5. Configuración Adicional

### 5.1. Configuración de Email (PHPMailer)

Edita `tallerApi/src/Services/EmailService.php` para configurar el servidor SMTP:

```php
$mailer = new PHPMailer\PHPMailer\PHPMailer();
$mailer->isSMTP();
$mailer->Host = 'smtp.tu-servidor.com';  // Servidor SMTP
$mailer->SMTPAuth = true;
$mailer->Username = 'tu-email@dominio.com';
$mailer->Password = 'tu-contraseña';
$mailer->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
$mailer->Port = 587;
```

### 5.2. Configuración de Producción

En el archivo `tallerApi/config/bootstrap.php`, cambia el modo de desarrollo:

```php
$config = ORMSetup::createAttributeMetadataConfiguration(
    paths: [__DIR__ . '/../src/Entities'],
    isDevMode: false,  // Cambiar a false en producción
    cache: new \Symfony\Component\Cache\Adapter\PhpFilesAdapter()  // Caché persistente
);
```

### 5.3. Variables de Entorno (Opcional)

Crea un archivo `.env` en `tallerApi/.env`:

```env
DB_HOST=localhost
DB_NAME=taller_turnos
DB_USER=taller_user
DB_PASSWORD=tu_contraseña_segura
DB_CHARSET=utf8mb4

MAIL_HOST=smtp.tu-servidor.com
MAIL_PORT=587
MAIL_USERNAME=tu-email@dominio.com
MAIL_PASSWORD=tu_contraseña
```

---

## 6. Verificación de la Instalación

### 6.1. Verificar PHP y Extensiones

```bash
php -m | grep -E "pdo_mysql|mbstring|curl|json"
```

### 6.2. Probar la API

Accede a la URL de la API:

```
http://tu-dominio/tallerApi/api/v1/
```

Deberías ver:

```json
{
  "status": "success",
  "data": {
    "message": "API Taller Turnos v1",
    "endpoints": [...]
  }
}
```

### 6.3. Probar Conexión a Base de Datos

```bash
cd /var/www/html/taller/tallerApi
php -r "
require_once 'vendor/autoload.php';
require_once 'config/bootstrap.php';
echo 'Conexión exitosa a la base de datos';
"
```

### 6.4. Verificar Endpoints

- `GET /api/v1/taller/{id}/estado` - Obtener estado del taller
- `POST /api/v1/taller/{id}/turnos` - Crear turno
- `POST /api/v1/admin/login` - Login de administrador

---

## 7. Solución de Problemas Comunes

### Error: "Connection refused" a MySQL
- Verifica que MySQL esté corriendo: `systemctl status mysql`
- Verifica el puerto (por defecto 3306): `netstat -tlnp | grep 3306`

### Error: "Class not found" de Doctrine
- Ejecuta `composer dump-autoload` para regenerar el autoloader

### Error: 500 Internal Server Error
- Revisa los logs de Apache/Nginx: `tail -f /var/log/apache2/taller-error.log`
- Revisa los logs de PHP: `tail -f /var/log/php8.1-fpm.log`

### Error: Permisos denegados
- Verifica los permisos: `chmod -R 755 /var/www/html/taller`
- Verifica el propietario: `chown -R www-data:www-data /var/www/html/taller`

### Error: PHPMailer no encontrado
- Ejecuta `composer install` en la carpeta `tallerApi/`
- Verifica que la carpeta `vendor/` exista

---

## 8. Mantenimiento

### Actualizar Dependencias

```bash
cd /var/www/html/taller/tallerApi
composer update
```

### Backup de Base de Datos

```bash
mysqldump -u taller_user -p taller_turnos > backup_taller_$(date +%Y%m%d).sql
```

### Restaurar Backup

```bash
mysql -u taller_user -p taller_turnos < backup_taller_20240101.sql
```

---

# ENGLISH

## 1. Server Requirements

### Minimum Requirements
- **PHP**: Version 8.1 or higher
- **MySQL**: Version 5.7 or higher / MariaDB 10.2 or higher
- **Composer**: PHP dependency manager
- **Required PHP Extensions**:
  - `pdo_mysql`
  - `mbstring`
  - `tokenizer`
  - `xml`
  - `ctype`
  - `json`
  - `curl`

### Recommended Requirements for Production
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **RAM**: Minimum 512MB (1GB+ recommended)
- **Disk Space**: Minimum 100MB

---

## 2. Environment Preparation

### 2.1. Verify PHP Version

```bash
php -v
```

Should show PHP 8.1 or higher.

### 2.2. Verify Composer

```bash
composer --version
```

If you don't have Composer installed, install it from [getcomposer.org](https://getcomposer.org)

### 2.3. Verify MySQL

```bash
mysql --version
```

---

## 3. Step-by-Step Installation

### Step 1: Clone or Download the Project

```bash
# Option A: Clone from repository
git clone <repository-url> /var/www/html/taller

# Option B: Download and extract
cd /var/www/html
wget <archive-url>.zip
unzip taller.zip
```

### Step 2: Install Dependencies with Composer

```bash
cd /var/www/html/taller/tallerApi
composer install
```

This command will install:
- **doctrine/ORM**: ORM for database interaction
- **doctrine/dbal**: Database abstraction layer
- **doctrine/annotations**: Metadata for entities
- **symfony/cache**: Caching system
- **phpmailer/phpmailer**: Email sending

### Step 3: Create the Database

Connect to MySQL and create the database:

```bash
mysql -u root -p
```

Execute the following commands in MySQL:

```sql
-- Create the database
CREATE DATABASE taller_turnos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user (recommended for security)
CREATE USER 'taller_user'@'localhost' IDENTIFIED BY 'your_secure_password';

-- Grant privileges
GRANT ALL PRIVILEGES ON taller_turnos.* TO 'taller_user'@'localhost';

-- Apply changes
FLUSH PRIVILEGES;

-- Exit
EXIT;
```

### Step 4: Configure Database Connection

Edit the file `tallerApi/config/bootstrap.php`:

```php
// MySQL database connection parameters
$connectionParams = [
    'dbname' => 'taller_turnos',    // Database name
    'user' => 'taller_user',        // MySQL user
    'password' => 'your_secure_password',  // Password
    'host' => 'localhost',          // Database server
    'driver' => 'pdo_mysql',        // PDO driver for MySQL
    'charset' => 'utf8mb4',         // Character encoding
];
```

> ⚠️ **Important**: For production, use secure credentials and consider using environment variables.

### Step 5: Configure Web Server

#### Option A: Apache

Make sure the `mod_rewrite` module is enabled and create a VirtualHost:

```apache
<VirtualHost *:80>
    ServerName taller.local
    DocumentRoot /var/www/html/taller
    
    <Directory /var/www/html/taller>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/taller-error.log
    CustomLog ${APACHE_LOG_DIR}/taller-access.log combined
</VirtualHost>
```

Enable the site and restart Apache:

```bash
a2ensite taller.conf
a2enmod rewrite
systemctl restart apache2
```

#### Option B: Nginx

Create a configuration file:

```nginx
server {
    listen 80;
    server_name taller.local;
    root /var/www/html/taller;
    index index.html index.php;

    location / {
        try_files $uri $uri/ /index.html;
    }

    location /tallerApi/ {
        try_files $uri $uri/ /tallerApi/index.php?$args;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

Enable the site:

```bash
ln -s /etc/nginx/sites-available/taller.conf /etc/nginx/sites-enabled/
nginx -t
systemctl restart nginx
```

### Step 6: Configure File Permissions

```bash
cd /var/www/html/taller
# Permissions for files
find . -type f -exec chmod 644 {} \;
# Permissions for directories
find . -type d -exec chmod 755 {} \;
# Write permissions for uploads if any
chmod 775 uploads/
```

---

## 4. Database Initialization

### Automatic Method (Doctrine ORM)

The application is configured to automatically create tables on first access. Doctrine ORM will read entities in `tallerApi/src/Entities/` and create the schema.

### Manual Method (Optional)

If you prefer to create tables manually:

```sql
USE taller_turnos;

-- Workshops table
CREATE TABLE talleres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    direccion VARCHAR(500),
    telefono VARCHAR(50),
    email VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Appointments table
CREATE TABLE turnos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    taller_id INT,
    cliente_nombre VARCHAR(255) NOT NULL,
    cliente_telefono VARCHAR(50),
    cliente_email VARCHAR(255),
    vehiculo_patente VARCHAR(50),
    vehiculo_modelo VARCHAR(255),
    descripcion TEXT,
    estado ENUM('pendiente', 'en_proceso', 'finalizado', 'cancelado') DEFAULT 'pendiente',
    fecha_turno DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (taller_id) REFERENCES talleres(id)
);

-- Admin users table
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    taller_id INT,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'empleado') DEFAULT 'empleado',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (taller_id) REFERENCES talleres(id)
);
```

---

## 5. Additional Configuration

### 5.1. Email Configuration (PHPMailer)

Edit `tallerApi/src/Services/EmailService.php` to configure the SMTP server:

```php
$mailer = new PHPMailer\PHPMailer\PHPMailer();
$mailer->isSMTP();
$mailer->Host = 'smtp.your-server.com';  // SMTP server
$mailer->SMTPAuth = true;
$mailer->Username = 'your-email@domain.com';
$mailer->Password = 'your-password';
$mailer->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
$mailer->Port = 587;
```

### 5.2. Production Configuration

In the file `tallerApi/config/bootstrap.php`, change the development mode:

```php
$config = ORMSetup::createAttributeMetadataConfiguration(
    paths: [__DIR__ . '/../src/Entities'],
    isDevMode: false,  // Change to false in production
    cache: new \Symfony\Component\Cache\Adapter\PhpFilesAdapter()  // Persistent cache
);
```

### 5.3. Environment Variables (Optional)

Create an `.env` file in `tallerApi/.env`:

```env
DB_HOST=localhost
DB_NAME=taller_turnos
DB_USER=taller_user
DB_PASSWORD=your_secure_password
DB_CHARSET=utf8mb4

MAIL_HOST=smtp.your-server.com
MAIL_PORT=587
MAIL_USERNAME=your-email@domain.com
MAIL_PASSWORD=your_password
```

---

## 6. Installation Verification

### 6.1. Verify PHP and Extensions

```bash
php -m | grep -E "pdo_mysql|mbstring|curl|json"
```

### 6.2. Test the API

Access the API URL:

```
http://your-domain/tallerApi/api/v1/
```

You should see:

```json
{
  "status": "success",
  "data": {
    "message": "API Taller Turnos v1",
    "endpoints": [...]
  }
}
```

### 6.3. Test Database Connection

```bash
cd /var/www/html/taller/tallerApi
php -r "
require_once 'vendor/autoload.php';
require_once 'config/bootstrap.php';
echo 'Database connection successful';
"
```

### 6.4. Verify Endpoints

- `GET /api/v1/taller/{id}/estado` - Get workshop status
- `POST /api/v1/taller/{id}/turnos` - Create appointment
- `POST /api/v1/admin/login` - Admin login

---

## 7. Common Troubleshooting

### Error: "Connection refused" to MySQL
- Verify MySQL is running: `systemctl status mysql`
- Verify the port (default 3306): `netstat -tlnp | grep 3306`

### Error: Doctrine "Class not found"
- Run `composer dump-autoload` to regenerate the autoloader

### Error: 500 Internal Server Error
- Check Apache/Nginx logs: `tail -f /var/log/apache2/taller-error.log`
- Check PHP logs: `tail -f /var/log/php8.1-fpm.log`

### Error: Permission denied
- Check permissions: `chmod -R 755 /var/www/html/taller`
- Check owner: `chown -R www-data:www-data /var/www/html/taller`

### Error: PHPMailer not found
- Run `composer install` in the `tallerApi/` folder
- Verify the `vendor/` folder exists

---

## 8. Maintenance

### Update Dependencies

```bash
cd /var/www/html/taller/tallerApi
composer update
```

### Database Backup

```bash
mysqldump -u taller_user -p taller_turnos > backup_taller_$(date +%Y%m%d).sql
```

### Restore Backup

```bash
mysql -u taller_user -p taller_turnos < backup_taller_20240101.sql
```

---

## 9. API Endpoints Reference

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/taller/{id}/estado` | Get workshop status |
| POST | `/api/v1/taller/{id}/turnos` | Create appointment |
| POST | `/api/v1/admin/login` | Admin login |
| GET | `/api/v1/admin/taller/{id}/turnos` | List appointments (auth required) |
| POST | `/api/v1/admin/turno/{id}/finalizar` | Finish appointment (auth required) |
| GET | `/api/v1/admin/taller/{id}/usuarios` | List users |
| POST | `/api/v1/admin/taller/{id}/usuarios` | Create user |
| PUT | `/api/v1/admin/usuario/{id}/password` | Update password |
| DELETE | `/api/v1/admin/usuario/{id}` | Delete user |
| GET | `/api/v1/admin/taller/{id}/configuracion-email` | Get email config |
| POST | `/api/v1/admin/taller/{id}/configuracion-email` | Save email config |
| POST | `/api/v1/admin/taller/{id}/probar-email` | Test email config |
| POST | `/api/v1/admin/talleres` | Create workshop (auth required) |

---

## 10. Security Recommendations

1. **Use HTTPS**: Always use SSL/TLS certificates in production
2. **Secure Credentials**: Use environment variables for database passwords
3. **Limit Permissions**: Don't use root MySQL user in applications
4. **Regular Updates**: Keep PHP, MySQL, and dependencies updated
5. **Firewall**: Configure firewall to only allow necessary ports
6. **Backup**: Implement regular backup schedules
7. **Log Monitoring**: Monitor logs for suspicious activity

---

**Fecha de creación / Created**: 2024
**Versión / Version**: 1.0
