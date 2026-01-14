<?php
namespace App\Middleware;

use App\Utils\ApiResponse;

/**
 * AuthMiddleware provides static methods to enforce authentication and authorization for routes.
 * It checks session-based authentication to protect access to resources, ensuring that only authenticated users
 * can access protected endpoints and that users have appropriate permissions for specific workshops.
 */
class AuthMiddleware
{
    /**
     * Requires that the user is authenticated by checking if the user_id is set in the session.
     * If the user is not authenticated, sends an unauthorized error response and terminates the request.
     *
     * @return void
     */
    public static function requireAuth(): void
    {
        // Check if the user is logged in by verifying the presence of user_id in session
        if (!isset($_SESSION['user_id'])) {
            // Send an unauthorized error response with HTTP 401 status
            ApiResponse::error('Unauthorized access', 401);
            exit;
        }
    }

    /**
     * Requires authentication and verifies that the authenticated user has access to the specified workshop.
     * First ensures the user is authenticated, then checks if the user's associated workshop ID matches the provided ID.
     * If access is denied, sends a forbidden error response and terminates the request.
     *
     * @param int $tallerId The ID of the workshop to check access for.
     * @return void
     */
    public static function requireTallerAccess(int $tallerId): void
    {
        // First, ensure the user is authenticated
        self::requireAuth();

        // Check if the user's workshop ID matches the requested workshop ID
        if ($_SESSION['taller_id'] != $tallerId) {
            // Send a forbidden error response with HTTP 403 status
            ApiResponse::error('Access denied to workshop', 403);
            exit;
        }
    }
}