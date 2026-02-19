<?php
/**
 * Configuración de Doctrine ORM
 *
 * Este archivo configura el EntityManager de Doctrine utilizado en toda la aplicación
 * para interactuar con la base de datos MySQL utilizando el patrón ORM (Object-Relational Mapping).
 *
 * Propósito general:
 * - Configurar la conexión a la base de datos.
 * - Crear el EntityManager que maneja las entidades y las operaciones de base de datos.
 * - Actualizar automáticamente el esquema de la base de datos basado en las entidades definidas.
 *
 * Dependencias:
 * - Este archivo es incluido por index.php al inicio.
 * - Las entidades en src/Entities dependen de esta configuración para ser mapeadas a la base de datos.
 * - Los servicios y controladores usan el EntityManager global para acceder a datos.
 */

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\DBAL\DriverManager;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use App\Utils\Env;

// Cargar variables de entorno
Env::load(__DIR__ . '/..');

// Configurar metadatos utilizando Atributos de PHP
$config = ORMSetup::createAttributeMetadataConfiguration(
    paths: [__DIR__ . '/../src/Entities'],
    isDevMode: Env::get('APP_ENV', 'development') === 'development',
    cache: new ArrayAdapter()
);

// Parámetros de conexión desde variables de entorno
$connectionParams = [
    'dbname' => Env::get('DB_NAME', 'taller_turnos'),
    'user' => Env::get('DB_USER', 'root'),
    'password' => Env::get('DB_PASSWORD', ''),
    'host' => Env::get('DB_HOST', 'localhost'),
    'driver' => 'pdo_mysql',
    'charset' => 'utf8mb4',
];

// Crear la conexión a la base de datos
// Utiliza los parámetros anteriores para establecer la conexión
$connection = DriverManager::getConnection($connectionParams, $config);

// Crear el EntityManager (objeto principal de Doctrine)
// Este es el punto central para todas las operaciones de base de datos en la aplicación
$entityManager = new EntityManager($connection, $config);

// Creación/actualización automática de tablas basada en entidades
// Esto lee las entidades definidas y automáticamente crea o actualiza el esquema de la base de datos
// Útil en desarrollo, pero en producción se recomienda usar migraciones
$schemaTool = new \Doctrine\ORM\Tools\SchemaTool($entityManager);
$classes = $entityManager->getMetadataFactory()->getAllMetadata();
$schemaTool->updateSchema($classes);

// Hacer disponible el EntityManager globalmente para toda la aplicación
// Los controladores y servicios acceden a $GLOBALS['entityManager'] para realizar operaciones de BD
$GLOBALS['entityManager'] = $entityManager;