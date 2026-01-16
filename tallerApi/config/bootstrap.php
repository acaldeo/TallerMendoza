<?php
/**
 * Doctrine ORM Configuration
 *
 * This file configures the Doctrine EntityManager used throughout the application
 * to interact with the MySQL database using the ORM pattern.
 */

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\DBAL\DriverManager;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

// Configure metadata using PHP Attributes
// - paths: directory where entities are located
// - isDevMode: development mode (true = no production cache)
// - cache: metadata caching system
$config = ORMSetup::createAttributeMetadataConfiguration(
    paths: [__DIR__ . '/../src/Entities'],
    isDevMode: true,
    cache: new ArrayAdapter()
);

// MySQL database connection parameters
$connectionParams = [
    'dbname' => 'taller_turnos',    // Database name
    'user' => 'root',               // MySQL user
    'password' => 'Matute@2025',    // MySQL password
    'host' => 'localhost',          // Database server
    'driver' => 'pdo_mysql',        // PDO driver for MySQL
    'charset' => 'utf8mb4',         // Character encoding
];

// Create database connection
$connection = DriverManager::getConnection($connectionParams, $config);

// Create EntityManager (main Doctrine object)
$entityManager = new EntityManager($connection, $config);

// Auto-creation/update of tables based on entities
// This reads entities and automatically creates/updates the DB schema
$schemaTool = new \Doctrine\ORM\Tools\SchemaTool($entityManager);
$classes = $entityManager->getMetadataFactory()->getAllMetadata();
$schemaTool->updateSchema($classes);

// Make EntityManager available globally for the entire application
$GLOBALS['entityManager'] = $entityManager;