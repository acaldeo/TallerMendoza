<?php
/**
 * Initialization script for creating initial data in the database.
 * Creates a test workshop, an admin user, and the super user "acaldeo" for development purposes.
 */

require_once 'vendor/autoload.php';
require_once 'config/bootstrap.php';

use App\Entities\Taller;
use App\Entities\Usuario;

// Get the entity manager from global scope
$em = $GLOBALS['entityManager'];

// Create a test workshop
$taller = new Taller();
$taller->setNombre('Taller Mecánico Central')
       ->setCapacidad(3);

// Persist the workshop entity
$em->persist($taller);

// Create an admin user
$usuario = new Usuario();
$usuario->setTaller($taller)
        ->setUsuario('admin')
        ->setPasswordHash(password_hash('123456', PASSWORD_DEFAULT))
        ->setRol('empleado');

// Persist the user entity
$em->persist($usuario);

// Create super user "acaldeo" (can create/delete workshops, doesn't need own workshop)
$acaldeo = new Usuario();
$acaldeo->setUsuario('acaldeo')
        ->setPasswordHash('\$2y\$12\$sEIElcs./BtfYvZHVjOvcuh/ZcsdeHoJkgUJLlOs1mqHBUARlVkaO')
        ->setRol('super');

$em->persist($acaldeo);

// Flush changes to the database
$em->flush();

// Output confirmation of created data
echo "Datos iniciales creados:\n";
echo "- Taller ID: " . $taller->getId() . "\n";
echo "- Usuario: admin / Password: 123456\n";
echo "- Usuario: acaldeo / Password: acaldeo123\n";