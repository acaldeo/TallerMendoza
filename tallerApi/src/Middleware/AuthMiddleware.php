<?php
namespace App\Middleware;

use App\Utils\ApiResponse;

/**
 * AuthMiddleware proporciona métodos estáticos para hacer cumplir autenticación y autorización en rutas.
 * Verifica autenticación basada en sesiones para proteger acceso a recursos, asegurando que solo usuarios autenticados
 * puedan acceder a endpoints protegidos y que los usuarios tengan permisos apropiados para talleres específicos.
 *
 * Propósito general:
 * - Proteger rutas administrativas de acceso no autorizado.
 * - Verificar que usuarios estén logueados.
 * - Controlar acceso a talleres específicos basado en permisos.
 *
 * Dependencias:
 * - Utiliza ApiResponse para enviar respuestas de error.
 * - Depende de sesiones PHP para mantener estado de autenticación.
 * - Es utilizado por index.php y controladores para verificar acceso.
 *
 * Interacciones con otras capas:
 * - Los controladores llaman a estos métodos antes de procesar solicitudes protegidas.
 * - Si falla la verificación, termina la ejecución y envía error HTTP.
 */
class AuthMiddleware
{
    /**
     * Requiere que el usuario esté autenticado verificando si user_id está establecido en la sesión.
     * Si el usuario no está autenticado, envía una respuesta de error no autorizado y termina la solicitud.
     *
     * @return void
     */
    public static function requireAuth(): void
    {
        // Verificar si el usuario está logueado verificando la presencia de user_id en sesión
        if (!isset($_SESSION['user_id'])) {
            // Enviar respuesta de error no autorizado con estado HTTP 401
            ApiResponse::error('Acceso no autorizado', 401);
            exit;
        }
    }

    /**
     * Requiere autenticación y verifica que el usuario autenticado tenga acceso al taller especificado.
     * Primero asegura que el usuario esté autenticado, luego verifica si el ID del taller del usuario coincide con el ID proporcionado.
     * El usuario "acaldeo" tiene acceso a todos los talleres.
     * Si el acceso es denegado, envía una respuesta de error prohibido y termina la solicitud.
     *
     * @param int $tallerId El ID del taller para verificar acceso.
     * @return void
     */
    public static function requireTallerAccess(int $tallerId): void
    {
        // Primero, asegurar que el usuario esté autenticado
        self::requireAuth();

        // Verificar si es el super usuario "acaldeo" - tiene acceso a todos los talleres
        $currentUser = $_SESSION['usuario'] ?? '';
        if ($currentUser === 'acaldeo') {
            return; // Permitir acceso
        }

        // Verificar si el ID del taller del usuario coincide con el ID del taller solicitado
        if (($_SESSION['taller_id'] ?? null) != $tallerId) {
            // Enviar respuesta de error prohibido con estado HTTP 403
            ApiResponse::error('Acceso denegado al taller', 403);
            exit;
        }
    }
}