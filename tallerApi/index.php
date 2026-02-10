<?php
/**
 * Punto de entrada principal de la API del Taller.
 * Este archivo maneja el enrutamiento de las solicitudes HTTP, configura los encabezados CORS para permitir solicitudes de origen cruzado,
 * registra el manejador de errores global y despacha las solicitudes a los controladores apropiados.
 *
 * Flujo de ejecución general:
 * 1. Se incluyen los archivos necesarios (autoload y bootstrap).
 * 2. Se configuran los encabezados CORS.
 * 3. Se maneja la solicitud OPTIONS (preflight).
 * 4. Se registra el manejador de errores.
 * 5. Se inicia la sesión para autenticación.
 * 6. Se parsea la URI de la solicitud.
 * 7. Se enruta la solicitud basada en la ruta y el método HTTP.
 * 8. Si ocurre una excepción, se lanza para que el ErrorHandler la gestione.
 *
 * Dependencias:
 * - Utiliza TallerController y AdminController para manejar las lógicas de negocio.
 * - Usa AuthMiddleware para verificar autenticación en rutas protegidas.
 * - ApiResponse para enviar respuestas estandarizadas.
 * - ErrorHandler para manejar excepciones globalmente.
 */

require_once 'vendor/autoload.php';
require_once 'config/bootstrap.php';

use App\Controllers\TallerController;
use App\Controllers\AdminController;
use App\Middleware\AuthMiddleware;
use App\Utils\ApiResponse;
use App\Utils\ErrorHandler;

