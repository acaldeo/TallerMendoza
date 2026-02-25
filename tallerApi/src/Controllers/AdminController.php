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
use App\Entities\Taller;
use App\Entities\Turno;
use App\Entities\Usuario;
use App\Entities\ConfiguracionEmail;
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

            // Verificar que no sea el usuario "admin"
            if (strtolower($input['usuario']) === 'admin') {
                ApiResponse::error('El usuario "admin" no está permitido. Use otro nombre de usuario.', 403);
                return;
            }

            // Intentar login usando el servicio de autenticación
            $user = $this->authService->login($input['usuario'], $input['password']);

            // Obtener datos del taller si existe
            $taller = $user->getTaller();
            $tallerId = $taller ? $taller->getId() : null;
            $tallerNombre = $taller ? $taller->getNombre() : null;

            // Retornar respuesta de éxito con detalles del usuario
            ApiResponse::success([
                'id' => $user->getId(),
                'usuario' => $user->getUsuario(),
                'rol' => $user->getRol(),
                'tallerId' => $tallerId,
                'tallerNombre' => $tallerNombre
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

    /**
     * Cancela un turno verificando la patente del vehículo.
     * Endpoint público (sin autenticación) para que los clientes puedan cancelar sus turnos.
     * @return void
     */
    public function cancelarTurno(): void
    {
        try {
            // Decodificar entrada JSON
            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input || !isset($input['turnoId']) || !isset($input['patente'])) {
                ApiResponse::error('Turno ID y patente son requeridos', 400);
                return;
            }

            $turnoId = (int)$input['turnoId'];
            $patente = trim(strtoupper($input['patente']));

            $this->turnoService->cancelarTurno($turnoId, $patente);

            ApiResponse::success(['message' => 'Turno cancelado correctamente']);
        } catch (Exception $e) {
            ApiResponse::error($e->getMessage(), 400);
        }
    }

    /**
     * Cancela un turno por patente para un taller específico.
     * Busca el turno activo por patente y lo cancela.
     * @param int $tallerId El ID del taller
     * @return void
     */
    public function cancelarTurnoPorPatente(int $tallerId): void
    {
        try {
            // Decodificar entrada JSON
            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input || !isset($input['patente'])) {
                ApiResponse::error('Patente es requerida', 400);
                return;
            }

            $patente = trim(strtoupper($input['patente']));

            // Buscar el turno activo por patente
            $em = $GLOBALS['entityManager'];
            $taller = $em->find(Taller::class, $tallerId);
            
            if (!$taller) {
                ApiResponse::error('Taller no encontrado', 404);
                return;
            }

            // Buscar turno activo con esa patente
            $turno = $em->createQuery(
                'SELECT t FROM App\Entities\Turno t
                 WHERE t.taller = :taller AND t.patente = :patente
                 AND t.estado != :finalizado AND t.estado != :cancelado'
            )->setParameters([
                'taller' => $taller,
                'patente' => $patente,
                'finalizado' => Turno::ESTADO_FINALIZADO,
                'cancelado' => Turno::ESTADO_CANCELADO
            ])->getOneOrNullResult();

            if (!$turno) {
                ApiResponse::error('No se encontró un turno activo con esa patente en este taller', 404);
                return;
            }

            // Cancelar el turno
            $this->turnoService->cancelarTurno($turno->getId(), $patente);

            ApiResponse::success([
                'message' => 'Turno #' . $turno->getNumeroTurno() . ' cancelado correctamente',
                'numeroTurno' => $turno->getNumeroTurno()
            ]);
        } catch (Exception $e) {
            ApiResponse::error($e->getMessage(), 400);
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
     * Crea un nuevo taller y lo asigna al usuario actual.
     * Solo el usuario "acaldeo" puede crear talleres.
     * @return void
     */
    public function crearTaller(): void
    {
        try {
            // Requerir autenticación
            AuthMiddleware::requireAuth();

            $em = $GLOBALS['entityManager'];
            $user = $this->authService->getCurrentUser();

            if (!$user) {
                ApiResponse::error('Usuario no encontrado', 404);
                return;
            }

            // Solo "acaldeo" puede crear talleres
            if ($user->getUsuario() !== 'acaldeo') {
                ApiResponse::error('Solo el usuario acaldeo puede crear talleres', 403);
                return;
            }

            // Decodificar entrada JSON del cuerpo de la solicitud
            $input = json_decode(file_get_contents('php://input'), true);

            // Verificar si el JSON es válido
            if (!$input) {
                ApiResponse::error('JSON inválido', 400);
                return;
            }

            // Validar entrada
            if (empty($input['nombre']) || !isset($input['capacidad']) || empty($input['ciudad'])) {
                ApiResponse::error('Nombre, ciudad y capacidad son requeridos', 400);
                return;
            }

            // Validar usuario y contraseña si se proporcionan
            if (empty($input['usuario']) || empty($input['password'])) {
                ApiResponse::error('Usuario y contraseña son requeridos para crear el administrador del taller', 400);
                return;
            }

            // Verificar que no sea el usuario "admin"
            if (strtolower($input['usuario']) === 'admin') {
                ApiResponse::error('El usuario "admin" no está permitido. Use otro nombre de usuario.', 400);
                return;
            }

            // Crear entidad taller
            $taller = new \App\Entities\Taller();
            $taller->setNombre($input['nombre'])
                   ->setCiudad($input['ciudad'])
                   ->setCapacidad((int)$input['capacidad']);

            // Persistir el taller
            $em->persist($taller);
            $em->flush();

            // Crear usuario administrador para el taller
            $usuario = new \App\Entities\Usuario();
            $usuario->setTaller($taller)
                    ->setUsuario($input['usuario'])
                    ->setPasswordHash(password_hash($input['password'], PASSWORD_DEFAULT));

            $em->persist($usuario);
            $em->flush();

            // Retornar respuesta de éxito
            ApiResponse::success([
                'id' => $taller->getId(),
                'nombre' => $taller->getNombre(),
                'capacidad' => $taller->getCapacidad(),
                'usuario' => [
                    'id' => $usuario->getId(),
                    'usuario' => $usuario->getUsuario()
                ]
            ], 201);
        } catch (Exception $e) {
            // Re-lanzar excepciones para manejo de nivel superior
            throw $e;
        }
    }

    /**
     * Elimina un taller con todos sus datos relacionados (turnos, usuarios, configuración de email, logo).
     * @return void
     */
    public function eliminarTaller(): void
    {
        try {
            // Requerir autenticación
            AuthMiddleware::requireAuth();

            // Obtener el taller del usuario actual
            $em = $GLOBALS['entityManager'];
            $user = $this->authService->getCurrentUser();

            if (!$user) {
                ApiResponse::error('Usuario no encontrado', 404);
                return;
            }

            $taller = $user->getTaller();
            if (!$taller) {
                ApiResponse::error('No tiene un taller asignado', 400);
                return;
            }

            $tallerId = $taller->getId();

            // Eliminar taller
            $this->eliminarTallerPorId($tallerId, $em);

            // Destruir la sesión si es el usuario actual
            if ($user->getUsuario() !== 'acaldeo') {
                session_destroy();
            }

            ApiResponse::success(['message' => 'Taller eliminado correctamente']);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Elimina un taller por su ID (para super usuario acaldeo).
     * @param int $tallerId El ID del taller a eliminar
     * @param mixed $em EntityManager (opcional)
     * @return void
     */
    public function eliminarTallerPorId(int $tallerId = null, $em = null): void
    {
        try {
            // Requerir autenticación
            AuthMiddleware::requireAuth();

            $em = $em ?? $GLOBALS['entityManager'];
            $user = $this->authService->getCurrentUser();

            if (!$user) {
                ApiResponse::error('Usuario no encontrado', 404);
                return;
            }

            // Solo "acaldeo" puede eliminar talleres por ID
            if ($user->getUsuario() !== 'acaldeo') {
                ApiResponse::error('Solo el usuario acaldeo puede eliminar talleres por ID', 403);
                return;
            }

            // Obtener el taller por ID
            $taller = $em->find(Taller::class, $tallerId);
            if (!$taller) {
                ApiResponse::error('Taller no encontrado', 404);
                return;
            }

            // 1. Eliminar todos los turnos del taller
            $turnos = $em->getRepository(Turno::class)->findBy(['taller' => $taller]);
            foreach ($turnos as $turno) {
                $em->remove($turno);
            }

            // 2. Eliminar todos los usuarios del taller
            $usuarios = $em->getRepository(Usuario::class)->findBy(['taller' => $taller]);
            foreach ($usuarios as $usuario) {
                $em->remove($usuario);
            }

            // 3. Eliminar la configuración de email si existe
            $configEmail = $em->getRepository(ConfiguracionEmail::class)->findOneBy(['taller' => $taller]);
            if ($configEmail) {
                $em->remove($configEmail);
            }

            // 4. Eliminar el logo si existe
            $logo = $taller->getLogo();
            if ($logo) {
                $rutaLogo = __DIR__ . '/../../uploads/logos/' . $logo;
                if (file_exists($rutaLogo)) {
                    unlink($rutaLogo);
                }
            }

            // 5. Eliminar el taller
            $em->remove($taller);
            $em->flush();

            ApiResponse::success(['message' => 'Taller eliminado correctamente']);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Seleccionar un taller para administrar (para super usuario).
     * Guarda el taller seleccionado en la sesión.
     * @return void
     */
    public function seleccionarTaller(): void
    {
        try {
            // Requerir autenticación
            AuthMiddleware::requireAuth();

            $em = $GLOBALS['entityManager'];
            $user = $this->authService->getCurrentUser();

            if (!$user) {
                ApiResponse::error('Usuario no encontrado', 404);
                return;
            }

            // Solo "acaldeo" puede seleccionar talleres
            if ($user->getUsuario() !== 'acaldeo') {
                ApiResponse::error('Solo el usuario acaldeo puede seleccionar talleres', 403);
                return;
            }

            // Decodificar entrada JSON
            $input = json_decode(file_get_contents('php://input'), true);

            // Verificar si el JSON es válido
            if (!$input || !isset($input['tallerId'])) {
                ApiResponse::error('ID del taller es requerido', 400);
                return;
            }

            // Verificar que el taller existe
            $taller = $em->find(Taller::class, $input['tallerId']);
            if (!$taller) {
                ApiResponse::error('Taller no encontrado', 404);
                return;
            }

            // Guardar el taller seleccionado en la sesión
            $_SESSION['taller_id'] = $taller->getId();

            ApiResponse::success([
                'tallerId' => $taller->getId(),
                'tallerNombre' => $taller->getNombre(),
                'message' => 'Taller seleccionado correctamente'
            ]);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Cambia la contraseña del usuario actual (solo para super usuario acaldeo).
     * @return void
     */
    public function cambiarPasswordPropia(): void
    {
        try {
            // Requerir autenticación
            AuthMiddleware::requireAuth();

            $em = $GLOBALS['entityManager'];
            $user = $this->authService->getCurrentUser();

            if (!$user) {
                ApiResponse::error('Usuario no encontrado', 404);
                return;
            }

            // Solo "acaldeo" puede cambiar su propia contraseña aquí
            if ($user->getUsuario() !== 'acaldeo') {
                ApiResponse::error('Solo el usuario acaldeo puede cambiar su contraseña aquí', 403);
                return;
            }

            // Decodificar entrada JSON
            $input = json_decode(file_get_contents('php://input'), true);

            // Verificar si el JSON es válido
            if (!$input || !isset($input['password']) || empty($input['password'])) {
                ApiResponse::error('Contraseña es requerida', 400);
                return;
            }

            // Validar longitud mínima
            if (strlen($input['password']) < 6) {
                ApiResponse::error('La contraseña debe tener al menos 6 caracteres', 400);
                return;
            }

            // Actualizar contraseña
            $user->setPasswordHash(password_hash($input['password'], PASSWORD_DEFAULT));
            $em->flush();

            ApiResponse::success(['message' => 'Contraseña actualizada correctamente']);
        } catch (Exception $e) {
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

    // === GESTIÓN DE LOGO DEL TALLER ===

    /**
     * Sube el logo de un taller.
     * @param int $tallerId El ID del taller
     * @return void
     */
    public function subirLogo(int $tallerId): void
    {
        try {
            // Verificar acceso al taller
            AuthMiddleware::requireTallerAccess($tallerId);

            // Verificar que se envió un archivo
            if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
                $error = $_FILES['logo']['error'] ?? 'No se recibió ningún archivo';
                ApiResponse::error($error, 400);
                return;
            }

            $archivo = $_FILES['logo'];

            // Validar tipo de archivo (solo imágenes)
            $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

            if (!in_array($extension, $extensionesPermitidas)) {
                ApiResponse::error('Tipo de archivo no permitido. Solo se permiten: jpg, jpeg, png, gif, webp', 400);
                return;
            }

            // Validar tamaño (2MB máximo)
            $tamanoMaximo = 2 * 1024 * 1024; // 2MB
            if ($archivo['size'] > $tamanoMaximo) {
                ApiResponse::error('El archivo es demasiado grande. Máximo 2MB', 400);
                return;
            }

            // Obtener el taller
            $em = $GLOBALS['entityManager'];
            $taller = $em->find(Taller::class, $tallerId);

            if (!$taller) {
                ApiResponse::error('Taller no encontrado', 404);
                return;
            }

            // Eliminar logo anterior si existe
            $logoAnterior = $taller->getLogo();
            if ($logoAnterior) {
                $rutaAnterior = __DIR__ . '/../../uploads/logos/' . $logoAnterior;
                if (file_exists($rutaAnterior)) {
                    unlink($rutaAnterior);
                }
            }

            // Generar nombre único para el archivo
            $nombreArchivo = 'logo_' . $tallerId . '_' . uniqid() . '.' . $extension;
            $rutaDestino = __DIR__ . '/../../uploads/logos/' . $nombreArchivo;

            // Mover el archivo
            if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
                ApiResponse::error('Error al guardar el archivo', 500);
                return;
            }

            // Guardar nombre del archivo en la base de datos
            $taller->setLogo($nombreArchivo);
            $em->flush();

            ApiResponse::success([
                'logo' => $nombreArchivo,
                'message' => 'Logo subido correctamente'
            ]);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Elimina el logo de un taller.
     * @param int $tallerId El ID del taller
     * @return void
     */
    public function eliminarLogo(int $tallerId): void
    {
        try {
            // Verificar acceso al taller
            AuthMiddleware::requireTallerAccess($tallerId);

            // Obtener el taller
            $em = $GLOBALS['entityManager'];
            $taller = $em->find(Taller::class, $tallerId);

            if (!$taller) {
                ApiResponse::error('Taller no encontrado', 404);
                return;
            }

            $logoActual = $taller->getLogo();

            if (!$logoActual) {
                ApiResponse::error('El taller no tiene un logo configurado', 400);
                return;
            }

            // Eliminar archivo físico
            $rutaArchivo = __DIR__ . '/../../uploads/logos/' . $logoActual;
            if (file_exists($rutaArchivo)) {
                unlink($rutaArchivo);
            }

            // Eliminar referencia en base de datos
            $taller->setLogo(null);
            $em->flush();

            ApiResponse::success([
                'message' => 'Logo eliminado correctamente'
            ]);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Obtiene el logo de un taller.
     * @param int $tallerId El ID del taller
     * @return void
     */
    public function obtenerLogo(int $tallerId): void
    {
        try {
            // Verificar acceso al taller
            AuthMiddleware::requireTallerAccess($tallerId);

            // Obtener el taller
            $em = $GLOBALS['entityManager'];
            $taller = $em->find(Taller::class, $tallerId);

            if (!$taller) {
                ApiResponse::error('Taller no encontrado', 404);
                return;
            }

            ApiResponse::success([
                'logo' => $taller->getLogo()
            ]);
        } catch (Exception $e) {
            throw $e;
        }
    }
}
