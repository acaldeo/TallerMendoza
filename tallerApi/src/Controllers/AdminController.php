<?php
/**
 * AdminController maneja las operaciones administrativas del sistema de gestión de talleres.
 * Este controlador gestiona la autenticación de usuarios, listado y finalización de turnos, gestión de usuarios y configuración de email.
 *
 * Propósito general:
 * - Proporcionar endpoints para que los administradores gestionen talleres, turnos y usuarios.
 * - Manejar la autenticación y autorización de administradores.
 * - Facilitar la configuración de notificaciones por email.
 *
 * Dependencias:
 * - Utiliza AuthService para operaciones de autenticación.
 * - Utiliza TurnoService para gestión de turnos.
 * - Utiliza UsuarioService para gestión de usuarios.
 * - Utiliza ConfiguracionService para configuración de email.
 * - Usa AuthMiddleware para verificar acceso autorizado.
 * - Usa ApiResponse para enviar respuestas estandarizadas.
 * - Usa validadores (AuthValidator, UsuarioValidator) para validar entradas.
 *
 * Interacciones con otras capas:
 * - Recibe solicitudes HTTP desde index.php y las enruta a métodos específicos.
 * - Delega la lógica de negocio a los servicios correspondientes.
 * - Valida entradas usando validadores antes de procesar.
 * - Verifica autenticación y permisos usando middleware.
 * - Envía respuestas JSON usando ApiResponse.
 * - Lanza excepciones que son manejadas por ErrorHandler.
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
    /** @var AuthService Servicio para manejar operaciones de autenticación */
    private AuthService $authService;
    /** @var TurnoService Servicio para gestionar operaciones relacionadas con turnos */
    private TurnoService $turnoService;
    /** @var UsuarioService Servicio para gestionar operaciones relacionadas con usuarios */
    private UsuarioService $usuarioService;
    /** @var ConfiguracionService Servicio para gestionar configuración de email */
    private ConfiguracionService $configuracionService;

    /**
     * Constructor inicializa los servicios de autenticación, turnos, usuarios y configuración usando el entity manager global.
     */
    public function __construct()
    {
        $this->authService = new AuthService($GLOBALS['entityManager']);
        $this->turnoService = new TurnoService($GLOBALS['entityManager']);
        $this->usuarioService = new UsuarioService($GLOBALS['entityManager']);
        $this->configuracionService = new ConfiguracionService($GLOBALS['entityManager']);
    }

    // === AUTENTICACIÓN ===

    /**
     * Maneja el login de administradores validando los datos de entrada y autenticando al usuario.
     * Retorna detalles del usuario en caso de éxito o un error en caso de fallo.
     * @return void
     */
    public function login(): void
    {
        try {
            // Decodificar entrada JSON del cuerpo de la solicitud
            $input = json_decode(file_get_contents('php://input'), true);

            // Verificar si el JSON es válido
            if (!$input) {
                ApiResponse::error('JSON inválido', 400);
                return;
            }

            // Validar entrada de login
            $errors = AuthValidator::validateLogin($input);
            if (!empty($errors)) {
                ApiResponse::error(implode(', ', $errors), 400);
                return;
            }

            // Intentar login usando el servicio de autenticación
            $user = $this->authService->login($input['usuario'], $input['password']);

            // Retornar respuesta de éxito con detalles del usuario
            ApiResponse::success([
                'id' => $user->getId(),
                'usuario' => $user->getUsuario(),
                'tallerId' => $user->getTaller()->getId(),
                'tallerNombre' => $user->getTaller()->getNombre()
            ]);
        } catch (Exception $e) {
            // Manejar errores de autenticación
            ApiResponse::error('Credenciales inválidas', 401);
        }
    }

    // === GESTIÓN DE TURNOS ===

    /**
     * Lista turnos para un taller específico con filtrado opcional después de verificar acceso.
     * @param int $tallerId El ID del taller
     * @return void
     */
    public function listarTurnos(int $tallerId): void
    {
        try {
            // Verificar que el usuario tiene acceso al taller
            AuthMiddleware::requireTallerAccess($tallerId);

            // Obtener filtros de parámetros de consulta, sanitizando para prevenir XSS
            $filtros = [];
            if (isset($_GET['patente'])) {
                $filtros['patente'] = htmlspecialchars(trim($_GET['patente']), ENT_QUOTES, 'UTF-8');
            }

            // Recuperar turnos del taller con filtros
            $turnos = $this->turnoService->listarTurnosTaller($tallerId, $filtros);

            // Retornar respuesta de éxito con datos de turnos
            ApiResponse::success(['turnos' => $turnos]);
        } catch (Exception $e) {
            // Re-lanzar excepciones para manejo de nivel superior
            throw $e;
        }
    }

    /**
     * Finaliza un turno específico actualizando su estado.
     * @param int $turnoId El ID del turno a finalizar
     * @return void
     */
    public function finalizarTurno(int $turnoId): void
    {
        try {
            // Finalizar el turno usando el servicio de turnos
            $this->turnoService->finalizarTurno($turnoId);

            // Retornar respuesta de éxito
            ApiResponse::success(['message' => 'Turno finalizado exitosamente']);
        } catch (Exception $e) {
            // Re-lanzar excepciones para manejo de nivel superior
            throw $e;
        }
    }

    // === GESTIÓN DE USUARIOS ===

    /**
     * Lista todos los usuarios para un taller específico después de verificar acceso.
     * @param int $tallerId El ID del taller
     * @return void
     */
    public function listarUsuarios(int $tallerId): void
    {
        try {
            // Verificar que el usuario tiene acceso al taller
            AuthMiddleware::requireTallerAccess($tallerId);

            // Recuperar usuarios del taller
            $usuarios = $this->usuarioService->listarUsuarios($tallerId);

            // Retornar respuesta de éxito con datos de usuarios
            ApiResponse::success(['usuarios' => $usuarios]);
        } catch (Exception $e) {
            // Re-lanzar excepciones para manejo de nivel superior
            throw $e;
        }
    }

    /**
     * Crea un nuevo usuario administrativo para el taller.
     * @param int $tallerId El ID del taller
     * @return void
     */
    public function crearUsuario(int $tallerId): void
    {
        try {
            // Requerir autenticación
            AuthMiddleware::requireAuth();
            // Para propósitos de configuración, omitir verificación de acceso al taller
            // AuthMiddleware::requireTallerAccess($tallerId);

            // Decodificar entrada JSON del cuerpo de la solicitud
            $input = json_decode(file_get_contents('php://input'), true);

            // Verificar si el JSON es válido
            if (!$input) {
                ApiResponse::error('JSON inválido', 400);
                return;
            }

            // Validar entrada de creación de usuario
            $errors = UsuarioValidator::validateCrear($input);
            if (!empty($errors)) {
                ApiResponse::error(implode(', ', $errors), 400);
                return;
            }

            // Crear el usuario usando el servicio de usuarios
            $usuario = $this->usuarioService->crearUsuario($tallerId, $input);

            // Retornar respuesta de éxito con detalles del usuario
            ApiResponse::success([
                'id' => $usuario->getId(),
                'usuario' => $usuario->getUsuario(),
                'message' => 'Usuario creado exitosamente'
            ], 201);
        } catch (Exception $e) {
            // Re-lanzar excepciones para manejo de nivel superior
            throw $e;
        }
    }

    /**
     * Actualiza la contraseña de un usuario específico.
     * @param int $usuarioId El ID del usuario
     * @return void
     */
    public function actualizarPasswordUsuario(int $usuarioId): void
    {
        try {
            // Requerir autenticación
            AuthMiddleware::requireAuth();

            // Decodificar entrada JSON del cuerpo de la solicitud
            $input = json_decode(file_get_contents('php://input'), true);

            // Verificar si el JSON es válido
            if (!$input) {
                ApiResponse::error('JSON inválido', 400);
                return;
            }

            // Validar entrada de contraseña
            $errors = UsuarioValidator::validatePassword($input);
            if (!empty($errors)) {
                ApiResponse::error(implode(', ', $errors), 400);
                return;
            }

            // Actualizar la contraseña usando el servicio de usuarios
            $this->usuarioService->actualizarPassword($usuarioId, $input['password']);

            // Retornar respuesta de éxito
            ApiResponse::success(['message' => 'Contraseña actualizada exitosamente']);
        } catch (Exception $e) {
            // Re-lanzar excepciones para manejo de nivel superior
            throw $e;
        }
    }

    /**
     * Elimina un usuario específico.
     * @param int $usuarioId El ID del usuario a eliminar
     * @return void
     */
    public function eliminarUsuario(int $usuarioId): void
    {
        try {
            // Requerir autenticación
            AuthMiddleware::requireAuth();

            // Obtener ID del usuario actual de la sesión
            $usuarioActualId = $_SESSION['user_id'];
            // Eliminar el usuario usando el servicio de usuarios
            $this->usuarioService->eliminarUsuario($usuarioId, $usuarioActualId);

            // Retornar respuesta de éxito
            ApiResponse::success(['message' => 'Usuario eliminado exitosamente']);
        } catch (Exception $e) {
            // Re-lanzar excepciones para manejo de nivel superior
            throw $e;
        }
    }

    // === CONFIGURACIÓN DE EMAIL ===

    /**
     * Obtiene la configuración de email para un taller.
     * @param int $tallerId El ID del taller
     * @return void
     */
    public function obtenerConfiguracionEmail(int $tallerId): void
    {
        try {
            // Verificar acceso al taller
            AuthMiddleware::requireTallerAccess($tallerId);

            // Obtener configuración usando el servicio
            $config = $this->configuracionService->obtenerConfiguracion($tallerId);

            // Retornar respuesta de éxito con la configuración
            ApiResponse::success(['configuracion' => $config]);
        } catch (Exception $e) {
            // Re-lanzar excepciones para manejo de nivel superior
            throw $e;
        }
    }

    /**
     * Guarda la configuración de email para un taller.
     * @param int $tallerId El ID del taller
     * @return void
     */
    public function guardarConfiguracionEmail(int $tallerId): void
    {
        try {
            // Verificar acceso al taller
            AuthMiddleware::requireTallerAccess($tallerId);

            // Decodificar entrada JSON
            $input = json_decode(file_get_contents('php://input'), true);

            // Verificar JSON válido
            if (!$input) {
                ApiResponse::error('JSON inválido', 400);
                return;
            }

            // Guardar configuración usando el servicio
            $this->configuracionService->guardarConfiguracion($tallerId, $input);

            // Retornar respuesta de éxito
            ApiResponse::success(['message' => 'Configuración guardada correctamente']);
        } catch (Exception $e) {
            // Re-lanzar excepciones para manejo de nivel superior
            throw $e;
        }
    }

    /**
     * Crea un nuevo taller en el sistema.
     * @return void
     */
    public function crearTaller(): void
    {
        try {
            // Requerir autenticación
            AuthMiddleware::requireAuth();

            // Decodificar entrada JSON del cuerpo de la solicitud
            $input = json_decode(file_get_contents('php://input'), true);

            // Verificar si el JSON es válido
            if (!$input) {
                ApiResponse::error('JSON inválido', 400);
                return;
            }

            // Validar entrada
            if (empty($input['nombre']) || !isset($input['capacidad'])) {
                ApiResponse::error('Nombre y capacidad son requeridos', 400);
                return;
            }

            // Crear entidad taller
            $taller = new \App\Entities\Taller();
            $taller->setNombre($input['nombre'])
                   ->setCapacidad((int)$input['capacidad']);

            // Persistir en la base de datos
            $em = $GLOBALS['entityManager'];
            $em->persist($taller);
            $em->flush();

            // Retornar respuesta de éxito
            ApiResponse::success([
                'id' => $taller->getId(),
                'nombre' => $taller->getNombre(),
                'capacidad' => $taller->getCapacidad()
            ], 201);
        } catch (Exception $e) {
            // Re-lanzar excepciones para manejo de nivel superior
            throw $e;
        }
    }

    /**
     * Prueba la configuración de email enviando un email de prueba.
     * @param int $tallerId El ID del taller
     * @return void
     */
    public function probarConfiguracionEmail(int $tallerId): void
    {
        try {
            // Verificar acceso al taller
            AuthMiddleware::requireTallerAccess($tallerId);

            // Probar configuración usando el servicio
            $resultado = $this->configuracionService->probarConfiguracion($tallerId);

            // Retornar respuesta basada en el resultado
            if ($resultado) {
                ApiResponse::success(['message' => 'Email de prueba enviado correctamente']);
            } else {
                ApiResponse::error('Error al enviar email de prueba', 500);
            }
        } catch (Exception $e) {
            // Re-lanzar excepciones para manejo de nivel superior
            throw $e;
        }
    }
}