// Configurar encabezados CORS para permitir solicitudes de origen cruzado
// Whitelist de orígenes permitidos para seguridad
$allowedOrigins = ['https://tallermendoza.com', 'http://localhost', 'http://127.0.0.1'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// Manejar solicitud OPTIONS (preflight) para CORS
// Las solicitudes OPTIONS son enviadas por el navegador antes de la solicitud real
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Registrar el manejador de errores global
// Esto asegura que todas las excepciones sean manejadas de manera consistente
ErrorHandler::register();

// Iniciar sesión para manejar la autenticación de usuarios
// Las sesiones se usan para mantener el estado de login del administrador
session_start();

// Obtener la URI y el método de la solicitud HTTP
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Parsear la ruta y remover prefijos si existen
// Esto permite que la API funcione en diferentes rutas de despliegue
$path = parse_url($requestUri, PHP_URL_PATH);
// Remover cualquier prefijo antes de /api/ para hacer que funcione en diferentes rutas de despliegue
if (strpos($path, '/api/') !== 0) {
    $pos = strpos($path, '/api/');
    if ($pos !== false) {
        $path = substr($path, $pos);
    }
}

// Enrutar la solicitud basada en la ruta y el método
// Cada ruta corresponde a un endpoint específico de la API
try {
    if ($path === '/' || $path === '/index.php') {
        // Endpoint de información de la API
        // Devuelve una lista de endpoints disponibles para documentación
        ApiResponse::success([
            'message' => 'API Taller Turnos v1',
            'endpoints' => [
                'POST /api/v1/taller/{id}/turnos' => 'Crear turno',
                'GET /api/v1/taller/{id}/estado' => 'Estado del taller',
                'POST /api/v1/admin/login' => 'Login admin',
                'GET /api/v1/admin/taller/{id}/turnos' => 'Listar turnos',
                'POST /api/v1/admin/turno/{id}/finalizar' => 'Finalizar turno'
            ]
        ]);
    } elseif (preg_match('#^/api/v1/taller/(\d+)/turnos$#', $path, $matches)) {
        $tallerId = (int)$matches[1];
        if ($requestMethod === 'POST') {
            // Crear un nuevo turno para el taller especificado
            // Este endpoint permite a los clientes crear turnos sin autenticación
            $controller = new TallerController();
            $controller->crearTurno($tallerId);
        }
    } elseif (preg_match('#^/api/v1/taller/(\d+)/estado$#', $path, $matches)) {
        $tallerId = (int)$matches[1];
        if ($requestMethod === 'GET') {
            // Obtener el estado del taller (número de turnos en cola, etc.)
            // Información pública para mostrar en el frontend del cliente
            $controller = new TallerController();
            $controller->obtenerEstado($tallerId);
        }
    } elseif ($path === '/api/v1/admin/login' && $requestMethod === 'POST') {
        // Endpoint de login para administradores
        // Autentica al usuario y establece la sesión
        $controller = new AdminController();
        $controller->login();
    } elseif (preg_match('#^/api/v1/admin/taller/(\d+)/turnos$#', $path, $matches)) {
        $tallerId = (int)$matches[1];
        if ($requestMethod === 'GET') {
            // Requiere autenticación para acciones de admin
            // Lista todos los turnos del taller para gestión administrativa
            AuthMiddleware::requireAuth();
            $controller = new AdminController();
            $controller->listarTurnos($tallerId);
        }
    } elseif (preg_match('#^/api/v1/admin/turno/(\d+)/finalizar$#', $path, $matches)) {
        $turnoId = (int)$matches[1];
        if ($requestMethod === 'POST') {
            // Requiere autenticación
            // Finaliza un turno específico, marcándolo como completado
            AuthMiddleware::requireAuth();
            $controller = new AdminController();
            $controller->finalizarTurno($turnoId);
        }
    } elseif (preg_match('#^/api/v1/admin/taller/(\d+)/usuarios$#', $path, $matches)) {
        $tallerId = (int)$matches[1];
        if ($requestMethod === 'GET') {
            // Lista los usuarios asociados al taller
            $controller = new AdminController();
            $controller->listarUsuarios($tallerId);
        } elseif ($requestMethod === 'POST') {
            // Crear un nuevo usuario para el taller
            $controller = new AdminController();
            $controller->crearUsuario($tallerId);
        }
    } elseif (preg_match('#^/api/v1/admin/usuario/(\d+)/password$#', $path, $matches)) {
        $usuarioId = (int)$matches[1];
        if ($requestMethod === 'PUT') {
            // Actualizar la contraseña de un usuario específico
            $controller = new AdminController();
            $controller->actualizarPasswordUsuario($usuarioId);
        }
    } elseif (preg_match('#^/api/v1/admin/usuario/(\d+)$#', $path, $matches)) {
        $usuarioId = (int)$matches[1];
        if ($requestMethod === 'DELETE') {
            // Eliminar un usuario del sistema
            $controller = new AdminController();
            $controller->eliminarUsuario($usuarioId);
        }
    } elseif (preg_match('#^/api/v1/admin/taller/(\d+)/configuracion-email$#', $path, $matches)) {
        $tallerId = (int)$matches[1];
        if ($requestMethod === 'GET') {
            // Obtener la configuración de email del taller
            $controller = new AdminController();
            $controller->obtenerConfiguracionEmail($tallerId);
        } elseif ($requestMethod === 'POST') {
            // Guardar o actualizar la configuración de email
            $controller = new AdminController();
            $controller->guardarConfiguracionEmail($tallerId);
        }
    } elseif (preg_match('#^/api/v1/admin/taller/(\d+)/probar-email$#', $path, $matches)) {
        $tallerId = (int)$matches[1];
        if ($requestMethod === 'POST') {
            // Probar la configuración de email enviando un email de prueba
            $controller = new AdminController();
            $controller->probarConfiguracionEmail($tallerId);
        }
    } elseif (preg_match('#^/api/v1/admin/taller/(\d+)/logo$#', $path, $matches)) {
        $tallerId = (int)$matches[1];
        if ($requestMethod === 'GET') {
            // Obtener el logo del taller (requiere autenticación)
            AuthMiddleware::requireAuth();
            $controller = new AdminController();
            $controller->obtenerLogo($tallerId);
        } elseif ($requestMethod === 'POST') {
            // Subir el logo del taller (requiere autenticación)
            AuthMiddleware::requireAuth();
            $controller = new AdminController();
            $controller->subirLogo($tallerId);
        } elseif ($requestMethod === 'DELETE') {
            // Eliminar el logo del taller (requiere autenticación)
            AuthMiddleware::requireAuth();
            $controller = new AdminController();
            $controller->eliminarLogo($tallerId);
        }
    } elseif (preg_match('#^/api/v1/taller/(\d+)/logo$#', $path, $matches)) {
        $tallerId = (int)$matches[1];
        if ($requestMethod === 'GET') {
            // Obtener el logo del taller (público)
            $controller = new TallerController();
            $controller->obtenerLogo($tallerId);
        }
    } elseif ($path === '/api/v1/admin/talleres' && $requestMethod === 'POST') {
        // Requiere autenticación
        // Crear un nuevo taller en el sistema
        AuthMiddleware::requireAuth();
        $controller = new AdminController();
        $controller->crearTaller();
    } elseif ($path === '/api/v1/admin/taller' && $requestMethod === 'DELETE') {
        // Requiere autenticación
        // Eliminar el taller del usuario actual con todos sus datos
        AuthMiddleware::requireAuth();
        $controller = new AdminController();
        $controller->eliminarTaller();
    } elseif (preg_match('#^/api/v1/admin/talleres/(\d+)$#', $path, $matches)) {
        $tallerId = (int)$matches[1];
        if ($requestMethod === 'DELETE') {
            // Requiere autenticación
            // Eliminar taller por ID (solo para super usuario acaldeo)
            AuthMiddleware::requireAuth();
            $controller = new AdminController();
            $controller->eliminarTallerPorId($tallerId);
        }
    } elseif ($path === '/api/v1/admin/talleres/seleccionar' && $requestMethod === 'POST') {
        // Requiere autenticación
        // Seleccionar un taller para administrar (para super usuario)
        AuthMiddleware::requireAuth();
        $controller = new AdminController();
        $controller->seleccionarTaller();
    } elseif ($path === '/api/v1/admin/password' && $requestMethod === 'PUT') {
        // Requiere autenticación
        // Cambiar contraseña del usuario actual (solo para acaldeo)
        AuthMiddleware::requireAuth();
        $controller = new AdminController();
        $controller->cambiarPasswordPropia();
    } elseif ($path === '/api/v1/talleres' && $requestMethod === 'GET') {
        // Listar todos los talleres disponibles (público)
        $controller = new TallerController();
        $controller->listarTalleres();
    } else {
        // Endpoint no encontrado
        // Si ninguna ruta coincide, devolver error 404
        ApiResponse::error('Endpoint no encontrado', 404);
    }
} catch (Exception $e) {
    // Dejar que el ErrorHandler gestione las excepciones
    // Esto permite un manejo centralizado de errores
    throw $e;
}