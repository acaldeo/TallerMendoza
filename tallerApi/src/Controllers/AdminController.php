<?php
/**
 * AdminController handles administrative operations for the workshop management system.
 * This controller manages user authentication, listing and finalizing turns, and user management.
 */

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\TurnoService;
use App\Services\UsuarioService;
use App\Services\ConfiguracionService;
use App\Middleware\AuthMiddleware;
use App\Utils\ApiResponse;
use App\Validators\AuthValidator;
use App\Validators\UsuarioValidator;
use Exception;

class AdminController
{
    /** @var AuthService Service for handling authentication operations */
    private AuthService $authService;
    /** @var TurnoService Service for managing turn-related operations */
    private TurnoService $turnoService;
    /** @var UsuarioService Service for managing user-related operations */
    private UsuarioService $usuarioService;
    /** @var ConfiguracionService Service for managing email configuration */
    private ConfiguracionService $configuracionService;

    /**
     * Constructor initializes the authentication, turn, and user services using the global entity manager.
     */
    public function __construct()
    {
        $this->authService = new AuthService($GLOBALS['entityManager']);
        $this->turnoService = new TurnoService($GLOBALS['entityManager']);
        $this->usuarioService = new UsuarioService($GLOBALS['entityManager']);
        $this->configuracionService = new ConfiguracionService($GLOBALS['entityManager']);
    }

    // === AUTHENTICATION ===

    /**
     * Handles admin login by validating input data and authenticating the user.
     * Returns user details on success or an error on failure.
     * @return void
     */
    public function login(): void
    {
        try {
            // Decode JSON input from the request body
            $input = json_decode(file_get_contents('php://input'), true);

            // Check if JSON is valid
            if (!$input) {
                ApiResponse::error('Invalid JSON', 400);
                return;
            }

            // Validate login input
            $errors = AuthValidator::validateLogin($input);
            if (!empty($errors)) {
                ApiResponse::error(implode(', ', $errors), 400);
                return;
            }

            // Attempt to login using auth service
            $user = $this->authService->login($input['usuario'], $input['password']);

            // Return success response with user details
            ApiResponse::success([
                'id' => $user->getId(),
                'usuario' => $user->getUsuario(),
                'tallerId' => $user->getTaller()->getId(),
                'tallerNombre' => $user->getTaller()->getNombre()
            ]);
        } catch (Exception $e) {
            // Handle authentication errors
            ApiResponse::error('Invalid credentials', 401);
        }
    }

    // === TURN MANAGEMENT ===

    /**
     * Lists turns for a specific workshop with optional filtering after verifying access.
     * @param int $tallerId The ID of the workshop
     * @return void
     */
    public function listarTurnos(int $tallerId): void
    {
        try {
            // Verify that the user has access to the workshop
            AuthMiddleware::requireTallerAccess($tallerId);

            // Get filters from query parameters
            $filtros = [];
            if (isset($_GET['patente'])) {
                $filtros['patente'] = $_GET['patente'];
            }

            // Retrieve turns for the workshop with filters
            $turnos = $this->turnoService->listarTurnosTaller($tallerId, $filtros);

            // Return success response with turns data
            ApiResponse::success(['turnos' => $turnos]);
        } catch (Exception $e) {
            // Re-throw exceptions for higher-level handling
            throw $e;
        }
    }

    /**
     * Finalizes a specific turn by updating its status.
     * @param int $turnoId The ID of the turn to finalize
     * @return void
     */
    public function finalizarTurno(int $turnoId): void
    {
        try {
            // Finalize the turn using the turn service
            $this->turnoService->finalizarTurno($turnoId);

            // Return success response
            ApiResponse::success(['message' => 'Turno finalized successfully']);
        } catch (Exception $e) {
            // Re-throw exceptions for higher-level handling
            throw $e;
        }
    }

    // === USER MANAGEMENT ===

    /**
     * Lists all users for a specific workshop after verifying access.
     * @param int $tallerId The ID of the workshop
     * @return void
     */
    public function listarUsuarios(int $tallerId): void
    {
        try {
            // Verify that the user has access to the workshop
            AuthMiddleware::requireTallerAccess($tallerId);

            // Retrieve users for the workshop
            $usuarios = $this->usuarioService->listarUsuarios($tallerId);

            // Return success response with users data
            ApiResponse::success(['usuarios' => $usuarios]);
        } catch (Exception $e) {
            // Re-throw exceptions for higher-level handling
            throw $e;
        }
    }

