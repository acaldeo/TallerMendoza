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

// Configurar metadatos utilizando Atributos de PHP
// - paths: directorio donde se ubican las entidades (clases que representan tablas)
// - isDevMode: modo desarrollo (true = sin caché de producción, útil para desarrollo)
// - cache: sistema de caché para metadatos (ArrayAdapter es para desarrollo, en producción usar otro como Redis)
$config = ORMSetup::createAttributeMetadataConfiguration(
    paths: [__DIR__ . '/../src/Entities'],
    isDevMode: true,
    cache: new ArrayAdapter()
);

// Parámetros de conexión a la base de datos MySQL
// Estos parámetros definen cómo conectarse al servidor de base de datos
$connectionParams = [
    'dbname' => 'taller_turnos',    // Nombre de la base de datos
    'user' => 'root',               // Usuario de MySQL
    'password' => 'Matute@2025',    // Contraseña de MySQL
    'host' => 'localhost',          // Servidor de base de datos
    'driver' => 'pdo_mysql',        // Driver PDO para MySQL
    'charset' => 'utf8mb4',         // Codificación de caracteres (soporta emojis y caracteres especiales)
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