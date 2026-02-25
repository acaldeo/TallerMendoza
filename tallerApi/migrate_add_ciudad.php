<?php
/**
 * Script de migración para agregar el campo 'ciudad' a talleres existentes.
 * Este script actualiza la base de datos para agregar la columna ciudad
 * y asigna un valor por defecto a los talleres existentes.
 */

require_once 'vendor/autoload.php';
require_once 'config/bootstrap.php';

use App\Entities\Taller;

$em = $GLOBALS['entityManager'];
$connection = $em->getConnection();

try {
    echo "Iniciando migración...\n\n";

    // Verificar si la columna ya existe
    $sql = "SHOW COLUMNS FROM talleres LIKE 'ciudad'";
    $stmt = $connection->prepare($sql);
    $result = $stmt->executeQuery();
    
    if ($result->rowCount() > 0) {
        echo "La columna 'ciudad' ya existe en la tabla talleres.\n";
        echo "No se requiere migración.\n";
        exit(0);
    }

    // Agregar la columna ciudad
    echo "Agregando columna 'ciudad' a la tabla talleres...\n";
    $sql = "ALTER TABLE talleres ADD COLUMN ciudad VARCHAR(100) NOT NULL DEFAULT 'Sin especificar'";
    $connection->executeStatement($sql);
    echo "✓ Columna agregada correctamente.\n\n";

    // Actualizar talleres existentes con valor por defecto
    $talleres = $em->getRepository(Taller::class)->findAll();
    
    if (count($talleres) > 0) {
        echo "Actualizando " . count($talleres) . " taller(es) existente(s)...\n";
        foreach ($talleres as $taller) {
            if (!$taller->getCiudad() || $taller->getCiudad() === 'Sin especificar') {
                echo "- Taller ID {$taller->getId()}: {$taller->getNombre()} -> Ciudad: 'Sin especificar'\n";
            }
        }
        $em->flush();
        echo "✓ Talleres actualizados.\n\n";
    }

    echo "Migración completada exitosamente.\n";
    echo "\nNOTA: Actualiza manualmente la ciudad de cada taller desde el panel admin.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
