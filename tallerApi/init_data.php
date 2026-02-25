<?php
/**
 * Initialization script for creating initial data in the database.
 * Creates the super user "acaldeo" for system administration.
 */

require_once 'vendor/autoload.php';
require_once 'config/bootstrap.php';

use App\Entities\Usuario;
use App\Entities\Taller;

// Get the entity manager from global scope
$em = $GLOBALS['entityManager'];

// Create example talleres with cities
$taller1 = new Taller();
$taller1->setNombre('Taller Mecánico López')
        ->setCiudad('Mendoza')
        ->setCapacidad(3);
$em->persist($taller1);

$taller2 = new Taller();
$taller2->setNombre('Taller Mecánico López')
        ->setCiudad('San Juan')
        ->setCapacidad(3);
$em->persist($taller2);

// Flush to get IDs
$em->flush();

// Create super user "acaldeo" (can create/delete workshops, doesn't need own workshop)
$acaldeo = new Usuario();
$acaldeo->setUsuario('acaldeo')
        ->setPasswordHash('$2y$12$sEIElcs./BtfYvZHVjOvcuh/ZcsdeHoJkgUJLlOs1mqHBUARlVkaO')
        ->setRol('super');
$em->persist($acaldeo);

// Create admin user for taller1
$admin1 = new Usuario();
$admin1->setUsuario('admin_mendoza')
       ->setPasswordHash(password_hash('123456', PASSWORD_BCRYPT))
       ->setTaller($taller1)
       ->setRol('admin');
$em->persist($admin1);

// Create admin user for taller2
$admin2 = new Usuario();
$admin2->setUsuario('admin_sanjuan')
       ->setPasswordHash(password_hash('123456', PASSWORD_BCRYPT))
       ->setTaller($taller2)
       ->setRol('admin');
$em->persist($admin2);

// Flush changes to the database
$em->flush();

// Output confirmation of created data
echo "Datos iniciales creados:\n";
echo "\nTalleres:\n";
echo "- Taller Mecánico López - Mendoza (ID: {$taller1->getId()})\n";
echo "- Taller Mecánico López - San Juan (ID: {$taller2->getId()})\n";
echo "\nUsuarios:\n";
echo "- Usuario: acaldeo / Password: acaldeo123 (Super Admin)\n";
echo "- Usuario: admin_mendoza / Password: 123456 (Admin Mendoza)\n";
echo "- Usuario: admin_sanjuan / Password: 123456 (Admin San Juan)\n";
echo "\nEl usuario 'acaldeo' es super administrador y puede crear/eliminar talleres.\n";