    /**
     * Creates a new administrative user for the workshop.
     * @param int $tallerId The ID of the workshop
     * @return void
     */
    public function crearUsuario(int $tallerId): void
    {
        try {
            // Require authentication
            AuthMiddleware::requireAuth();
            // For setup purposes, skip taller access check
            // AuthMiddleware::requireTallerAccess($tallerId);

            // Decode JSON input from the request body
            $input = json_decode(file_get_contents('php://input'), true);

            // Check if JSON is valid
            if (!$input) {
                ApiResponse::error('Invalid JSON', 400);
                return;
            }

            // Validate user creation input
            $errors = UsuarioValidator::validateCrear($input);
            if (!empty($errors)) {
                ApiResponse::error(implode(', ', $errors), 400);
                return;
            }

            // Create the user using the user service
            $usuario = $this->usuarioService->crearUsuario($tallerId, $input);

            // Return success response with user details
            ApiResponse::success([
                'id' => $usuario->getId(),
                'usuario' => $usuario->getUsuario(),
                'message' => 'User created successfully'
            ], 201);
        } catch (Exception $e) {
            // Re-throw exceptions for higher-level handling
            throw $e;
        }
    }

    /**
     * Updates the password of a specific user.
     * @param int $usuarioId The ID of the user
     * @return void
     */
    public function actualizarPasswordUsuario(int $usuarioId): void
    {
        try {
            // Require authentication
            AuthMiddleware::requireAuth();

            // Decode JSON input from the request body
            $input = json_decode(file_get_contents('php://input'), true);

            // Check if JSON is valid
            if (!$input) {
                ApiResponse::error('Invalid JSON', 400);
                return;
            }

            // Validate password input
            $errors = UsuarioValidator::validatePassword($input);
            if (!empty($errors)) {
                ApiResponse::error(implode(', ', $errors), 400);
                return;
            }

            // Update the password using the user service
            $this->usuarioService->actualizarPassword($usuarioId, $input['password']);

            // Return success response
            ApiResponse::success(['message' => 'Password updated successfully']);
        } catch (Exception $e) {
            // Re-throw exceptions for higher-level handling
            throw $e;
        }
    }

    /**
     * Deletes a specific user.
     * @param int $usuarioId The ID of the user to delete
     * @return void
     */
    public function eliminarUsuario(int $usuarioId): void
    {
        try {
            // Require authentication
            AuthMiddleware::requireAuth();

            // Get current user ID from session
            $usuarioActualId = $_SESSION['user_id'];
            // Delete the user using the user service
            $this->usuarioService->eliminarUsuario($usuarioId, $usuarioActualId);

            // Return success response
            ApiResponse::success(['message' => 'User deleted successfully']);
        } catch (Exception $e) {
            // Re-throw exceptions for higher-level handling
            throw $e;
        }
    }

    // === EMAIL CONFIGURATION ===

    /**
     * Gets email configuration for a workshop
     */
    public function obtenerConfiguracionEmail(int $tallerId): void
    {
        try {
            AuthMiddleware::requireTallerAccess($tallerId);
            
            $config = $this->configuracionService->obtenerConfiguracion($tallerId);
            
            ApiResponse::success(['configuracion' => $config]);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Saves email configuration for a workshop
     */
    public function guardarConfiguracionEmail(int $tallerId): void
    {
        try {
            AuthMiddleware::requireTallerAccess($tallerId);
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                ApiResponse::error('JSON inválido', 400);
                return;
            }
            
            $this->configuracionService->guardarConfiguracion($tallerId, $input);
            
            ApiResponse::success(['message' => 'Configuración guardada correctamente']);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Creates a new workshop
     */
    public function crearTaller(): void
    {
        try {
            // Require authentication
            AuthMiddleware::requireAuth();

            // Decode JSON input from the request body
            $input = json_decode(file_get_contents('php://input'), true);

            // Check if JSON is valid
            if (!$input) {
                ApiResponse::error('Invalid JSON', 400);
                return;
            }

            // Validate input
            if (empty($input['nombre']) || !isset($input['capacidad'])) {
                ApiResponse::error('Nombre y capacidad son requeridos', 400);
                return;
            }

            // Create taller entity
            $taller = new \App\Entities\Taller();
            $taller->setNombre($input['nombre'])
                   ->setCapacidad((int)$input['capacidad']);

            // Persist to database
            $em = $GLOBALS['entityManager'];
            $em->persist($taller);
            $em->flush();

            // Return success response
            ApiResponse::success([
                'id' => $taller->getId(),
                'nombre' => $taller->getNombre(),
                'capacidad' => $taller->getCapacidad()
            ], 201);
        } catch (Exception $e) {
            // Re-throw exceptions for higher-level handling
            throw $e;
        }
    }

    /**
     * Tests email configuration by sending a test email
     */
    public function probarConfiguracionEmail(int $tallerId): void
    {
        try {
            AuthMiddleware::requireTallerAccess($tallerId);

            $resultado = $this->configuracionService->probarConfiguracion($tallerId);

            if ($resultado) {
                ApiResponse::success(['message' => 'Email de prueba enviado correctamente']);
            } else {
                ApiResponse::error('Error al enviar email de prueba', 500);
            }
        } catch (Exception $e) {
            throw $e;
        }
    }
}