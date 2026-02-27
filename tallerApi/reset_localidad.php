<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/bootstrap.php';

$entityManager = $GLOBALS['entityManager'];
$connection = $entityManager->getConnection();

try {
    $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
    $connection->executeStatement('DELETE FROM localidad');
    $connection->executeStatement('ALTER TABLE localidad AUTO_INCREMENT = 1');
    $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
    
    echo "✓ Datos de la tabla 'localidad' eliminados\n";
    echo "✓ Contador AUTO_INCREMENT reiniciado\n";
} catch (\Exception $e) {
    $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
    echo "✗ Error: " . $e->getMessage() . "\n";
}
