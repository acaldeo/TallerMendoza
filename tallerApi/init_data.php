<?php
/**
 * Initialization script for creating initial data in the database.
 * Creates the super user "acaldeo" for system administration.
 */

require_once 'vendor/autoload.php';
require_once 'config/bootstrap.php';

use App\Entities\Usuario;

// Get the entity manager from global scope
$em = $GLOBALS['entityManager'];

// Create super user "acaldeo" (can create/delete workshops, doesn't need own workshop)
$acaldeo = new Usuario();
$acaldeo->setUsuario('acaldeo')
        ->setPasswordHash('$2y$12$sEIElcs./BtfYvZHVjOvcuh/ZcsdeHoJkgUJLlOs1mqHBUARlVkaO')
        ->setRol('super');

$em->persist($acaldeo);

// Flush changes to the database
$em->flush();

// Output confirmation of created data
echo "Datos iniciales creados:\n";
echo "- Usuario: acaldeo / Password: acaldeo123\n";
echo "\nEste usuario es super administrador y puede crear/eliminar talleres.\n";
