<?php
/**
 * Main entry point for the Taller API.
 * Handles routing, CORS headers, and dispatches requests to appropriate controllers.
 */

require_once 'vendor/autoload.php';
require_once 'config/bootstrap.php';

use App\Controllers\TallerController;
use App\Controllers\AdminController;
use App\Middleware\AuthMiddleware;
use App\Utils\ApiResponse;
use App\Utils\ErrorHandler;

// Set CORS headers to allow cross-origin requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Register global error handler
ErrorHandler::register();

// Start session for user authentication
session_start();

// Get request URI and method
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Parse the path and remove prefix if exists
$path = parse_url($requestUri, PHP_URL_PATH);
// Remove any prefix before /api/ to make it work in different deployment paths
if (strpos($path, '/api/') !== 0) {
    $pos = strpos($path, '/api/');
    if ($pos !== false) {
        $path = substr($path, $pos);
    }
}

// Route the request based on path and method
try {
    if ($path === '/' || $path === '/index.php') {
        // API info endpoint
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
            // Create a new turno for the taller
            $controller = new TallerController();
            $controller->crearTurno($tallerId);
        }
    } elseif (preg_match('#^/api/v1/taller/(\d+)/estado$#', $path, $matches)) {
        $tallerId = (int)$matches[1];
        if ($requestMethod === 'GET') {
            // Get the estado of the taller
            $controller = new TallerController();
            $controller->obtenerEstado($tallerId);
        }
    } elseif ($path === '/api/v1/admin/login' && $requestMethod === 'POST') {
        // Admin login endpoint
        $controller = new AdminController();
        $controller->login();
    } elseif (preg_match('#^/api/v1/admin/taller/(\d+)/turnos$#', $path, $matches)) {
        $tallerId = (int)$matches[1];
        if ($requestMethod === 'GET') {
            // Require authentication for admin actions
            AuthMiddleware::requireAuth();
            // List turnos for the taller
            $controller = new AdminController();
            $controller->listarTurnos($tallerId);
        }
    } elseif (preg_match('#^/api/v1/admin/turno/(\d+)/finalizar$#', $path, $matches)) {
        $turnoId = (int)$matches[1];
        if ($requestMethod === 'POST') {
            // Require authentication
            AuthMiddleware::requireAuth();
            // Finalize the turno
            $controller = new AdminController();
            $controller->finalizarTurno($turnoId);
        }
    } elseif (preg_match('#^/api/v1/admin/taller/(\d+)/usuarios$#', $path, $matches)) {
        $tallerId = (int)$matches[1];
        if ($requestMethod === 'GET') {
            // List usuarios for the taller
            $controller = new AdminController();
            $controller->listarUsuarios($tallerId);
        } elseif ($requestMethod === 'POST') {
            // Create a new usuario for the taller
            $controller = new AdminController();
            $controller->crearUsuario($tallerId);
        }
    } elseif (preg_match('#^/api/v1/admin/usuario/(\d+)/password$#', $path, $matches)) {
        $usuarioId = (int)$matches[1];
        if ($requestMethod === 'PUT') {
            // Update password for the usuario
            $controller = new AdminController();
            $controller->actualizarPasswordUsuario($usuarioId);
        }
    } elseif (preg_match('#^/api/v1/admin/usuario/(\d+)$#', $path, $matches)) {
        $usuarioId = (int)$matches[1];
        if ($requestMethod === 'DELETE') {
            // Delete the usuario
            $controller = new AdminController();
            $controller->eliminarUsuario($usuarioId);
        }
    } elseif (preg_match('#^/api/v1/admin/taller/(\d+)/configuracion-email$#', $path, $matches)) {
        $tallerId = (int)$matches[1];
        if ($requestMethod === 'GET') {
            $controller = new AdminController();
            $controller->obtenerConfiguracionEmail($tallerId);
        } elseif ($requestMethod === 'POST') {
            $controller = new AdminController();
            $controller->guardarConfiguracionEmail($tallerId);
        }
    } elseif (preg_match('#^/api/v1/admin/taller/(\d+)/probar-email$#', $path, $matches)) {
        $tallerId = (int)$matches[1];
        if ($requestMethod === 'POST') {
            $controller = new AdminController();
            $controller->probarConfiguracionEmail($tallerId);
        }
    } elseif ($path === '/api/v1/admin/talleres' && $requestMethod === 'POST') {
        // Require authentication
        AuthMiddleware::requireAuth();
        // Create a new taller
        $controller = new AdminController();
        $controller->crearTaller();
    } else {
        // Endpoint not found
        ApiResponse::error('Endpoint no encontrado', 404);
    }
} catch (Exception $e) {
    // Let the ErrorHandler manage exceptions
    throw $e;
}