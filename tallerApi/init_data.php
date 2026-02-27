<?php
/**
 * Initialization script for creating initial data in the database.
 * Creates the super user "acaldeo" for system administration.
 */

require_once 'vendor/autoload.php';
require_once 'config/bootstrap.php';

use App\Entities\Usuario;
use App\Entities\Taller;
use App\Entities\Provincia;
use App\Entities\Localidad;

// Get the entity manager from global scope
$em = $GLOBALS['entityManager'];

// Crear provincias de Argentina (todas las 24)
$provinciasData = [
    ['Ciudad Autónoma de Buenos Aires (CABA)', 'AR-C'],
    ['Buenos Aires', 'AR-B'],
    ['Catamarca', 'AR-K'],
    ['Córdoba', 'AR-X'],
    ['Corrientes', 'AR-W'],
    ['Entre Ríos', 'AR-E'],
    ['Jujuy', 'AR-Y'],
    ['Mendoza', 'AR-M'],
    ['La Rioja', 'AR-F'],
    ['Salta', 'AR-A'],
    ['San Juan', 'AR-J'],
    ['San Luis', 'AR-D'],
    ['Santa Fe', 'AR-S'],
    ['Santiago del Estero', 'AR-G'],
    ['Tucumán', 'AR-T'],
    ['Chaco', 'AR-H'],
    ['Chubut', 'AR-U'],
    ['Formosa', 'AR-P'],
    ['Misiones', 'AR-N'],
    ['Neuquén', 'AR-Q'],
    ['La Pampa', 'AR-L'],
    ['Río Negro', 'AR-R'],
    ['Santa Cruz', 'AR-Z'],
    ['Tierra del Fuego', 'AR-V']
];

$provincias = [];
foreach ($provinciasData as $data) {
    $provincia = new Provincia();
    $provincia->setNombre($data[0])->setCodigo31662($data[1]);
    $em->persist($provincia);
    $provincias[] = $provincia;
}

// Flush para obtener IDs de provincias
$em->flush();

// Crear localidades de ejemplo (Mendoza y San Juan)
$mendoza = $provincias[7]; // Mendoza es el índice 7
$sanJuan = $provincias[10]; // San Juan es el índice 10

$mendozaCapital = new Localidad();
$mendozaCapital->setNombre('Mendoza Capital')
               ->setProvincia($mendoza)
               ->setCodigopostal(5500);
$em->persist($mendozaCapital);

$sanJuanCapital = new Localidad();
$sanJuanCapital->setNombre('San Juan Capital')
               ->setProvincia($sanJuan)
               ->setCodigopostal(5400);
$em->persist($sanJuanCapital);

// Flush para obtener IDs de localidades
$em->flush();

// Create example talleres with localidades
$taller1 = new Taller();
$taller1->setNombre('Taller Mecánico López')
        ->setLocalidad($mendozaCapital)
        ->setCapacidad(3);
$em->persist($taller1);

$taller2 = new Taller();
$taller2->setNombre('Taller Mecánico López')
        ->setLocalidad($sanJuanCapital)
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
echo "\nProvincias: " . count($provincias) . " provincias de Argentina\n";
echo "\nLocalidades:\n";
echo "- Mendoza Capital (ID: {$mendozaCapital->getId()})\n";
echo "- San Juan Capital (ID: {$sanJuanCapital->getId()})\n";
echo "\nTalleres:\n";
echo "- Taller Mecánico López - Mendoza Capital (ID: {$taller1->getId()})\n";
echo "- Taller Mecánico López - San Juan Capital (ID: {$taller2->getId()})\n";
echo "\nUsuarios:\n";
echo "- Usuario: acaldeo / Password: acaldeo123 (Super Admin)\n";
echo "- Usuario: admin_mendoza / Password: 123456 (Admin Mendoza)\n";
echo "- Usuario: admin_sanjuan / Password: 123456 (Admin San Juan)\n";
echo "\nEl usuario 'acaldeo' es super administrador y puede crear/eliminar talleres.\n";
